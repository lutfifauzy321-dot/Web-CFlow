<?php
class Uploader {
    const MAX = 3 * 1024 * 1024;
    const MIME = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    // Return nama file, null kalau tidak ada upload, atau lempar Exception kalau tidak valid
    static function receipt(?array $f): ?string {
        if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) return null;
        if (in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE])) throw new Exception('Foto terlalu besar untuk server. Coba foto yang lebih kecil.');
        if ($f['error'] !== UPLOAD_ERR_OK) throw new Exception('Upload gagal, coba lagi.');
        if ($f['size'] > self::MAX) throw new Exception('Ukuran foto maksimal 3 MB.');
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        if (!isset(self::MIME[$mime])) throw new Exception('Format harus JPG, PNG, atau WEBP.');
        $name = bin2hex(random_bytes(12)) . '.' . self::MIME[$mime];
        if (!move_uploaded_file($f['tmp_name'], ROOT . '/uploads/receipts/' . $name)) throw new Exception('Gagal menyimpan foto.');
        return $name;
    }
    static function avatar(?array $f): ?string {
        if (!$f || $f['error'] === UPLOAD_ERR_NO_FILE) return null;
        if (in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE])) throw new Exception('Foto profil terlalu besar untuk server.');
        if ($f['error'] !== UPLOAD_ERR_OK) throw new Exception('Upload foto profil gagal, coba lagi.');
        if ($f['size'] > self::MAX) throw new Exception('Ukuran foto profil maksimal 3 MB.');
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        if (!isset(self::MIME[$mime])) throw new Exception('Foto profil harus JPG, PNG, atau WEBP.');
        $dir = ROOT . '/uploads/avatars';
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) throw new Exception('Folder foto profil tidak dapat dibuat.');
        $name = bin2hex(random_bytes(12)) . '.' . self::MIME[$mime];
        if (!move_uploaded_file($f['tmp_name'], $dir . '/' . $name)) throw new Exception('Gagal menyimpan foto profil.');
        return $name;
    }
    static function removeAvatar(?string $avatar): void {
        if (strpos((string)$avatar, 'local:') !== 0) return;
        $path = ROOT . '/uploads/avatars/' . basename(substr($avatar, 6));
        if (is_file($path)) unlink($path);
    }
    static function remove(?string $n): void { if ($n && is_file($p = ROOT . '/uploads/receipts/' . basename($n))) unlink($p); }
}
