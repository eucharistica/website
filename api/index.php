<?php
declare(strict_types=1);

// ===== Bootstrap (SIK API config lama) =====
require_once __DIR__ . '/config.php';

// Tambahkan ini agar bisa akses dbx() (DB website) & helper dari aplikasi utama
$incApp = __DIR__ . '/../inc/app.php';
if (is_file($incApp)) {
    require_once $incApp; // menyediakan dbx(), dll
}

// Poli yang tidak boleh tampil (tetap)
const EXCLUDED_POLI = ['U0035','U0015','U0016','U0039','U0033','U0037','U0041','U0046','IGDK','U0012'];

// Pastikan error tidak bocor sebagai HTML
ini_set('display_errors', '0');
error_reporting(E_ALL);

// ===== CORS =====
// Tambahkan domain baru kamu di sini bila perlu cross-origin
$allowed_origins = [
    'https://rsudmatraman.jakarta.go.id',
    'https://rsudmatraman.my.id',
    'https://website.rsudmatraman.my.id',
];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($origin && in_array($origin, $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
}
header('Vary: Origin');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, If-None-Match, X-Requested-With');
header('Access-Control-Expose-Headers: X-Total-Count, ETag');
header('Access-Control-Max-Age: 86400');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json; charset=utf-8');

// ===== JSON helpers =====
function jsonOut($data, int $status = 200, array $extraHeaders = []): void {
    $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        $json = '{"ok":false,"error":"JSON encode failed"}';
        $status = 500;
    }
    $etag = '"' . sha1($json) . '"';
    header('ETag: ' . $etag);
    foreach ($extraHeaders as $k => $v) {
        header("$k: $v");
    }
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) === $etag) {
        http_response_code(304);
        exit;
    }
    http_response_code($status);
    echo $json;
    exit;
}
function bad(string $msg, int $status = 400): void {
    jsonOut(['ok' => false, 'error' => $msg], $status);
}

// ===== Error -> JSON =====
set_exception_handler(function (Throwable $e) {
    error_log('[API] '.$e->getMessage());
    jsonOut(['ok'=>false,'error'=>'Server error'], 500);
});
set_error_handler(function ($sev, $msg, $file, $line) {
    error_log("[API] $msg @ $file:$line");
    jsonOut(['ok'=>false,'error'=>'Server error'], 500);
});

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'Fatal error', 'detail' => $e['message']]);
    }
});

// ===== Router: normalisasi base path agar /api/... match =====
$reqPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'])), '/');
if ($base !== '' && strpos($reqPath, $base) === 0) {
    $reqPath = substr($reqPath, strlen($base));
}
$path = '/' . ltrim($reqPath, '/');

// ===== Router =====
switch (true) {
    // SIK (jadwal) — memakai DB SIK dari config.php (fungsi db())
    case preg_match('~^/jadwal$~', $path):      handleJadwal();   break;
    case preg_match('~^/poliklinik$~', $path):  handlePoli();     break;
    case preg_match('~^/dokter$~', $path):      handleDokter();   break;

    // WEBSITE (frontsite) — memakai DB Website via dbx() dari inc/app.php
    case preg_match('~^/news$~', $path):        handleNews();        break;
    case preg_match('~^/categories$~', $path):  handleCategories();  break;
    case preg_match('~^/services$~', $path):    handleServices();    break;
    case preg_match('~^/carousel$~', $path):    handleCarousel();    break;


    // healthcheck
    case preg_match('~^/ping$~', $path):        jsonOut(['ok'=>true,'pong'=>true]); break;

    default: bad('Endpoint not found', 404);
}

