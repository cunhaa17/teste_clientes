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
                    // Escapa os valores para evitar SQL injection
                    $funcionario_id = $conn->real_escape_string($funcionario_id);
                    $data_inicio = $conn->real_escape_string($data_inicio);
                    $data_fim = $conn->real_escape_string($data_fim);

                    // Remover horários existentes no período
                    $delete_sql = "DELETE FROM agenda_funcionario 
                                 WHERE funcionario_id = '$funcionario_id' 
                                 AND data_inicio >= '$data_inicio' 
                                 AND data_inicio <= '$data_fim'";
                    $conn->query($delete_sql);

                    // Inserir novos horários
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
                            
                            $data_inicio_manha = $conn->real_escape_string($data_inicio_manha);
                            $data_fim_manha = $conn->real_escape_string($data_fim_manha);
                            
                            $insert_sql = "INSERT INTO agenda_funcionario (funcionario_id, data_inicio, data_fim) 
                                         VALUES ('$funcionario_id', '$data_inicio_manha', '$data_fim_manha')";
                            $conn->query($insert_sql);
                            
                            // Horário da tarde (se definido)
                            if (!empty($hora_entrada_tarde) && !empty($hora_saida_tarde)) {
                                $data_inicio_tarde = $data_atual . ' ' . $hora_entrada_tarde;
                                $data_fim_tarde = $data_atual . ' ' . $hora_saida_tarde;
                                
                                $data_inicio_tarde = $conn->real_escape_string($data_inicio_tarde);
                                $data_fim_tarde = $conn->real_escape_string($data_fim_tarde);
                                
                                $insert_sql = "INSERT INTO agenda_funcionario (funcionario_id, data_inicio, data_fim) 
                                             VALUES ('$funcionario_id', '$data_inicio_tarde', '$data_fim_tarde')";
                                $conn->query($insert_sql);
                            }
                        }
                    }
                    
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

                // Escapa os valores
                $funcionario_id = $conn->real_escape_string($funcionario_id);
                $data_inicio = $conn->real_escape_string($data_inicio);
                $data_fim = $conn->real_escape_string($data_fim);

                $insert_sql = "INSERT INTO agenda_funcionario (funcionario_id, data_inicio, data_fim) 
                              VALUES ('$funcionario_id', '$data_inicio', '$data_fim')";
                
                if ($conn->query($insert_sql)) {
                    $_SESSION['success'] = "Horário específico salvo com sucesso!";
                    header("Location: horarios.php");
                    exit();
                } else {
                    $errors[] = "Erro ao salvar horário específico: " . $conn->error;
                }
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
                // Escapa os valores
                $funcionario_id = $conn->real_escape_string($funcionario_id);
                $data_folga = $conn->real_escape_string($data_folga);
                $motivo = $conn->real_escape_string($motivo);

                // Verificar se já existem horários para este dia
                $check_sql = "SELECT id FROM agenda_funcionario 
                             WHERE funcionario_id = '$funcionario_id' 
                             AND DATE(data_inicio) = '$data_folga'";
                $check_result = $conn->query($check_sql);
                
                if ($check_result->num_rows > 0) {
                    $errors[] = "Já existem horários cadastrados para esta data. Remova-os primeiro antes de adicionar uma folga.";
                } else {
                    $data_folga_inicio = $data_folga . ' 00:00:00';
                    $data_folga_fim = $data_folga . ' 23:59:59';
                    
                    $data_folga_inicio = $conn->real_escape_string($data_folga_inicio);
                    $data_folga_fim = $conn->real_escape_string($data_folga_fim);
                    
                    $insert_sql = "INSERT INTO agenda_funcionario (funcionario_id, data_inicio, data_fim, motivo) 
                                  VALUES ('$funcionario_id', '$data_folga_inicio', '$data_folga_fim', '$motivo')";
                    
                    if ($conn->query($insert_sql)) {
                        $_SESSION['success'] = "Folga registrada com sucesso!";
                        header("Location: horarios.php");
                        exit();
                    } else {
                        $errors[] = "Erro ao registrar folga: " . $conn->error;
                    }
                }
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