<?php
function db(): PDO {
    static $pdo;
    if ($pdo) return $pdo;
    $pdo = new PDO(
        'mysql:host=' . env('DB_HOST', 'localhost') . ';dbname=' . env('DB_NAME', 'cflow') . ';charset=utf8mb4',
        env('DB_USER', 'root'), env('DB_PASS', ''),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    if (empty($_SESSION['schema_ok'])) { // tambah kolom baru otomatis kalau DB lama
        foreach (['password_hash' => 'VARCHAR(255) NULL', 'daily_limit' => 'BIGINT DEFAULT 0'] as $c => $def)
            if (!$pdo->query("SHOW COLUMNS FROM users LIKE '$c'")->fetch()) $pdo->exec("ALTER TABLE users ADD $c $def");
        $note = $pdo->query("SHOW COLUMNS FROM transactions LIKE 'note'")->fetch();
        if ($note && preg_match('/varchar\((\d+)\)/i', $note['Type'], $match) && (int)$match[1] < 500)
            $pdo->exec('ALTER TABLE transactions MODIFY note VARCHAR(500)');
        $_SESSION['schema_ok'] = 1;
    }
    if (empty($_SESSION['schema_featured_ok'])) {
        if (!$pdo->query("SHOW COLUMNS FROM transactions LIKE 'featured'")->fetch())
            $pdo->exec('ALTER TABLE transactions ADD featured TINYINT(1) NOT NULL DEFAULT 0');
        $_SESSION['schema_featured_ok'] = 1;
    }
    if (empty($_SESSION['schema_title_ok'])) {
        if (!$pdo->query("SHOW COLUMNS FROM transactions LIKE 'title'")->fetch()) {
            $pdo->exec("ALTER TABLE transactions ADD title VARCHAR(150) NULL AFTER amount");
            $pdo->exec("UPDATE transactions SET title=CASE WHEN note IS NULL OR TRIM(note)='' OR note='Tanpa judul' THEN 'Transaksi' ELSE LEFT(note,150) END");
            $pdo->exec("ALTER TABLE transactions MODIFY title VARCHAR(150) NOT NULL DEFAULT 'Transaksi'");
        }
        $_SESSION['schema_title_ok'] = 1;
    }
    if (empty($_SESSION['schema_category_budgets_ok'])) {
        $pdo->exec("CREATE TABLE IF NOT EXISTS category_budgets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            category_id INT NOT NULL,
            budget_month CHAR(7) NOT NULL,
            amount BIGINT NOT NULL,
            UNIQUE KEY user_category_month (user_id, category_id, budget_month),
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
        )");
        $_SESSION['schema_category_budgets_ok'] = 1;
    }
    return $pdo;
}
