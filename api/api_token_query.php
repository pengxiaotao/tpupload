<?php
// 启动会话
session_start();
// 引入数据库连接文件
require_once '../includes/db.php';
// 引入配置文件
require_once '../includes/config.php';

// 设置响应头为 JSON 格式
header('Content-Type: application/json');

// 初始化响应数组
$response = [
    'status' => 'error',
    'message' => '无效的请求',
    'data' => []
];

// 检查是否有 token 参数
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // 根据 token 查询用户信息
    $stmt = $conn->prepare("SELECT id, username, email, balance FROM users WHERE token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // 获取用户信息
        $user = $result->fetch_assoc();

        // 获取每次扣费金额
        $upload_fee = floatval(UPLOAD_FEE);

        // 检查 balance 是否为有效的数值
        if (isset($user['balance']) && is_numeric($user['balance'])) {
            $balance = floatval($user['balance']);

            // 计算还能使用的次数
            if ($upload_fee > 0) {
                $remaining_uses = floor($balance / $upload_fee);
            } else {
                $remaining_uses = 0; // 避免除零错误
            }
        } else {
            $remaining_uses = 0; // 如果 balance 无效，默认剩余次数为 0
        }

        // 构建响应数据
        $response['status'] = 'success';
        $response['message'] = '用户信息获取成功';
        $response['data'] = [
            'username' => $user['username'],
            'email' => $user['email'],
            'balance' => $user['balance'],
            'remaining_uses' => $remaining_uses
        ];
    } else {
        $response['message'] = '无效的 token';
    }
}

// 输出 JSON 格式的响应
echo json_encode($response);
?>