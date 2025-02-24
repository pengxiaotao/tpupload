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
    // 获取 POST 请求中的用户名和密码
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 准备 SQL 查询，从 users 表中查找用户
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // 如果找到一个用户
    if ($result->num_rows === 1) {
        $row = $result->fetch_assoc();
        // 验证密码
        if (password_verify($password, $row['password'])) {
            // 登录成功，生成一个随机的 token
            $token = bin2hex(random_bytes(32));

            // 更新用户的 token 到数据库
            $update_stmt = $conn->prepare("UPDATE users SET token = ? WHERE id = ?");
            $update_stmt->bind_param("si", $token, $row['id']);
            if ($update_stmt->execute()) {
                // 设置会话变量
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['token'] = $token;

                // 构建成功响应数据
                $response['status'] = 'success';
                $response['message'] = '登录成功';
                $response['token'] = $token;
            } else {
                // 数据库更新失败，设置错误信息
                $error = 'Token 更新失败，请稍后再试';
                $response['status'] = 'error';
                $response['message'] = $error;
            }
        } else {
            // 密码验证失败，设置错误信息
            $error = '用户名或密码错误';
            $response['status'] = 'error';
            $response['message'] = $error;
        }
    } else {
        // 未找到用户，设置错误信息
        $error = '用户名或密码错误';
        $response['status'] = 'error';
        $response['message'] = $error;
    }
    // 关闭数据库查询语句
    $stmt->close();
} else {
    // 请求方法不是 POST，设置错误信息
    $error = '不支持的请求方法';
    $response['status'] = 'error';
    $response['message'] = $error;
}

// 输出 JSON 格式的响应数据
echo json_encode($response);
?>