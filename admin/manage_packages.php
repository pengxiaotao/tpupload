<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/config.php';

// 检查管理员是否已登录
if (!isset($_SESSION['admin_id'])) {
    header('Location: ../admin/login.php');
    exit;
}

// 处理套餐添加表单提交
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $price = isset($_POST['price']) ? floatval($_POST['price']) : 0.00;
    $bonus_amount = isset($_POST['bonus_amount']) ? floatval($_POST['bonus_amount']) : 0.00; // 修改这里，确保键存在
    $description = $_POST['description'] ?? '';

    $stmt = $conn->prepare("INSERT INTO packages (name, price, bonus_amount, description) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sdds", $name, $price, $bonus_amount, $description);
    $stmt->execute();
    $stmt->close();
}

// 获取所有套餐信息
$stmt = $conn->prepare("SELECT * FROM packages");
$stmt->execute();
$result = $stmt->get_result();
$packages = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <title>套餐管理</title>
</head>
<body>
    <h1>套餐管理</h1>
    <form method="post">
        <label for="name">套餐名称：</label>
        <input type="text" id="name" name="name" required><br>
        <label for="price">套餐价格：</label>
        <input type="number" id="price" name="price" step="0.01" required><br>
        <label for="bonus_amount">充值赠送金额：</label>
        <input type="number" id="bonus_amount" name="bonus_amount" step="0.01" value="0.00"><br>
        <label for="description">套餐描述：</label>
        <textarea id="description" name="description"></textarea><br>
        <input type="submit" value="添加套餐">
    </form>

    <h2>已有套餐</h2>
    <?php if (count($packages) > 0): ?>
        <table>
            <tr>
                <th>套餐名称</th>
                <th>套餐价格</th>
                <th>充值赠送金额</th>
                <th>套餐描述</th>
            </tr>
            <?php foreach ($packages as $package): ?>
                <tr>
                    <td><?php echo $package['name']; ?></td>
                    <td><?php echo $package['price']; ?> 元</td>
                    <td><?php echo $package['bonus_amount']; ?> 元</td>
                    <td><?php echo $package['description']; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
    <?php else: ?>
        <p>暂无套餐信息。</p>
    <?php endif; ?>
</body>
</html>