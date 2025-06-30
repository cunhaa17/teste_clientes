<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

header('Content-Type: application/json');
include_once '../includes/db_conexao.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['servico_id'])) {
    $servico_id = $conn->real_escape_string($_POST['servico_id']);
    $confirmar_com_reservas = isset($_POST['confirmar_com_reservas']) ? true : false;
    
    try {
        // Primeiro, buscar informações do serviço para deletar a imagem
        $sql_select = "SELECT imagem, nome FROM servico WHERE id = '$servico_id'";
        $result = $conn->query($sql_select);
        
        if ($result && $result->num_rows > 0) {
            $servico = $result->fetch_assoc();
            
            // Verificar se existem reservas associadas
            $check_sql = "SELECT COUNT(*) as count FROM Reserva WHERE servico_id = $servico_id";
            $check_result = $conn->query($check_sql);
            
            if ($check_result === false) {
                throw new Exception("Erro ao verificar reservas: " . $conn->error);
            }
            
            $row = $check_result->fetch_assoc();
            $has_reservas = $row['count'] > 0;

            // Verificar se existem subtipos associados
            $check_subtipos_sql = "SELECT id FROM servico_subtipo WHERE servico_id = $servico_id";
            $subtipos_result = $conn->query($check_subtipos_sql);
            $subtipo_ids = [];
            if ($subtipos_result && $subtipos_result->num_rows > 0) {
                while ($sub = $subtipos_result->fetch_assoc()) {
                    $subtipo_ids[] = $sub['id'];
                }
            }
            $has_subtipos = count($subtipo_ids) > 0;

            if (($has_reservas || $has_subtipos) && !$confirmar_com_reservas) {
                $_SESSION['error'] = "Não é possível excluir este serviço pois existem reservas ou subtipos associados a ele.";
            } else {
                // Se tem subtipos, excluir reservas e subtipos
                if ($has_subtipos && $confirmar_com_reservas) {
                    foreach ($subtipo_ids as $sub_id) {
                        // Excluir reservas do subtipo
                        $conn->query("DELETE FROM Reserva WHERE servico_subtipo_id = $sub_id");
                        // Excluir o subtipo
                        $conn->query("DELETE FROM servico_subtipo WHERE id = $sub_id");
                    }
                }
                // Se tem reservas do serviço, excluir
                if ($has_reservas && $confirmar_com_reservas) {
                    $conn->query("DELETE FROM Reserva WHERE servico_id = $servico_id");
                }
                
                // Excluir o serviço
                $delete_sql = "DELETE FROM servico WHERE id = $servico_id";
                $delete_result = $conn->query($delete_sql);
                
                if ($delete_result === false) {
                    throw new Exception("Erro ao excluir serviço: " . $conn->error);
                }
                
                if ($conn->affected_rows > 0) {
                    // Deletar a imagem se existir
                    if (!empty($servico['imagem']) && file_exists('../' . $servico['imagem'])) {
                        unlink('../' . $servico['imagem']);
                    }
                    
                    if (($has_reservas || $has_subtipos) && $confirmar_com_reservas) {
                        $_SESSION['success'] = "Serviço '{$servico['nome']}', subtipos e todas as reservas associadas excluídos com sucesso!";
                    } else {
                        $_SESSION['success'] = "Serviço '{$servico['nome']}' excluído com sucesso!";
                    }
                } else {
                    $_SESSION['error'] = "Erro ao excluir serviço.";
                }
            }
        } else {
            $_SESSION['error'] = "Serviço não encontrado.";
        }
    } catch (Exception $e) {
        $_SESSION['error'] = "Erro ao excluir serviço: " . $e->getMessage();
    }
    
    header('Location: servico.php');
    exit();
} else {
    header('Location: servico.php');
    exit();
}

$conn->close();
?>
