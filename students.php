<?php
require_once "sqlconnection.php";
require_once "functions.php";

if (!isset($_GET['class_id'])) {
    displayMessage("Nincs megadva osztály!", "error");
    exit;
}

$class_id = intval($_GET['class_id']);

// Osztály nevének és évfolyamának lekérdezése
$query_class_info = "SELECT name, evfolyam FROM osztalyok WHERE id = ?";
$stmt_class_info = $conn->prepare($query_class_info);
$stmt_class_info->bind_param("i", $class_id);
$stmt_class_info->execute();
$result_class_info = $stmt_class_info->get_result();
$class_info = $result_class_info->fetch_assoc();

if (!$class_info) {
    displayMessage("Az osztály nem található!", "error");
    exit;
}
$class_name = $class_info['name'];
$class_grade = $class_info['evfolyam'];

// Összes diák lekérdezése az osztályból
$query_students = "SELECT nev FROM diakok WHERE osztaly_id = ? ORDER BY nev";
$stmt_students = $conn->prepare($query_students);
$stmt_students->bind_param("i", $class_id);
$stmt_students->execute();
$result_students = $stmt_students->get_result();

$students = [];
while ($row = $result_students->fetch_assoc()) {
    $students[] = $row['nev'];
}

// Tantárgyak lekérdezése
$query_subjects = "SELECT DISTINCT tantargyak.tantargy 
                   FROM jegyek 
                   JOIN tantargyak ON jegyek.tantargy_id = tantargyak.tantargy_id
                   JOIN diakok ON jegyek.diak_id = diakok.diak_id
                   WHERE diakok.osztaly_id = ?
                   ORDER BY tantargyak.tantargy";
$stmt_subjects = $conn->prepare($query_subjects);
$stmt_subjects->bind_param("i", $class_id);
$stmt_subjects->execute();
$result_subjects = $stmt_subjects->get_result();

$subjects = [];
while ($row = $result_subjects->fetch_assoc()) {
    $subjects[] = $row['tantargy'];
}

// Tanulók tantárgyankénti átlagának lekérdezése
$query_avg_subjects = "
    SELECT diakok.nev, tantargyak.tantargy, AVG(jegyek.jegy) AS atlag 
    FROM jegyek
    JOIN diakok ON jegyek.diak_id = diakok.diak_id
    JOIN tantargyak ON jegyek.tantargy_id = tantargyak.tantargy_id
    WHERE diakok.osztaly_id = ?
    GROUP BY diakok.nev, tantargyak.tantargy
    ORDER BY diakok.nev, tantargyak.tantargy";
$stmt_avg_subjects = $conn->prepare($query_avg_subjects);
$stmt_avg_subjects->bind_param("i", $class_id);
$stmt_avg_subjects->execute();
$result_avg_subjects = $stmt_avg_subjects->get_result();

// Tanulók összes átlagának lekérdezése
$query_avg_all = "
    SELECT diakok.nev, AVG(jegyek.jegy) AS osszatlag 
    FROM jegyek
    JOIN diakok ON jegyek.diak_id = diakok.diak_id
    WHERE diakok.osztaly_id = ?
    GROUP BY diakok.nev
    ORDER BY diakok.nev";
$stmt_avg_all = $conn->prepare($query_avg_all);
$stmt_avg_all->bind_param("i", $class_id);
$stmt_avg_all->execute();
$result_avg_all = $stmt_avg_all->get_result();

// Osztály átlagának lekérdezése
$query_class_avg = "SELECT AVG(jegyek.jegy) as osztaly_atlag FROM jegyek
                    JOIN diakok ON jegyek.diak_id = diakok.diak_id
                    WHERE diakok.osztaly_id = ?";
$stmt_class_avg = $conn->prepare($query_class_avg);
$stmt_class_avg->bind_param("i", $class_id);
$stmt_class_avg->execute();
$result_class_avg = $stmt_class_avg->get_result();
$class_avg_row = $result_class_avg->fetch_assoc();

