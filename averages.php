<?php
require_once "sqlconnection.php";
require_once "functions.php";

// Osztályok átlagai
$query = "SELECT osztalyok.name, AVG(jegyek.jegy) as atlag FROM jegyek 
          JOIN diakok ON jegyek.diak_id = diakok.diak_id 
          JOIN osztalyok ON diakok.osztaly_id = osztalyok.id 
          GROUP BY osztalyok.id ORDER BY atlag DESC";
$result = $conn->query($query);
?>

<h2>Osztályok átlagai</h2>
<table>
    <tr><th>Osztály</th><th>Átlag</th></tr>
    <?php while ($row = $result->fetch_assoc()): ?>
        <tr><td><?php echo $row['name']; ?></td><td><?php echo number_format($row['atlag'], 2); ?></td></tr>
    <?php endwhile; ?>
</table>
