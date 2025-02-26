<?php
require_once "sqlconnection.php";
require_once "functions.php";

if (!isset($_GET['year'])) {
    //displayMessage("Nincs megadva évfolyam!", "error");
    exit;
}

$year = intval($_GET['year']);

// Osztályok lekérdezése az adott évfolyamon
$query = "SELECT id, name FROM osztalyok WHERE evfolyam = ? ORDER BY name";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $year);
$stmt->execute();
$result = $stmt->get_result();

// A legjobb 10 tanuló lekérdezése
$query_top_students = "
    SELECT diakok.nev, AVG(jegyek.jegy) AS atlag, osztalyok.name AS osztaly
    FROM jegyek
    JOIN diakok ON jegyek.diak_id = diakok.diak_id
    JOIN osztalyok ON diakok.osztaly_id = osztalyok.id
    WHERE osztalyok.evfolyam = ?
    GROUP BY diakok.nev, osztalyok.name
    ORDER BY atlag DESC
    LIMIT 10";

$stmt_top_students = $conn->prepare($query_top_students);
$stmt_top_students->bind_param("i", $year);
$stmt_top_students->execute();
$result_top_students = $stmt_top_students->get_result();
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Osztályok - <?php echo $year; ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include "header.php"; ?>

<main>
    <section class="classes">
        <a href="index.php" class='class'>Vissza</a>
    </section>
    <h1>Osztályok az <?php echo $year; ?> évfolyamban</h1>
    <section class="classes">
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<a href='students.php?class_id={$row['id']}' class='class'>{$year}/{$row['name']}</a>";
            }
        } else {
            displayMessage("Nincsenek osztályok ebben az évfolyamban!", "error");
        }
        ?>
    </section>

    <!-- Legjobb 10 tanuló listája -->
    <section class="top-students">
        <h2>A <?php echo $year; ?> évfolyam legjobb 10 tanulója</h2>
        <?php if ($result_top_students->num_rows > 0): ?>
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th style="border: 1px solid #ccc; padding: 8px;">Helyezés</th>
                        <th style="border: 1px solid #ccc; padding: 8px;">Név</th>
                        <th style="border: 1px solid #ccc; padding: 8px;">Átlag</th>
                        <th style="border: 1px solid #ccc; padding: 8px;">Osztály</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $rank = 1; ?>
                    <?php while ($row = $result_top_students->fetch_assoc()): ?>
                        <tr>
                            <td style="border: 1px solid #ccc; padding: 8px;"><?php echo $rank++; ?></td>
                            <td style="border: 1px solid #ccc; padding: 8px;"><?php echo $row['nev']; ?></td>
                            <td style="border: 1px solid #ccc; padding: 8px;"><?php echo number_format($row['atlag'], 2); ?></td>
                            <td style="border: 1px solid #ccc; padding: 8px;"><?php echo $row['osztaly']; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>Nincsenek elérhető legjobb tanulók ebben az évfolyamban.</p>
        <?php endif; ?>
    </section>
</main>

<?php include "footer.php"; ?>

</body>
</html>
