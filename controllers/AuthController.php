<?php
class AuthController {
    static function login() { if (Auth::check()) Helper::redirect('/'); require ROOT . '/views/auth/login.php'; }
    static function doLogin() {
        Helper::verify(); $u = User::byEmail(strtolower(trim($_POST['email'] ?? '')));
        if (!$u || !$u['password_hash'] || !password_verify($_POST['password'] ?? '', $u['password_hash'])) {
            Helper::flash('Email atau password salah.'); Helper::redirect('/login');
        }
        Auth::login((int)$u['id']); Helper::redirect('/');
    }
    static function register() {
        Helper::verify(); $name = Helper::clean($_POST['name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? '')); $pass = $_POST['password'] ?? '';
        if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($pass) < 6) { Helper::flash('Isi nama, email valid, dan password minimal 6 karakter.'); Helper::redirect('/login?daftar=1'); }
        if (User::byEmail($email)) { Helper::flash('Email sudah terdaftar, silakan masuk.'); Helper::redirect('/login'); }
        Auth::login(User::register($name, $email, $pass)); Helper::redirect('/');
    }
    static function logout() { Helper::verify(); Auth::logout(); header('Location: ' . Helper::url('/login')); exit; }
}
