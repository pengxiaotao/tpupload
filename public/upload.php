<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/ocr.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $user_id = $_SESSION['user_id'];
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
            $error = '余额不足，请先充值';
        } else {
            // 扣费
            $new_balance = $balance - UPLOAD_FEE;
            $stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
            $stmt->bind_param("di", $new_balance, $user_id);
            $stmt->execute();

            // 保存文件
            $upload_dir = '../uploads/';
            $upload_path = $upload_dir . uniqid() . '_' . $file_name;
            move_uploaded_file($file_tmp, $upload_path);

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
                $success = '识别结果: ' . $text;
            } else {
                $error = 'OCR 识别失败';
            }
    
        }
    } else {
        $error = '文件上传失败';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>图片上传</title>
</head>
<body>
    <h1>图片上传</h1>
    <?php if (isset($error)): ?>
        <p style="color: red;"><?php echo $error; ?></p>
    <?php endif; ?>
    <?php if (isset($success)): ?>
        <p style="color: green;"><?php echo $success; ?></p>
    <?php endif; ?>
    <form method="post" enctype="multipart/form-data">
        <label for="image">选择图片:</label>
        <input type="file" id="image" name="image" required><br>
        <input type="submit" value="上传">
    </form>
</body>
</html>