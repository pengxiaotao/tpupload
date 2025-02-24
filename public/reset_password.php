<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/config.php';

$error = '';
$success = '';

if (isset($_GET['token']) && isset($_GET['user_id'])) {
    $token = $_GET['token'];
    $user_id = $_GET['user_id'];

    // 检查 token 是否有效
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND reset_token = ? AND reset_token_expires > NOW()");
    $stmt->bind_param("is", $user_id, $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $error = '重置链接无效或已过期，请重新请求重置密码。';
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $new_password = $_POST['new_password'];
            $confirm_password = $_POST['confirm_password'];

            if ($new_password !== $confirm_password) {
                $error = '新密码和确认密码不一致，请重新输入。';
            } else {
                // 对新密码进行哈希处理
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

                // 更新用户密码并清除重置 token
                $stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?");
                $stmt->bind_param("si", $hashed_password, $user_id);

                if ($stmt->execute()) {
                    $success = '密码重置成功，请使用新密码登录。';
                } else {
                    $error = '密码重置失败，请稍后重试。错误信息：' . $conn->error;
                }
            }
        }
    }
} else {
    $error = '重置链接无效，请检查链接地址。';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>重置密码</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
        }

        form {
            margin-top: 20px;
        }

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
    <h1>重置密码</h1>
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
    <?php if (!$success): ?>
        <form method="post">
            <label for="new_password">新密码：</label><br>
            <input type="password" id="new_password" name="new_password" required><br>
            <label for="confirm_password">确认密码：</label><br>
            <input type="password" id="confirm_password" name="confirm_password" required><br>
            <input type="submit" value="重置密码">
        </form>
    <?php endif; ?>
</body>

</html>