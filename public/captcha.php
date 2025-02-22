<?php
session_start();

// 设置图片的宽度和高度
$width = 120;
$height = 40;

// 创建一个新的图像资源
$image = imagecreatetruecolor($width, $height);

// 设置背景颜色
$bgColor = imagecolorallocate($image, 255, 255, 255);
imagefill($image, 0, 0, $bgColor);

// 生成随机验证码
$characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
$captcha = '';
for ($i = 0; $i < 6; $i++) {
    $captcha .= $characters[rand(0, strlen($characters) - 1)];
}

// 将验证码存储到会话中
$_SESSION['captcha'] = $captcha;

// 设置文本颜色
$textColor = imagecolorallocate($image, 0, 0, 0);

// 在图像上绘制验证码
for ($i = 0; $i < strlen($captcha); $i++) {
    $x = 15 + $i * 15;
    $y = rand(20, 30);
    imagettftext($image, 20, rand(-30, 30), $x, $y, $textColor, '../fonts/arial.ttf', $captcha[$i]);
}

// 添加干扰线
for ($i = 0; $i < 5; $i++) {
    $lineColor = imagecolorallocate($image, rand(0, 255), rand(0, 255), rand(0, 255));
    imageline($image, rand(0, $width), rand(0, $height), rand(0, $width), rand(0, $height), $lineColor);
}

// 设置响应头
header('Content-type: image/png');

// 输出图像
imagepng($image);

// 销毁图像资源
imagedestroy($image);
?>