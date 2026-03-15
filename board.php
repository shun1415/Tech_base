<?php
// board.php
require_once 'config.php';
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: index.php");
    exit;
}

$pdo = get_db_connection();
$error_message = "";

// スレッド削除処理
if (isset($_POST["delete_thread"])) {
    $thread_id = (int)$_POST["thread_id"];

    // 作成者を確認
    $stmt = $pdo->prepare("SELECT user_id, image_path FROM posts WHERE id = :id");
    $stmt->bindParam(':id', $thread_id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch();

    if ($row && $row['user_id'] == $_SESSION["user_id"]) {
        // 画像削除 (uploads/ にある場合のみ)
        if (!empty($row['image_path']) && file_exists(__DIR__ . "/" . $row['image_path'])) {
            unlink(__DIR__ . "/" . $row['image_path']);
        }
        // 親スレッドおよび紐付く返信を削除
        $sql = $pdo->prepare("DELETE FROM posts WHERE id = :id OR parent_id = :id");
        $sql->bindParam(':id', $thread_id, PDO::PARAM_INT);
        $sql->execute();
    }
    header("Location: board.php");
    exit;
}
   
// 新しいスレッド作成
if (isset($_POST["new_thread"])) {
    $title = trim($_POST["title"]);
    $content = trim($_POST["content"]);
    $image_path = null;

    // MIMEタイプの簡易チェック
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    if (!empty($_FILES["image"]["name"]) && $_FILES["image"]["error"] == UPLOAD_ERR_OK) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $_FILES["image"]["tmp_name"]);
        finfo_close($finfo);

        if (in_array($mime_type, $allowed_types)) {
            $upload_dir = __DIR__ . "/uploads/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            // 安全なファイル名を生成
            $extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
            $filename = uniqid(time() . "_") . "." . $extension;
            $target_path = $upload_dir . $filename;

            if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_path)) {
                $image_path = "uploads/" . $filename;
            } else {
                $error_message = "画像のアップロードに失敗しました。";
            }
        } else {
            $error_message = "許可されていないファイル形式です（JPG, PNG, GIF, WEBPのみ対応）。";
        }
    }

    if (($title !== "" || $content !== "") && empty($error_message)) {
        $sql = $pdo->prepare("INSERT INTO posts (user_id, parent_id, title, content, image_path) 
                              VALUES (:user_id, NULL, :title, :content, :image_path)");
        $sql->bindParam(':user_id', $_SESSION["user_id"], PDO::PARAM_INT);
        $sql->bindParam(':title', $title, PDO::PARAM_STR);
        $sql->bindParam(':content', $content, PDO::PARAM_STR);
        $sql->bindParam(':image_path', $image_path, PDO::PARAM_STR);
        $sql->execute();
        header("Location: board.php");
        exit;
    } elseif (empty($error_message)) {
        $error_message = "タイトルまたは本文を入力してください。";
    }
}

// 親スレッド一覧取得
$sql = "SELECT p.id, p.user_id, p.title, p.content, p.image_path, p.created_at, u.username
        FROM posts p
        JOIN users u ON p.user_id = u.id
        WHERE p.parent_id IS NULL
        ORDER BY p.created_at DESC";
