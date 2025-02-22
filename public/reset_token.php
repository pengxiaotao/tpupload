<?php
session_start();
require_once '../includes/db.php';

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 生成一个新的随机 token
    $new_token = bin2hex(random_bytes(32));

    // 更新用户的 token
    $stmt = $conn->prepare("UPDATE users SET token = ? WHERE id = ?");
    $stmt->bind_param("si", $new_token, $user_id);
    if ($stmt->execute()) {
        $success = 'Token 重置成功！';
        // 更新会话中的 token
        $_SESSION['token'] = $new_token;
    } else {
        $error = 'Token 重置失败，请稍后重试。错误信息：' . $stmt->error;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>重置 Token</title>
</head>

<body>
    <h1>重置 Token</h1>
    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <p style="color: green;"><?php echo $success; ?></p>
    <?php endif; ?>
    <form method="post">
        <input type="submit" value="重置 Token">
    </form>
</body>

</html>