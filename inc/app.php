<?php
// inc/app.php — core helpers untuk Website RSUD Matraman
// Memerlukan /api/config.php yang menyiapkan koneksi DB website (db_site()) dan, bila perlu, DB SIK.

require_once __DIR__ . '/../api/config.php';

/* =========================================================
 * SESSION & SECURITY HEADERS
 * ======================================================= */

// Aman untuk HTTPS di balik proxy/CDN
$https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
      || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower($_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https')
      || (isset($_SERVER['HTTP_X_FORWARDED_SSL']) && strtolower($_SERVER['HTTP_X_FORWARDED_SSL']) === 'on');

ini_set('session.cookie_httponly','1');
ini_set('session.cookie_secure', $https ? '1' : '0');
ini_set('session.cookie_samesite','Lax');

session_set_cookie_params([
  'lifetime' => 0,
  'path'     => '/',
  'domain'   => '',
  'secure'   => $https,
  'httponly' => true,
  'samesite' => 'Lax',
]);
session_name('rsudv2');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Security headers dasar
if (PHP_SAPI !== 'cli') {
  header('X-Frame-Options: DENY'); // cegah clickjacking
  header('X-Content-Type-Options: nosniff');
  header('Referrer-Policy: strict-origin-when-cross-origin');
  header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
}

/* =========================================================
 * CSP NONCE (untuk inline <script>)
 * ======================================================= */
$GLOBALS['CSP_NONCE'] = bin2hex(random_bytes(16));

function csp_nonce(): string {
  return $GLOBALS['CSP_NONCE'] ?? '';
}
function csp_script_attr(): string {
  $n = csp_nonce();
  return $n ? 'nonce="'.$n.'"' : '';
}

// Set header CSP global (sesuaikan CDN yang dibutuhkan)
if (PHP_SAPI !== 'cli') {
  $nonce = csp_nonce();
  $csp = "default-src 'self'; ".
         "img-src 'self' data: https:; ".
         "script-src 'self' 'nonce-{$nonce}' https://cdn.jsdelivr.net https://cdn.jsdelivr.net/npm; ".
         "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdn.jsdelivr.net/npm; ".
         "font-src 'self' https://cdn.jsdelivr.net https://cdn.jsdelivr.net/npm; ".
         "connect-src 'self'; ".
         "frame-ancestors 'none'; ".
         "upgrade-insecure-requests";
  header("Content-Security-Policy: $csp");
}

/* =========================================================
 * FLASH & CSRF
 * ======================================================= */
function flash_set(string $k, string $v): void {
  $_SESSION['flash'][$k] = $v;
}
function flash_get(string $k): ?string {
  if (!empty($_SESSION['flash'][$k])) {
    $v = $_SESSION['flash'][$k];
    unset($_SESSION['flash'][$k]);
    return $v;
  }
  return null;
}

function csrf_token(): string {
  if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
  return $_SESSION['csrf'];
}
function csrf_validate(?string $t): bool {
  return is_string($t) && isset($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $t);
}

/* =========================================================
 * DB HELPERS
 * ======================================================= */
// Gunakan db_site() dari /api/config.php sebagai DB utama website
function dbx(): PDO {
  return db_site();
}

/* =========================================================
 * AUTH HELPERS
 * ======================================================= */
function is_logged_in(): bool { return !empty($_SESSION['user']); }

function login_user(array $u): void {
  // Normalisasi data user ke session
  $_SESSION['user'] = [
    'id'         => (int)$u['id'],
    'username'   => $u['username'] ?? ($u['email'] ?? 'user'.$u['id']),
    'full_name'  => $u['full_name'] ?? ($u['username'] ?? ''),
    'email'      => $u['email'] ?? null,
    'avatar_url' => $u['avatar_url'] ?? null,
    'role'       => $u['role'] ?? 'patient',
    'mfa_enabled'=> isset($u['mfa_enabled']) ? (int)$u['mfa_enabled'] : 0,
    'mfa_secret' => $u['mfa_secret'] ?? null,
  ];
  // Regenerate session id untuk mencegah fixation
  if (session_status() === PHP_SESSION_ACTIVE) session_regenerate_id(true);
  // Reset MFA gate tiap login
  unset($_SESSION['mfa_ok']);
}

function logout_user(): void {
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time()-42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
  }
  session_destroy();
}

