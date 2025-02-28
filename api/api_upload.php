<?php
session_start();
require_once '../includes/db.php';

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => '用户未登录']);
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['image'])) {
        $file = $_FILES['image'];
        $file_name = $file['name'];
        $file_tmp = $file['tmp_name'];
        $file_error = $file['error'];

        if ($file_error === 0) {
            // 检查余额
            $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $balance = $row['balance'];

                if ($balance < UPLOAD_FEE) {
                header('HTTP/1.1 402 Payment Required');
                echo json_encode(['error' => '余额不足，请先充值']);
                exit();
            } else {
                // 扣费
                $new_balance = $balance - UPLOAD_FEE;
                $stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
                $stmt->bind_param("d", $new_balance, $user_id);
                $stmt->execute();

                // 保存文件
                $upload_dir = '../uploads/';
                $upload_path = $upload_dir . uniqid() . '_' . $file_name;
                move_uploaded_file($file_tmp, $upload_path);

                // 记录上传日志
                $stmt = $conn->prepare("INSERT INTO upload_logs (user_id, file_name) VALUES (?, ?)");
                $stmt->bind_param("is", $user_id, $file_name);
                $stmt->execute();

                // 调用 OCR
                $text = tencent_ocr($upload_path);
                if ($text) {
                    header('HTTP/1.1 200 OK');
                    echo json_encode(['result' => $text]);
                } else {
                    header('HTTP/1.1 500 Internal Server Error');
                    echo json_encode(['error' => 'OCR 识别失败']);
                }
            }
        } else {
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['error' => '文件上传失败']);
        }
    } else {
        header('HTTP/1.1 400 Bad Request');
        echo json_encode(['error' => '未找到上传的图片']);
    }
} else {
    header('HTTP/1.1 405 Method Not Allowed');
    echo json_encode(['error' => '不支持的请求方法']);
}
?>