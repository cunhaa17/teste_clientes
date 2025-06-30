<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require('../includes/fpdf186/fpdf.php');
if (!isset($_SESSION['utilizador_id']) || ($_SESSION['utilizador_tipo'] !== 'admin' && $_SESSION['utilizador_tipo'] !== 'funcionario')) {
    die("Acesso negado.");
}
include_once '../includes/db_conexao.php';

class PDF extends FPDF {
    function Header() {
        $this->SetFont('Arial', 'B', 15);
        $this->Cell(0, 10, clean_text('Relatório de Horários'), 0, 1, 'C');
        $this->Ln(10);
    }
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, clean_text('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Obter os filtros da URL (enviados pela página de horários)
$funcionario_id = isset($_GET['funcionario_id']) ? $_GET['funcionario_id'] : '';
$data_filtro = isset($_GET['data']) ? $_GET['data'] : '';

// Construir a query usando as mesmas stored procedures que a página de horários
if (empty($funcionario_id) && empty($data_filtro)) {
    $sql = "CALL horarios()";
} else {
    if (($funcionario_id == 0 || empty($funcionario_id)) && !empty($data_filtro)) {
        $data_filtro = $conn->real_escape_string($data_filtro);
        $sql = "CALL horarios_data('" . $data_filtro . "')";
    } elseif (!empty($funcionario_id) && $funcionario_id != 0 && empty($data_filtro)) {
        $funcionario_id = $conn->real_escape_string($funcionario_id);
        $sql = "CALL horarios_funcionario(" . $funcionario_id . ")";
    } elseif (!empty($funcionario_id) && $funcionario_id != 0 && !empty($data_filtro)) {
        $funcionario_id = $conn->real_escape_string($funcionario_id);
        $data_filtro = $conn->real_escape_string($data_filtro);
        $sql = "CALL horarios_filtro(" . $funcionario_id . ", '" . $data_filtro . "')";
    } else {
        $sql = "CALL horarios()";
    }
}

$result = $conn->query($sql);
if (!$result) {
    die('Erro na consulta: ' . $conn->error . "\nSQL: " . $sql);
}
$horarios = $result->fetch_all(MYSQLI_ASSOC);

function clean_text($text) {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text ?? '');
}

$pdf = new PDF('L', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

$pdf->SetFont('Arial', 'B', 10);
$header = [clean_text('Funcionário'), clean_text('Dia'), clean_text('Manhã Início'), clean_text('Manhã Fim'), clean_text('Tarde Início'), clean_text('Tarde Fim')];
$w = [50, 30, 35, 35, 35, 35];
for($i = 0; $i < count($header); $i++) {
    $pdf->SetFillColor(230,230,230);
    $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
}
$pdf->Ln();
$pdf->SetFont('Arial', '', 9);
foreach($horarios as $horario) {
    $pdf->Cell($w[0], 6, clean_text($horario['nome']), 1, 0, '', false);
    $pdf->Cell($w[1], 6, clean_text($horario['dia']), 1, 0, '', false);
    $pdf->Cell($w[2], 6, clean_text($horario['manha_inicio']), 1, 0, '', false);
    $pdf->Cell($w[3], 6, clean_text($horario['manha_fim']), 1, 0, '', false);
    $pdf->Cell($w[4], 6, clean_text($horario['tarde_inicio']), 1, 0, '', false);
    $pdf->Cell($w[5], 6, clean_text($horario['tarde_fim']), 1, 1, '', false);
}
$conn->close();
$pdf->Output('I', 'relatorio_horarios.pdf');
exit();