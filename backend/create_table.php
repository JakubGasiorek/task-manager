<?php

include 'config.php';

$sql = "CREATE TABLE IF NOT EXISTS tasks (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(20) NOT NULL,
    description TEXT,
    status ENUM('pending', 'completed') DEFAULT 'pending',
    CREATED_AT TIMESTAMP DEFAULT CURRENT_TIMESTAMP

)";

try {
    $connect->exec($sql);
    echo "Task 'table creation' successfull";
} catch (PDOException $e) {
    echo "Error creating table failed: " . $e->getMessage();
}
