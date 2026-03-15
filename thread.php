<?php
// thread.php
require_once 'config.php';
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

$pdo = get_db_connection();
$thread_id = $_GET["id"] ?? null;

if (!$thread_id || !is_numeric($thread_id)) {
    die("有効なスレッドIDが指定されていません。");
}

// 親スレッド取得
$sql = "SELECT p.id, p.title, p.content, p.image_path, p.created_at, u.username
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.id = :id";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(":id", $thread_id, PDO::PARAM_INT);
$stmt->execute();
$thread = $stmt->fetch();
if (!$thread) {
    die("指定されたスレッドは存在しないか、削除されました。");
}

// 返信処理
if (isset($_POST["reply"])) {
    $content = trim($_POST["content"]);
    if ($content !== "") {
        $sql = $pdo->prepare("INSERT INTO posts (user_id, parent_id, content) VALUES (:user_id, :parent_id, :content)");
        $sql->bindParam(":user_id", $_SESSION["user_id"], PDO::PARAM_INT);
        $sql->bindParam(":parent_id", $thread_id, PDO::PARAM_INT);
        $sql->bindParam(":content", $content, PDO::PARAM_STR);
        $sql->execute();
    }
    // PRG (Post/Redirect/Get) パターンで再読み込み時の二重投稿を防止
    header("Location: thread.php?id=" . $thread_id);
    exit;
}

// 返信一覧取得
$sql = "SELECT p.id, p.content, p.created_at, u.username
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.parent_id = :parent_id
        ORDER BY p.created_at ASC";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(":parent_id", $thread_id, PDO::PARAM_INT);
$stmt->execute();
$replies = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= h($thread["title"] ?: "無題のスレッド") ?> - みんなの本棚</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      --primary: #4F46E5;
      --primary-hover: #4338CA;
      --bg: #F9FAFB;
      --surface: #FFFFFF;
      --text: #111827;
      --text-muted: #6B7280;
      --border: #E5E7EB;
    }
    body {
      font-family: 'Inter', sans-serif;
      background-color: var(--bg);
      color: var(--text);
      margin: 0;
      padding: 0;
    }
    .header {
      background: white;
      padding: 1rem 2rem;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
      display: flex;
      justify-content: space-between;
      align-items: center;
      position: sticky;
      top: 0;
      z-index: 100;
    }
    .header h1 {
      margin: 0;
      font-size: 1.5rem;
      color: var(--primary);
    }
    .container {
      max-width: 800px;
      margin: 2rem auto;
      padding: 0 1rem;
    }
    
    .main-post {
      background: var(--surface);
      border-radius: 12px;
      padding: 2rem;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
      border: 1px solid var(--border);
      margin-bottom: 2rem;
    }
    .main-post-title {
      font-size: 1.5rem;
      font-weight: 700;
      margin-top: 0;
      margin-bottom: 0.5rem;
    }
    .meta-info {
      font-size: 0.875rem;
      color: var(--text-muted);
      margin-bottom: 1.5rem;
      border-bottom: 1px solid var(--border);
      padding-bottom: 1rem;
    }
    .post-content {
      font-size: 1.05rem;
      line-height: 1.6;
      white-space: pre-wrap; /* 改行を反映 */
      color: var(--text);
      margin-bottom: 1.5rem;
    }
    .post-image {
      max-width: 100%;
      height: auto;
      border-radius: 8px;
      margin-bottom: 1rem;
      border: 1px solid var(--border);
    }
    
    .replies-section {
      margin-top: 2.5rem;
    }
    .reply-card {
      background: white;
      border-radius: 12px;
      padding: 1.5rem;
      border: 1px solid var(--border);
      margin-bottom: 1rem;
      display: flex;
      flex-direction: column;
    }
    .reply-header {
      font-size: 0.875rem;
      color: var(--text-muted);
      margin-bottom: 0.75rem;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }
    .avatar {
      width: 24px;
      height: 24px;
      background-color: var(--primary);
      color: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.75rem;
    }
    
    .reply-form-card {
      background: white;
      border-radius: 12px;
      padding: 1.5rem;
      border: 1px solid var(--border);
      margin-top: 2rem;
    }
    textarea {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 1rem;
      margin-bottom: 1rem;
      box-sizing: border-box;
      font-family: inherit;
      resize: vertical;
    }
    textarea:focus {
      outline: none;
      border-color: var(--primary);
      box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.2);
    }
    .btn {
      padding: 0.75rem 1.5rem;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      background-color: var(--primary);
      color: white;
      transition: background-color 0.2s;
    }
    .btn:hover { background-color: var(--primary-hover); }
    .back-link {
      display: inline-block;
      margin-bottom: 1rem;
      color: var(--text-muted);
      text-decoration: none;
      font-weight: 600;
    }
    .back-link:hover { color: var(--primary); }
  </style>
</head>
<body>

  <header class="header">
    <h1>みんなの本棚</h1>
    <div style="font-size: 0.9rem; font-weight: 600;">
      <a href="board.php" style="color: var(--text-muted); text-decoration: none;">本棚に戻る</a>
    </div>
  </header>

  <div class="container">
    <a href="board.php" class="back-link">&larr; スレッド一覧に戻る</a>

    <!-- メインスレッド（親） -->
    <div class="main-post">
      <h2 class="main-post-title"><?= h($thread["title"] ?: "無題のスレッド") ?></h2>
      <div class="meta-info">
        作成者: <b><?= h($thread["username"]) ?></b> &nbsp;|&nbsp; <?= h(date('Y/m/d H:i', strtotime($thread['created_at']))) ?>
      </div>
      
      <?php if (!empty($thread["image_path"])): ?>
        <img src="<?= h($thread["image_path"]) ?>" alt="サムネイル" class="post-image">
      <?php endif; ?>
      
      <div class="post-content"><?= nl2br(h($thread["content"])) ?></div>
    </div>

    <!-- 返信一覧 -->
    <div class="replies-section">
      <h3 style="margin-bottom: 1.5rem; border-bottom: 2px solid var(--border); padding-bottom: 0.5rem;">
        コメント・返信 (<?= count($replies) ?>)
      </h3>
      
      <?php if(empty($replies)): ?>
        <p style="color: var(--text-muted); text-align: center; padding: 2rem;">まだコメントがありません。最初の返信をしてみましょう！</p>
      <?php endif; ?>

      <?php foreach ($replies as $reply): ?>
        <div class="reply-card">
          <div class="reply-header">
            <!-- 簡易なアバターアイコン -->
            <div class="avatar"><?= mb_substr(h($reply["username"]), 0, 1) ?></div>
            <b><?= h($reply["username"]) ?></b>
            <span style="font-weight: 400;"><?= h(date('Y/m/d H:i', strtotime($reply['created_at']))) ?></span>
          </div>
          <div class="post-content" style="margin-bottom: 0; font-size: 1rem;">
            <?= nl2br(h($reply["content"])) ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- 返信フォーム -->
    <div class="reply-form-card">
      <h3 style="margin-top: 0;">返信を投稿する</h3>
      <form method="post">
        <textarea name="content" rows="4" placeholder="スレッドへの返信や感想を入力してください" required></textarea>
        <button type="submit" name="reply" class="btn">返信する</button>
      </form>
    </div>

  </div>

</body>
</html>