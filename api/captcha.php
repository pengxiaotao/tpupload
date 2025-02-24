<?php
// 启动会话
session_start();

// 创建验证码图片
$image = imagecreatetruecolor(120, 40);
$bg_color = imagecolorallocate($image, 255, 255, 255);
imagefill($image, 0, 0, $bg_color);

// 生成随机验证码
$captcha = '';
$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
for ($i = 0; $i < 6; $i++) {
    $captcha .= $characters[rand(0, strlen($characters) - 1)];
}

// 将验证码保存到会话中
$_SESSION['captcha'] = $captcha;

// 在图片上绘制验证码
$text_color = imagecolorallocate($image, 0, 0, 0);
imagestring($image, 5, 30, 10, $captcha, $text_color);

// 添加干扰线
for ($i = 0; $i < 5; $i++) {
    $line_color = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
    imageline($image, rand(0, 120), rand(0, 40), rand(0, 120), rand(0, 40), $line_color);
}

// 输出图片
header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);
?>