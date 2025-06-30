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
        $this->Cell(0, 10, clean_text('Relatório de Subserviços'), 0, 1, 'C');
        $this->Ln(10);
    }
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, clean_text('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sql = "SELECT ss.id, s.nome as servico_nome, ss.nome as subtipo_nome, ss.descricao, ss.preco, ss.duracao FROM servico_subtipo ss JOIN servico s ON ss.servico_id = s.id WHERE 1=1";
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $sql .= " AND (s.nome LIKE '%$search%' OR ss.nome LIKE '%$search%' OR ss.descricao LIKE '%$search%')";
}
$sql .= " ORDER BY s.nome ASC, ss.nome ASC";
$result = $conn->query($sql);
$subservicos = $result->fetch_all(MYSQLI_ASSOC);

function clean_text($text) {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text ?? '');
}

$pdf = new PDF('L', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

$pdf->SetFont('Arial', 'B', 10);
$header = [clean_text('Serviço'), clean_text('Subtipo'), clean_text('Descrição'), clean_text('Preço'), clean_text('Duração (min)')];
$w = [50, 50, 80, 30, 30];
for($i = 0; $i < count($header); $i++) {
    $pdf->SetFillColor(230,230,230);
    $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
}
$pdf->Ln();
$pdf->SetFont('Arial', '', 9);
foreach($subservicos as $sub) {
    $pdf->Cell($w[0], 6, clean_text($sub['servico_nome']), 1, 0, '', false);
    $pdf->Cell($w[1], 6, clean_text($sub['subtipo_nome']), 1, 0, '', false);
    $pdf->Cell($w[2], 6, clean_text($sub['descricao']), 1, 0, '', false);
    $pdf->Cell($w[3], 6, isset($sub['preco']) ? clean_text(number_format($sub['preco'], 2, ',', '.') . ' MZN') : '', 1, 0, '', false);
    $pdf->Cell($w[4], 6, isset($sub['duracao']) ? clean_text($sub['duracao']) : '', 1, 1, '', false);
}
$conn->close();
$pdf->Output('I', 'relatorio_servicos.pdf');
exit(); 