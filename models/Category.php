<?php
class Category {
    static function all(): array { return db()->query('SELECT * FROM categories ORDER BY type DESC, id')->fetchAll(); }
}
