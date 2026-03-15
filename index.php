<?php
// index.php
require_once 'config.php';
session_start();

$pdo = get_db_connection();
$error_message = "";
$success_message = "";

// 新規登録処理
if (isset($_POST["signup"])) {
    $name_signup = trim($_POST["name_signup"]);
    $pw_signup = $_POST["pw_signup"];
    
    if (!empty($name_signup) && !empty($pw_signup)) {
        // 同名ユーザーが存在するか確認
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = :username");
        $stmt->bindParam(':username', $name_signup, PDO::PARAM_STR);
        $stmt->execute();
        
        if ($stmt->fetch()) {
            $error_message = "そのユーザー名は既に使われています。";
        } else {
            // パスワードをハッシュ化して保存（セキュリティ対策）
            $hashed = password_hash($pw_signup, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
            $stmt->bindParam(':username', $name_signup, PDO::PARAM_STR);
            $stmt->bindParam(':password', $hashed, PDO::PARAM_STR);
            
            if ($stmt->execute()) {
                $_SESSION["user_id"] = $pdo->lastInsertId();
                $_SESSION["username"] = $name_signup;
                header("Location: board.php");
                exit;
            } else {
                $error_message = "登録中にエラーが発生しました。";
            }
        }
    } else {
        $error_message = "すべての項目を入力してください。";
    }
}

// ユーザーログイン処理
if (isset($_POST["login"])) {
    $name_login = trim($_POST["name_login"]);
    $pw_login = $_POST["pw_login"];
    
    if (!empty($name_login) && !empty($pw_login)) {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $name_login, PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch();
        
        if ($user && password_verify($pw_login, $user["password"])) {
            $_SESSION["user_id"] = $user["id"];
            $_SESSION["username"] = $user["username"];
            
            // 古いセッションIDを無効化（セッションハイジャック対策）
            session_regenerate_id(true);
            header("Location: board.php");
            exit;
        } else {
            $error_message = "ユーザー名またはパスワードが間違っています。";
        }
    } else {
        $error_message = "すべての項目を入力してください。";
    }
}

// 管理者ログイン処理
if (isset($_POST["admin_login"])) {
    $admin_id = $_POST["id"];
    $admin_pw = $_POST["pw_admin"];

    if ($admin_id === ADMIN_ID && $admin_pw === ADMIN_PASS) {
        $_SESSION["admin"] = true;
        session_regenerate_id(true);
        header("Location: admin.php"); // ※ 現状admin.phpは存在しないが遷移先として保留
        exit;
    } else {
        $error_message = "管理者ログインに失敗しました。";
    }
}

// コミュニティメンバー一覧取得
$stmt = $pdo->query("SELECT id, username, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
$member_count = count($users);
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>みんなの本棚 - ログイン・新規登録</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    /* CSS変数でカラーパレットを定義 */
    :root {
      --primary: #4F46E5;
      --primary-hover: #4338CA;
      --bg: #F3F4F6;
      --surface: rgba(255, 255, 255, 0.9);
      --text: #1F2937;
      --text-muted: #6B7280;
      --error: #EF4444;
      --border: #E5E7EB;
    }
    
    body {
      font-family: 'Inter', sans-serif;
      background: linear-gradient(135deg, #E0E7FF 0%, #EDE9FE 100%);
      color: var(--text);
      margin: 0;
      padding: 0;
      display: flex;
      justify-content: center;
      align-items: flex-start;
      min-height: 100vh;
    }
    
    .container {
      width: 100%;
      max-width: 1000px;
      margin: 2rem auto;
      padding: 0 1rem;
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 2rem;
    }

    @media (max-width: 768px) {
      .container {
        grid-template-columns: 1fr;
      }
    }

    .glass-card {
      background: var(--surface);
      backdrop-filter: blur(10px);
      -webkit-backdrop-filter: blur(10px);
      border-radius: 16px;
      padding: 2.5rem;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
      border: 1px solid rgba(255, 255, 255, 0.8);
      margin-bottom: 2rem;
    }

    h1 {
      font-size: 1.8rem;
      font-weight: 700;
      text-align: center;
      margin-top: 0;
      margin-bottom: 0.5rem;
      color: var(--primary);
    }

    h2 {
      font-size: 1.25rem;
      font-weight: 600;
      margin-bottom: 1.5rem;
      border-bottom: 2px solid var(--border);
      padding-bottom: 0.5rem;
    }

    p.subtitle {
      text-align: center;
      color: var(--text-muted);
      margin-bottom: 2rem;
    }

    .alert {
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1.5rem;
      font-weight: 600;
      text-align: center;
    }

    .alert-error {
      background-color: #FEE2E2;
      color: var(--error);
      border: 1px solid #FECACA;
    }

    .form-group {
      margin-bottom: 1.25rem;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      font-size: 0.875rem;
      font-weight: 600;
    }

    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 0.75rem 1rem;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 1rem;
      transition: all 0.2s;
      box-sizing: border-box;
      background: white;
    }

    input[type="text"]:focus,
    input[type="password"]:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
    }

    .btn {
      width: 100%;
      padding: 0.75rem 1.5rem;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-primary {
      background-color: var(--primary);
      color: white;
    }

    .btn-primary:hover {
      background-color: var(--primary-hover);
      transform: translateY(-1px);
      box-shadow: 0 4px 6px rgba(79, 70, 229, 0.2);
    }

    .btn-outline {
      background-color: transparent;
      color: var(--text-muted);
      border: 1px solid var(--border);
    }

    .btn-outline:hover {
      background-color: #F9FAFB;
      color: var(--text);
    }

    /* メンバーテーブル用 */
    .member-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 1rem;
    }

    .member-table th,
    .member-table td {
      padding: 0.75rem;
      text-align: left;
      border-bottom: 1px solid var(--border);
    }

    .member-table th {
      font-weight: 600;
      color: var(--text-muted);
      background-color: #F9FAFB;
    }
  </style>
</head>
<body>

  <div class="container">
    
    <!-- 左側 (ヘッダー, メンバーリスト) -->
    <div class="column">
      <div class="glass-card">
        <h1>📚 みんなの本棚</h1>
        <p class="subtitle">あなたのお気に入りの本を共有しよう</p>
        
        <?php if ($error_message): ?>
          <div class="alert alert-error"><?= h($error_message) ?></div>
        <?php endif; ?>

        <h2>コミュニティメンバー (<?= $member_count ?>)</h2>
        <?php if ($users): ?>
          <div style="overflow-x:auto;">
            <table class="member-table">
              <tr>
                <th>名前</th>
                <th>登録日時</th>
              </tr>
              <?php foreach ($users as $user): ?>
              <tr>
                <td><?= h($user['username']) ?></td>
                <td><?= h($user['created_at']) ?></td>
              </tr>
              <?php endforeach; ?>
            </table>
          </div>
        <?php else: ?>
          <p style="color: var(--text-muted);">まだメンバーがいません。</p>
        <?php endif; ?>
      </div>
      
      <!-- 管理者ログインをアコーディオン風、または下部に小さく配置 -->
      <div class="glass-card" style="padding: 1.5rem;">
        <h2 style="font-size: 1rem; border: none; margin-bottom: 1rem; color: var(--text-muted);">管理者ログイン</h2>
        <form action="" method="post">
          <div class="form-group">
            <input type="text" name="id" placeholder="管理者ID">
          </div>
          <div class="form-group">
            <input type="password" name="pw_admin" placeholder="パスワード">
          </div>
          <button type="submit" name="admin_login" class="btn btn-outline">ログイン</button>
        </form>
      </div>
    </div>

    <!-- 右側 (登録/ログインフォーム) -->
    <div class="column">
      <div class="glass-card">
        <h2>👋 いつものユーザー</h2>
        <form action="" method="post">
          <div class="form-group">
            <label>ユーザー名</label>
            <input type="text" name="name_login" placeholder="例: Yamada Tarou" required>
          </div>
          <div class="form-group">
            <label>パスワード</label>
            <input type="password" name="pw_login" placeholder="••••••••" required>
          </div>
          <button type="submit" name="login" class="btn btn-primary">ログイン</button>
        </form>
      </div>

      <div class="glass-card">
        <h2>✨ 新しくはじめる</h2>
        <form action="" method="post">
          <div class="form-group">
            <label>新しく登録するユーザー名</label>
            <input type="text" name="name_signup" placeholder="例: Book Lover" required>
          </div>
          <div class="form-group">
            <label>パスワード</label>
            <input type="password" name="pw_signup" placeholder="••••••••" required>
          </div>
          <button type="submit" name="signup" class="btn btn-primary">新規アカウント作成</button>
        </form>
      </div>
    </div>
    
  </div>

</body>
</html>