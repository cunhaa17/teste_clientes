<?php
// Configuração da conexão
$host = "localhost";
$user = "root";
$password = ""; // Use a senha configurada ou deixe vazio se não houver senha
$database = "lotus_spa";

// Criando a conexão
$conn = new mysqli($host, $user, $password, $database);

// Verificando a conexão
if ($conn->connect_error) {
    die("Falha na conexão: " . $conn->connect_error);
}


