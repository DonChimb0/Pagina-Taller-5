<?php
// Configuración de conexión a la base de datos
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'veterinaria';

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Conexión fallida: " . $conn->connect_error);
}

// Cargar la clase CRUD
require_once 'class/Crud.php';
$crud = new Crud($conn);
?>