// ======= HANDLERS: SIK (pakai db()) =======
function handleJadwal(): void {
    $pdo = db();

    $kd_poli = trim($_GET['kd_poli'] ?? '');
    $hari    = strtoupper(trim($_GET['hari'] ?? ''));
    $q       = trim($_GET['q'] ?? '');
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(50, max(1, (int)($_GET['per_page'] ?? 200)));

    $validHari = ['SENIN','SELASA','RABU','KAMIS','JUMAT','SABTU','MINGGU'];
    if ($hari && !in_array($hari, $validHari, true)) {
        bad('Parameter hari tidak valid. Gunakan: '.implode(',', $validHari));
    }

    $excludedList = "'" . implode("','", EXCLUDED_POLI) . "'";

    $where = ['p.status = "1"', 'd.status = "1"', "p.kd_poli NOT IN ($excludedList)"];
    $bind  = [];

    if ($kd_poli !== '') { $where[] = 'j.kd_poli = :kd_poli'; $bind[':kd_poli'] = $kd_poli; }
    if ($hari    !== '') { $where[] = 'j.hari_kerja = :hari';  $bind[':hari']    = $hari;    }
    if ($q       !== '') {
        $where[] = '(d.nm_dokter LIKE :qq1 OR p.nm_poli LIKE :qq2)';
        $bind[':qq1'] = '%'.$q.'%';
        $bind[':qq2'] = '%'.$q.'%';
    }
    $whereSql = 'WHERE '.implode(' AND ', $where);

    $stc = $pdo->prepare("
        SELECT COUNT(*) AS c
        FROM jadwal j
        JOIN dokter d     ON d.kd_dokter = j.kd_dokter
        JOIN poliklinik p ON p.kd_poli   = j.kd_poli
        $whereSql
    ");
    foreach ($bind as $k=>$v) $stc->bindValue($k, $v);
    $stc->execute();
    $total = (int)$stc->fetchColumn();

    $offset = ($page - 1) * $perPage;

    $st = $pdo->prepare("
        SELECT
            j.kd_dokter, d.nm_dokter,
            j.hari_kerja,
            DATE_FORMAT(j.jam_mulai, '%H:%i')   AS jam_mulai,
            DATE_FORMAT(j.jam_selesai, '%H:%i') AS jam_selesai,
            j.kd_poli, p.nm_poli,
            j.kuota
        FROM jadwal j
        JOIN dokter d     ON d.kd_dokter = j.kd_dokter
        JOIN poliklinik p ON p.kd_poli   = j.kd_poli
        $whereSql
        ORDER BY FIELD(j.hari_kerja,'SENIN','SELASA','RABU','KAMIS','JUMAT','SABTU','MINGGU'),
                 p.nm_poli, d.nm_dokter, j.jam_mulai
        LIMIT :limit OFFSET :offset
    ");
    foreach ($bind as $k=>$v) $st->bindValue($k, $v);
    $st->bindValue(':limit',  $perPage, PDO::PARAM_INT);
    $st->bindValue(':offset', $offset,  PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll();

    jsonOut([
        'ok'=>true,
        'data'=>$rows,
        'meta'=>[
            'total'=>$total,'page'=>$page,'per_page'=>$perPage,
            'filters'=>[
                'kd_poli'=>$kd_poli ?: null,
                'hari'   =>$hari    ?: null,
                'q'      =>$q       ?: null,
            ],
        ],
    ], 200, ['X-Total-Count'=>(string)$total]);
}

function handlePoli(): void {
    $pdo = db();
    $excludedList = "'" . implode("','", EXCLUDED_POLI) . "'";
    $rows = $pdo->query("
        SELECT kd_poli, nm_poli
        FROM poliklinik
        WHERE status = '1'
          AND kd_poli NOT IN ($excludedList)
        ORDER BY nm_poli
    ")->fetchAll();
    jsonOut(['ok'=>true,'data'=>$rows]);
}

function handleDokter(): void {
    $pdo = db();
    $kd_poli = trim($_GET['kd_poli'] ?? '');
    $excludedList = "'" . implode("','", EXCLUDED_POLI) . "'";

    if ($kd_poli !== '') {
        $st = $pdo->prepare("
            SELECT DISTINCT d.kd_dokter, d.nm_dokter
            FROM dokter d
            JOIN jadwal j     ON j.kd_dokter = d.kd_dokter
            JOIN poliklinik p ON p.kd_poli   = j.kd_poli
            WHERE j.kd_poli = :kd_poli
              AND d.status = '1'
              AND p.status = '1'
              AND p.kd_poli NOT IN ($excludedList)
            ORDER BY d.nm_dokter
        ");
        $st->execute([':kd_poli'=>$kd_poli]);
        $rows = $st->fetchAll();
    } else {
        $rows = $pdo->query("
            SELECT kd_dokter, nm_dokter
            FROM dokter
            WHERE status = '1'
            ORDER BY nm_dokter
       ")->fetchAll();
    }

    jsonOut(['ok'=>true,'data'=>$rows]);
}

// ======= HANDLERS: WEBSITE (pakai dbx()) =======
function dbWebsite(): PDO {
    if (function_exists('dbx')) {
        return dbx(); // dari inc/app.php
    }
    throw new RuntimeException('Website DB (dbx) tidak tersedia. Pastikan inc/app.php di-include.');
}

function handleNews(): void {
    $pdo  = dbWebsite();
    $q    = trim($_GET['q'] ?? '');
    $cat  = trim($_GET['category'] ?? '');   // slug kategori (opsional)
    $page = max(1, (int)($_GET['page'] ?? 1));
    $per  = min(24, max(1, (int)($_GET['pageSize'] ?? 6)));
    $off  = ($page - 1) * $per;

    $where = ["p.status='published'", "COALESCE(p.published_at, NOW()) <= NOW()"];
    $bind  = [];
    $joins = '';

    if ($q !== '') {
        $where[] = "(p.title LIKE :q OR p.slug LIKE :q OR p.excerpt LIKE :q)";
        $bind[':q'] = '%'.$q.'%';
    }
    if ($cat !== '') {
        $joins .= " JOIN post_categories pc ON pc.post_id = p.id
                    JOIN categories c ON c.id = pc.category_id ";
        $where[] = " c.slug = :cat ";
        $bind[':cat'] = $cat;
    }

    $wsql = 'WHERE '.implode(' AND ', $where);

    // Count
    $stc = $pdo->prepare("SELECT COUNT(DISTINCT p.id) FROM posts p {$joins} {$wsql}");
    foreach($bind as $k=>$v) $stc->bindValue($k,$v);
    $stc->execute();
    $total = (int)$stc->fetchColumn();

    // Data
    $std = $pdo->prepare("
        SELECT DISTINCT p.id, p.slug, p.title, p.excerpt, p.cover_path, p.published_at
        FROM posts p
        {$joins}
        {$wsql}
        ORDER BY p.published_at DESC, p.id DESC
        LIMIT :lim OFFSET :off
    ");
    foreach($bind as $k=>$v) $std->bindValue($k,$v);
    $std->bindValue(':lim', $per, PDO::PARAM_INT);
    $std->bindValue(':off', $off, PDO::PARAM_INT);
    $std->execute();
    $rows = $std->fetchAll();

    // Kategori per post
    $byId = [];
    if ($rows) {
        $ids = implode(',', array_map('intval', array_column($rows,'id')));
        $qk = $pdo->query("
            SELECT pc.post_id, c.id, c.name, c.slug
            FROM post_categories pc
            JOIN categories c ON c.id=pc.category_id
            WHERE pc.post_id IN ($ids)
            ORDER BY c.name
        ");
        while ($r = $qk->fetch()) {
            $byId[(int)$r['post_id']][] = ['id'=>(int)$r['id'],'name'=>$r['name'],'slug'=>$r['slug']];
        }
    }

    $items = array_map(function($r) use ($byId) {
        return [
            'id'          => (int)$r['id'],
            'slug'        => $r['slug'],
            'title'       => $r['title'],
            'excerpt'     => $r['excerpt'],
            'image'       => !empty($r['cover_path']) ? '/'.$r['cover_path'] : null,
            'publishedAt' => $r['published_at'],
            'categories'  => $byId[(int)$r['id']] ?? [],
        ];
    }, $rows);

    $hasMore = ($page * $per) < $total;

    jsonOut(['ok'=>true,'items'=>$items,'page'=>$page,'pageSize'=>$per,'total'=>$total,'hasMore'=>$hasMore]);
}

function handleCategories(): void {
    $pdo = dbWebsite();

    // Deteksi kolom status untuk filter aktif
    $hasStatus=false;
    try{$hasStatus=(bool)$pdo->query("SHOW COLUMNS FROM categories LIKE 'status'")->fetch();}catch(Throwable $e){}

    $q = trim($_GET['q'] ?? '');
    $withCounts = (int)($_GET['withCounts'] ?? 1) === 1;

    $where=[]; $bind=[];
    if ($hasStatus) $where[] = "c.status = 1";
    if ($q!==''){ $where[]="(c.name LIKE :q OR c.slug LIKE :q)"; $bind[':q']='%'.$q.'%'; }
    $wsql=$where?('WHERE '.implode(' AND ',$where)):'';

    if ($withCounts) {
        $sql="
          SELECT c.id, c.name, c.slug,
                 (SELECT COUNT(DISTINCT pc.post_id) FROM post_categories pc
                  JOIN posts p ON p.id=pc.post_id AND p.status='published' AND COALESCE(p.published_at,NOW())<=NOW()
                  WHERE pc.category_id=c.id) AS posts_count
          FROM categories c
          $wsql
          ORDER BY c.name
        ";
    } else {
        $sql="SELECT c.id,c.name,c.slug FROM categories c $wsql ORDER BY c.name";
    }

    $st=$pdo->prepare($sql);
    foreach($bind as $k=>$v)$st->bindValue($k,$v);
    $st->execute(); $rows=$st->fetchAll();

    jsonOut(['ok'=>true,'items'=>$rows]);
}

function handleServices(): void {
    $pdo = dbWebsite();

    $limit = min(50, max(1, (int)($_GET['limit'] ?? 12)));
    $q     = trim($_GET['q'] ?? '');

    $where=[]; $bind=[];
    $where[] = "s.status = 1";
    if ($q!==''){ $where[]="(s.name LIKE :q OR s.slug LIKE :q OR s.excerpt LIKE :q)"; $bind[':q']='%'.$q.'%'; }
    $wsql = 'WHERE '.implode(' AND ',$where);

    $sql = "
      SELECT s.id, s.name, s.slug, s.excerpt, s.cover_path
      FROM services s
      $wsql
      ORDER BY s.sort_order ASC, s.name ASC
      LIMIT :lim
    ";
    $st = $pdo->prepare($sql);
    foreach($bind as $k=>$v)$st->bindValue($k,$v);
    $st->bindValue(':lim',$limit,PDO::PARAM_INT);
    $st->execute();
    $rows=$st->fetchAll();

    $items = array_map(function($r){
      return [
        'id'      => (int)$r['id'],
        'name'    => $r['name'],
        'slug'    => $r['slug'],
        'excerpt' => $r['excerpt'],
        'cover'   => !empty($r['cover_path']) ? '/'.$r['cover_path'] : null,
      ];
    }, $rows);

    jsonOut(['ok'=>true,'items'=>$items]);
}

function handleCarousel(): void {
    $pdo = dbWebsite();
    $st = $pdo->query("SELECT id, path, url FROM carousel WHERE status=1 ORDER BY sort_order ASC, id DESC");
    $rows = $st->fetchAll();

    $items = [];
    foreach ($rows as $r) {
        $items[] = [
            'id'  => (int)$r['id'],
            'url' => $r['url'] ? $r['url'] : null,
            'img' => '/'.$r['path'],
        ];
    }

    jsonOut(['ok'=>true,'items'=>$items]);
}

