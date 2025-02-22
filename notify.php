<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';

require_once  $_SERVER['DOCUMENT_ROOT'] .  '/includes/config.php';
require_once  $_SERVER['DOCUMENT_ROOT'] . '/includes/functions.php';

// 签名验证函数
function verify_sign($params, $pay_key) {
    $received_sign = $params['sign'];
    unset($params['sign']);
    unset($params['sign_type']);
    // 过滤掉空值参数
    $filtered_params = array_filter($params, function ($value) {
        return $value!== '';
    });
    // 按键名排序
    ksort($filtered_params);
    // 拼接参数
    $sign_str = '';
    foreach ($filtered_params as $key => $value) {
        $sign_str .= $key . '=' . $value . '&';
    }
    $sign_str = rtrim($sign_str, '&');
    $sign_str .= $pay_key;
    // 生成签名
    $check_sign = md5($sign_str);
    return $received_sign === $check_sign;
}

// 获取回调参数
$params = $_GET;
$pay_key = RAINBOW_PAY_KEY;

// 计算得出通知验证结果
$verify_result = verify_sign($params, $pay_key);

if ($verify_result) { // 验证成功
    // 商户订单号
    $out_trade_no = $params['out_trade_no'];

    // 彩虹易支付交易号
    $trade_no = $params['trade_no'];

    // 交易状态
    $trade_status = $params['trade_status'];

    // 支付方式
    $type = $params['type'];

    // 支付金额
    $money = $params['money'];

    if ($trade_status == 'TRADE_SUCCESS') {
        // 判断该笔订单是否在商户网站中已经做过处理
        // 这里简单通过查询数据库判断订单是否处理过，你可以根据实际情况优化
        $stmt = $conn->prepare("SELECT id FROM recharge_logs WHERE order_id = ? AND is_paid = 1");
        $stmt->bind_param("s", $out_trade_no);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows === 0) {
            // 如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
            $stmt = $conn->prepare("SELECT user_id FROM recharge_logs WHERE order_id = ?");
            $stmt->bind_param("s", $out_trade_no);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                $user_id = $row['user_id'];

                // 获取用户当前余额
                $stmt = $conn->prepare("SELECT balance FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $balance = $row['balance'];

                // 更新用户余额
                $new_balance = $balance + $money;
                $stmt = $conn->prepare("UPDATE users SET balance = ? WHERE id = ?");
                $stmt->bind_param("di", $new_balance, $user_id);
                if (!$stmt->execute()) {
                    // 记录错误日志
                    log_message("更新用户余额失败，订单号: $out_trade_no，错误信息: " . $stmt->error);
                    echo "fail";
                    return;
                }

                // 更新订单状态为已支付
                $stmt = $conn->prepare("UPDATE recharge_logs SET is_paid = 1 WHERE order_id = ?");
                $stmt->bind_param("s", $out_trade_no);
                if (!$stmt->execute()) {
                    // 记录错误日志
                    log_message("更新订单状态失败，订单号: $out_trade_no，错误信息: " . $stmt->error);
                    echo "fail";
                    return;
                }
            }
        }
    }

    // 验证成功返回
    echo "success";
} else {
    // 验证失败
    log_message("彩虹易支付异步通知签名验证失败，订单号: $out_trade_no");
    echo "fail";
}