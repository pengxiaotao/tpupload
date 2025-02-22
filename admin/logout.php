<?php
session_start();

// 销毁管理员会话
if (isset($_SESSION['admin_id'])) {
    unset($_SESSION['admin_id']);
}
session_destroy();

// 重定向到管理员登录页面
header('Location: login.php');
exit;