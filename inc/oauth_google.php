<?php
require_once __DIR__ . '/app.php';
require_once __DIR__ . '/oauth_google_secrets.php'; // isi dari TEMPLATE di atas

function google_cfg(): array {
  return [
    'client_id'     => GOOGLE_CLIENT_ID,
    'client_secret' => GOOGLE_CLIENT_SECRET,
    'redirect_uri'  => GOOGLE_REDIRECT_URI,
    'scope'         => 'openid email profile',
    'auth_url'      => 'https://accounts.google.com/o/oauth2/v2/auth',
    'token_url'     => 'https://oauth2.googleapis.com/token',
    'userinfo_url'  => 'https://openidconnect.googleapis.com/v1/userinfo',
    'allowed_domains' => defined('GOOGLE_ALLOWED_DOMAINS') ? GOOGLE_ALLOWED_DOMAINS : '',
  ];
}

// PKCE utils
function b64url(string $s): string { return rtrim(strtr(base64_encode($s), '+/', '-_'), '='); }
function pkce_verifier(): string { return b64url(random_bytes(32)); }
function pkce_challenge(string $verifier): string { return b64url(hash('sha256', $verifier, true)); }

// HTTP helpers (cURL wajib aktif)
function http_post_form(string $url, array $params): array {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/x-www-form-urlencoded'],
    CURLOPT_POSTFIELDS => http_build_query($params),
    CURLOPT_TIMEOUT => 15,
  ]);
  $res = curl_exec($ch); $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($res === false) { $err = curl_error($ch); curl_close($ch); throw new Exception('cURL error: '.$err); }
  curl_close($ch);
  $json = json_decode($res, true);
  if ($status >= 400) { throw new Exception('HTTP '.$status.': '.$res); }
  return is_array($json) ? $json : [];
}
function http_get_json(string $url, string $bearer): array {
  $ch = curl_init($url);
  curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Authorization: Bearer '.$bearer],
    CURLOPT_TIMEOUT => 15,
  ]);
  $res = curl_exec($ch); $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if ($res === false) { $err = curl_error($ch); curl_close($ch); throw new Exception('cURL error: '.$err); }
  curl_close($ch);
  if ($status >= 400) { throw new Exception('HTTP '.$status.': '.$res); }
  return json_decode($res, true) ?: [];
}

// Build auth URL + simpan PKCE state
function google_build_auth_url(): string {
  $cfg = google_cfg();
  $state = bin2hex(random_bytes(16));
  $ver = pkce_verifier(); $challenge = pkce_challenge($ver);
  $_SESSION['oauth_google'] = ['state'=>$state,'verifier'=>$ver,'ts'=>time()];
  $params = [
    'response_type' => 'code',
    'client_id' => $cfg['client_id'],
    'redirect_uri' => $cfg['redirect_uri'],
    'scope' => $cfg['scope'],
    'state' => $state,
    'code_challenge' => $challenge,
    'code_challenge_method' => 'S256',
    'access_type' => 'offline',
    'prompt' => 'select_account',
  ];
  return $cfg['auth_url'].'?'.http_build_query($params);
}
function google_exchange_code(string $code): array {
  $cfg = google_cfg(); $sess = $_SESSION['oauth_google'] ?? null; if (!$sess) throw new Exception('Missing PKCE state');
  return http_post_form($cfg['token_url'], [
    'grant_type' => 'authorization_code',
    'code' => $code,
    'client_id' => $cfg['client_id'],
    'client_secret' => $cfg['client_secret'],
    'redirect_uri' => $cfg['redirect_uri'],
    'code_verifier' => $sess['verifier'],
  ]);
}
function google_fetch_userinfo(string $access_token): array {
  $cfg = google_cfg();
  return http_get_json($cfg['userinfo_url'], $access_token);
}

// Domain allow
function allowed_domain_ok(string $email): bool {
  $cfg = google_cfg(); $allow = trim($cfg['allowed_domains'] ?? ''); if ($allow==='') return true;
  $domain = strtolower(substr(strrchr($email,'@'),1) ?: '');
  foreach (preg_split('/[,\\s]+/', $allow) as $d) { if ($domain === strtolower(trim($d))) return true; }
  return false;
}

