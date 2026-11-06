<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;port=3306", "root", "");
    $pdo->exec("CREATE DATABASE IF NOT EXISTS hedefler_db");
    echo "Database created successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
