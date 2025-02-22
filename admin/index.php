<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/config.php';

// 检查管理员是否已登录
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
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

?>
<!DOCTYPE html>
<html lang="zh-CN">


<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理面板 - 统计信息</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }

        h1 {
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        a {
            color: #007BFF;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }
    </style>
</head>

<body>
    <h1>管理面板 - 统计信息</h1>
    // 在适当的位置添加以下代码，例如在退出登录链接旁边
<a href="change_password.php">修改密码</a>
    <a href="logout.php">退出登录</a>
    <h2>充值统计</h2>
    <p>充值成功总计金额：<?php echo $total_success_amount; ?> 元</p>

    <h2>用户统计</h2>
    <p>用户总量：<?php echo $total_users; ?></p>

    <h2>图片上传统计</h2>
    <p>用户上传图片总量：<?php echo $total_uploaded_images; ?></p>

    <h2>用户上传图片详情</h2>
    <table>
        <tr>
            <th>用户名</th>
            <th>图片路径</th>
        </tr>
        <?php foreach ($uploaded_image_details as $detail): ?>
            <tr>
                <td><?php echo $detail['username']; ?></td>
                <td><?php echo $detail['image_path']; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</body>

</html>