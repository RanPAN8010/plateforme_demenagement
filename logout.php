<?php
// logout.php
session_start();

// 1. 清除 Session
session_unset();
session_destroy();

// 2. 退出后跳回广告列表页
header("Location: ads-list.php");
exit();
?>