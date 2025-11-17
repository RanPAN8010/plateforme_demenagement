<?php
// --- 数据库连接配置 ---
$host = '127.0.0.1'; // UwAmp 的默认主机 (或 'localhost')
$db   = 'demenagerbdd'; // 你的新数据库名称
$user = 'root'; // UwAmp 的默认 MySQL 用户名
$pass = 'root'; // UwAmp 的默认 MySQL 密码
$charset = 'utf8mb4';
// -------------------------

/* * 重要提示：
 * UwAmp 的默认 MySQL 密码是 'root'。
 * 如果 'root' 失败了，请尝试一个空密码: $pass = '';
*/

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     // $pdo 变量就是你的数据库连接
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     // 如果连接失败，显示错误信息
     throw new \PDOException($e.getMessage(), (int)$e.getCode());
}
?>