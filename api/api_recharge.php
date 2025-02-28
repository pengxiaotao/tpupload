<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 检查请求方法是否为 POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 检查 Content-Type 是否为 application/x-www-form-urlencoded
    $contentType = isset($_SERVER["CONTENT_TYPE"]) 
       ? strtolower($_SERVER["CONTENT_TYPE"]) 
        : '';
    if (strpos($contentType, 'application/x-www-form-urlencoded') === 0) {
        $token = $_POST['token']?? null;
        $package_id = $_POST['package_id']?? null;

        // 验证 token
        if ($token === null ||!verifyToken($token)) {
            echo json_encode(["error" => "无效的身份验证 token"]);
            exit;
        }

        // 从验证通过的 token 中获取用户 ID
        $stmt = $conn->prepare("SELECT id FROM users WHERE token =?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $userInfo = $result->fetch_assoc();
        if ($userInfo === null) {
            echo json_encode(["error" => "无法获取用户 ID，请检查 token 的有效性"]);
            exit;
        }
        $user_id = $userInfo['id'];

        if ($package_id === null) {
            echo json_encode(["error" => "缺少必要的参数 package_id"]);
            exit;
        }

        // 获取套餐信息
        $stmt = $conn->prepare("SELECT price, bonus_amount FROM packages WHERE id =?");
        $stmt->bind_param("i", $package_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $package = $result->fetch_assoc();
        if ($package === null) {
            echo json_encode(["error" => "无法获取套餐信息，请检查 package_id 的有效性"]);
            exit;
        }
        $amount = $package['price'];
        $bonus_amount = $package['bonus_amount'];

        // 生成唯一的订单号
        $order_id = uniqid();
        // 记录充值订单到数据库
        $stmt = $conn->prepare("INSERT INTO recharge_logs (user_id, amount, order_id, is_paid) VALUES (?,?,?, 0)");
        $stmt->bind_param("ids", $user_id, $amount, $order_id);
        if (!$stmt->execute()) {
            echo json_encode(["error" => "订单记录失败: ". $stmt->error]);
            exit;
        }

        // 更新用户余额，包括赠送金额
        $stmt = $conn->prepare("UPDATE users SET balance = balance +? +? WHERE id =?");
        $stmt->bind_param("ddi", $amount, $bonus_amount, $user_id);
        if (!$stmt->execute()) {
            echo json_encode(["error" => "更新用户余额失败: ". $stmt->error]);
            exit;
        }

        echo json_encode(["message" => "充值请求已提交", "order_id" => $order_id]);
        $stmt->close();
    } else {
        echo json_encode(["error" => "不支持的 Content-Type，仅支持 application/x-www-form-urlencoded"]);
    }
} else {
    echo json_encode(["error" => "仅支持 POST 请求"]);
}

$conn->close();
?>