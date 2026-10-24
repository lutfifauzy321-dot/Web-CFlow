<?php

if (!defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2));
}

class Helper
{
    public static function rp($amount): string
    {
        return 'Rp ' . number_format((float) $amount, 0, ',', '.');
    }

    public static function e($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }

    public static function avatarUrl($avatar): string
    {
        if (str_starts_with((string) $avatar, 'local:')) {
            return self::url('/uploads/avatars/' . basename(substr($avatar, 6)));
        }

        return (string) $avatar;
    }

    public static function tgl($date): string
    {
        $months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        $timestamp = strtotime($date);

        return date('j', $timestamp) . ' ' . $months[(int) date('n', $timestamp)] . ', ' . date('H.i', $timestamp);
    }

    public static function url(string $path = ''): string
    {
        $fragment = '';
        $query = '';
        if (($index = strpos($path, '#')) !== false) {
            $fragment = substr($path, $index);
            $path = substr($path, 0, $index);
        }
        if (($index = strpos($path, '?')) !== false) {
            $query = substr($path, $index + 1);
            $path = substr($path, 0, $index);
        }

        $url = preg_match('#^/(assets|uploads)/#', $path)
            ? asset(ltrim($path, '/'))
            : url($path);

        $url .= $query !== '' ? '?' . $query : '';
        if (preg_match('#^/(assets|uploads)/#', $path)) {
            $assetPath = public_path(ltrim($path, '/'));
            if (is_file($assetPath)) {
                $url .= ($query !== '' ? '&' : '?') . 'v=' . filemtime($assetPath);
            }
        }

        return $url . $fragment;
    }

    public static function field(): string
    {
        return csrf_field();
    }

    public static function flash(?string $message = null): ?string
    {
        if ($message !== null) {
            session()->flash('legacy_flash', $message);

            return null;
        }

        return session()->pull('legacy_flash') ?? session('errors')?->first() ?? '';
    }
}
