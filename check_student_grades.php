<?php
require_once "sqlconnection.php";
if (isset($_POST['student_id'])) {
    $student_id = intval($_POST['student_id']);
    $stmt = $conn->prepare("SELECT COUNT(*) FROM jegyek WHERE diak_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    echo $stmt->get_result()->fetch_row()[0] > 0 ? "has_grades" : "no_grades";
}
?>