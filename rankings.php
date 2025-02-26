<?php require_once "sqlconnection.php"; ?>
<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hall of Fame</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <?php include "header.php"; ?>

    <main>
        <h1>Hall of Fame</h1>

        <!-- Top 10 diák -->
        <section class="top-students">
            <h2>Top 10 diák</h2>
            <?php
            // Top 10 diák lekérdezése, osztály és évfolyam megjelenítése
            $query_top_students = "
                SELECT diakok.nev, osztalyok.name AS osztaly, osztalyok.evfolyam, AVG(jegyek.jegy) AS atlag 
                FROM jegyek 
                JOIN diakok ON jegyek.diak_id = diakok.diak_id
                JOIN osztalyok ON diakok.osztaly_id = osztalyok.id
                GROUP BY jegyek.diak_id
                ORDER BY atlag DESC
                LIMIT 10";
            $result_top_students = $conn->query($query_top_students);

            $rank = 1;
            while ($row = $result_top_students->fetch_assoc()) {
                echo "<div class='student-item'>
                        <div class='student-info'>
                            <div><span>Helyezés:</span> $rank</div>
                            <div><span>Név:</span> {$row['nev']}</div>
                            <div><span>Osztály:</span> {$row['osztaly']}</div>
                            <div><span>Évfolyam:</span> {$row['evfolyam']}</div>
                            <div><span>Átlag:</span> " . round($row['atlag'], 2) . "</div>
                        </div>
                    </div>";
                $rank++;
            }
            ?>
        </section>

        <!-- Mindenkori legjobb osztály -->
        <section class="best-class">
            <h2>Mindenkori legjobb osztály</h2>
            <?php
            // Mindenkori legjobb osztály lekérdezése, évfolyammal együtt
            $query_best_class = "
                SELECT osztalyok.name AS osztaly, osztalyok.evfolyam, AVG(jegyek.jegy) AS atlag
                FROM jegyek
                JOIN diakok ON jegyek.diak_id = diakok.diak_id
                JOIN osztalyok ON diakok.osztaly_id = osztalyok.id
                GROUP BY osztalyok.id
                ORDER BY atlag DESC
                LIMIT 1";
            $result_best_class = $conn->query($query_best_class);
            $row_best_class = $result_best_class->fetch_assoc();

            echo "<p><span>Legjobb osztály:</span> {$row_best_class['osztaly']} - <span>Évfolyam:</span> {$row_best_class['evfolyam']} - <span>Átlag:</span> " . round($row_best_class['atlag'], 2) . "</p>";
            ?>
        </section>
        
    </main>

    <?php include "footer.php"; ?>

</body>
</html>
