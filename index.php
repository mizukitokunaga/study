<?php
session_start();
date_default_timezone_set('Asia/Tokyo');
// ログインしていない場合はログインページへリダイレクト
if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$dsn = "pgsql:host=dpg-cvv0bgqdbo4c73famae0-a;port=5432;dbname=study_rf18";
$user = "study_rf18_user";
$password = "VOBl51BTGwEXCzLu7bxD9ZVeNGR4yC3A";

$pdo = new PDO($dsn, $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
]);


// 初回のみ：テーブルがなければ作成
$pdo->exec("
    CREATE TABLE IF NOT EXISTS memos (
        id SERIAL PRIMARY KEY,
        content TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )
");


// 削除リクエストが来ていたら処理
if (isset($_GET['delete'])) {
  $deleteId = (int) $_GET['delete']; // 数値として扱う（安全のため）

  $stmt = $pdo->prepare("DELETE FROM memos WHERE id = ?");
  $stmt->execute([$deleteId]);
}


// フォームが送信されたら保存
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = $_POST['content'] ?? '';

    if ($content) {
      $now = date('Y-m-d H:i:s'); // ← Asia/Tokyo タイムゾーンでの現在時刻
      $stmt = $pdo->prepare("INSERT INTO memos (content, created_at) VALUES (?, ?)");
      $stmt->execute([$content, $now]);
    }
}
// 編集保存処理
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
  $updateId = (int) $_POST['update_id'];
  $newContent = $_POST['new_content'] ?? '';

  if ($newContent) {
      $stmt = $pdo->prepare("UPDATE memos SET content = ? WHERE id = ?");
      $stmt->execute([$newContent, $updateId]);

      header("Location: index.php");
      exit;
  }
}

// キーワードを取得
$keyword = $_GET['keyword'] ?? '';

// SQLクエリを動的に切り替え
if ($keyword) {
    $stmt = $pdo->prepare("SELECT * FROM memos WHERE content LIKE ? ORDER BY created_at DESC");
    $stmt->execute(['%' . $keyword . '%']);
} else {
    $stmt = $pdo->query("SELECT * FROM memos ORDER BY created_at DESC");
}
$memos = $stmt->fetchAll(PDO::FETCH_ASSOC);


// メモ取得
//$stmt = $pdo->query("SELECT * FROM memos ORDER BY created_at DESC");
//$memos = $stmt->fetchAll(PDO::FETCH_ASSOC);
$editId = isset($_GET['edit']) ? (int) $_GET['edit'] : null;
$previousDate = '';

// 現在の月（GETパラメータ）取得、なければ今月
$currentMonth = $_GET['month'] ?? date('Y-m');

// 検索用に開始・終了日時を作成
$startOfMonth = $currentMonth . '-01 00:00:00';
$endOfMonth = date('Y-m-t 23:59:59', strtotime($startOfMonth));

if ($keyword) {
  $stmt = $pdo->prepare("SELECT * FROM memos WHERE content LIKE ? AND created_at BETWEEN ? AND ? ORDER BY created_at DESC");
  $stmt->execute(['%' . $keyword . '%', $startOfMonth, $endOfMonth]);
} else {
  $stmt = $pdo->prepare("SELECT * FROM memos WHERE created_at BETWEEN ? AND ? ORDER BY created_at DESC");
  $stmt->execute([$startOfMonth, $endOfMonth]);
}
$memos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
  <head>
      <meta charset="UTF-8">
      <title>たも日報</title>
      <link rel="stylesheet" href="css/reset.css">
      <link rel="stylesheet" href="css/bootstrap-grid.min.css">
      <link rel="stylesheet" href="css/style.css">
      <!-- Font Awesome CDN -->
      <script src="https://kit.fontawesome.com/c339f9e74c.js" crossorigin="anonymous"></script>
  </head>
