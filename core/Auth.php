<?php
class Auth {
    static function check(): bool { return !empty($_SESSION['uid']); }
    static function user(): ?array { return self::check() ? User::find($_SESSION['uid']) : null; }
    static function login(int $id): void { session_regenerate_id(true); $_SESSION['uid'] = $id; }
    static function logout(): void { session_destroy(); }
    static function require(): void { if (!self::check()) Helper::redirect('/login'); }
}
