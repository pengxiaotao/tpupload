<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/config.php';

// 检查管理员是否已登录
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// 处理管理员设置每次扣费金额的表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_fee'])) {
    $upload_fee = floatval($_POST['upload_fee']);
    // 将新的扣费金额保存到配置文件中
    $config_file = '../includes/config.php';
    $config_content = file_get_contents($config_file);

    // 查找并替换 UPLOAD_FEE 的定义
    $pattern = "/define\('UPLOAD_FEE', [\d\.]+(\);)/";
    if (preg_match($pattern, $config_content, $matches)) {
        $old_define = $matches[0];
        $new_define = "define('UPLOAD_FEE', $upload_fee);";
        $config_content = str_replace($old_define, $new_define, $config_content);
        file_put_contents($config_file, $config_content);
    }

    header('Location: index.php');
    exit;
}

// 统计充值成功总计金额
$stmt = $conn->prepare("SELECT SUM(amount) FROM recharge_logs WHERE is_paid = 1");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_row();
$total_success_amount = $row[0] ?? 0;

// 统计用户量
$stmt = $conn->prepare("SELECT COUNT(*) FROM users");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_row();
$total_users = $row[0];

// 统计用户上传图片总量
$stmt = $conn->prepare("SELECT COUNT(*) FROM uploaded_images");
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_row();
$total_uploaded_images = $row[0];

// 获取用户上传图片详情
$stmt = $conn->prepare("SELECT u.username, ui.image_path FROM uploaded_images ui JOIN users u ON ui.user_id = u.id");
$stmt->execute();
$result = $stmt->get_result();
$uploaded_image_details = $result->fetch_all(MYSQLI_ASSOC);

// 获取当前的扣费金额
$upload_fee = UPLOAD_FEE;
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>后台管理</title>
</head>
<body>
    <h1>后台管理</h1>
    <p>充值成功总计金额: <?php echo $total_success_amount; ?> 元</p>
    <p>用户量: <?php echo $total_users; ?></p>
    <p>用户上传图片总量: <?php echo $total_uploaded_images; ?></p>

    <h2>设置每次扣费金额</h2>
    <form method="post">
        <label for="upload_fee">每次扣费金额（元）:</label>
        <input type="number" id="upload_fee" name="upload_fee" step="0.01" value="<?php echo $upload_fee; ?>">
        <button type="submit">保存</button>
    </form>

    <!-- 其他管理功能 -->
</body>
</html>