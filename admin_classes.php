<?php
require_once "sqlconnection.php";
require_once "functions.php";

function addClass($conn){
    $name = trim($_POST['class_name']);
    $year = trim($_POST['year']);
    if (!empty($name) && !empty($year)) {
        $stmt = $conn->prepare("INSERT INTO osztalyok (name, evfolyam) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $year);
        $stmt->execute() ? displayMessage("Osztály hozzáadva!", "success") : displayMessage("Hiba: " . $conn->error, "error");
    }
}

function modifyClass($conn){
    $id = intval($_POST['class_id']);
    $name = trim($_POST['class_name']);
    $year = trim($_POST['year']);
    if (!empty($name) && !empty($year)) {
        $stmt = $conn->prepare("UPDATE osztalyok SET name = ?, evfolyam = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $year, $id);
        $stmt->execute() ? displayMessage("Osztály módosítva!", "success") : displayMessage("Hiba: " . $conn->error, "error");
    }
}

function deleteClass($conn){
    $id = intval($_POST['class_id']);
    $stmt = $conn->prepare("SELECT COUNT(*) FROM diakok WHERE osztaly_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    if ($stmt->get_result()->fetch_row()[0] == 0) {
        $stmt = $conn->prepare("DELETE FROM osztalyok WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute() ? displayMessage("Osztály törölve!", "success") : displayMessage("Hiba: " . $conn->error, "error");
    } else {
        displayMessage("Nem törölhető, mert diákok tartoznak hozzá!", "error");
    }
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_class'])) {
        addClass($conn);
    } elseif (isset($_POST['edit_class'])) {
        modifyClass($conn);
    } elseif (isset($_POST['delete_class'])) {
        deleteClass($conn);
    }
}

$result = $conn->query("SELECT id, name, evfolyam FROM osztalyok ORDER BY evfolyam, name");
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <title>Admin - Osztályok</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include "header.php"; ?>
    <main>
        <h1>Osztályok kezelése</h1>
        <section>
            <h2>Osztályok listája</h2>
            <a href="admin_classes.php?action=add" class="button">Új osztály</a>
            <?php if (isset($_GET['action']) && in_array($_GET['action'], ['add', 'edit'])): ?>
            <?php
            $edit_mode = $_GET['action'] === 'edit';
            $class_id = $edit_mode ? intval($_GET['id']) : 0;
            $class_name = $year = '';
            if ($edit_mode) {
                $stmt = $conn->prepare("SELECT name, evfolyam FROM osztalyok WHERE id = ?");
                $stmt->bind_param("i", $class_id);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $class_name = $row['name'];
                $year = $row['evfolyam'];
            }
            ?>
            <section>
                <h2><?php echo $edit_mode ? 'Osztály módosítása' : 'Új osztály hozzáadása'; ?></h2>
                <form action="admin_classes.php" method="POST">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                    <?php endif; ?>
                    <label for="class_name">Osztály neve:</label>
                    <input type="text" id="class_name" name="class_name" value="<?php echo htmlspecialchars($class_name); ?>" maxlength="3" required>
                    <label for="year">Évfolyam:</label>
                    <input type="number" id="year" name="year" value="<?php echo htmlspecialchars($year); ?>" maxlength="9" required>
                    <button type="submit" name="<?php echo $edit_mode ? 'edit_class' : 'add_class'; ?>" class="button">Mentés</button>
                    <a href="admin_classes.php" class="button">Mégse</a>
                </form>
            </section>
            <?php endif; ?>
            <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
                <thead>
                    <tr>
                        <th style="border: 1px solid #ccc; padding: 8px;">Név</th>
                        <th style="border: 1px solid #ccc; padding: 8px;">Évfolyam</th>
                        <th style="border: 1px solid #ccc; padding: 8px;">Műveletek</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td style="border: 1px solid #ccc; padding: 8px;"><?php echo htmlspecialchars($row['name']); ?></td>
                            <td style="border: 1px solid #ccc; padding: 8px;"><?php echo htmlspecialchars($row['evfolyam']); ?></td>
                            <td style="border: 1px solid #ccc; padding: 8px;">
                                <a href="admin_classes.php?action=edit&id=<?php echo $row['id']; ?>" class="button">Módosít</a>
                                <form action="admin_classes.php" method="POST" style="display: inline;" onsubmit="return confirm('Biztosan törlöd?');">
                                    <input type="hidden" name="class_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="delete_class" class="button delete">Töröl</button>
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
            $class_id = $edit_mode ? intval($_GET['id']) : 0;
            $class_name = $year = '';
            if ($edit_mode) {
                $stmt = $conn->prepare("SELECT name, evfolyam FROM osztalyok WHERE id = ?");
                $stmt->bind_param("i", $class_id);
                $stmt->execute();
                $row = $stmt->get_result()->fetch_assoc();
                $class_name = $row['name'];
                $year = $row['evfolyam'];
            }
            ?>
            <section>
                <h2><?php echo $edit_mode ? 'Osztály módosítása' : 'Új osztály hozzáadása'; ?></h2>
                <form action="admin_classes.php" method="POST">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                    <?php endif; ?>
                    <label for="class_name">Osztály neve:</label>
                    <input type="text" id="class_name" name="class_name" value="<?php echo htmlspecialchars($class_name); ?>" maxlength="3" required>
                    <label for="year">Évfolyam:</label>
                    <input type="number" id="year" name="year" value="<?php echo htmlspecialchars($year); ?>" maxlength="9" required>
                    <button type="submit" name="<?php echo $edit_mode ? 'edit_class' : 'add_class'; ?>" class="button">Mentés</button>
                    <a href="admin_classes.php" class="button">Mégse</a>
                </form>
            </section>
        <?php endif; ?>
    </main>
    <?php include "footer.php"; ?>
</body>
</html>