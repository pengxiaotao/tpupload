<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/config.php';
$error = '';
$success = '';
$username = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];

    if (empty($email)) {
        $error = '请输入邮箱地址';
    } else {
        $stmt = $conn->prepare("SELECT id, username, last_reset_request_time FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = '该邮箱未注册，请检查输入';
        } else {
            $user = $result->fetch_assoc();
            $user_id = $user['id'];
            $last_reset_request_time = $user['last_reset_request_time'];

            // 检查时间间隔，这里设定为 5 分钟
            $now = new DateTime();
            if ($last_reset_request_time) {
                $last_time = new DateTime($last_reset_request_time);
                $interval = $now->diff($last_time);
                if ($interval->i < 5) {
                    $error = '你请求过于频繁，请在 ' . (5 - $interval->i) . ' 分钟后再试。';
                }
            }

            if (!$error) {
                if (isset($user['username'])) {
                    $username = $user['username'];
                } else {
                    $username = '用户';
                }

                $token = bin2hex(random_bytes(32));

                // 更新用户表中的重置令牌、过期时间和最后请求时间
                $stmt = $conn->prepare("UPDATE users SET reset_token = ?, reset_token_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR), last_reset_request_time = NOW() WHERE id = ?");
                $stmt->bind_param("si", $token, $user_id);

                if ($stmt->execute()) {
                    if (isset($config['site_url'])) {
                        $reset_link = $config['site_url'] . "/public/reset_password.php?token=$token&user_id=$user_id";
                    } else {
                        $error = '网站域名配置错误，请联系管理员';
                    }

                    $subject = "重置密码";
                    $message = "亲爱的 $username，\n\n你请求重置密码，请点击以下链接进行重置：\n$reset_link\n\n该链接有效期为 1 小时。\n\n如果你没有请求重置密码，请忽略此邮件。";

                    require __DIR__ . '/../vendor/autoload.php';
                    $mail = new PHPMailer\PHPMailer\PHPMailer();

                    $mail->isSMTP();
                    $mail->Host = $config['smtp_host'];
                    $mail->Port = $config['smtp_port'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $config['smtp_username'];
                    $mail->Password = $config['smtp_password'];
                    $mail->SMTPSecure = $config['smtp_secure'];

                    $mail->setFrom($config['from_email'], $config['from_name']);
                    $mail->addAddress($email);
                    $mail->Subject = $subject;
                    $mail->Body = $message;

                    if ($mail->send()) {
                        $success = '重置密码链接已发送到你的邮箱，请查收';
                    } else {
                        $error = '邮件发送失败，请稍后重试。错误信息：' . $mail->ErrorInfo;
                    }
                } else {
                    $error = '重置密码请求失败，请稍后重试';
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
    <title>找回密码</title>
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
    <h1>找回密码</h1>
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
        <label for="email">邮箱：</label><br>
        <input type="email" id="email" name="email" required><br>
        <input type="submit" value="发送重置链接">
    </form>
</body>

</html>