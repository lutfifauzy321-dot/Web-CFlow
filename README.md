# CFlow

CFlow is a Laravel 12 application for tracking personal income, expenses, budgets, and savings goals.

## Requirements

- PHP 8.2 or newer
- Composer
- MySQL or MariaDB

## Local setup

1. Copy `.env.example` to `.env` and set the database connection and `APP_URL`.
2. Install PHP dependencies with `composer install`.
3. Generate the application key with `php artisan key:generate`.
4. Run `php artisan migrate --seed`.
5. Point the local web server to this folder. The root `index.php` forwards requests to Laravel's `public/index.php`; configure `APP_URL` to match the local URL.

The migrations detect the existing CFlow tables and preserve their data. Back up the database before applying migrations. For a fresh database, the category seeder inserts the default income and expense categories.

CSS, JavaScript, and uploaded images are served from `public/assets/` and `public/uploads/`. The root `.htaccess` bridges the old Laragon folder URL to these public directories; a production web server can point directly to `public/`.
