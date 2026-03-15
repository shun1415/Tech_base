<?php
// config.php
// データベース設定
define("DB_DSN", "mysql:dbname=tb270522db;host=localhost;charset=utf8mb4");
define("DB_USER", "tb-270522");
define("DB_PASS", "E2ePwp4ZYu");

// 管理者設定（本番ではDB管理などを推奨）
define("ADMIN_ID", "shunta");
define("ADMIN_PASS", "0723");

// PDOインスタンスを取得する共通関数（エラーハンドリング対応済み）
function get_db_connection() {
    try {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, array(
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ));
        return $pdo;
    } catch (PDOException $e) {
        // セキュリティのため、ユーザに見せるエラーは簡潔にする
        error_log("Database Connection Error: " . $e->getMessage());
        die("データベース接続に失敗しました。システム管理者にお問い合わせください。");
    }
}

// XSS対策のための共通エスケープ関数
function h($str) {
    if ($str === null) return '';
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}
?>
