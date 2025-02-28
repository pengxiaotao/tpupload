<?php
session_start();
require_once '../includes/db.php';

// 设置响应头为 JSON 格式///查询使用次数
header('Content-Type: application/json');

// 检查请求方法是否为 GET（假设查询使用 GET 请求）
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['error' => '只支持 GET 请求']);
    exit;
}

// 检查是否提供了 token
if (!isset($_GET['token'])) {
    echo json_encode(['error' => '缺少 token 参数']);
    exit;
}

$token = $_GET['token'];

// 验证 token
$stmt = $conn->prepare("SELECT id, username, email FROM users WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(['error' => '无效的 token']);
    exit;
}

// 构建成功响应数据
$response = [
    'status' => 'success',
    'user' => $user
];

echo json_encode($response);
?>