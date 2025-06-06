<?php
// Incluindo o arquivo de conexão
require_once 'includes/db_conexao.php';


$funcionario_filtro= '  ';
$data_filtro = '';
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!isset($_GET['funcionario_id']) && !isset($_GET['data'])) {
    $sql = "CALL horarios ()";
  } else{
  
  if (($_GET['funcionario_id']==0) && ($_GET['data'])=='') {
    $sql = "CALL horarios ()";
  }
  if (($_GET['funcionario_id']==0) && ($_GET['data'])!='') {
    $data_filtro = $_GET['data'];
    $sql = "CALL horarios_data('" . $data_filtro . "')";
  }
  if (($_GET['funcionario_id']!=0) && ($_GET['data'])=='') {
    $funcionario_filtro = $_GET['funcionario_id'];
    $sql = "CALL horarios_funcionario(" . $funcionario_filtro . ")";
  }
  if (($_GET['funcionario_id']!=0) && ($_GET['data'])!='') {
    $funcionario_filtro = $_GET['funcionario_id'];
    $data_filtro = $_GET['data'];
    $sql = "CALL horarios_filtro(" . $funcionario_filtro . ", '" . $data_filtro . "')";
  }
}


/*
if (isset($_GET['funcionario_id']) && isset($_GET['data']) ){
  $funcionario_filtro = ($_GET['funcionario_id']);
  $data_filtro = ($_GET['data']);
  if ($funcionario_filtro == '' && $data_filtro == '') {
    $sql = "CALL horarios ()";
  } else {
    // Se ambos os filtros estiverem preenchidos, chama o procedimento com ambos os parâmetros
    $sql = "CALL horarios_filtro(" . $funcionario_filtro . ", '" . $data_filtro . "')";
  }
}elseif(isset($_GET['funcionario_id']))
{   
  $funcionario_filtro = ($_GET['funcionario_id']);
  if ($funcionario_filtro == '') {
    // Se o filtro de funcionário estiver vazio, chama o procedimento sem o filtro
    $sql = "CALL horarios ()";
  } else {
  $sql = "CALL horarios_funcionario(" . $funcionario_filtro . ")";
  }
} elseif (isset($_GET['data'])) {

  $data_filtro = ($_GET['data']);
  if ($data_filtro == '') {
    // Se o filtro de data estiver vazio, chama o procedimento sem o filtro
    $sql = "CALL horarios ()";
  } else {
   $sql = "CALL horarios_data('" . $data_filtro . "')";
  }
}else{
$sql= "CALL horarios ()";
}*/
}
// Filtro de funcionário
//$funcionario_filtro = 12;
$sql_funcionarios = "SELECT id, nome FROM funcionario ORDER BY nome";
$result_funcionarios = $conn->query($sql_funcionarios);
$funcionarios = $result_funcionarios->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bootstrap demo</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
  </head>
  <body>

   <div class="card mb-4">
        <div class="card-header">
            <h5>Filtros</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="teste.php" class="row g-3">
                <div class="col-md-6">
                    <label for="funcionario_id" class="form-label">Funcionário</label>
                    <select name="funcionario_id" id="funcionario_id" class="form-select">
                        <option value="0">Todos os funcionários</option>
                        <?php foreach ($funcionarios as $funcionario): ?>
                            <option value="<?php echo $funcionario['id']; ?>" <?php echo ($funcionario_filtro == $funcionario['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($funcionario['nome']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="data" class="form-label">Data</label>
                    <input type="date" <?php if (isset($_GET['data'])) {
                        echo 'value="' . htmlspecialchars($_GET['data']) . '"';
                    }?> name="data" id="data" class="form-control">
               </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">Filtrar</button>
                    <a href="teste.php" class="btn btn-secondary">Limpar</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
  <div class="card-header">
    Featured
  </div>
  <div class="card-body overflow-auto" style="max-height: 500px;">
    <div class="accordion accordion-flush" id="accordionFlushExample">
      <?php 
      // Criar o item de cada acordion
      $result = $conn->query($sql);
      $i= 1;
      if ($result->num_rows > 0) {  
        while($row = $result->fetch_assoc()) {
      ?>



        <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapse-<?php echo $i; ?>" aria-expanded="false" aria-controls="flush-collapse-<?php echo $i; ?>">
              <?php echo htmlspecialchars($row['nome']); ?> - <?php echo htmlspecialchars($row['dia']); ?>
            </button>
          </h2>
          <div id="flush-collapse-<?php echo $i++; ?>" class="accordion-collapse collapse" data-bs-parent="#accordionFlushExample">
            <div class="accordion-body">
           <div class="row">
                <div class="col-6">

                  <div class="card">
                      <h5 class="card-header">Manha</h5>
                      <div class="card-body">
                       <p> <b>Data de Inicio: </b> <?php echo htmlspecialchars($row['manha_inicio']); ?></p>
                        <p><b>Data de Fim: </b> <?php echo htmlspecialchars($row['manha_fim']); ?></p>
                      </div>
                    </div>
                </div>
                <div class="col-6">
                  <div class="card">
                      <h5 class="card-header">Tarde</h5>
                      <div class="card-body">
                       <p><b>Data de Inicio: </b> <?php echo htmlspecialchars($row['tarde_inicio']); ?></p>
                       <p><b>Data de Fim: </b> <?php echo htmlspecialchars($row['tarde_fim']); ?></p>
                      </div>
                    </div>
                </div>
            </div>
            
          </div>
          </div>
        </div>
<?php
     } }else {
      ?>
      <div class="accordion-item">
          <h2 class="accordion-header">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#flush-collapseOne" aria-expanded="false" aria-controls="flush-collapseOne">
              Nao existem registos
            </button>
          </h2>
          <div id="flush-collapseOne" class="accordion-collapse collapse" data-bs-parent="#accordionFlushExample">
            <div class="accordion-body">
              Não existem registos  <?php echo $sql; ?>.
            </div>
          </div>
        </div>
        <?php
      }
      ?>
  </div>
    </div>
</div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
  </body>
</html>