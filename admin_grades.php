<?php
require_once "sqlconnection.php";
require_once "functions.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_grade'])) {
        $student_id = intval($_POST['student_id']);
        $subject_id = intval($_POST['subject_id']);
        $grade = intval($_POST['grade']);
        $date = $_POST['date'];
        if ($student_id > 0 && $subject_id > 0 && $grade >= 1 && $grade <= 5 && !empty($date)) {
            $stmt = $conn->prepare("INSERT INTO jegyek (diak_id, tantargy_id, jegy, datum) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiis", $student_id, $subject_id, $grade, $date);
            $stmt->execute() ? displayMessage("Jegy hozzáadva!", "success") : displayMessage("Hiba: " . $conn->error, "error");
        }
    } elseif (isset($_POST['edit_grade'])) {
        $id = intval($_POST['grade_id']);
        $student_id = intval($_POST['student_id']);
        $subject_id = intval($_POST['subject_id']);
        $grade = intval($_POST['grade']);
        $date = $_POST['date'];
        if ($student_id > 0 && $subject_id > 0 && $grade >= 1 && $grade <= 5 && !empty($date)) {
            $stmt = $conn->prepare("UPDATE jegyek SET diak_id = ?, tantargy_id = ?, jegy = ?, datum = ? WHERE osztalyzat_id = ?");
            $stmt->bind_param("iiisi", $student_id, $subject_id, $grade, $date, $id);
            $stmt->execute() ? displayMessage("Jegy módosítva!", "success") : displayMessage("Hiba: " . $conn->error, "error");
        }
    } elseif (isset($_POST['delete_grade'])) {
        $id = intval($_POST['grade_id']);
        $stmt = $conn->prepare("DELETE FROM jegyek WHERE osztalyzat_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute() ? displayMessage("Jegy törölve!", "success") : displayMessage("Hiba: " . $conn->error, "error");
    }
}

$result = $conn->query("SELECT j.osztalyzat_id, d.nev, t.tantargy, j.jegy, j.datum FROM jegyek j JOIN diakok d ON j.diak_id = d.diak_id JOIN tantargyak t ON j.tantargy_id = t.tantargy_id ORDER BY j.datum DESC");
$students = $conn->query("SELECT diak_id, nev FROM diakok ORDER BY nev");
$subjects = $conn->query("SELECT tantargy_id, tantargy FROM tantargyak ORDER BY tantargy");
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Admin - Jegyek</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include "header.php"; ?>
    <main>
        <h1>Jegyek kezelése</h1>
        <section>
            <h2>Jegyek listája</h2>
            <a href="admin_grades.php?action=add" class="button">Új jegy</a>
            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                <thead>
                    <tr>
                        <th style="border: 1px solid #ccc; padding: 8px;">Diák</th>
                        <th style="border: 1px solid #ccc; padding: 8px;">Tantárgy</th>
                        <th style="border: 1px solid #ccc; padding: 8px;">Jegy</th>
                        <th style="border: 1px solid #ccc; padding: 8px;">Dátum</th>
                        <th style="border: 1px solid #ccc; padding: 8px;">Műveletek</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td style="border: 1px solid #ccc; padding: 8px;"><?php echo htmlspecialchars($row['nev']); ?></td>
                            <td style="border: 1px solid #ccc; padding: 8px;"><?php echo htmlspecialchars($row['tantargy']); ?></td>
                            <td style="border: 1px solid #ccc; padding: 8px;"><?php echo $row['jegy']; ?></td>
                            <td style="border: 1px solid #ccc; padding: 8px;"><?php echo $row['datum']; ?></td>
                            <td style="border: 1px solid #ccc; padding: 8px;">
                                <a href="admin_grades.php?action=edit&id=<?php echo $row['osztalyzat_id']; ?>" class="button">Módosít</a>
                                <form action="admin_grades.php" method="POST" style="display: inline;" onsubmit="return confirm('Biztosan törlöd?');">
                                    <input type="hidden" name="grade_id" value="<?php echo $row['osztalyzat_id']; ?>">
                                    <button type="submit" name="delete_grade" class="button delete">Töröl</button>
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
            $grade_id = $edit_mode ? intval($_GET['id']) : 0;
            $student_id = $subject_id = $grade = $date = '';
            if ($edit_mode) {
                $stmt = $conn->prepare("SELECT diak_id, tantargy_id, jegy, datum FROM jegyek WHERE osztalyzat_id = ?");
                $stmt->bind_param("i", $grade_id);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $student_id = $row['diak_id'];
                $subject_id = $row['tantargy_id'];
                $grade = $row['jegy'];
                $date = $row['datum'];
            }
            ?>
            <section>
                <h2><?php echo $edit_mode ? 'Jegy módosítása' : 'Új jegy hozzáadása'; ?></h2>
                <form action="admin_grades.php" method="POST">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="grade_id" value="<?php echo $grade_id; ?>">
                    <?php endif; ?>
                    <label for="student_id">Diák:</label>
                    <select id="student_id" name="student_id" required>
                        <?php while ($student = $students->fetch_assoc()): ?>
                            <option value="<?php echo $student['diak_id']; ?>" <?php echo $student_id == $student['diak_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($student['nev']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <label for="subject_id">Tantárgy:</label>
                    <select id="subject_id" name="subject_id" required>
                        <?php while ($subject = $subjects->fetch_assoc()): ?>
                            <option value="<?php echo $subject['tantargy_id']; ?>" <?php echo $subject_id == $subject['tantargy_id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($subject['tantargy']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <label for="grade">Jegy:</label>
                    <input type="number" id="grade" name="grade" min="1" max="5" value="<?php echo $grade; ?>" required>
                    <label for="date">Dátum:</label>
                    <input type="date" id="date" name="date" value="<?php echo $date; ?>" required>
                    <button type="submit" name="<?php echo $edit_mode ? 'edit_grade' : 'add_grade'; ?>" class="button">Mentés</button>
                    <a href="admin_grades.php" class="button">Mégse</a>
                </form>
            </section>
        <?php endif; ?>
    </main>
    <?php include "footer.php"; ?>
</body>
</html>