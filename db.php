<?php
// $servername = "localhost";
// $username = "root";
// $password = "MyNewPass";
// $dbname = "payment_management";

$servername = "10.130.20.98";
$username = "admin";
$password = "Citybank@2024";
$dbname = "visawd";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}
