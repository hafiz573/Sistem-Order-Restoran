<?php
session_start();
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$query = "SELECT * FROM tables ORDER BY table_number";
$stmt = $db->prepare($query);
$stmt->execute();

while ($table = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $isOccupied = $table['status'] === 'occupied';
    $class = $isOccupied ? 'table-btn occupied' : 'table-btn';
    
    echo '<button class="' . $class . '" data-table="' . $table['table_number'] . '" ' . 
         ($isOccupied ? 'disabled' : 'onclick="selectTable(\'' . $table['table_number'] . '\')"') . '>
            ' . $table['table_number'] . '
            <br><small>Capacity: ' . $table['capacity'] . '</small>
          </button>';
}
?>