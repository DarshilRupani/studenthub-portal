<?php
// Practical 14: record admin activity without storing credentials or session tokens.
function adminAudit($action, $targetType, $targetId, $summary) {
    if (empty($_SESSION['user_id'])) return;
    $actor=(int)$_SESSION['user_id'];
    $targetId=$targetId ? (int)$targetId : null;
    $summary=substr(trim((string)$summary),0,255);
    try {
        $statement=authDb()->prepare('INSERT INTO audit_logs (actor_user_id,action,target_type,target_id,summary) VALUES (?,?,?,?,?)');
        $statement->bind_param('isiss',$actor,$action,$targetType,$targetId,$summary);
        $statement->execute();
    } catch (mysqli_sql_exception $e) {
        // Practicals 10-13 can run before the Practical 14 audit migration.
        if ($e->getCode() !== 1146) throw $e;
        error_log('StudentHub admin audit: apply sql/practical14.sql to enable the general audit log.');
    }
}
