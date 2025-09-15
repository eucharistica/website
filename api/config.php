<?php
declare(strict_types=1);

//// === SIK DB (SIMRS) ===
const SIK_HOST = '100.10.1.2';
const SIK_NAME = 'sik';
const SIK_USER = '';
const SIK_PASS = '';

//// === WEBSITE DB ===
const SITE_HOST = '100.10.1.2';      
const SITE_NAME = 'website';      
const SITE_USER = '';        // buat user khusus (disarankan)
const SITE_PASS = ''; // ganti password kuat

//// === Umum ===
const DB_CHARSET  = 'utf8mb4';
const DB_TIMEZONE = '+07:00';

function pdo_make(string $host, string $db, string $user, string $pass): PDO {
    static $cache = [];
    $key = "$host|$db|$user";
    if (isset($cache[$key]) && $cache[$key] instanceof PDO) return $cache[$key];

    $dsn  = "mysql:host={$host};dbname={$db};charset=" . DB_CHARSET;
    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, $user, $pass, $opts);
    $pdo->exec("SET time_zone = '" . DB_TIMEZONE . "'");
    return $cache[$key] = $pdo;
}

// ==== Koneksi siap pakai ====
function db_sik(): PDO  { return pdo_make(SIK_HOST,  SIK_NAME,  SIK_USER,  SIK_PASS); }
function db_site(): PDO {
    static $pdo;
    if ($pdo instanceof PDO) return $pdo;

    $dsn  = 'mysql:host=' . SITE_HOST . ';dbname=' . SITE_NAME . ';charset=utf8mb4';
    $opts = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, SITE_USER, SITE_PASS, $opts);

    // PENTING: samakan timezone agar NOW() di MySQL seragam (WIB)
    $pdo->exec("SET time_zone = '+07:00'");

    return $pdo;
}

// ==== Backward-compat untuk kode API lama (jadwal, dll) ====
function db(): PDO { return db_sik(); }
