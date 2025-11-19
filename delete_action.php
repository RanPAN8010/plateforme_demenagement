<?php
// delete_action.php - Script de traitement de la suppression
session_start();

// 1. 最小权限检查 (请在正式环境替换为严格的管理员角色检查)
if (empty($_SESSION['user_id'])) {
    die("Accès refusé. Vous devez être connecté pour effectuer cette action.");
}

// Connexion BDD
include 'connexion.inc.php'; 

// 2. 验证输入
$module = $_GET['module'] ?? null;
$id = $_GET['id'] ?? null;

// 检查 module 和 ID 是否有效
if (!$module || !is_numeric($id) || $id <= 0) {
    header("Location: admin_dashboard.php");
    exit();
}

// 3. 确定目标表和 ID 列
$target_table = '';
$id_column = '';

switch ($module) {
    case 'ads':
        $target_table = 'annonce';
        $id_column = 'id_annonce';
        break;
    case 'users':
        $target_table = 'utilisateur';
        $id_column = 'id_utilisateur';
        break;
    case 'orders':
        $target_table = 'case'; // 您的订单表名
        $id_column = 'id_case';
        break;
    default:
        header("Location: admin_dashboard.php");
        exit();
}

try {
    $pdo->beginTransaction();

    // 禁用外键检查 (危险的临时方案，但能解决删除时的 FK 约束问题)
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0"); 
    
    // 4. 执行删除请求
    $sql_delete = "DELETE FROM `$target_table` WHERE `$id_column` = ?";
    $stmt = $pdo->prepare($sql_delete);
    $stmt->execute([$id]);

    // 重新启用外键检查
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1"); 

    $pdo->commit();

    // 5. 成功后跳转回原模块页面
    header("Location: admin_dashboard.php?module=" . htmlspecialchars($module) . "&delete_success=true");
    exit();

} catch (\PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // 提示错误信息
    $error_message = "Erreur de suppression. La cause la plus probable est qu'un enregistrement essentiel dépend de cet élément.";
    die($error_message . "<br><a href='admin_dashboard.php?module=" . htmlspecialchars($module) . "'>Retour</a>");
}
?>