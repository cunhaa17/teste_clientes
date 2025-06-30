<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// PASSO 1: Incluir a biblioteca FPDF. 
// O caminho está ajustado para a "maneira simples" que escolheu.
require('../includes/fpdf186/fpdf.php'); // <-- VERIFIQUE SE O NOME DA PASTA 'fpdf186' ESTÁ CORRETO

// Verifica se o utilizador está autenticado e tem permissão
if (!isset($_SESSION['utilizador_id']) || ($_SESSION['utilizador_tipo'] !== 'admin' && $_SESSION['utilizador_tipo'] !== 'funcionario')) {
    die("Acesso negado.");
}

include_once '../includes/db_conexao.php';

// Classe personalizada para adicionar cabeçalho e rodapé ao PDF
class PDF extends FPDF
{
    // Cabeçalho da página
    function Header()
    {
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, 'Relatorio de Reservas', 0, 1, 'C'); // Título Centralizado
        $this->Ln(10); // Espaçamento após o título
    }

    // Rodapé da página
    function Footer()
    {
        $this->SetY(-15); // Posição a 1.5 cm do fim
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, 'Pagina ' . $this->PageNo() . '/{nb}', 0, 0, 'C'); // Número da página
    }
}

// --- Lógica Principal ---

// Obter os filtros da URL (enviados pela página de reservas)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$data_inicio = isset($_GET['data_inicio']) ? $_GET['data_inicio'] : '';
$data_fim = isset($_GET['data_fim']) ? $_GET['data_fim'] : '';
$view = isset($_GET['view']) ? $_GET['view'] : 'tabela';

// Construir a mesma query que usa na página de reservas para obter os dados filtrados
$sql = "SELECT r.*, c.nome as cliente_nome, s.nome as servico_nome, ss.nome as subtipo_nome, f.nome as funcionario_nome 
        FROM reserva r 
        JOIN cliente c ON r.cliente_id = c.id 
        JOIN servico s ON r.servico_id = s.id 
        JOIN servico_subtipo ss ON r.servico_subtipo_id = ss.id 
        JOIN funcionario f ON r.funcionario_id = f.id 
        WHERE 1=1";

if (!empty($search)) {
    $sql .= " AND (c.nome LIKE '%$search%' OR s.nome LIKE '%$search%' OR ss.nome LIKE '%$search%' OR f.nome LIKE '%$search%')";
}
if (!empty($status_filter)) {
    $sql .= " AND r.status = '" . $conn->real_escape_string($status_filter) . "'";
}
if (!empty($data_inicio)) {
    $sql .= " AND r.data_reserva >= '" . $conn->real_escape_string($data_inicio) . " 00:00:00'";
}
if (!empty($data_fim)) {
    $sql .= " AND r.data_reserva <= '" . $conn->real_escape_string($data_fim) . " 23:59:59'";
}
$sql .= " ORDER BY r.data_reserva ASC";

$result = $conn->query($sql);
$reservas = $result->fetch_all(MYSQLI_ASSOC);

// Função para converter texto para um formato que o FPDF entende (evita erros com acentos)
function clean_text($text) {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text ?? '');
}

// Cria uma instância do PDF. 'L' para paisagem (deitado), 'P' para retrato (em pé)
$pdf = new PDF('L', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);


// Decide qual formato de PDF gerar com base na "view"
if ($view === 'tabela') {
    // --- GERAÇÃO DO PDF EM FORMATO DE TABELA ---
    $pdf->SetFont('Arial', 'B', 10);
    $header = ['Data', 'Cliente', 'Servico', 'Subtipo', 'Funcionario', 'Status'];
    $w = [35, 50, 45, 45, 50, 25]; // Larguras das colunas

    // Desenha o cabeçalho da tabela
    for($i = 0; $i < count($header); $i++) {
        $pdf->Cell($w[$i], 7, clean_text($header[$i]), 1, 0, 'C');
    }
    $pdf->Ln();

    // Preenche os dados da tabela
    $pdf->SetFont('Arial', '', 9);
    foreach($reservas as $reserva) {
        $pdf->Cell($w[0], 6, date('d/m/Y H:i', strtotime($reserva['data_reserva'])), 1, 0, '');
        $pdf->Cell($w[1], 6, clean_text($reserva['cliente_nome']), 1, 0, '');
        $pdf->Cell($w[2], 6, clean_text($reserva['servico_nome']), 1, 0, '');
        $pdf->Cell($w[3], 6, clean_text($reserva['subtipo_nome']), 1, 0, '');
        $pdf->Cell($w[4], 6, clean_text($reserva['funcionario_nome']), 1, 0, '');
        $pdf->Cell($w[5], 6, clean_text(ucfirst($reserva['status'])), 1, 1, '');
    }
} else {
    // --- GERAÇÃO DO PDF EM FORMATO DE LISTA (PARA A VIEW "CALENDÁRIO") ---
    $pdf->SetFont('Arial', 'B', 12);
    $pdf->Cell(0, 10, clean_text('Agenda de Reservas'), 0, 1, 'L');
    $pdf->Ln(5);

    $current_day = '';
    foreach($reservas as $reserva) {
        $day = date('d/m/Y', strtotime($reserva['data_reserva']));
        if ($day !== $current_day) {
            if ($current_day !== '') $pdf->Ln(8);
            $pdf->SetFont('Arial', 'B', 11);
            $pdf->Cell(0, 8, "Reservas para " . $day, 'B', 1, 'L');
            $pdf->Ln(2);
            $current_day = $day;
        }
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(30, 6, date('H:i', strtotime($reserva['data_reserva'])), 0, 0, '');
        $pdf->SetFont('Arial', '', 10);
        $pdf->MultiCell(0, 6, clean_text(
            $reserva['cliente_nome'] . " - " . $reserva['servico_nome'] . 
            " (" . $reserva['subtipo_nome'] . ")\n" .
            "Funcionario: " . $reserva['funcionario_nome'] . " | Status: " . ucfirst($reserva['status'])
        ), 0, 'L');
        $pdf->Ln(2);
    }
}

$conn->close();
// Envia o PDF para o browser. 'I' mostra o PDF, 'D' força o download.
$pdf->Output('I', 'relatorio_reservas.pdf');
exit();