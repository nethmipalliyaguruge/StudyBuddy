<?php
// config/config.php

// DB credentials
const DB_HOST = '127.0.0.1';
const DB_NAME = 'studyhub_db';
const DB_USER = 'root';
const DB_PASS = ''; // XAMPP default

// Start session (guarded to avoid warnings)
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

try {
  $pdo = new PDO(
    'mysql:host='.DB_HOST.';dbname='.DB_NAME.';charset=utf8mb4',
    DB_USER, DB_PASS,
    [
      PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
  );
} catch (PDOException $e) {
  die('DB connection failed: ' . $e->getMessage());
}

