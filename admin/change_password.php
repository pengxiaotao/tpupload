<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/config.php';

// 检查管理员是否已登录
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['old_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // 获取管理员当前密码
    $admin_id = $_SESSION['admin_id'];
    $stmt = $conn->prepare("SELECT password FROM admins WHERE id = ?");
    $stmt->bind_param("i", $admin_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    // 验证旧密码
    if (!password_verify($old_password, $row['password'])) {
        $error = '旧密码错误';
    } elseif ($new_password !== $confirm_password) {
        $error = '新密码和确认密码不一致';
    } else {
        // 更新密码
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE admins SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $hashed_password, $admin_id);
        if ($stmt->execute()) {
            $success = '密码修改成功';
        } else {
            $error = '密码修改失败，请稍后再试';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理员修改密码</title>
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
    <h1>管理员修改密码</h1>
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
        <label for="old_password">旧密码：</label><br>
        <input type="password" id="old_password" name="old_password" required><br>
        <label for="new_password">新密码：</label><br>
        <input type="password" id="new_password" name="new_password" required><br>
        <label for="confirm_password">确认密码：</label><br>
        <input type="password" id="confirm_password" name="confirm_password" required><br>
        <input type="submit" value="修改密码">
    </form>
    <a href="index.php">返回管理面板</a>
</body>

</html>