<?php
session_start();
include_once '../includes/db_conexao.php';

// Verifica se a sessão está iniciada corretamente
if (!isset($_SESSION['utilizador_id'])) {
    header("Location: ../login.php");
    exit();
}

// Verifica se o usuário é do tipo admin
if ($_SESSION['utilizador_tipo'] !== 'admin') {
    header("Location: ../index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $conn->real_escape_string($_POST['id']);
    $data_reserva = $conn->real_escape_string($_POST['data_reserva']);
    $status = $conn->real_escape_string($_POST['status']);
    $cliente_id = $conn->real_escape_string($_POST['cliente_id']);
    $servico_id = $conn->real_escape_string($_POST['servico_id']);
    $servico_subtipo_id = $conn->real_escape_string($_POST['servico_subtipo_id']);
    $funcionario_id = $conn->real_escape_string($_POST['funcionario_id']);
    $observacao = $conn->real_escape_string($_POST['observacao']);

    $sql = "UPDATE reserva SET data_reserva='$data_reserva', status='$status', cliente_id='$cliente_id', servico_id='$servico_id', servico_subtipo_id='$servico_subtipo_id', funcionario_id='$funcionario_id', observacao='$observacao' WHERE id='$id'";

    if ($conn->query($sql)) {
        $_SESSION['success'] = "Reserva atualizada com sucesso!";
    } else {
        $_SESSION['mensagem'] = "Erro ao atualizar reserva.";
    }
    header("Location: reservas.php");
    exit();
}
// ... restante do código para guardar reserva ...

