<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// 检查用户是否已登录
$is_logged_in = isset($_SESSION['user_id']);

if ($is_logged_in) {
    // 获取用户信息
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT username, balance FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $username = $user['username'];
    $balance = $user['balance'];
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>图片转文字系统</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
        }

        .button {
            display: inline-block;
            background-color: #007BFF;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px;
        }

        .button:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <h1>图片转文字系统</h1>
    <?php if ($is_logged_in): ?>
        <p>欢迎，
            <?php echo $username; ?>！您的余额：
            <?php echo $balance; ?> 元
        </p>
        <a href="upload.php" class="button">上传图片进行识别</a>
        <a href="recharge.php" class="button">充值</a>
        <a href="logout.php" class="button">退出登录</a>
    <?php else: ?>
        <a href="register.php" class="button">注册</a>
        <a href="login.php" class="button">登录</a>
    <?php endif; ?>
</body>

</html>