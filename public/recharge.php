<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/config.php';
require_once '../includes/functions.php';

// 检查用户是否已登录
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// 获取所有套餐信息
$stmt = $conn->prepare("SELECT * FROM packages");
$stmt->execute();
$result = $stmt->get_result();
$packages = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id'];
    $package_id = $_POST['package_id'];

    // 获取套餐信息
    $stmt = $conn->prepare("SELECT price, bonus_amount FROM packages WHERE id = ?");
    $stmt->bind_param("i", $package_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $package = $result->fetch_assoc();
    $amount = $package['price'];
    $bonus_amount = $package['bonus_amount'];

    // 生成唯一的订单号
    $order_id = uniqid();
    // 记录充值订单到数据库
    $stmt = $conn->prepare("INSERT INTO recharge_logs (user_id, amount, order_id, is_paid) VALUES (?, ?, ?, 0)");
    $stmt->bind_param("ids", $user_id, $amount, $order_id);
    if (!$stmt->execute()) {
        log_message("订单记录失败，订单号: $order_id，错误信息: " . $stmt->error);
        $error = "订单记录失败，请稍后重试。";
    }
    $stmt->close();

    if (!isset($error)) {
        // 彩虹易支付配置
        $pid = RAINBOW_PAY_ID;
        $pay_key = RAINBOW_PAY_KEY;
        $notify_url = $config['site_url'] . '/notify.php'; // 请替换为实际的异步通知地址
        $return_url = $config['site_url'] . '/recharge_success.php'; // 请替换为实际的跳转通知地址
        $payment_type = 'alipay'; // 可根据需求修改支付方式，如 wechat 等
        $product_name = '电话助手会员充值'; // 商品名称

        // 构造签名所需的参数数组
        $sign_params = [
            'pid' => $pid,
            'type' => $payment_type,
            'out_trade_no' => $order_id,
            'notify_url' => $notify_url,
            'return_url' => $return_url,
            'name' => $product_name,
           'money' => $amount
        ];

        // 按照参数名排序
        ksort($sign_params);

        // 拼接参数
        $sign_str = '';
        foreach ($sign_params as $key => $value) {
            $sign_str .= $key . '=' . $value . '&';
        }
        $sign_str = rtrim($sign_str, '&');
        $sign_str .= $pay_key;

        // 生成签名
        $sign = md5($sign_str);

        // 构造支付请求参数
        $pay_params = [
            'pid' => $pid,
            'type' => $payment_type,
            'out_trade_no' => $order_id,
            'notify_url' => $notify_url,
            'return_url' => $return_url,
            'name' => $product_name,
           'money' => $amount,
           'sign' => $sign,
           'sign_type' => 'MD5'
        ];

        // 使用 POST 方式提交表单到支付接口
        $form = '<form id="payForm" action="https://pay.uomg.cn/submit.php" method="post">';
        foreach ($pay_params as $key => $value) {
            $form .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';
        }
        $form .= '<input type="submit" value="立即支付" style="display:none;"></form>';
        $form .= '<script>document.getElementById("payForm").submit();</script>';

        echo $form;
        // 更新用户余额，包括赠送金额
        $stmt = $conn->prepare("UPDATE users SET balance = balance + ? + ? WHERE id = ?");
        $stmt->bind_param("ddi", $amount, $bonus_amount, $user_id);
        $stmt->execute();
        $stmt->close();

        return;
    }
}
?>
<!DOCTYPE html>
<html>

<head>
    <title>用户充值</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            margin-top: 50px;
        }

        form {
            margin-top: 20px;
        }

        select {
            padding: 8px;
            width: 200px;
            margin-bottom: 10px;
        }

        input[type="submit"] {
            padding: 8px 20px;
            background-color: #007BFF;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #0056b3;
        }

       .error {
            color: red;
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <h1>用户充值</h1>
    <?php if (isset($error)): ?>
        <p class="error">
            <?php echo $error; ?>
        </p>
    <?php endif; ?>
    <form method="post">
        <label for="package_id">选择套餐:</label><br>
        <select id="package_id" name="package_id" required>
            <?php foreach ($packages as $package): ?>
                <option value="<?php echo $package['id']; ?>"><?php echo $package['name']; ?> - <?php echo $package['price']; ?> 元（赠送 <?php echo $package['bonus_amount']; ?> 元）</option>
            <?php endforeach; ?>
        </select><br>
        <input type="submit" value="提交充值请求">
    </form>
</body>

</html>