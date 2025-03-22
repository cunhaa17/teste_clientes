<?php
session_start();

include 'includes/db_conexao.php'; // Arquivo que conecta ao banco de dados

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $senha = $_POST['senha'];

    // Verifica se o utilizador existe
    $sql = "SELECT * FROM utilizadores WHERE email = ? LIMIT 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $resultado = $stmt->get_result();

    if ($resultado->num_rows > 0) {
        $utilizador = $resultado->fetch_assoc();

        // Verifica a senha
        if (password_verify($senha, $utilizador['senha'])) {
            $_SESSION['utilizador_id'] = $utilizador['id'];
            $_SESSION['utilizador_nome'] = $utilizador['nome'];
            $_SESSION['utilizador_tipo'] = $utilizador['tipo'];

            // Redireciona para a dashboard
            header("Location: index.php");
            exit();
        }
    }

    // Mensagem de erro genérica
    $erro = "Uma das credenciais está errada.";
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { 
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            background-color: #f8f9fa; 
        }
        .login-container { 
            background: #fff; 
            padding: 40px; 
            border-radius: 10px; 
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1); 
            width: 100%;
            max-width: 500px;
        }
        .login-container h2 { 
            margin-bottom: 30px; 
        }
        .login-container .form-control { 
            margin-bottom: 20px; 
        }
        .login-container .btn { 
            width: 100%; 
            padding: 10px;
        }
        .login-container .erro { 
            color: red; 
            margin-bottom: 20px;
        }
    </style>
</head>
<body>

<div class="login-container">
    <h2>Login</h2>

    <?php if (isset($erro)) { echo "<p class='erro'>$erro</p>"; } ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="email" class="form-label">Email:</label>
            <input type="email" class="form-control" id="email" name="email" required>
        </div>
        <div class="mb-3">
            <label for="senha" class="form-label">Senha:</label>
            <input type="password" class="form-control" id="senha" name="senha" required>
        </div>
        <button type="submit" class="btn btn-primary">Entrar</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>