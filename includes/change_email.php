<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/config.php';

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_email = $_POST['new_email'];
    $user_id = $_SESSION['user_id'];

    // 检查新邮箱是否已被注册
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $new_email, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $error = '该邮箱已被注册，请使用其他邮箱。';
    } else {
        // 更新用户邮箱
        $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
        $stmt->bind_param("si", $new_email, $user_id);
        if ($stmt->execute()) {
            $success = '邮箱更换成功。';
        } else {
            $error = '邮箱更换失败，请稍后重试。';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>更换邮箱</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
        }

        form {
            margin-top: 20px;
        }

        input[type="email"] {
            padding: 8px;
            width: 200px;
            margin-bottom: 10px;
        }

        input[type="submit"] {
            padding: 8px 20px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

        .error {
            color: red;
            margin-top: 10px;
        }

        .success {
            color: green;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <h1>更换邮箱</h1>
    <?php if ($error): ?>
        <p class="error">
            <?php echo $error; ?>
        </p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="success">
            <?php echo $success; ?>
        </p>
    <?php endif; ?>
    <form method="post">
        <label for="new_email">新邮箱：</label><br>
        <input type="email" id="new_email" name="new_email" required><br>
        <input type="submit" value="更换邮箱">
    </form>
</body>

</html>