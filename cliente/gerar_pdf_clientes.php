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
        $this->Cell(0, 10, clean_text('Relatório de Clientes'), 0, 1, 'C');
        $this->Ln(10);
    }
    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->Cell(0, 10, clean_text('Página ') . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Obter os filtros da URL (enviados pela página de clientes)
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$colunas_selecionadas = isset($_GET['colunas']) 
    ? (is_array($_GET['colunas']) ? $_GET['colunas'] : explode(',', $_GET['colunas'])) 
    : ['nome', 'email', 'telefone'];
$colunas_permitidas = ['id', 'nome', 'email', 'telefone'];
$colunas_selecionadas = array_intersect($colunas_selecionadas, $colunas_permitidas);

if (empty($colunas_selecionadas)) {
    $colunas_selecionadas = ['nome', 'email', 'telefone'];
}

// Construir a mesma query que usa na página de clientes para obter os dados filtrados
$sql = "SELECT id, " . implode(", ", $colunas_selecionadas) . " FROM cliente WHERE 1=1";
if (!empty($search)) {
    $search = $conn->real_escape_string($search);
    $sql .= " AND (nome LIKE '%$search%' OR email LIKE '%$search%' OR telefone LIKE '%$search%')";
}
$sql .= " ORDER BY nome ASC";
$result = $conn->query($sql);
$clientes = $result->fetch_all(MYSQLI_ASSOC);

function clean_text($text) {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text ?? '');
}

$pdf = new PDF('L', 'mm', 'A4');
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// Definir cabeçalhos e larguras baseados nas colunas selecionadas
$header = [];
$w = [];
$total_width = 0;

foreach($colunas_selecionadas as $coluna) {
    switch($coluna) {
        case 'nome':
            $header[] = clean_text('Nome');
            $w[] = 60;
            break;
        case 'email':
            $header[] = clean_text('Email');
            $w[] = 80;
            break;
        case 'telefone':
            $header[] = clean_text('Telefone');
            $w[] = 40;
            break;
        default:
            $header[] = clean_text(ucfirst($coluna));
            $w[] = 40;
    }
    $total_width += end($w);
}

// Adicionar coluna de data de registo se não estiver nas colunas selecionadas
if (!in_array('data_registo', $colunas_selecionadas)) {
    $header[] = clean_text('Data de Registo');
    $w[] = 60;
    $total_width += 60;
}

$pdf->SetFont('Arial', 'B', 10);
for($i = 0; $i < count($header); $i++) {
    $pdf->SetFillColor(230,230,230);
    $pdf->Cell($w[$i], 7, $header[$i], 1, 0, 'C', true);
}
$pdf->Ln();
$pdf->SetFont('Arial', '', 9);

foreach($clientes as $cliente) {
    foreach($colunas_selecionadas as $index => $coluna) {
        $pdf->Cell($w[$index], 6, clean_text($cliente[$coluna]), 1, 0, '', false);
    }
    
    // Adicionar data de registo se não estiver nas colunas selecionadas
    if (!in_array('data_registo', $colunas_selecionadas)) {
        $pdf->Cell($w[count($colunas_selecionadas)], 6, isset($cliente['data_registo']) ? clean_text(date('d/m/Y', strtotime($cliente['data_registo']))) : '', 1, 0, '', false);
    }
    $pdf->Ln();
}

$conn->close();
$pdf->Output('I', 'relatorio_clientes.pdf');
exit(); 