function require_login(): void {
  if (!is_logged_in()) {
    $next = urlencode($_SERVER['REQUEST_URI'] ?? '/');
    header("Location: /login.php?next={$next}");
    exit;
  }
}

function is_staff_role(string $r): bool { return in_array($r, ['admin','editor','operator'], true); }
function current_user_role(): string { return $_SESSION['user']['role'] ?? ''; }

function require_role(array $roles): void {
  if (!is_logged_in() || !in_array(current_user_role(), $roles, true)) {
    header('Location: /login.php'); exit;
  }
}

function redirect_after_login(): void {
  $r = current_user_role();
  $next = $_GET['next'] ?? $_POST['next'] ?? null;
  $target = $next ?: (is_staff_role($r) ? '/dashboard.php' : '/portal.php');
  header('Location: '.$target); exit;
}

/* =========================================================
 * AUDIT LOG
 * ======================================================= */
function client_ip(): string {
  foreach (['HTTP_CF_CONNECTING_IP','HTTP_X_FORWARDED_FOR','HTTP_X_REAL_IP','REMOTE_ADDR'] as $k) {
    if (!empty($_SERVER[$k])) {
      $v = trim(explode(',', $_SERVER[$k])[0]);
      if (filter_var($v, FILTER_VALIDATE_IP)) return substr($v, 0, 45);
    }
  }
  return '0.0.0.0';
}

/**
 * Tuliskan ke tabel audit_log:
 * - event (varchar)
 * - entity_type (varchar)
 * - entity_id (bigint/int)
 * - user_id (bigint/int, boleh 0)
 * - ip (varchar 45)
 * - user_agent (varchar 255)
 * - details (JSON TEXT/JSON)
 * - created_at (datetime default now)
 */
