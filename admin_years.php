<?php
require_once "sqlconnection.php";
require_once "functions.php";

$result = $conn->query("SELECT DISTINCT evfolyam FROM osztalyok ORDER BY evfolyam");
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Admin - Évfolyamok</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include "header.php"; ?>
    <main>
        <h1>Évfolyamok kezelése</h1>
        <section>
            <h2>Évfolyamok listája</h2>
            <p>Az évfolyamokat az osztályokon keresztül kezelheted.</p>
            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                <thead>
                    <tr>
                        <th style="border: 1px solid #ccc; padding: 8px;">Évfolyam</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td style="border: 1px solid #ccc; padding: 8px;"><?php echo htmlspecialchars($row['evfolyam']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </section>
    </main>
    <?php include "footer.php"; ?>
</body>
</html>