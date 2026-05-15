<?php
$pdo = new PDO(
  "mysql:host=localhost;dbname=netafnit;charset=utf8mb4",
  "nettafnitadmin",
  "",
  [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
  ]
);
