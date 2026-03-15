# 📚 みんなの本棚 (Tech-Base Internship Project)

ローカル/簡易サーバー環境で動作する、スレッド形式の書籍共有掲示板アプリケーションです。
長期インターンシップ「TECH-BASE」の課題を発展させ、**「モダンなUIデザイン」**と**「実践的なセキュリティ対策」**を取り入れてフルリファクタリングを行いました。

---

## 🌟 特徴 (Features)
単なる課題提出用ではなく、「Webアプリケーションとして安全かつモダンに動くか」にこだわって制作しました。

*   **🔒 強固なセキュリティ対策**:
    *   **SQLインジェクション対策**: DBアクセスにはすべてPDO（PHP Data Objects）のプリペアドステートメントを使用しています。
    *   **パスワード保護**: 平文保存を廃止し、`password_hash()` を用いたハッシュ化（Bcrypt）を行っています。ログイン認証も `password_verify()` で照合。
    *   **XSS（クロスサイトスクリプティング）対策**: 入力されたデータを出力する際はすべて専用の `h()` 関数で `htmlspecialchars(..., ENT_QUOTES)` を通し、スクリプト実行を防止。
    *   **MIMEタイプチェック**: プロフィール画像などのアップロード機能において、「拡張子の偽装」を防ぐため `finfo_file` でMIMEタイプを厳密にチェックしています。
    *   **セッションハイジャック対策**: ログイン成功時に `session_regenerate_id(true)` を実行し、セッション固定攻撃を防止。
    *   **設定とロジックの分離**: `.env` や `config.php` を導入し、DB接続情報や管理用パスワードのソースコード直書き（ハードコーディング）を排除しました。

*   **✨ モダン UI (Glassmorphism / Responsive)**:
    *   CSSフレームワークに頼らず、ネイティブなCSS Grid / Flexbox で組んでいます。
    *   昨今のトレンドである「グラスモーフィズム (Glassmorphism)」を取り入れ、半透明の美しいカードUIと心地よいホバーエフェクトを実現しました。

---

## 💻 デモ画面 (Demo)
*(※ ここにスクリーンショットを配置します)*  
*(例: ログイン画面のスクショ、掲示板のカード型一覧画面のスクショ、投稿画面のスクショなど)*

| ログイン画面 (Glassmorphism) | 掲示板一覧 (Card Form) |
| :---: | :---: |
| <img src="https://via.placeholder.com/600x400.png?text=Login+Screen" width="100%"> | <img src="https://via.placeholder.com/600x400.png?text=Board+List" width="100%"> |

---

## 🛠 技術スタック (Tech Stack)

*   **Backend:** PHP 8.x
*   **Database:** MySQL (MariaDB)
*   **Frontend:** HTML5, Vanilla CSS3 (Custom Glassmorphism Design)

---

## 📁 ディレクトリ構成

```text
.
├── config.php    # DB接続設定、共通セキュリティ関数などを集約
├── index.php     # 新規登録・ログイン画面兼エントリポイント
├── board.php     # メインボード（スレッド一覧・親スレッド作成）
├── thread.php    # スレッド詳細画面（返信・コメント機能）
├── uploads/      # アップロード画像保存先（実行時に自動生成）
└── README.md     # This file
```

---

## 🚀 実行手順 (Setup)

お手元のローカル環境（XAMPP, MAMP, またはPHPビルトインサーバー）で簡単に動作確認が可能です。

### 1. データベースの準備
MySQLにログインし、以下のデータベースとテーブルを作成してください。

```sql
-- データベースの作成
CREATE DATABASE tb270522db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tb270522db;

-- ユーザーテーブルの作成
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 投稿（スレッド・返信）テーブルの作成
CREATE TABLE posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    parent_id INT DEFAULT NULL,
    title VARCHAR(255) DEFAULT '',
    content TEXT NOT NULL,
    image_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

### 2. 設定ファイルの更新
`config.php` 内の `DB_DSN`, `DB_USER`, `DB_PASS` をご自身の環境に合わせて変更してください。

### 3. アプリケーションの起動
PHPビルトインサーバーを使った簡単な起動方法：

```bash
# プロジェクトルートディレクトリで実行
php -S localhost:8000
```
ブラウザで `http://localhost:8000/index.php` にアクセスします。

---

## 💡 今後の課題・拡張予定
*   **管理画面の分離**: 簡易実装している管理者ログイン（`ADMIN_ID`）をDB管理にし、専用の `admin.php` ダッシュボードを構築する。
*   **MVCアーキテクチャへの完全移行**: 現在の手続き型PHPスクリプトを、より本格的な業務レベルのフレームワーク（Laravel等）に近しい構造へリファクタリング。
