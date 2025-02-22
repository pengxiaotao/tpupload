<?php
session_start();
// 使用绝对路径引入文件
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/config.php';

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 获取用户 ID
$user_id = $_SESSION['user_id'];

// 获取用户当前余额
$stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

// 初始化 $balance 变量，避免未定义错误
$balance = 0; 

if ($result && $user = $result->fetch_assoc()) {
    $balance = $user['balance'];
} else {
    // 处理查询失败的情况，例如记录日志或给出错误提示
    // log_message("获取用户余额失败");
    // $error = "获取用户余额失败，请稍后重试。";
}

// 获取订单号（假设通过 GET 参数传递）
$order_id = isset($_GET['out_trade_no']) ? $_GET['out_trade_no'] : '';
?>
<!DOCTYPE html>
<html lang="zh-CN">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>充值成功</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
        }

        h1 {
            color: #007BFF;
        }

        p {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .button {
            display: inline-block;
            background-color: #007BFF;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin: 10px;
        }

        .button:hover {
            background-color: #0056b3;
        }
    </style>
</head>

<body>
    <h1>充值成功！</h1>
    <?php if ($order_id): ?>
        <p>您的订单号为：
            <?php echo $order_id; ?>
        </p>
    <?php endif; ?>
    <p>当前余额：
        <?php echo $balance; ?> 元
    </p>
    <a href="/public/index.php" class="button">返回首页</a>
    <a href="/public/user_profile.php" class="button">查看个人信息</a>
</body>

</html>