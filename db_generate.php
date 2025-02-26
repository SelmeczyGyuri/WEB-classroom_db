<?php require_once "sqlconnection.php"; ?>
<?php require_once "header.php"; ?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Osztálynapló</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<main>
    <h1>Üdvözöllek az Osztálynaplóban!</h1>
    <p>Hozd létre az adatbázist!</p>
    <form action="db_generate.php" method="POST">
        <button type="submit" name="install">Adatbázis létrehozása</button>
    </form>
</main>

<?php require_once "footer.php"; ?>

</body>
</html>
