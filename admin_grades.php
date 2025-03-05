<?php
require_once "sqlconnection.php";
require_once "functions.php";

// Function to handle POST requests
function handlePostRequest($conn) {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') return;

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

// Function to fetch data from database with search filter
function fetchData($conn, $search_query = '') {
    $data = [];
    
    // Prepare the grades query with optional search filter
    $query = "SELECT j.osztalyzat_id, d.nev AS student_name, t.tantargy, j.jegy, j.datum, d.diak_id, t.tantargy_id 
              FROM jegyek j 
              JOIN diakok d ON j.diak_id = d.diak_id 
              JOIN tantargyak t ON j.tantargy_id = t.tantargy_id";
    
    if (!empty($search_query)) {
        $query .= " WHERE d.nev LIKE ?";
        $stmt = $conn->prepare($query);
        $search_param = "%" . $search_query . "%";
        $stmt->bind_param("s", $search_param);
        $stmt->execute();
        $data['grades'] = $stmt->get_result();
    } else {
        $query .= " ORDER BY d.nev, j.datum DESC";
        $data['grades'] = $conn->query($query);
    }
    
    $data['students'] = $conn->query("SELECT diak_id, nev FROM diakok ORDER BY nev");
    $data['subjects'] = $conn->query("SELECT tantargy_id, tantargy FROM tantargyak ORDER BY tantargy");
    
    return $data;
}

// Function to fetch grade data for editing
function fetchGradeForEdit($conn, $grade_id) {
    $stmt = $conn->prepare("SELECT diak_id, tantargy_id, jegy, datum FROM jegyek WHERE osztalyzat_id = ?");
    $stmt->bind_param("i", $grade_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Function to render the grades table
function renderGradesTable($result) {
    ?>
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
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td style="border: 1px solid #ccc; padding: 8px;"><?php echo htmlspecialchars($row['student_name']); ?></td>
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
            <?php else: ?>
                <tr>
                    <td colspan="5" style="border: 1px solid #ccc; padding: 8px; text-align: center;">Nincsenek jegyek az adatbázisban vagy a keresés nem adott találatot.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php
}

// Function to render the grade form
function renderGradeForm($students, $subjects, $edit_mode = false, $grade_data = []) {
    $student_id = $edit_mode ? $grade_data['diak_id'] : '';
    $subject_id = $edit_mode ? $grade_data['tantargy_id'] : '';
    $grade = $edit_mode ? $grade_data['jegy'] : '';
    $date = $edit_mode ? $grade_data['datum'] : '';
    $grade_id = $edit_mode ? $_GET['id'] : '';
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
    <?php
}

// Main execution
handlePostRequest($conn);
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';
$data = fetchData($conn, $search_query);
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
            <h2>Minden diák jegyei</h2>
            <a href="admin_grades.php?action=add" class="button">Új jegy</a>
            <br>
            <form action="admin_grades.php" method="GET" style="margin-top: 10px;">
                <label for="search">Diák keresése:</label>
                <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Diák neve...">
                <button type="submit" class="button">Keresés</button>
                <?php if (!empty($search_query)): ?>
                    <a href="admin_grades.php" class="button">Keresés törlése</a>
                <?php endif; ?>
            </form>
            <?php 
            if (isset($_GET['action']) && in_array($_GET['action'], ['add', 'edit'])) {
                $edit_mode = $_GET['action'] === 'edit';
                $grade_data = $edit_mode ? fetchGradeForEdit($conn, intval($_GET['id'])) : [];
                $data['students']->data_seek(0);
                $data['subjects']->data_seek(0);
                renderGradeForm($data['students'], $data['subjects'], $edit_mode, $grade_data);
            }
            renderGradesTable($data['grades']);
            ?>
        </section>
    </main>
    <?php include "footer.php"; ?>
</body>
</html>