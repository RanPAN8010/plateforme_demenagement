<?php
// quick_login.php
session_start();

// 1. 设置 Session，假装用户 ID 为 100
$_SESSION['user_id'] = 100;
$_SESSION['user_name'] = "测试用户";

// 2. 登录完成后，立刻跳回广告列表页
header("Location: ads-list.php");
exit();
?>