// Osztály tantárgyankénti átlagainak lekérdezése
$query_class_avg_subjects = "
    SELECT tantargyak.tantargy, AVG(jegyek.jegy) AS atlag 
    FROM jegyek
    JOIN tantargyak ON jegyek.tantargy_id = tantargyak.tantargy_id
    JOIN diakok ON jegyek.diak_id = diakok.diak_id
    WHERE diakok.osztaly_id = ?
    GROUP BY tantargyak.tantargy
    ORDER BY tantargyak.tantargy";
$stmt_class_avg_subjects = $conn->prepare($query_class_avg_subjects);
$stmt_class_avg_subjects->bind_param("i", $class_id);
$stmt_class_avg_subjects->execute();
$result_class_avg_subjects = $stmt_class_avg_subjects->get_result();

// Adatok tárolása
$student_data = [];
while ($row = $result_avg_subjects->fetch_assoc()) {
    $student_data[$row['nev']][$row['tantargy']] = number_format($row['atlag'], 2);
}

$student_averages = [];
while ($row = $result_avg_all->fetch_assoc()) {
    $student_averages[$row['nev']]['osszatlag'] = number_format($row['osszatlag'], 2);
}

$class_subject_averages = [];
while ($row = $result_class_avg_subjects->fetch_assoc()) {
    $class_subject_averages[$row['tantargy']] = number_format($row['atlag'], 2);
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Tanulók - <?php echo $class_grade . '/' . $class_name; ?></title>
    <link rel="stylesheet" href="style.css">
    <script>
        function toggleStudentDetails(studentName) {
            var studentDetails = document.getElementById(studentName);
            studentDetails.style.display = studentDetails.style.display === "none" ? "table" : "none";
        }
    </script>
</head>
<body>

<?php include "header.php"; ?>

<main>
    <section class="classes">
        <a href="index.php" class='class'>Vissza</a>
    </section>
    <h1><?php echo $class_grade; ?>. évfolyam - <?php echo $class_name; ?> osztály</h1>

    <section id="students">
        <h2>Diákok átlagai tantárgyanként</h2>
        <?php if (empty($students)): ?>
            <p>Nincsenek diákok ebben az osztályban.</p>
        <?php else: ?>
            <?php foreach ($students as $student): ?>
                <h3 onclick="toggleStudentDetails('<?php echo $student; ?>')" style="cursor: pointer; color: blue;">
                    <?php echo $student; ?>
                </h3>
                <table id="<?php echo $student; ?>" style="display: none; width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                    <thead>
                        <tr>
                            <?php foreach ($subjects as $subject): ?>
                                <th style="border: 1px solid #ccc; padding: 8px;"><?php echo $subject; ?></th>
                            <?php endforeach; ?>
                            <th style="border: 1px solid #ccc; padding: 8px;">Összes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <?php foreach ($subjects as $subject): ?>
                                <td style="border: 1px solid #ccc; padding: 8px;">
                                    <?php echo isset($student_data[$student][$subject]) ? $student_data[$student][$subject] : '-'; ?>
                                </td>
                            <?php endforeach; ?>
                            <td style="border: 1px solid #ccc; padding: 8px;">
                                <?php echo isset($student_averages[$student]['osszatlag']) ? $student_averages[$student]['osszatlag'] : '-'; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            <?php endforeach; ?>
        <?php endif; ?>

        <h2>Osztály átlagok tantárgyanként</h2>
        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
            <thead>
                <tr>
                    <?php foreach ($subjects as $subject): ?>
                        <th style="border: 1px solid #ccc; padding: 8px;"><?php echo $subject; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <?php foreach ($subjects as $subject): ?>
                        <td style="border: 1px solid #ccc; padding: 8px;">
                            <?php echo isset($class_subject_averages[$subject]) ? $class_subject_averages[$subject] : '-'; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            </tbody>
        </table>
        <h2>Osztály összesített átlaga: <?php echo number_format($class_avg_row['osztaly_atlag'] ?? 0, 2); ?></h2>
    </section>
</main>

<?php include "footer.php"; ?>

</body>
</html>