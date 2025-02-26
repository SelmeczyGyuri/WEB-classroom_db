<?php
require_once "sqlconnection.php";
require_once "functions.php";
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Osztálynapló</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include "header.php"; ?>

<main class="main">
    <h1>Üdvözöllek az Osztálynaplóban!</h1>
    <p class="choose">Válassz egy évfolyamot az osztályok és diákok megtekintéséhez.</p>

    <section class="years">
        <?php
        $query = "SELECT DISTINCT evfolyam FROM osztalyok ORDER BY evfolyam ASC";
        $result = $conn->query($query);

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<a href='classes.php?year={$row['evfolyam']}' class='year'>{$row['evfolyam']}</a>";
            }
        } else {
            displayMessage("Nincsenek elérhető évfolyamok!", "error");
        }
        ?>
    </section>
</main>

<?php include "footer.php"; ?>

</body>
</html>
