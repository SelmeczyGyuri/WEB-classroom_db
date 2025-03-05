<?php
require_once "sqlconnection.php";
if (isset($_POST['subject_id'])) {
    $subject_id = intval($_POST['subject_id']);
    $stmt = $conn->prepare("SELECT COUNT(*) FROM jegyek WHERE tantargy_id = ?");
    $stmt->bind_param("i", $subject_id);
    $stmt->execute();
    echo $stmt->get_result()->fetch_row()[0] > 0 ? "has_grades" : "no_grades";
}
?>