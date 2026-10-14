<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$servername = getenv('HOLOVERSE_DB_HOST') ?: 'localhost';
$username = getenv('HOLOVERSE_DB_USER') ?: 'root';
$password = getenv('HOLOVERSE_DB_PASSWORD') ?: '';
$dbname = getenv('HOLOVERSE_DB_NAME') ?: 'holoverse';
$port = (int) (getenv('HOLOVERSE_DB_PORT') ?: 3306);

$conn = new mysqli($servername, $username, $password, $dbname, $port);
$conn->set_charset('utf8mb4');
