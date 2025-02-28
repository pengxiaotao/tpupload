<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/ocr.php';

// 设置响应头为 JSON 格式
header('Content-Type: application/json');

// 初始化响应数组
$response = array(
    'status' => 'error',
    'message' => ''
);

// 检查请求方法是否为 POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = '只支持 POST 请求';
    echo json_encode($response);
    exit;
}

// 检查是否提供了 token
if (!isset($_POST['token'])) {
    $response['message'] = '缺少 token 参数';
    echo json_encode($response);
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
    $response['message'] = '无效的 token';
    echo json_encode($response);
    exit;
}

$user_id = $user['id'];
$balance = $user['balance'];

// 检查余额
if ($balance < 0.5) {
    $response['message'] = '余额不足，请先充值';
    echo json_encode($response);
    exit;
}

// 扣费
$new_balance = $balance - 0.5;
$stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
$stmt->bind_param("di", $new_balance, $user_id);
$stmt->execute();

// 处理文件上传
if (!isset($_FILES['image'])) {
    $response['message'] = '未上传图片';
    echo json_encode($response);
    exit;
}

$file = $_FILES['image'];
$file_name = $file['name'];
$file_tmp = $file['tmp_name'];

// 保存文件
$upload_dir = '../uploads/';
$upload_path = $upload_dir . uniqid() . '_' . $file_name;
if (!move_uploaded_file($file_tmp, $upload_path)) {
    $response['message'] = '文件上传失败';
    echo json_encode($response);
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
    $response['status'] = 'success';
    $response['message'] = '识别成功';
    $response['result'] = $text;
} else {
    $response['message'] = 'OCR 识别失败';
}

// 输出 JSON 响应
echo json_encode($response);
?>