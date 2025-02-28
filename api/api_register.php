<?php
// 启动会话
session_start();
// 引入数据库连接文件
require_once '../includes/db.php';
// 引入配置文件
require_once '../includes/config.php';

// 设置响应头为 JSON 格式
header('Content-Type: application/json');

// 初始化错误信息和响应数据
$error = '';
$response = array();

// 检查请求方法是否为 POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 获取 POST 请求中的用户名、邮箱、密码、确认密码
    $username = $_POST['username']?? '';
    $email = $_POST['email']?? '';
    $password = $_POST['password']?? '';
    $confirm_password = $_POST['confirm_password']?? '';

    // 检查 captcha 是否存在于 POST 数据中
    $captcha = $_POST['captcha']?? '';

    // 验证验证码
    if (empty($captcha) || strtolower(trim($captcha)) != strtolower(trim($_SESSION['captcha']?? ''))) {
        $error = '验证码输入错误，请重新输入。';
        $response['status'] = 'error';
        $response['message'] = $error;
    } else {
        // 验证新密码和确认密码是否一致
        if ($password !== $confirm_password) {
            $error = '新密码和确认密码不一致，请重新输入。';
            $response['status'] = 'error';
            $response['message'] = $error;
        } else {
            // 检查用户名是否已存在
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error = '该用户名已被使用，请选择其他用户名。';
                $response['status'] = 'error';
                $response['message'] = $error;
            } else {
                // 检查邮箱是否已存在（可选步骤，根据需求决定是否添加）
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    $error = '该邮箱已被注册，请使用其他邮箱。';
                    $response['status'] = 'error';
                    $response['message'] = $error;
                } else {
                    // 对密码进行哈希处理
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                    // 生成一个随机的 token
                    $token = bin2hex(random_bytes(32));

                    // 插入新用户记录
                    $stmt = $conn->prepare("INSERT INTO users (username, email, password, token) VALUES (?, ?, ?, ?)");
                    $stmt->bind_param("ssss", $username, $email, $hashed_password, $token);
                    if ($stmt->execute()) {
                        // 获取新用户的 ID
                        $user_id = $conn->insert_id;

                        // 设置会话变量
                        $_SESSION['user_id'] = $user_id;
                        $_SESSION['token'] = $token;

                        // 构建成功响应数据
                        $response['status'] = 'success';
                        $response['message'] = '注册成功！请使用新账号登录。';
                        $response['token'] = $token;
                    } else {
                        // 注册失败，设置错误信息
                        $error = '注册失败，请稍后重试。错误信息：' . $stmt->error;
                        $response['status'] = 'error';
                        $response['message'] = $error;
                    }
                }
            }
        }
    }
} else {
    // 请求方法不是 POST，设置错误信息
    $error = '不支持的请求方法';
    $response['status'] = 'error';
    $response['message'] = $error;
}

// 输出 JSON 格式的响应数据
echo json_encode($response);
?>