$stmt = $pdo->query($sql);
$threads = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>みんなの本棚 - スレッド一覧</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
  <!-- index.php と共通のベーススタイル -->
  <style>
    :root {
      --primary: #4F46E5;
      --primary-hover: #4338CA;
      --bg: #F3F4F6;
      --surface: #FFFFFF;
      --text: #1F2937;
      --text-muted: #6B7280;
      --error: #EF4444;
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
      max-width: 1200px;
      margin: 2rem auto;
      padding: 0 1rem;
    }
    .card {
      background: var(--surface);
      border-radius: 12px;
      padding: 1.5rem;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
      margin-bottom: 2rem;
      border: 1px solid var(--border);
    }
    h2 { font-size: 1.25rem; font-weight: 600; margin-top: 0; margin-bottom: 1rem; }
    
    input[type="text"], textarea {
      width: 100%;
      padding: 0.75rem;
      border: 1px solid var(--border);
      border-radius: 8px;
      font-size: 1rem;
      margin-bottom: 1rem;
      box-sizing: border-box;
      font-family: inherit;
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
    .btn-danger { background-color: white; color: var(--error); border: 1px solid var(--error); padding: 0.4rem 0.8rem; font-size: 0.875rem;}
    .btn-danger:hover { background-color: #FEF2F2; }
    
    .alert-error {
      background-color: #FEE2E2;
      color: var(--error);
      padding: 1rem;
      border-radius: 8px;
      margin-bottom: 1rem;
      font-weight: 600;
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 1.5rem;
    }

    .thread-card {
      background: white;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
      border: 1px solid var(--border);
      transition: transform 0.2s, box-shadow 0.2s;
      display: flex;
      flex-direction: column;
    }
    .thread-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
    }
    .thread-img {
      width: 100%;
      height: 180px;
      object-fit: cover;
      background-color: #F3F4F6;
    }
    .thread-content {
      padding: 1.25rem;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
    }
    .thread-title {
      font-size: 1.125rem;
      font-weight: 700;
      margin: 0 0 0.5rem 0;
      color: var(--text);
      text-decoration: none;
    }
    .thread-title:hover { color: var(--primary); text-decoration: underline; }
    .thread-meta {
      font-size: 0.875rem;
      color: var(--text-muted);
      margin-bottom: auto;
    }
    .thread-footer {
      margin-top: 1rem;
      display: flex;
      justify-content: space-between;
      align-items: center;
      border-top: 1px solid var(--border);
      padding-top: 0.75rem;
    }
  </style>
</head>
<body>

  <header class="header">
    <h1>みんなの本棚</h1>
    <div style="font-size: 0.9rem; font-weight: 600;">
      ようこそ <b><?= h($_SESSION["username"]) ?></b> さん
      <a href="index.php" style="margin-left: 1rem; color: var(--text-muted); text-decoration: none;">ログアウト</a>
    </div>
  </header>

  <div class="container">
    <div class="card">
      <h2>📖 新しい本（スレッド）を登録する</h2>
      <?php if ($error_message): ?>
        <div class="alert-error"><?= h($error_message) ?></div>
      <?php endif; ?>
      <form method="post" action="" enctype="multipart/form-data">
          <input type="text" name="title" placeholder="本の題名（スレッドタイトル）" required>
          <textarea name="content" rows="3" placeholder="感想、質問、コメントを入力してください"></textarea>
          
          <div style="margin-bottom: 1rem; display: flex; flex-direction: column; gap: 0.5rem;">
            <label style="font-size: 0.875rem; font-weight: 600; color: var(--text-muted);">サムネイル画像 (任意)</label>
            <input type="file" name="image" accept="image/jpeg, image/png, image/gif, image/webp" style="font-size: 0.9rem;">
          </div>
          <button type="submit" name="new_thread" class="btn">スレッドを作成する</button>
      </form>
    </div>

    <h2>📚 本棚（スレッド一覧）</h2>
    <div class="grid">
      <?php foreach ($threads as $thread): ?>
        <div class="thread-card">
          <?php if (!empty($thread['image_path'])): ?>
              <img src="<?= h($thread['image_path']) ?>" alt="サムネイル" class="thread-img">
          <?php else: ?>
              <div class="thread-img" style="display:flex; align-items:center; justify-content:center; color: #9CA3AF;">
                画像なし
              </div>
          <?php endif; ?>
          
          <div class="thread-content">
            <a href="thread.php?id=<?= $thread['id'] ?>" class="thread-title">
                <?= h($thread['title'] ?: "タイトルなし") ?>
            </a>
            <div class="thread-meta">
              投稿者: <?= h($thread['username']) ?><br>
              <?= h(date('Y/m/d H:i', strtotime($thread['created_at']))) ?>
            </div>
            
            <div class="thread-footer">
              <a href="thread.php?id=<?= $thread['id'] ?>" style="font-size: 0.875rem; font-weight: 600; color: var(--primary); text-decoration: none;">詳細を見る &rarr;</a>
              
              <!-- 削除ボタン（本人のみ） -->
              <?php if ($thread['user_id'] == $_SESSION["user_id"]): ?>
              <form method="post" action="" style="margin: 0;">
                  <input type="hidden" name="thread_id" value="<?= $thread['id'] ?>">
                  <button type="submit" name="delete_thread" class="btn-danger" onclick="return confirm('本当に削除しますか？紐づく返信もすべて消去されます。');">削除</button>
              </form>
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if(empty($threads)): ?>
        <p style="color: var(--text-muted); grid-column: 1 / -1;">まだスレッドがありません。最初の本を登録してみましょう！</p>
      <?php endif; ?>
    </div>
  </div>

</body>
</html>