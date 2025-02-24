<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/ocr.php';

// 设置响应头为 JSON 格式
header('Content-Type: application/json');

// 检查请求方法是否为 POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['error' => '只支持 POST 请求']);
    exit;
}

// 检查是否提供了 token
if (!isset($_POST['token'])) {
    echo json_encode(['error' => '缺少 token 参数']);
    exit;
}

$token = $_POST['token'];

// 验证 token
$stmt = $conn->prepare("SELECT id, balance FROM users WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo json_encode(['error' => '无效的 token']);
    exit;
}

$user_id = $user['id'];
$balance = $user['balance'];

// 检查余额
if ($balance < 0.5) {
    echo json_encode(['error' => '余额不足，请先充值']);
    exit;
}

// 扣费
$new_balance = $balance - 0.5;
$stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
$stmt->bind_param("di", $new_balance, $user_id);
$stmt->execute();

// 处理文件上传
if (!isset($_FILES['image'])) {
    echo json_encode(['error' => '未上传图片']);
    exit;
}

$file = $_FILES['image'];
$file_name = $file['name'];
$file_tmp = $file['tmp_name'];

// 保存文件
$upload_dir = '../uploads/';
$upload_path = $upload_dir . uniqid() . '_' . $file_name;
if (!move_uploaded_file($file_tmp, $upload_path)) {
    echo json_encode(['error' => '文件上传失败']);
    exit;
}

// 记录上传日志
$stmt = $conn->prepare("INSERT INTO upload_logs (user_id, file_name) VALUES (?, ?)");
$stmt->bind_param("is", $user_id, $file_name);
$stmt->execute();

// 插入上传记录到 uploaded_images 表
$insert_stmt = $conn->prepare("INSERT INTO uploaded_images (user_id, image_path) VALUES (?, ?)");
$insert_stmt->bind_param("is", $user_id, $upload_path);
$insert_stmt->execute();

// 调用 OCR
$text = tencent_ocr($upload_path);
if ($text) {
    echo json_encode(['result' => $text]);
} else {
    echo json_encode(['error' => 'OCR 识别失败']);
}
?>