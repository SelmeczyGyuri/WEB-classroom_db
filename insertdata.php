<?php

require_once "config.php";

function insertSubjectsIntoDatabase($conn) {
    $checkSql = "SELECT COUNT(*) AS count FROM tantargyak";
    $result = $conn->query($checkSql);
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        /*echo "A tantárgyak már fel vannak töltve az adatbázisba.<br>";*/
        return;
    }

    $sql = "INSERT INTO tantargyak (tantargy) VALUES (?)";
    $stmt = $conn->prepare($sql);

    foreach (SUBJECTS as $subject) {
        $stmt->bind_param("s", $subject);
        $stmt->execute();
    }

    /*echo "A tantárgyak sikeresen feltöltve az adatbázisba.<br>";*/
}

function insertClassesIntoDatabase($conn) {
    $checkSql = "SELECT COUNT(*) AS count FROM osztalyok";
    $result = $conn->query($checkSql);
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        /*echo "Az osztályok már fel vannak töltve az adatbázisba.<br>";*/
        return;
    }

    $sql = "INSERT INTO osztalyok (name, evfolyam) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);

    foreach (CLASSES as $class) {
        $year = rand(2021, 2025);

        $stmt->bind_param("ss", $class, $year);
        $stmt->execute();
    }

    /*echo "Az osztályok sikeresen feltöltve az adatbázisba.<br>";*/
}


function getName() {
    $nevek = [];
 
    for ($i = 0; $i < 180; $i++) {
 
        $veznev = NAMES['lastnames'][rand(0, count(NAMES['lastnames']) - 1)];
 
        $nem = rand(0, 1);
 
        if ($nem == 1) {
            $kernev = NAMES['firstnames']['men'][rand(0, count(NAMES['firstnames']['men']) - 1)];
        } else {
            $kernev = NAMES['firstnames']['women'][rand(0, count(NAMES['firstnames']['women']) - 1)];
        }
 
        $nev = $veznev . " " . $kernev;
 
 
        $nevek[] = ["name" => $nev, "gender" => $nem];
    }
 
    return $nevek;
}
 
$generatedNames = getName();

function insertStudentsIntoDatabase($nevek) {
    $host = 'localhost';
    $dbname = 'naplo';
    $username = 'root';
    $password = '';
 
    try {
 
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 
        $sql = "INSERT INTO diakok (nev, nem, osztaly_id) VALUES (:nev, :nem, :osztaly_id)";
 
        $stmt = $pdo->prepare($sql);
 
        $classIds = [];
        for ($classId = 1; $classId <= 12; $classId++) {
            for ($i = 0; $i < 15; $i++) {
                $classIds[] = $classId;
            }
        }
 
        foreach ($nevek as $index => $student) {
            $stmt->execute([
                ':nev' => $student['name'],
                ':nem' => $student['gender'],
                ':osztaly_id' => $classIds[$index % count($classIds)]
            ]);
        }
 
        /*echo "A tanulók sikeresen feltöltve az adatbázisba.<br>";*/
    } catch (PDOException $e) {
       
        /*echo "Hiba történt: " . $e->getMessage();*/
    }
}

function insertGradesIntoDatabase($conn) {
    $checkSql = "SELECT COUNT(*) AS count FROM jegyek";
    $result = $conn->query($checkSql);
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        return;
    }

    $classYears = [];
    $classSql = "SELECT id, evfolyam FROM osztalyok";
    $classResult = $conn->query($classSql);

    while ($classRow = $classResult->fetch_assoc()) {
        $classYears[$classRow['id']] = $classRow['evfolyam'];
    }

    $sql = "INSERT INTO jegyek (diak_id, tantargy_id, jegy, datum) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    $studentSql = "SELECT diak_id, osztaly_id FROM diakok";
    $studentResult = $conn->query($studentSql);

    $currentYear = date("Y");

    while ($studentRow = $studentResult->fetch_assoc()) {
        $studentId = $studentRow['diak_id'];
        $classId = $studentRow['osztaly_id'];

        //$evfolyam = $classYears[$classId] ?? 9;

        $studentYear = $currentYear;

        $jegyszam = rand(3, 5);
        for ($i = 1; $i <= $jegyszam; $i++) {

            for ($subjectId = 1; $subjectId <= 8; $subjectId++) {
                $randomGrade = rand(1, 5);

                $randomMonth = rand(9, 12);
                $randomYear = $studentYear;

                if (rand(0, 1) === 1) { 
                    $randomMonth = rand(1, 6);
                    $randomYear = $studentYear + 1;
                }

                $randomDate = sprintf("%d-%02d-%02d", $randomYear, $randomMonth, rand(1, 28));

                $stmt->bind_param("iiis", $studentId, $subjectId, $randomGrade, $randomDate);
                $stmt->execute();
            }
        }
    }

    echo "A jegyek sikeresen feltöltve az adatbázisba.<br>";
}

?>