<?php
$servername = "localhost";
$dbusername = "root";
$password = "";
$dbname = "moviesitedb";

try {
    $pdo = new PDO("mysql:host=$servername;dbname=$dbname", $dbusername, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}

