<?php
include '../includes/db_conexao.php';

$id = $_GET['id'];

// Ensure the ID is an integer to prevent SQL injection
if (filter_var($id, FILTER_VALIDATE_INT)) {
    $query = "DELETE FROM cliente WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id); // Bind the ID as an integer

    if ($stmt->execute()) {
        header('Location: clientes.php');
        exit();
    } else {
        echo "Erro ao eliminar cliente: " . mysqli_error($conn);
    }
} else {
    echo "ID inválido.";
}

$stmt->close();
$conn->close();
?>

