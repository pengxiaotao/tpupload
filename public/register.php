<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/config.php';

// 初始化错误和成功消息变量
$error = '';
$success = '';

// 处理表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取表单数据
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $captcha = $_POST['captcha'];

    // 验证验证码
    if (strtolower($captcha) !== strtolower($_SESSION['captcha'])) {
        $error = '验证码输入错误，请重新输入。';
    } elseif (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = '所有字段均为必填项，请填写完整。';
    } elseif ($password !== $confirm_password) {
        $error = '两次输入的密码不一致，请重新输入。';
    } else {
        // 检查用户名是否已存在
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            $error = '该用户名已被使用，请选择其他用户名。';
        } else {
            // 检查邮箱是否已存在
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows > 0) {
                $error = '该邮箱已被注册，请使用其他邮箱。';
            } else {
                // 对密码进行哈希处理
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // 生成一个随机的 token
                $token = bin2hex(random_bytes(32));

                // 插入新用户记录
                $stmt = $conn->prepare("INSERT INTO users (username, email, password, token) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("ssss", $username, $email, $hashed_password, $token);
                if ($stmt->execute()) {
                    $success = '注册成功！请使用新账号登录。';
                } else {
                    $error = '注册失败，请稍后重试。错误信息：' . $stmt->error;
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户注册</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
        }

        form {
            margin-top: 20px;
        }

        input[type="text"],
        input[type="email"],
        input[type="password"] {
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
    <h1>用户注册</h1>
    <?php if ($error): ?>
        <p class="error"><?php echo $error; ?></p>
    <?php endif; ?>
    <?php if ($success): ?>
        <p class="success"><?php echo $success; ?></p>
    <?php endif; ?>
    <?php if (!$success): ?>
        <form method="post">
            <label for="username">用户名：</label><br>
            <input type="text" id="username" name="username" required><br>
            <label for="email">邮箱：</label><br>
            <input type="email" id="email" name="email" required><br>
            <label for="password">密码：</label><br>
            <input type="password" id="password" name="password" required><br>
            <label for="confirm_password">确认密码：</label><br>
            <input type="password" id="confirm_password" name="confirm_password" required><br>
            <label for="captcha">验证码：</label><br>
            <input type="text" id="captcha" name="captcha" required><br>
            <img src="captcha.php" alt="验证码" onclick="this.src='captcha.php?' + Math.random();"><br>
            <input type="submit" value="注册">
        </form>
    <?php endif; ?>
</body>

</html>