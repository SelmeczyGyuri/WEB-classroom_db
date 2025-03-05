<?php
require_once "sqlconnection.php";
require_once "functions.php";
require_once "insertdata.php";

// Function to handle POST requests
function handlePostRequest($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

    if (isset($_POST['add_student'])) {
        $name = trim($_POST['student_name']);
        $gender = trim($_POST['gender']);
        $class_id = intval($_POST['class_id']);
        if (!empty($name) && !empty($gender) && $class_id > 0) {
            $stmt = $conn->prepare("INSERT INTO diakok (nev, nem, osztaly_id) VALUES (?, ?, ?)");
            $stmt->bind_param("ssi", $name, $gender, $class_id);
            if ($stmt->execute()) {
                $newStudentId = $conn->insert_id;
                insertGradesForStudent($conn, $newStudentId, $class_id);
                displayMessage("Diák hozzáadva és jegyek generálva!", "success");
            } else {
                displayMessage("Hiba: " . $conn->error, "error");
            }
        }
    } elseif (isset($_POST['edit_student'])) {
        $id = intval($_POST['student_id']);
        $name = trim($_POST['student_name']);
        $gender = trim($_POST['gender']);
        $class_id = intval($_POST['class_id']);
        if (!empty($name) && !empty($gender) && $class_id > 0) {
            $stmt = $conn->prepare("UPDATE diakok SET nev = ?, nem = ?, osztaly_id = ? WHERE diak_id = ?");
            $stmt->bind_param("ssii", $name, $gender, $class_id, $id);
            $stmt->execute() ? displayMessage("Diák módosítva!", "success") : displayMessage("Hiba: " . $conn->error, "error");
        }
    } elseif (isset($_POST['delete_student'])) {
        $id = intval($_POST['student_id']);
        $delete_mode = $_POST['delete_mode'];
        $stmt = $conn->prepare("SELECT COUNT(*) FROM jegyek WHERE diak_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $count = $stmt->get_result()->fetch_row()[0];
        if ($count > 0) {
            if ($delete_mode === 'delete_with_grades') {
                $stmt = $conn->prepare("DELETE FROM jegyek WHERE diak_id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
            } else {
                displayMessage("Nem törölhető, mert jegyek vannak!", "error");
                header("Refresh: 2; url=admin_students.php");
                exit;
            }
        }
        $stmt = $conn->prepare("DELETE FROM diakok WHERE diak_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute() ? displayMessage("Diák törölve!", "success") : displayMessage("Hiba: " . $conn->error, "error");
    }
}

// Function to fetch data from database
function fetchData($conn) {
    $data = [];
    $data['students'] = $conn->query("SELECT d.diak_id, d.nev, d.nem, o.name AS osztaly 
                                     FROM diakok d 
                                     JOIN osztalyok o ON d.osztaly_id = o.id 
                                     ORDER BY d.nev");
    $data['classes'] = $conn->query("SELECT id, name FROM osztalyok ORDER BY name");
    return $data;
}

// Function to fetch student data for editing
function fetchStudentForEdit($conn, $student_id) {
    $stmt = $conn->prepare("SELECT nev, nem, osztaly_id FROM diakok WHERE diak_id = ?");
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Function to render the students table
function renderStudentsTable($result) {
    ?>
    <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
        <thead>
            <tr>
                <th style="border: 1px solid #ccc; padding: 8px;">Név</th>
                <th style="border: 1px solid #ccc; padding: 8px;">Nem</th>
                <th style="border: 1px solid #ccc; padding: 8px;">Osztály</th>
                <th style="border: 1px solid #ccc; padding: 8px;">Műveletek</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td style="border: 1px solid #ccc; padding: 8px;"><?php echo htmlspecialchars($row['nev']); ?></td>
                    <td style="border: 1px solid #ccc; padding: 8px;"><?php echo htmlspecialchars($row['nem']); ?></td>
                    <td style="border: 1px solid #ccc; padding: 8px;"><?php echo htmlspecialchars($row['osztaly']); ?></td>
                    <td style="border: 1px solid #ccc; padding: 8px;">
                        <a href="admin_students.php?action=edit&id=<?php echo $row['diak_id']; ?>" class="button">Módosít</a>
                        <form action="admin_students.php" method="POST" style="display: inline;" onsubmit="return confirmDelete(<?php echo $row['diak_id']; ?>);">
                            <input type="hidden" name="student_id" value="<?php echo $row['diak_id']; ?>">
                            <input type="hidden" name="delete_mode" id="delete_mode_<?php echo $row['diak_id']; ?>" value="check">
                            <button type="submit" name="delete_student" class="button delete">Töröl</button>
                        </form>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <?php
}

// Function to render the student form
function renderStudentForm($classes, $edit_mode = false, $student_data = []) {
    $student_name = $edit_mode ? $student_data['nev'] : '';
    $gender = $edit_mode ? $student_data['nem'] : '';
    $class_id = $edit_mode ? $student_data['osztaly_id'] : '';
    $student_id = $edit_mode ? $_GET['id'] : '';
    ?>
    <section>
        <h2><?php echo $edit_mode ? 'Diák módosítása' : 'Új diák hozzáadása'; ?></h2>
        <form action="admin_students.php" method="POST">
            <?php if ($edit_mode): ?>
                <input type="hidden" name="student_id" value="<?php echo $student_id; ?>">
            <?php endif; ?>
            <label for="student_name">Név:</label>
            <input type="text" id="student_name" name="student_name" value="<?php echo htmlspecialchars($student_name); ?>" maxlength="40" required>
            <label for="gender">Nem:</label>
            <input type="text" id="gender" name="gender" value="<?php echo htmlspecialchars($gender); ?>" maxlength="40" required>
            <label for="class_id">Osztály:</label>
            <select id="class_id" name="class_id" required>
                <?php while ($class = $classes->fetch_assoc()): ?>
                    <option value="<?php echo $class['id']; ?>" <?php echo $class_id == $class['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($class['name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <button type="submit" name="<?php echo $edit_mode ? 'edit_student' : 'add_student'; ?>" class="button">Mentés</button>
            <a href="admin_students.php" class="button">Mégse</a>
        </form>
    </section>
    <?php
}

// Main execution
handlePostRequest($conn);
$data = fetchData($conn);
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Admin - Diákok</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include "header.php"; ?>
    <main>
        <h1>Diákok kezelése</h1>
        <section>
            <h2>Diákok listája</h2>
            <a href="admin_students.php?action=add" class="button">Új diák</a>
            <?php 
            if (isset($_GET['action']) && in_array($_GET['action'], ['add', 'edit'])) {
                $edit_mode = $_GET['action'] === 'edit';
                $student_data = $edit_mode ? fetchStudentForEdit($conn, intval($_GET['id'])) : [];
                $data['classes']->data_seek(0);
                renderStudentForm($data['classes'], $edit_mode, $student_data);
            }
            renderStudentsTable($data['students']);
            ?>
        </section>
    </main>
    <?php include "footer.php"; ?>
    <script>
        function confirmDelete(studentId) {
            var xhr = new XMLHttpRequest();
            xhr.open("POST", "check_student_grades.php", false);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.send("student_id=" + studentId);
            if (xhr.responseText === "has_grades") {
                if (confirm("Ez a diák jegyeket tartalmaz. Törölni akarod a jegyekkel együtt?")) {
                    document.getElementById("delete_mode_" + studentId).value = "delete_with_grades";
                    return true;
                }
                return false;
            }
            return confirm("Biztosan törlöd ezt a diákot?");
        }
    </script>
</body>
</html>