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

function insertStudentsIntoDatabase($conn, $nevek) {
    $checkSql = "SELECT COUNT(*) AS count FROM diakok";
    $result = $conn->query($checkSql);
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        return;
    }

    $sql = "INSERT INTO diakok (nev, nem, osztaly_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    $classIds = [];
    for ($classId = 1; $classId <= 12; $classId++) {
        for ($i = 0; $i < 15; $i++) {
            $classIds[] = $classId;
        }
    }

    foreach ($nevek as $index => $student) {
        $stmt->bind_param("ssi", $student['name'], $student['gender'], $classIds[$index % count($classIds)]);
        $stmt->execute();
    }
}

function insertGradesIntoDatabase($conn) {
    $checkSql = "SELECT COUNT(*) AS count FROM jegyek";
    $result = $conn->query($checkSql);
    $row = $result->fetch_assoc();

    if ($row['count'] > 0) {
        return;
    }

    // Fetch class years (evfolyam) for each class
    $classYears = [];
    $classSql = "SELECT id, evfolyam FROM osztalyok";
    $classResult = $conn->query($classSql);

    while ($classRow = $classResult->fetch_assoc()) {
        $classYears[$classRow['id']] = intval($classRow['evfolyam']); // Convert evfolyam to integer
    }

    $sql = "INSERT INTO jegyek (diak_id, tantargy_id, jegy, datum) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    $studentSql = "SELECT diak_id, osztaly_id FROM diakok";
    $studentResult = $conn->query($studentSql);

    while ($studentRow = $studentResult->fetch_assoc()) {
        $studentId = $studentRow['diak_id'];
        $classId = $studentRow['osztaly_id'];

        // Get the base year (evfolyam) for this student's class
        $baseYear = $classYears[$classId] ?? 2020; // Default to 2020 if not found

        // Randomly decide how many grade entries (3 to 5) for this student
        $jegyszam = rand(3, 5);

        for ($i = 1; $i <= $jegyszam; $i++) {
            for ($subjectId = 1; $subjectId <= 8; $subjectId++) { // Assuming 8 subjects from SUBJECTS
                $randomGrade = rand(1, 5);

                // Randomly choose between fall (Sep-Dec) or spring (Jan-Jun) semester
                $isFallSemester = rand(0, 1) === 0;

                if ($isFallSemester) {
                    // Fall semester: September to December of baseYear
                    $randomMonth = rand(9, 12);
                    $randomYear = $baseYear;
                } else {
                    // Spring semester: January to June of next year
                    $randomMonth = rand(1, 6);
                    $randomYear = $baseYear + 1;
                }

                // Generate a random day (1-28 to avoid month-end issues)
                $randomDay = rand(1, 28);
                $randomDate = sprintf("%d-%02d-%02d", $randomYear, $randomMonth, $randomDay);

                // Insert the grade
                $stmt->bind_param("iiis", $studentId, $subjectId, $randomGrade, $randomDate);
                $stmt->execute();
            }
        }
    }

    //echo "A jegyek sikeresen feltöltve az adatbázisba.<br>";
}

?>