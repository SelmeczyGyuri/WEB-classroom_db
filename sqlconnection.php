<?php

require_once "insertdata.php";
require_once "config.php";

echo "  <form method='POST'>
            <button type='submit' name='install'>Mentés/Letöltés/Nem tudom</button>
        </form>";

if (isset($_POST["install"])) {
    main();
}


function create_database($conn){
    $sql = 'CREATE DATABASE IF NOT EXISTS naplo
            CHARACTER SET utf8 
            COLLATE utf8_general_ci;';
    if ($conn->query($sql) === TRUE) {
        /*echo "<br> Database created successfully or already exists";*/
    } else {
        /*echo "Error creating database: " . $conn->error;*/
    }
}


function osztalyok($conn){
    $sql = 'CREATE TABLE IF NOT EXISTS osztalyok(
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(3) NOT NULL,
        evfolyam VARCHAR(9) NOT NULL
    )';

    if ($conn->query($sql) === TRUE) {
        /*echo "<br>Table created successfully: osztalyok";*/
    } else {
        /*echo "<br>Error creating table: " . $conn->error;*/
    }
}

function diakok($conn){
    $sql = 'CREATE TABLE IF NOT EXISTS diakok(
        diak_id INT AUTO_INCREMENT PRIMARY KEY,
        osztaly_id INT,
        FOREIGN KEY (osztaly_id) REFERENCES osztalyok(id),
        nev VARCHAR(40) NOT NULL,
        nem VARCHAR(40)
    )';

    if ($conn->query($sql) === TRUE) {
        /*echo "<br>Table created successfully: diakok";*/
    } else {
        /*echo "<br>Error creating table: " . $conn->error;*/
    }
}

function tantargyak($conn){
    $sql = 'CREATE TABLE IF NOT EXISTS tantargyak(
        tantargy_id INT AUTO_INCREMENT PRIMARY KEY,
        tantargy VARCHAR(40) NOT NULL
    )';

    if ($conn->query($sql) === TRUE) {
        /*echo "<br>Table created successfully: tantargyak";*/
    } else {
        /*echo "<br>Error creating table: " . $conn->error;*/
    }
}

function jegyek($conn){
    $sql = 'CREATE TABLE IF NOT EXISTS jegyek(
        osztalyzat_id INT AUTO_INCREMENT PRIMARY KEY,
        diak_id INT,
        tantargy_id INT,
        FOREIGN KEY (tantargy_id) REFERENCES tantargyak(tantargy_id),
        jegy TINYINT,
        datum DATE NOT NULL
    )';

    if ($conn->query($sql) === TRUE) {
        /*echo "<br>Table created successfully: jegyek<br>";*/
    } else {
        /*echo "<br>Error creating table: " . $conn->error;*/
    }
}

function main() {
    $servername = "localhost";
    $username = "root";
    $password = "";
    $dbname = "naplo";

    $conn = new mysqli($servername, $username, $password);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    /*echo "Connected successfully";*/
    create_database($conn);
    $conn = new mysqli($servername, $username, $password, $dbname);

    osztalyok($conn);
    diakok($conn);
    tantargyak($conn);
    jegyek($conn);
    insertStudentsIntoDatabase(getName());
    insertSubjectsIntoDatabase($conn);
    insertClassesIntoDatabase($conn);
    insertGradesIntoDatabase($conn);
    $conn->close();

}



?>