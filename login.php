<?php
session_start();

// 仮のログイン情報
$valid_user = 'user';
$valid_pass = 'pass123';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === $valid_user && $password === $valid_pass) {
        $_SESSION['logged_in'] = true;
        header('Location: index.php');
        exit;
    } else {
        $error = 'ユーザー名またはパスワードが違います';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>ログイン</title>
</head>
<body>
    <h1>ログイン</h1>
    <?php if ($error): ?>
        <p style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <form method="post">
        <input type="text" name="username" placeholder="ユーザー名"><br>
        <input type="password" name="password" placeholder="パスワード"><br>
        <button type="submit">ログイン</button>
    </form>
</body>
</html>
