<?php
// config.php

// .env ファイルを読み込む
function load_env($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim(trim($value), '"\'');
        putenv("$key=$value");
        $_ENV[$key] = $value;
    }
}

load_env(__DIR__ . '/.env');

// データベース設定（.env から読み込む）
define("DB_DSN",  getenv('DB_DSN')  ?: "mysql:dbname=tb270522db;host=localhost;charset=utf8mb4");
define("DB_USER", getenv('DB_USER') ?: "");
define("DB_PASS", getenv('DB_PASS') ?: "");

// 管理者設定（.env から読み込む）
define("ADMIN_ID",   getenv('ADMIN_ID')   ?: "");
define("ADMIN_PASS", getenv('ADMIN_PASS') ?: "");

// セッションをセキュアに設定する（session_start() より前に呼ぶ）
function configure_secure_session() {
    ini_set('session.cookie_httponly', 1);   // JavaScript からの Cookie アクセスを禁止
    ini_set('session.cookie_samesite', 'Lax'); // CSRF 緩和
    ini_set('session.use_strict_mode', 1);   // 未知のセッション ID を拒否
    ini_set('session.use_only_cookies', 1);  // URL にセッション ID を埋め込まない
    // HTTPS 環境では以下を有効化
    // ini_set('session.cookie_secure', 1);
}

// セキュリティヘッダーを送信する
function send_security_headers() {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header("Content-Security-Policy: default-src 'self'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; img-src 'self' data:;");
}

// PDOインスタンスを取得する共通関数
function get_db_connection() {
    try {
        $pdo = new PDO(DB_DSN, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database Connection Error: " . $e->getMessage());
        die("データベース接続に失敗しました。システム管理者にお問い合わせください。");
    }
}

// XSS対策のための共通エスケープ関数
function h($str) {
    if ($str === null) return '';
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// CSRF トークンを取得（セッションに保存）
function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// CSRF トークンを検証する
function verify_csrf_token() {
    return isset($_POST['csrf_token'], $_SESSION['csrf_token'])
        && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

// フォームに埋め込む CSRF hidden input を返す
function csrf_input() {
    return '<input type="hidden" name="csrf_token" value="' . h(get_csrf_token()) . '">';
}
?>