function audit_log(string $event, string $entity_type, int $entity_id, ?array $details = null, ?int $user_id = null): void {
  try {
    $pdo = dbx();
    $uid = $user_id ?? (int)($_SESSION['user']['id'] ?? 0);
    $ip  = client_ip();
    $ua  = substr($_SERVER['HTTP_USER_AGENT'] ?? '-', 0, 255);
    $json = $details ? json_encode($details, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    $st = $pdo->prepare("INSERT INTO audit_log (event, entity_type, entity_id, user_id, ip, user_agent, details, created_at)
                         VALUES (:e, :t, :eid, :uid, :ip, :ua, :d, NOW())");
    $st->execute([
      ':e'=>$event, ':t'=>$entity_type, ':eid'=>$entity_id,
      ':uid'=>$uid, ':ip'=>$ip, ':ua'=>$ua, ':d'=>$json
    ]);
  } catch (Throwable $e) {
    error_log('audit_log fail: '.$e->getMessage());
  }
}

/* =========================================================
 * RATE LIMIT LOGIN (IP/identity)
 * ======================================================= */
/**
 * Batasi percobaan login: max 5 gagal / 10 menit per identity atau IP
 * Return true jika boleh mencoba; false jika diblok sementara.
 * Memerlukan tabel login_attempts (identity, ip, success, created_at)
 */
function login_rate_allowed(PDO $pdo, string $identity, string $ip): bool {
  $sql = "SELECT SUM(CASE WHEN success=0 THEN 1 ELSE 0 END) AS fails
          FROM login_attempts
          WHERE created_at > (NOW() - INTERVAL 10 MINUTE)
            AND (identity = :i OR ip = :ip)";
  $st = $pdo->prepare($sql);
  $st->execute([':i'=>$identity, ':ip'=>$ip]);
  $fails = (int)$st->fetchColumn();
  return $fails < 5;
}
function login_rate_note(PDO $pdo, string $identity, string $ip, bool $success): void {
  try {
    $st = $pdo->prepare("INSERT INTO login_attempts (identity, ip, success) VALUES (:i, :ip, :s)");
    $st->execute([':i'=>$identity, ':ip'=>$ip, ':s'=>$success ? 1 : 0]);
  } catch (Throwable $e) { /* ignore */ }
}

/* =========================================================
 * MFA / TOTP
 * ======================================================= */
function base32_decode_rfc($b32) {
  $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
  $b32 = strtoupper(preg_replace('/[^A-Z2-7]/','', $b32));
  $buffer = 0; $bits = 0; $out = '';
  for ($i=0,$len=strlen($b32); $i<$len; $i++){
    $val = strpos($alphabet, $b32[$i]); if ($val===false) continue;
    $buffer = ($buffer<<5) | $val; $bits+=5;
    if ($bits>=8){ $bits-=8; $out.= chr(($buffer>>$bits) & 0xFF); }
  }
  return $out;
}
function base32_encode_rfc($bin) {
  $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567'; $out=''; $buffer=0; $bits=0;
  for ($i=0,$len=strlen($bin); $i<$len; $i++){
    $buffer = ($buffer<<8) | ord($bin[$i]); $bits+=8;
    while ($bits>=5){ $bits-=5; $out .= $alphabet[($buffer>>$bits)&31]; }
  }
  if ($bits>0) $out .= $alphabet[($buffer<< (5-$bits)) & 31];
  return $out;
}
function mfa_generate_secret(int $bytes=20): string { return base32_encode_rfc(random_bytes($bytes)); }

function hotp(string $secret_bin, int $counter, int $digits=6): int {
  $bin = pack('N*', 0) . pack('N*', $counter);
  $hash = hash_hmac('sha1', $bin, $secret_bin, true);
  $offset = ord($hash[19]) & 0x0F;
  $code = ((ord($hash[$offset]) & 0x7F) << 24)
        | ((ord($hash[$offset+1]) & 0xFF) << 16)
        | ((ord($hash[$offset+2]) & 0xFF) << 8)
        | (ord($hash[$offset+3]) & 0xFF);
  return $code % (10 ** $digits);
}
function totp_now(string $secret_b32, ?int $time = null, int $step = 30, int $digits = 6): int {
  if ($time === null) $time = time();
  $counter = (int) floor($time / $step);
  $secret_bin = base32_decode_rfc($secret_b32);
  return hotp($secret_bin, $counter, $digits);
}
function mfa_verify(string $secret_b32, string $code, int $window=1): bool {
  $code = preg_replace('/\D/','', $code);
  if ($code==='') return false;
  $now = time();
  for ($w=-$window; $w<=$window; $w++) {
    if (sprintf('%06d', totp_now($secret_b32, $now + ($w*30))) === $code) return true;
  }
  return false;
}
function mfa_otpauth_uri(string $secret_b32, string $email, string $issuer='RSUD Matraman'): string {
  $label = rawurlencode($issuer.':'.$email);
  $iss   = rawurlencode($issuer);
  return "otpauth://totp/{$label}?secret={$secret_b32}&issuer={$iss}&period=30&digits=6";
}
function user_needs_mfa(array $u): bool {
  return (($u['role'] ?? '') === 'admin') && (int)($u['mfa_enabled'] ?? 0) === 1;
}
function is_mfa_ok(): bool { return !empty($_SESSION['mfa_ok']); }
/** Panggil di awal halaman admin bila perlu
 * if (user_needs_mfa($_SESSION['user'] ?? []) && !is_mfa_ok()) { header('Location:/mfa_verify.php'); exit; }
 */
function require_mfa_if_needed(): void {
  $u = $_SESSION['user'] ?? null;
  if ($u && user_needs_mfa($u) && !is_mfa_ok()) { header('Location:/mfa_verify.php'); exit; }
}

/* =========================================================
 * MEDIA: Upload & Responsive Images
 * ======================================================= */
/**
 * Simpan gambar yang diupload dan buat varian JPG/PNG/WebP di beberapa lebar (400/800/1200).
 * Mengembalikan relative path file asli.
 */
function save_uploaded_image(string $field, string $targetDir, array $sizes = [400, 800, 1200]): ?string {
    if (empty($_FILES[$field]['tmp_name'])) return null;

    $tmp  = $_FILES[$field]['tmp_name'];
    $info = @getimagesize($tmp);
    if (!$info) {
        throw new Exception('Berkas gambar tidak valid');
    }

    // Normalisasi MIME -> ekstensi (tanpa match)
    $mime = strtolower($info['mime'] ?? '');
    $ext  = null;
    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $ext = 'jpg';
            break;
        case 'image/png':
            $ext = 'png';
            break;
        case 'image/webp':
            $ext = 'webp';
            break;
        default:
            throw new Exception('Format tidak didukung');
    }

    // Folder simpan (kamu pakai assets/uploads)
    $relDir = 'assets/uploads/' . trim($targetDir, '/') . '/' . date('Y/m');
    $absDir = __DIR__ . '/../' . $relDir;
    if (!is_dir($absDir) && !mkdir($absDir, 0775, true)) {
        throw new Exception('Gagal membuat folder upload');
    }

    // Simpan file asli
    $base = bin2hex(random_bytes(8));
    $orig = "$relDir/{$base}.{$ext}";
    if (!move_uploaded_file($tmp, __DIR__ . '/../' . $orig)) {
        throw new Exception('Gagal memindahkan file');
    }

    // Buat varian (abaikan error agar upload tetap sukses)
    try {
        generate_variants(__DIR__ . '/../' . $orig, $relDir . '/' . $base, $ext, $sizes);
    } catch (Throwable $e) {
        error_log('variant_fail: ' . $e->getMessage());
    }

    return $orig;
}

function image_create_from(string $path, string $ext) {
    if (!is_file($path)) return null;
    $ext = strtolower($ext);

    switch ($ext) {
        case 'jpg':
        case 'jpeg':
            $im = @imagecreatefromjpeg($path);
            return $im ?: null;

        case 'png':
            $im = @imagecreatefrompng($path);
            return $im ?: null;

        case 'webp':
            // Kalau GD support WebP
            if (function_exists('imagecreatefromwebp')) {
                $im = @imagecreatefromwebp($path);
                if ($im !== false) return $im;
            }
            // Fallback: coba generic decoder
            $bin = @file_get_contents($path);
            if ($bin !== false) {
                $im = @imagecreatefromstring($bin);
                return $im ?: null;
            }
            return null;

        default:
            // Ekstensi lain: coba generic decoder
            $bin = @file_get_contents($path);
            if ($bin !== false) {
                $im = @imagecreatefromstring($bin);
                return $im ?: null;
            }
            return null;
    }
}


function generate_variants(string $absOrig, string $baseRelNoExt, string $ext, array $sizes): void {
  [$w0, $h0] = getimagesize($absOrig);
  $img0 = image_create_from($absOrig, $ext);
  if (!$img0) return;

  foreach ($sizes as $w) {
    if ($w >= $w0) $w = $w0;
    $h = (int)round($h0 * ($w / $w0));
    $dst = imagecreatetruecolor($w, $h);
    imagecopyresampled($dst, $img0, 0,0,0,0, $w,$h, $w0,$h0);

    // Simpan sesuai ekstensi asal
    $out = __DIR__.'/../'.$baseRelNoExt."-{$w}w.$ext";
    if ($ext==='png') imagepng($dst, $out, 6);
    elseif ($ext==='webp') imagewebp($dst, $out, 82);
    else imagejpeg($dst, $out, 82);

    // WebP tambahan
    $webpOut = __DIR__.'/../'.$baseRelNoExt."-{$w}w.webp";
    imagewebp($dst, $webpOut, 82);

    imagedestroy($dst);
  }
  imagedestroy($img0);
}

/**
 * <picture> helper dari 1 path asli (tanpa domain). Menghasilkan srcset + webp.
 * $sizes contoh: "(min-width: 992px) 33vw, 100vw"
 */
function picture_tag(string $path, string $alt = '', string $class = 'img-cover', string $sizes = '100vw'): string {
  if ($path === '') return '';
  $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
  $base = substr($path, 0, -strlen($ext)-1); // tanpa .ext
  $wset = [400,800,1200];
  $webp = implode(', ', array_map(fn($w)=> "/{$base}-{$w}w.webp {$w}w", $wset));
  $orig = implode(', ', array_map(fn($w)=> "/{$base}-{$w}w.{$ext} {$w}w", $wset));
  $alt  = htmlspecialchars($alt);
  return <<<HTML
<picture>
  <source type="image/webp" srcset="{$webp}" sizes="{$sizes}">
  <img src="/{$path}" srcset="{$orig}" sizes="{$sizes}" alt="{$alt}" class="{$class}" loading="lazy">
</picture>
HTML;
}