// Mapping role default (intent staff/patient + whitelist email)
function determine_role_for_google(string $email, string $intent): string {
  $intent = strtolower($intent ?: 'patient');
  $adminEmails  = defined('GOOGLE_ADMIN_EMAILS')  ? explode(',', (string)GOOGLE_ADMIN_EMAILS)  : [];
  $editorEmails = defined('GOOGLE_EDITOR_EMAILS') ? explode(',', (string)GOOGLE_EDITOR_EMAILS) : [];
  $emailLc = strtolower($email);

  foreach ($adminEmails as $e)  { if (trim(strtolower($e)) === $emailLc)  return 'admin'; }
  foreach ($editorEmails as $e) { if (trim(strtolower($e)) === $emailLc) return 'editor'; }

  if ($intent === 'staff') {
    return allowed_domain_ok($email) ? 'editor' : 'patient';
  }
  return 'patient';
}

// Temukan/buat user lokal dari Google userinfo (default role configurable)
function find_or_create_user_from_google(array $ui, array $tokens, string $defaultRole = 'patient'): array {
  $pdo = dbx();
  $sub = $ui['sub'] ?? null; $email = $ui['email'] ?? null; $name = $ui['name'] ?? ''; $avatar = $ui['picture'] ?? '';
  if (!$sub || !$email) throw new Exception('Google userinfo incomplete');

  // 1) Sudah ada mapping subject?
  $st = $pdo->prepare("SELECT u.* FROM oauth_identities oi JOIN admin_users u ON u.id=oi.user_id WHERE oi.provider='google' AND oi.subject=:s LIMIT 1");
  $st->execute([':s'=>$sub]); $user = $st->fetch();
  if ($user) {
    $pdo->prepare("UPDATE admin_users SET email=:e, avatar_url=:a WHERE id=:id")
        ->execute([':e'=>$email, ':a'=>$avatar, ':id'=>$user['id']]);
  } else {
    // 2) Sudah ada user dengan email sama?
    $st = $pdo->prepare("SELECT * FROM admin_users WHERE email=:e LIMIT 1");
    $st->execute([':e'=>$email]); $user = $st->fetch();

    if (!$user) {
      // 3) Buat user baru
      $usernameBase = strstr($email,'@',true) ?: ('user'.substr($sub,-6));
      $tmp = $usernameBase; $check = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE username=:u"); $i=1;
      while(true){ $check->execute([':u'=>$tmp]); if((int)$check->fetchColumn()===0){ break; } $tmp = $usernameBase.$i++; if($i>50){ $tmp='user'.time(); break; } }
      $pwdhash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
      $roleToSet = in_array($defaultRole, ['admin','editor','operator','patient'], true) ? $defaultRole : 'patient';

      $pdo->prepare("INSERT INTO admin_users (username,password_hash,full_name,role,is_active,email,avatar_url,created_at)
                     VALUES (:u,:p,:f,:r,1,:e,:a,NOW())")
          ->execute([':u'=>$tmp, ':p'=>$pwdhash, ':f'=>$name, ':r'=>$roleToSet, ':e'=>$email, ':a'=>$avatar]);

      $id = (int)$pdo->lastInsertId();
      $user = $pdo->query("SELECT * FROM admin_users WHERE id=".$id)->fetch();
    }

    // 4) Simpan/Update identity & token
    $exp = !empty($tokens['expires_in']) ? date('Y-m-d H:i:s', time() + (int)$tokens['expires_in']) : null;
    $raw = json_encode(['ui'=>$ui,'tokens'=>$tokens], JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
    $pdo->prepare("INSERT INTO oauth_identities (user_id,provider,subject,email,access_token,refresh_token,expires_at,raw_json,created_at,updated_at)
                    VALUES (:uid,'google',:s,:e,:at,:rt,:exp,:raw,NOW(),NOW())
                    ON DUPLICATE KEY UPDATE email=VALUES(email),access_token=VALUES(access_token),
                      refresh_token=VALUES(refresh_token),expires_at=VALUES(expires_at),
                      raw_json=VALUES(raw_json),updated_at=NOW()")
        ->execute([':uid'=>$user['id'], ':s'=>$sub, ':e'=>$email, ':at'=>$tokens['access_token'] ?? '', ':rt'=>$tokens['refresh_token'] ?? null, ':exp'=>$exp, ':raw'=>$raw]);
  }
  return $user;
}
