<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tipo = $_POST['tipo'] ?? '';
    $funcionario_id = $_POST['funcionario_id'] ?? '';
    $errors = [];

    // Validação básica
    if (empty($funcionario_id)) {
        $errors[] = "É necessário selecionar um funcionário.";
    }

    switch ($tipo) {
        case 'normal':
            // Horário normal (semanal)
            $data_inicio = $_POST['data_inicio'] ?? '';
            $data_fim = $_POST['data_fim'] ?? '';
            $hora_entrada_manha = $_POST['hora_entrada_manha'] ?? '';
            $hora_saida_manha = $_POST['hora_saida_manha'] ?? '';
            $hora_entrada_tarde = $_POST['hora_entrada_tarde'] ?? '';
            $hora_saida_tarde = $_POST['hora_saida_tarde'] ?? '';
            $dias_semana = $_POST['dias_semana'] ?? [];

            if (empty($data_inicio) || empty($data_fim)) {
                $errors[] = "As datas de início e fim são obrigatórias.";
            }
            if (empty($hora_entrada_manha) || empty($hora_saida_manha)) {
                $errors[] = "Os horários da manhã são obrigatórios.";
            }
            if (empty($dias_semana)) {
                $errors[] = "Selecione pelo menos um dia da semana.";
            }

            if (empty($errors)) {
                $conn->begin_transaction();
                try {
                    // Remover horários existentes no período
                    $delete_sql = "DELETE FROM agenda_funcionario 
                                 WHERE funcionario_id = ? 
                                 AND data_inicio >= ? 
                                 AND data_inicio <= ?";
                    $delete_stmt = $conn->prepare($delete_sql);
                    $delete_stmt->bind_param("iss", $funcionario_id, $data_inicio, $data_fim);
                    $delete_stmt->execute();
                    $delete_stmt->close();

                    // Inserir novos horários
                    $insert_sql = "INSERT INTO agenda_funcionario (funcionario_id, data_inicio, data_fim) VALUES (?, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_sql);

                    $inicio = new DateTime($data_inicio);
                    $fim = new DateTime($data_fim);
                    $fim->modify('+1 day'); // Para incluir o último dia
                    
                    $interval = new DateInterval('P1D');
                    $periodo = new DatePeriod($inicio, $interval, $fim);

                    foreach ($periodo as $dt) {
                        $dia_semana = $dt->format('N'); // 1 (Segunda) a 7 (Domingo)
                        
                        if (in_array($dia_semana, $dias_semana)) {
                            $data_atual = $dt->format('Y-m-d');
                            
                            // Horário da manhã
                            $data_inicio_manha = $data_atual . ' ' . $hora_entrada_manha;
                            $data_fim_manha = $data_atual . ' ' . $hora_saida_manha;
                            
                            $insert_stmt->bind_param("iss", $funcionario_id, $data_inicio_manha, $data_fim_manha);
                            $insert_stmt->execute();
                            
                            // Horário da tarde (se definido)
                            if (!empty($hora_entrada_tarde) && !empty($hora_saida_tarde)) {
                                $data_inicio_tarde = $data_atual . ' ' . $hora_entrada_tarde;
                                $data_fim_tarde = $data_atual . ' ' . $hora_saida_tarde;
                                
                                $insert_stmt->bind_param("iss", $funcionario_id, $data_inicio_tarde, $data_fim_tarde);
                                $insert_stmt->execute();
                            }
                        }
                    }
                    
                    $insert_stmt->close();
                    $conn->commit();
                    
                    $_SESSION['success'] = "Horário normal salvo com sucesso!";
                    header("Location: horarios.php");
                    exit();
                } catch (Exception $e) {
                    $conn->rollback();
                    $errors[] = "Erro ao salvar horário: " . $e->getMessage();
                }
            }
            break;

        case 'especifico':
            // Horário específico (data específica)
            $data_especifica = $_POST['data_especifica'] ?? '';
            $hora_inicio_especifica = $_POST['hora_inicio_especifica'] ?? '';
            $hora_fim_especifica = $_POST['hora_fim_especifica'] ?? '';

            if (empty($data_especifica)) {
                $errors[] = "Data específica é obrigatória.";
            }
            if (empty($hora_inicio_especifica) || empty($hora_fim_especifica)) {
                $errors[] = "Horário de início e fim são obrigatórios.";
            }

            if (empty($errors)) {
                $data_inicio = $data_especifica . ' ' . $hora_inicio_especifica;
                $data_fim = $data_especifica . ' ' . $hora_fim_especifica;

                $insert_sql = "INSERT INTO agenda_funcionario (funcionario_id, data_inicio, data_fim) VALUES (?, ?, ?)";
                $insert_stmt = $conn->prepare($insert_sql);
                
                if ($insert_stmt === false) {
                    throw new Exception("Erro ao preparar a query: " . $conn->error);
                }
                
                $insert_stmt->bind_param("iss", $funcionario_id, $data_inicio, $data_fim);
                
                if ($insert_stmt->execute()) {
                    $_SESSION['success'] = "Horário específico salvo com sucesso!";
                    header("Location: horarios.php");
                    exit();
                } else {
                    $errors[] = "Erro ao salvar horário específico: " . $insert_stmt->error;
                }
                $insert_stmt->close();
            }
            break;

        case 'folga':
            // Folga/Ausência
            $data_folga = $_POST['data_folga'] ?? '';
            $motivo = $_POST['motivo'] ?? '';

            if (empty($data_folga)) {
                $errors[] = "Data da folga é obrigatória.";
            }

            if (empty($errors)) {
                // Verificar se já existem horários para este dia
                $check_sql = "SELECT id FROM agenda_funcionario 
                             WHERE funcionario_id = ? 
                             AND DATE(data_inicio) = ?";
                $check_stmt = $conn->prepare($check_sql);
                $check_stmt->bind_param("is", $funcionario_id, $data_folga);
                $check_stmt->execute();
                
                if ($check_stmt->get_result()->num_rows > 0) {
                    $errors[] = "Já existem horários cadastrados para esta data. Remova-os primeiro antes de adicionar uma folga.";
                } else {
                    $insert_sql = "INSERT INTO agenda_funcionario (funcionario_id, data_inicio, data_fim, motivo) 
                                  VALUES (?, ?, ?, ?)";
                    $insert_stmt = $conn->prepare($insert_sql);
                    $data_folga_inicio = $data_folga . ' 00:00:00';
                    $data_folga_fim = $data_folga . ' 23:59:59';
                    $insert_stmt->bind_param("isss", $funcionario_id, $data_folga_inicio, $data_folga_fim, $motivo);
                    
                    if ($insert_stmt->execute()) {
                        $_SESSION['success'] = "Folga registrada com sucesso!";
                        header("Location: horarios.php");
                        exit();
                    } else {
                        $errors[] = "Erro ao registrar folga: " . $conn->error;
                    }
                    $insert_stmt->close();
                }
                $check_stmt->close();
            }
            break;

        default:
            $errors[] = "Tipo de horário inválido.";
    }

    if (!empty($errors)) {
        $_SESSION['mensagem'] = implode("<br>", $errors);
        header("Location: adicionar_horario.php");
        exit();
    }
}

// Se chegou aqui, redireciona para a página de adicionar horário
header("Location: adicionar_horario.php");
exit();
?> 