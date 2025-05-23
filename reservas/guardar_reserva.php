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
    $id = intval($_POST['id']);
    $data_reserva = $_POST['data_reserva'];
    $status = $_POST['status'];
    $cliente_id = $_POST['cliente_id'];
    $servico_id = $_POST['servico_id'];
    $servico_subtipo_id = $_POST['servico_subtipo_id'];
    $funcionario_id = $_POST['funcionario_id'];
    $observacao = $_POST['observacao'];

    $stmt = $conn->prepare("UPDATE reserva SET data_reserva=?, status=?, cliente_id=?, servico_id=?, servico_subtipo_id=?, funcionario_id=?, observacao=? WHERE id=?");
    $stmt->bind_param("ssiiiisi", $data_reserva, $status, $cliente_id, $servico_id, $servico_subtipo_id, $funcionario_id, $observacao, $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "Reserva atualizada com sucesso!";
    } else {
        $_SESSION['mensagem'] = "Erro ao atualizar reserva.";
    }
    $stmt->close();
    header("Location: reservas.php");
    exit();
    }
// ... restante do código para guardar reserva ...

