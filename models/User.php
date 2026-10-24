<?php
class User {
    static function find($id) { $s = db()->prepare('SELECT * FROM users WHERE id=?'); $s->execute([$id]); return $s->fetch() ?: null; }
    static function byEmail($e) { $s = db()->prepare('SELECT * FROM users WHERE email=?'); $s->execute([$e]); return $s->fetch() ?: null; }
    static function register($name, $email, $pass): int {
        db()->prepare('INSERT INTO users (name,email,password_hash) VALUES (?,?,?)')->execute([$name, $email, password_hash($pass, PASSWORD_DEFAULT)]);
        return (int)db()->lastInsertId();
    }
    static function updateProfile($id, $name, $email, ?string $avatar = null): void {
        if ($avatar !== null) {
            db()->prepare('UPDATE users SET name=?,email=?,avatar=? WHERE id=?')->execute([$name, $email, $avatar, $id]);
            return;
        }
        db()->prepare('UPDATE users SET name=?,email=? WHERE id=?')->execute([$name, $email, $id]);
    }
    static function setPassword($id, $pass): void {
        db()->prepare('UPDATE users SET password_hash=? WHERE id=?')->execute([password_hash($pass, PASSWORD_DEFAULT), $id]);
    }
    static function setGoal($id, $v) { db()->prepare('UPDATE users SET savings_goal=? WHERE id=?')->execute([$v, $id]); }
    static function setLimit($id, $v) { db()->prepare('UPDATE users SET daily_limit=? WHERE id=?')->execute([$v, $id]); }
}
