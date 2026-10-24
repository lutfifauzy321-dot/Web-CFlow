<?php
class ProfileController {
    static function index() {
        $u = Auth::user(); $tab = 'profile';
        require ROOT . '/views/profile/index.php';
    }
    static function account() {
        $u = Auth::user(); $tab = 'profile';
        require ROOT . '/views/profile/account.php';
    }
    static function security() {
        $u = Auth::user(); $tab = 'profile';
        require ROOT . '/views/profile/security.php';
    }
    static function update() {
        Auth::require(); Helper::verify();
        $uid = (int)$_SESSION['uid'];
        $name = Helper::clean($_POST['name'] ?? '');
        $email = strtolower(trim($_POST['email'] ?? ''));
        if ($name === '' || mb_strlen($name) > 100 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
            Helper::flash('Nama wajib diisi (maksimal 100 karakter) dan email harus valid.');
            Helper::redirect('/profile/account');
        }
        $existing = User::byEmail($email);
        if ($existing && (int)$existing['id'] !== $uid) {
            Helper::flash('Email sudah dipakai akun lain.');
            Helper::redirect('/profile/account');
        }
        try {
            $avatarFile = Uploader::avatar($_FILES['avatar'] ?? null);
        } catch (Exception $e) {
            Helper::flash($e->getMessage());
            Helper::redirect('/profile/account');
        }
        $oldAvatar = Auth::user()['avatar'];
        try {
            User::updateProfile($uid, $name, $email, $avatarFile !== null ? 'local:' . $avatarFile : null);
        } catch (Throwable $e) {
            if ($avatarFile !== null) Uploader::removeAvatar('local:' . $avatarFile);
            throw $e;
        }
        if ($avatarFile !== null) Uploader::removeAvatar($oldAvatar);
        Helper::flash('Profil berhasil diperbarui.');
        Helper::redirect('/profile/account');
    }
    static function password() {
        Auth::require(); Helper::verify();
        $u = Auth::user();
        $current = $_POST['current_password'] ?? '';
        $password = $_POST['password'] ?? '';
        $confirmation = $_POST['password_confirmation'] ?? '';
        if (strlen($password) < 6) {
            Helper::flash('Password baru minimal 6 karakter.');
            Helper::redirect('/profile/security');
        }
        if ($password !== $confirmation) {
            Helper::flash('Konfirmasi password tidak cocok.');
            Helper::redirect('/profile/security');
        }
        if ($u['password_hash'] && !password_verify($current, $u['password_hash'])) {
            Helper::flash('Password saat ini salah.');
            Helper::redirect('/profile/security');
        }
        User::setPassword((int)$u['id'], $password);
        Helper::flash($u['password_hash'] ? 'Password berhasil diperbarui.' : 'Password berhasil dibuat untuk akun ini.');
        Helper::redirect('/profile/security');
    }
}
