<?php
require_once 'db.php';

function generate_token($length = 32) {
    return bin2hex(random_bytes($length));
}

function log_message($message) {
    $log_file = '../logs/system.log';
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

function generate_captcha() {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    $captcha = '';
    for ($i = 0; $i < 6; $i++) {
        $captcha .= $characters[rand(0, strlen($characters) - 1)];
    }
    $_SESSION['captcha'] = $captcha;

    $image = imagecreatetruecolor(120, 40);
    $bg_color = imagecolorallocate($image, 255, 255, 255);
    $text_color = imagecolorallocate($image, 0, 0, 0);
    imagefill($image, 0, 0, $bg_color);
    imagettftext($image, 20, 0, 20, 30, $text_color, '../public/fonts/arial.ttf', $captcha);

    header('Content-type: image/png');
    imagepng($image);
    imagedestroy($image);
    
}

// ... 其他代码 ...

if (!function_exists('generate_sign')) {
    function generate_sign($params, $private_key) {
        unset($params['sign']);
        unset($params['sign_type']);
        $filtered_params = array_filter($params, function ($value) {
            return is_scalar($value) && !empty($value);
        });
        ksort($filtered_params);
        $sign_string = http_build_query($filtered_params);
        openssl_sign($sign_string, $signature, $private_key, OPENSSL_ALGO_SHA256);
        $sign = base64_encode($signature);
        return $sign;
    }
}

if (!function_exists('verify_sign')) {
    function verify_sign($params, $public_key) {
        $received_sign = $params['sign'];
        unset($params['sign']);
        unset($params['sign_type']);
        $filtered_params = array_filter($params, function ($value) {
            return is_scalar($value) && !empty($value);
        });
        ksort($filtered_params);
        $sign_string = http_build_query($filtered_params);
        $decoded_sign = base64_decode($received_sign);
        $result = openssl_verify($sign_string, $decoded_sign, $public_key, OPENSSL_ALGO_SHA256);
        return $result === 1;
    }
}

// ... 其他代码 ...