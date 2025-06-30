<?php
session_start();

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

include_once '../includes/db_conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subservico_id'])) {
    $subservico_id = $conn->real_escape_string($_POST['subservico_id']);
    $confirmar_com_reservas = isset($_POST['confirmar_com_reservas']) ? true : false;
    
    try {
        // Primeiro, buscar informações do subtipo para deletar a imagem
        $sql_select = "SELECT imagem, nome FROM servico_subtipo WHERE id = '$subservico_id'";
        $result = $conn->query($sql_select);
        
        if ($result && $result->num_rows > 0) {
            $subtipo = $result->fetch_assoc();
            
            // Verificar se existem reservas associadas
            $check_sql = "SELECT COUNT(*) as count FROM Reserva WHERE servico_subtipo_id = $subservico_id";
            $check_result = $conn->query($check_sql);
            
            if ($check_result === false) {
                throw new Exception("Erro ao verificar reservas: " . $conn->error);
            }
            
            $row = $check_result->fetch_assoc();
            $has_reservas = $row['count'] > 0;
            
            if ($has_reservas && !$confirmar_com_reservas) {
                // Se tem reservas e não foi confirmado, retornar erro
                $_SESSION['error'] = "Este subtipo tem reservas associadas. Confirme a eliminação para prosseguir.";
                header('Location: servico.php');
                exit();
            }
            
            // Se tem reservas e confirmou, deletar reservas antes
            if ($has_reservas && $confirmar_com_reservas) {
                $delete_reservas_sql = "DELETE FROM Reserva WHERE servico_subtipo_id = $subservico_id";
                $conn->query($delete_reservas_sql);
            }
            // Excluir o subtipo (com ou sem reservas)
            $delete_sql = "DELETE FROM servico_subtipo WHERE id = $subservico_id";
            $delete_result = $conn->query($delete_sql);
            
            if ($delete_result === false) {
                throw new Exception("Erro ao excluir subtipo: " . $conn->error);
            }
            
            if ($conn->affected_rows > 0) {
                // Deletar a imagem se existir
                if (!empty($subtipo['imagem']) && file_exists('../' . $subtipo['imagem'])) {
                    unlink('../' . $subtipo['imagem']);
                }
                
                if ($has_reservas && $confirmar_com_reservas) {
                    $_SESSION['success'] = "Subtipo '{$subtipo['nome']}' e todas as reservas associadas excluídos com sucesso!";
                } else {
                    $_SESSION['success'] = "Subtipo '{$subtipo['nome']}' excluído com sucesso!";
                }
            } else {
                $_SESSION['error'] = "Erro ao excluir subtipo.";
            }
        } else {
            $_SESSION['error'] = "Subtipo não encontrado.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Erro ao excluir subtipo: " . $e->getMessage();
    }
    
    header('Location: servico.php');
    exit();
} else {
    header('Location: servico.php');
    exit();
}

$conn->close();
?> 