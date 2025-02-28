<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'zs_5sbdiabidww_cn');
define('DB_PASS', '5sbdiabidww');
define('DB_NAME', 'zs_5sbdiabidww_cn');
// 腾讯 OCR 配置
// config.php
define('TENCENT_OCR_SECRETID', '5sbdiabidww');
define('TENCENT_OCR_SECRETKEY', '5sbdiabidww');


define('RAINBOW_PAY_ID', '5sbdiabidww');
define('RAINBOW_PAY_KEY', '5sbdiabidww');
define('RAINBOW_PAY_PRIVATE_KEY', '5sbdiabidww');

// 上传扣费金额
// 上传扣费金额
define('UPLOAD_FEE', 0.5);




// 过期文件清理时间（单位：天）
define('FILE_EXPIRATION_DAYS', 7);

define('CACHE_EXPIRE_TIME', 3600); // 缓存有效期为 1 小时

// SMTP 服务器配置
$config = [
    'smtp_host' => 'smtp.qq.com', // 替换为你的 SMTP 服务器地址
    'smtp_port' => 465, // SMTP 端口号
    'smtp_username' => 'etdsw@qq.com', // 替换为你的邮箱地址
    'smtp_password' => '5sbdiabidww', // 替换为你的邮箱密码
    'smtp_secure' => 'ssl', // 使用 SSL 加密
    'from_email' => 'et5sbdiabidwwcom', // 发件人邮箱地址
    'from_name' => '小蜜蜂电话助手', // 发件人名称
    'site_url' => 'http://zs.5sbdiabidww.cn'
];
?>