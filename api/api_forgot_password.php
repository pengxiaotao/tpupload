<?php
// 开启会话
session_start();

// 引入数据库连接文件和配置文件
require_once '../includes/db.php';
require_once '../includes/config.php';

// 设置响应头为 JSON 格式
header('Content-Type: application/json');

// 初始化错误信息和成功信息
$error = '';
$success = '';

// 检查请求方法是否为 POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取 POST 请求中的邮箱参数
    $email = $_POST['email']?? '';

    // 检查邮箱是否为空
    if (empty($email)) {
        $error = '请输入邮箱地址';
    } else {
        // 准备 SQL 查询语句，查询用户信息，包括 id、username 和 last_reset_request_time
        $stmt = $conn->prepare("SELECT id, username, last_reset_request_time FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // 检查查询结果是否为空
        if ($result->num_rows === 0) {
            $error = '该邮箱未注册，请检查输入';
        } else {
            // 获取用户信息
            $user = $result->fetch_assoc();
            $user_id = $user['id'];
            $last_reset_request_time = $user['last_reset_request_time'];

            // 检查时间间隔，设定为 5 分钟
            $now = new DateTime();
            if ($last_reset_request_time) {
                $last_time = new DateTime($last_reset_request_time);
                $interval = $now->diff($last_time);
                if ($interval->i < 5) {
                    $error = '你请求过于频繁，请在 '. (5 - $interval->i). ' 分钟后再试。';
                }
            }

            // 如果没有错误，继续处理
            if (!$error) {
                // 获取用户名，若不存在则默认为 '用户'
                $username = $user['username']?? '用户';

                // 生成重置令牌
                $token = bin2hex(random_bytes(32));

                // 更新用户表中的重置令牌、过期时间和最后请求时间
                $stmt = $conn->prepare("UPDATE users SET reset_token =?, reset_token_expires = DATE_ADD(NOW(), INTERVAL 1 HOUR), last_reset_request_time = NOW() WHERE id =?");
                $stmt->bind_param("si", $token, $user_id);

                // 执行更新操作
                if ($stmt->execute()) {
                    // 检查配置文件中是否有网站 URL
                    if (isset($config['site_url'])) {
                        $reset_link = $config['site_url']. "/public/reset_password.php?token=$token&user_id=$user_id";
                    } else {
                        $error = '网站域名配置错误，请联系管理员';
                    }

                    // 邮件主题和内容
                    $subject = "重置密码";
                    $message = "亲爱的用户，\n\n你请求重置密码，请点击以下链接进行重置：\n$reset_link\n\n该链接有效期为 1 小时。\n\n如果你没有请求重置密码，请忽略此邮件。";

                    // 引入 PHPMailer 库
                    require __DIR__. '/../vendor/autoload.php';
                    $mail = new PHPMailer\PHPMailer\PHPMailer();

                    // 配置 SMTP 服务器
                    $mail->isSMTP();
                    $mail->Host = $config['smtp_host'];
                    $mail->Port = $config['smtp_port'];
                    $mail->SMTPAuth = true;
                    $mail->Username = $config['smtp_username'];
                    $mail->Password = $config['smtp_password'];
                    $mail->SMTPSecure = $config['smtp_secure'];

                    // 设置发件人和收件人
                    $mail->setFrom($config['from_email'], $config['from_name']);
                    $mail->addAddress($email);
                    $mail->Subject = $subject;
                    $mail->Body = $message;

                    // 发送邮件
                    if ($mail->send()) {
                        $success = '重置密码链接已发送到你的邮箱，请查收';
                    } else {
                        $error = '邮件发送失败，请稍后重试。错误信息：'. $mail->ErrorInfo;
                    }
                } else {
                    $error = '重置密码请求失败，请稍后重试';
                }
            }
        }
    }
} else {
    // 如果请求方法不是 POST，返回错误信息
    $error = '不支持的请求方法，仅支持 POST 请求';
}

// 准备响应数据
$response = [];
if ($error) {
    $response['status'] = 'error';
    $response['message'] = $error;
} else {
    $response['status'] = 'success';
    $response['message'] = $success;
}

// 输出 JSON 格式的响应
echo json_encode($response);
?>