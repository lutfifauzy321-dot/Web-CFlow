<?php
class Helper {
    static function rp($n): string { return 'Rp ' . number_format((float)$n, 0, ',', '.'); }
    static function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
    static function avatarUrl($avatar): string {
        if (strpos((string)$avatar, 'local:') === 0) return self::url('/uploads/avatars/' . basename(substr($avatar, 6)));
        return (string)$avatar;
    }
    static function clean($s): string { return trim(strip_tags((string)$s)); }
    static function tgl($d): string {
        $b = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $t = strtotime($d); return date('j', $t) . ' ' . $b[(int)date('n', $t)] . ', ' . date('H.i', $t);
    }
    // PRETTY_URLS=0 -> index.php?r=... (aman di semua server) | PRETTY_URLS=1 -> /path (butuh mod_rewrite)
    static function url($p = ''): string {
        $frag = ''; $qs = '';
        if (($i = strpos($p, '#')) !== false) { $frag = substr($p, $i); $p = substr($p, 0, $i); }
        if (($i = strpos($p, '?')) !== false) { $qs = substr($p, $i + 1); $p = substr($p, 0, $i); }
        if (preg_match('#^/(assets|uploads)/#', $p)) { $v = is_file(ROOT . $p) ? filemtime(ROOT . $p) : 0; return BASE_URL . $p . '?' . ($qs ?: 'v=' . $v); }
        $p = trim($p, '/');
        if (env('PRETTY_URLS') === '1') return BASE_URL . '/' . $p . ($qs ? '?' . $qs : '') . $frag;
        return BASE_URL . '/index.php' . ($p !== '' ? '?r=' . $p . ($qs ? '&' . $qs : '') : ($qs ? '?' . $qs : '')) . $frag;
    }
    static function redirect($p) { header('Location: ' . self::url($p)); exit; }
    static function flash($m = null) {
        if ($m !== null) { $_SESSION['flash'] = $m; return; }
        $f = $_SESSION['flash'] ?? ''; unset($_SESSION['flash']); return $f;
    }
    static function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
    static function field(): string { return '<input type="hidden" name="csrf" value="' . self::csrf() . '">'; }
    static function verify(): void {
        if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { Helper::flash('Sesi kedaluwarsa, coba ulangi.'); Helper::redirect('/'); }
    }
}
