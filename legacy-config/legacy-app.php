<?php
foreach (@file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $l) {
    if ($l[0] === '#' || !strpos($l, '=')) continue;
    [$k, $v] = explode('=', $l, 2); $_ENV[trim($k)] = trim($v);
}
function env($k, $d = '') { return $_ENV[$k] ?? $d; }
define('APP_NAME', 'cflow');
define('BASE_URL', rtrim(env('BASE_URL', 'http://localhost/cflow'), '/'));
define('ROOT', dirname(__DIR__));
date_default_timezone_set('Asia/Jakarta');
session_start();
