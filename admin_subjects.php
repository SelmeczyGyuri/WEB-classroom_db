<?php
require_once "sqlconnection.php";
require_once "functions.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_subject'])) {
        $subject_name = trim($_POST['subject_name']);
        if (!empty($subject_name)) {
            $stmt = $conn->prepare("INSERT INTO tantargyak (tantargy) VALUES (?)");
            $stmt->bind_param("s", $subject_name);
            $stmt->execute() ? displayMessage("Tantárgy hozzáadva!", "success") : displayMessage("Hiba: " . $conn->error, "error");
        }
    } elseif (isset($_POST['edit_subject'])) {
        $subject_id = intval($_POST['subject_id']);
        $subject_name = trim($_POST['subject_name']);
        if (!empty($subject_name)) {
            $stmt = $conn->prepare("UPDATE tantargyak SET tantargy = ? WHERE tantargy_id = ?");
            $stmt->bind_param("si", $subject_name, $subject_id);
            $stmt->execute() ? displayMessage("Tantárgy módosítva!", "success") : displayMessage("Hiba: " . $conn->error, "error");
        }
    } elseif (isset($_POST['delete_subject'])) {
        $subject_id = intval($_POST['subject_id']);
        $delete_mode = $_POST['delete_mode'];
        $stmt = $conn->prepare("SELECT COUNT(*) FROM jegyek WHERE tantargy_id = ?");
        $stmt->bind_param("i", $subject_id);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_row()[0];
        if ($count > 0) {
            if ($delete_mode === 'delete_with_grades') {
                $stmt = $conn->prepare("DELETE FROM jegyek WHERE tantargy_id = ?");
                $stmt->bind_param("i", $subject_id);
                $stmt->execute();
            } else {
                displayMessage("Nem törölhető, mert jegyek vannak!", "error");
                header("Refresh: 2; url=admin_subjects.php");
                exit;
            }
        }
        $stmt = $conn->prepare("DELETE FROM tantargyak WHERE tantargy_id = ?");
        $stmt->bind_param("i", $subject_id);
        $stmt->execute() ? displayMessage("Tantárgy törölve!", "success") : displayMessage("Hiba: " . $conn->error, "error");
    }
}

$result = $conn->query("SELECT tantargy_id, tantargy FROM tantargyak ORDER BY tantargy");
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Admin - Tantárgyak</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include "header.php"; ?>
    <main>
        <h1>Tantárgyak kezelése</h1>
        <section>
            <h2>Tantárgyak listája</h2>
            <a href="admin_subjects.php?action=add" class="button">Új tantárgy</a>
            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                <thead>
                    <tr>
                        <th style="border: 1px solid #ccc; padding: 8px;">Tantárgy neve</th>
                        <th style="border: 1px solid #ccc; padding: 8px;">Műveletek</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td style="border: 1px solid #ccc; padding: 8px;"><?php echo htmlspecialchars($row['tantargy']); ?></td>
                            <td style="border: 1px solid #ccc; padding: 8px;">
                                <a href="admin_subjects.php?action=edit&id=<?php echo $row['tantargy_id']; ?>" class="button">Módosít</a>
                                <form action="admin_subjects.php" method="POST" style="display: inline;" onsubmit="return confirmDelete(<?php echo $row['tantargy_id']; ?>);">
                                    <input type="hidden" name="subject_id" value="<?php echo $row['tantargy_id']; ?>">
                                    <input type="hidden" name="delete_mode" id="delete_mode_<?php echo $row['tantargy_id']; ?>" value="check">
                                    <button type="submit" name="delete_subject" class="button delete">Töröl</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>

        <?php if (isset($_GET['action']) && in_array($_GET['action'], ['add', 'edit'])): ?>
            <?php
            $edit_mode = $_GET['action'] === 'edit';
            $subject_id = $edit_mode ? intval($_GET['id']) : 0;
            $subject_name = $edit_mode ? $conn->query("SELECT tantargy FROM tantargyak WHERE tantargy_id = $subject_id")->fetch_assoc()['tantargy'] : '';
            ?>
            <section>
                <h2><?php echo $edit_mode ? 'Tantárgy módosítása' : 'Új tantárgy hozzáadása'; ?></h2>
                <form action="admin_subjects.php" method="POST">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="subject_id" value="<?php echo $subject_id; ?>">
                    <?php endif; ?>
                    <label for="subject_name">Tantárgy neve:</label>
                    <input type="text" id="subject_name" name="subject_name" value="<?php echo htmlspecialchars($subject_name); ?>" maxlength="40" required>
                    <button type="submit" name="<?php echo $edit_mode ? 'edit_subject' : 'add_subject'; ?>" class="button">Mentés</button>
                    <a href="admin_subjects.php" class="button">Mégse</a>
                </form>
            </section>
        <?php endif; ?>
    </main>
    <?php include "footer.php"; ?>
    <script>
        function confirmDelete(subjectId) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "check_grades.php", false);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.send("subject_id=" + subjectId);
            if (xhr.responseText === "has_grades") {
                if (confirm("Ez a tantárgy jegyeket tartalmaz. Törölni akarod a jegyekkel együtt?")) {
                    document.getElementById("delete_mode_" + subjectId).value = "delete_with_grades";
                    return true;
                }
                return false;
            }
            return confirm("Biztosan törlöd ezt a tantárgyat?");
        }
    </script>
</body>
</html>