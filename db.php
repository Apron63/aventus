<?php

$dbHost = 'localhost';
$dbName = 'aventus';
$dbUsername = 'root';
$dbPassword = 'root';

try {
    $dbcon = new PDO("mysql:host=$dbHost;dbname=$dbName", $dbUsername, $dbPassword);
    $dbcon->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo 'Connection error: ' . $e->getMessage();
}