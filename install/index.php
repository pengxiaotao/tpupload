<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = $_POST['db_host'];
    $db_user = $_POST['db_user'];
    $db_pass = $_POST['db_pass'];
    $db_name = $_POST['db_name'];

    $conn = new mysqli($db_host, $db_user, $db_pass);
    if ($conn->connect_error) {
        die("数据库连接失败: " . $conn->connect_error);
    }

    $sql = "CREATE DATABASE IF NOT EXISTS $db_name";
    if ($conn->query($sql) === TRUE) {
        $conn->select_db($db_name);

        $tables = [
            "CREATE TABLE IF NOT EXISTS users (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(255) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                balance DECIMAL(10, 2) DEFAULT 0.00,
                token VARCHAR(255)
            )",
            "CREATE TABLE IF NOT EXISTS upload_logs (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                user_id INT(11) NOT NULL,
                file_name VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )",
            "CREATE TABLE IF NOT EXISTS recharge_logs (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                user_id INT(11) NOT NULL,
                amount DECIMAL(10, 2) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
        ];

        foreach ($tables as $table) {
            $conn->query($table);
        }

        $config_content = "<?php
define('DB_HOST', '$db_host');
define('DB_USER', '$db_user');
define('DB_PASS', '$db_pass');
define('DB_NAME', '$db_name');
define('TENCENT_OCR_APPID', 'your_appid');
define('TENCENT_OCR_SECRETID', 'your_secretid');
define('TENCENT_OCR_SECRETKEY', 'your_secretkey');
define('RAINBOW_PAY_ID', 'your_rainbow_pay_id');
define('RAINBOW_PAY_KEY', 'your_rainbow_pay_key');
?>";

        file_put_contents('../includes/config.php', $config_content);
        header('Location: ../public/index.php');
    } else {
        echo "数据库创建失败: " . $conn->error;
    }
    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>安装系统</title>
</head>
<body>
    <h1>安装系统</h1>
    <form method="post">
        <label for="db_host">数据库主机:</label>
        <input type="text" id="db_host" name="db_host" required><br>
        <label for="db_user">数据库用户名:</label>
        <input type="text" id="db_user" name="db_user" required><br>
        <label for="db_pass">数据库密码:</label>
        <input type="password" id="db_pass" name="db_pass" required><br>
        <label for="db_name">数据库名:</label>
        <input type="text" id="db_name" name="db_name" required><br>
        <input type="submit" value="安装">
    </form>
</body>
</html>