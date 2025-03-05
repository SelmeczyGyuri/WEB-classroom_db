<?php
require_once "sqlconnection.php";
require_once "functions.php";

// Handle POST request to add a new year
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_year'])) {
    $year = trim($_POST['year']);
    $default_class_name = 'A'; // Default class name for simplicity
    
    if (!empty($year) && is_numeric($year)) {
        // Check if the year already exists to avoid duplicates
        $check_stmt = $conn->prepare("SELECT COUNT(*) FROM osztalyok WHERE evfolyam = ?");
        $check_stmt->bind_param("s", $year);
        $check_stmt->execute();
        $count = $check_stmt->get_result()->fetch_row()[0];
        
        if ($count == 0) {
            // Insert a new class with the year
            $stmt = $conn->prepare("INSERT INTO osztalyok (name, evfolyam) VALUES (?, ?)");
            $stmt->bind_param("ss", $default_class_name, $year);
            $stmt->execute() ? displayMessage("Évfolyam hozzáadva!", "success") : displayMessage("Hiba: " . $conn->error, "error");
        } else {
            displayMessage("Ez az évfolyam már létezik!", "error");
        }
    } else {
        displayMessage("Érvénytelen évfolyam!", "error");
    }
}

// Fetch distinct years
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
            <a href="admin_years.php?action=add" class="button">Új évfolyam</a>
            <?php if (isset($_GET['action']) && $_GET['action'] === 'add'): ?>
            <section>
                <h2>Új évfolyam hozzáadása</h2>
                <form action="admin_years.php" method="POST">
                    <label for="year">Évfolyam (pl. 2023):</label>
                    <input type="number" id="year" name="year" min="2000" max="2099" required>
                    <button type="submit" name="add_year" class="button">Hozzáad</button>
                    <a href="admin_years.php" class="button">Mégse</a>
                </form>
            </section>
            <?php endif; ?>
            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                <thead>
                    <tr>
                        <th style="border: 1px solid #ccc; padding: 8px;">Évfolyam</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td style="border: 1px solid #ccc; padding: 8px;"><?php echo htmlspecialchars($row['evfolyam']); ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td style="border: 1px solid #ccc; padding: 8px; text-align: center;">Nincsenek évfolyamok az adatbázisban.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </section>

        
    </main>
    <?php include "footer.php"; ?>
</body>
</html>