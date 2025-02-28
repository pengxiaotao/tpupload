<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/config.php';

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 获取用户 ID
$user_id = $_SESSION['user_id'];
// 获取用户信息，包括 token
$stmt = $conn->prepare("SELECT username, email, balance, token FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// 获取用户充值记录
$recharge_stmt = $conn->prepare("SELECT order_id, amount, created_at, is_paid FROM recharge_logs WHERE user_id = ? ORDER BY created_at DESC");
$recharge_stmt->bind_param("i", $user_id);
$recharge_stmt->execute();
$recharge_result = $recharge_stmt->get_result();
$recharge_records = $recharge_result->fetch_all(MYSQLI_ASSOC);

// 计算用户还能使用的次数
$upload_fee = UPLOAD_FEE;
$remaining_uses = floor($user['balance'] / $upload_fee);
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>用户个人信息</title>
</head>
<body>
    <h1>用户个人信息</h1>
    <p>用户名: <?php echo $user['username']; ?></p>
    <p>邮箱: <?php echo $user['email']; ?></p>
    <p>余额: <?php echo $user['balance']; ?> 元</p>
    <p>还能使用的次数: <?php echo $remaining_uses; ?> 次</p>

    <?php if (count($recharge_records) > 0): ?>
        <table>
            <tr>
                <th>订单号</th>
                <th>充值金额</th>
                <th>充值时间</th>
                <th>状态</th>
            </tr>
            <?php foreach ($recharge_records as $record): ?>
                <tr>
                    <td><?php echo $record['order_id']; ?></td>
                    <td><?php echo $record['amount']; ?> 元</td>
                    <td><?php echo $record['created_at']; ?></td>
                    <td>
                        <?php if ($record['is_paid'] == 1): ?>
                            已支付
                        <?php else: ?>
                            未支付
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>暂无充值记录。</p>
    <?php endif; ?>
    <a href="reset_token.php">重置 Token</a>
    <p><a href="change_password.php">修改密码</a></p>
    <p><a href="logout.php">退出登录</a></p>
</body>
</html>