<body>
  <header>
    <div class="container-fluid">
      <div class="row">
        <div class="flex">
          <img src="img/tamo.svg" class="header-img">
          <h1>たも日報</h1>
          <a class="logout" href="logout.php">ログアウト</a>
        </div>
      </div>
    </div>
  </header>
  <div class="hero">
    <img src="img/hero.png" alt="メイン画像" class="hero-bg" />
    <div class="dot-overlay"></div>
    <div class="wave-bottom"></div>
  </div>
  <div class="container">
    <div class="row">
      <div class="form-area col-12 mt-30">
        <form class="form-card" method="post">
          <label>たもは何してた？</label><br>
          <textarea name="content" class="mt-10 mb-10"></textarea><br>
          <button class="gradient-border-button" type="submit"><span>保存</span></button>
        </form>
      </div>

      <!--検索エリア-->
      <div class="col-12 z-index mt-30 mb-30">
        <form class="search" method="get">
            <input style="width:70%;" type="text" name="keyword" placeholder="キーワードで検索" value="<?= htmlspecialchars($_GET['keyword'] ?? '') ?>">
            <button class="button" type="submit"><i class="fas fa-search"></i></button>
            <input style="width:30%;" class="ml-15" type="month" name="month" id="month" value="<?= htmlspecialchars($currentMonth) ?>">
            <button class="button" type="submit"><i class="fas fa-search"></i></button>
        </form>
      </div>

      <div class="col-12 z-index">
        <?php
        $currentDate = '';
        ?>

        <?php foreach ($memos as $memo): ?>
          <?php
              $fullDatetime = $memo['created_at'];
              $date = date('Y年n月j日', strtotime($fullDatetime));
              $time = date('H:i', strtotime($fullDatetime));
          ?>
          <?php if ($date !== $currentDate): ?>
            <?php if ($currentDate !== ''): ?>
                </tbody></table> <!-- 前の日付のテーブルを閉じる -->
            <?php endif; ?>
            <h2 class="mb-10"><?= $date ?></h2>
            <table class="card" border="1" cellpadding="8">
              <tbody>
                    <?php $currentDate = $date; ?>
                  <?php endif; ?>
                <tr id="memo-<?= $memo['id'] ?>">
                  <td class="time"><?= $time ?></td>
                  <td>
                    <?php if ($editId === (int)$memo['id']): ?>
                        <!-- 編集フォーム本体（操作ボタンは右列に分離） -->
                        <form method="post" id="edit-form-<?= $memo['id'] ?>">
                            <input type="hidden" name="update_id" value="<?= $memo['id'] ?>">
                            <textarea name="new_content" rows="2" cols="30" required><?= htmlspecialchars($memo['content']) ?></textarea>
                        </form>
                    <?php else: ?>
                        <?= nl2br(htmlspecialchars($memo['content'])) ?>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($editId === (int)$memo['id']): ?>
                        <button class="mt-10 edit-save" type="submit" form="edit-form-<?= $memo['id'] ?>">保存</button>
                        <a class="edit-cancel" href="index.php">キャンセル</a>
                        <?php else: ?>
                          <a href="?edit=<?= $memo['id'] ?>#memo-<?= $memo['id'] ?>" class="icon-button" title="編集">
                            <i class="fas fa-pen"></i>
                          </a>
                          <a href="?delete=<?= $memo['id'] ?>" class="icon-button" onclick="return confirm('削除してもよろしいですか？')" title="削除">
                            <i class="fas fa-trash"></i>
                          </a>
                        <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              <?php if ($currentDate !== ''): ?>
            </tbody></table> <!-- 最後のテーブルを閉じる -->
            </div>
      <?php endif; ?>
    </div>
  </div>
  <footer class="footer">
  <div class="container">
    <p>&copy; <?= date('Y') ?> たも日報. All rights reserved.</p>
  </div>
</footer>
<div class="paw-tile">
  <?php for ($i = 0; $i < 100; $i++): ?>
    <i class="fa-solid fa-paw"></i>
  <?php endfor; ?>
</div>
</body>
</html>
