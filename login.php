<?php
session_start();

include 'includes/db_conexao.php'; // Arquivo que conecta ao banco de dados

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $conn->real_escape_string($_POST['email']);
    $senha = $_POST['senha'];

    // Verifica se o utilizador existe
    $sql = "SELECT * FROM adms WHERE email = '$email' LIMIT 1";
    $resultado = $conn->query($sql);

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
    <title>Login - LotusSPA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        :root {
            --primary: #A36A07;
            --primary-dark: #805306;
            --primary-light: #FFB52E;
            --secondary: #FFF8EC;
            --dark: #2C1810;
            --light: #FFF8EC;
            --accent: #D4973D;
            --tertiary: #62816C;
            --quaternary: #8BA893;
        }

        body { 
            font-family: 'Inter', sans-serif;
            display: flex; 
            justify-content: center; 
            align-items: center; 
            min-height: 100vh; 
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.3)),
                        url('../goncalo_pap/site_pap/images/about-us.jpg');
            background-size: cover;
            background-position: center;
            padding: 20px;
        }

        .login-container { 
            background: rgba(255, 255, 255, 0.95);
            padding: 40px; 
            border-radius: 24px; 
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2); 
            width: 100%;
            max-width: 500px;
            backdrop-filter: blur(10px);
        }

        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-header img {
            height: 60px;
            margin-bottom: 20px;
        }

        .login-header h2 {
            color: var(--dark);
            font-weight: 700;
            margin-bottom: 10px;
        }

        .login-header p {
            color: var(--tertiary);
            font-size: 1.1rem;
        }

        .form-control {
            border: 2px solid var(--secondary);
            border-radius: 12px;
            padding: 12px 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(163, 106, 7, 0.25);
        }

        .form-label {
            color: var(--dark);
            font-weight: 500;
            margin-bottom: 8px;
        }

        .btn-primary {
            background: var(--primary);
            border: none;
            border-radius: 12px;
            padding: 12px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        .erro {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            padding: 12px;
            border-radius: 12px;
            margin-bottom: 20px;
            font-weight: 500;
            text-align: center;
        }

        .form-floating {
            margin-bottom: 20px;
        }

        .form-floating > .form-control {
            padding: 1rem 0.75rem;
        }

        .form-floating > label {
            padding: 1rem 0.75rem;
        }
    </style>
</head>
<body>

<div class="login-container">
    <div class="login-header">
        <img src="../goncalo_pap/site_pap/images/logo.svg" alt="LotusSPA Logo">
        <h2>Bem-vindo de volta</h2>
        <p>Acesse sua conta para continuar</p>
    </div>

    <?php if (isset($erro)) { echo "<div class='erro'><i class='bi bi-exclamation-circle me-2'></i>$erro</div>"; } ?>

    <form method="POST" action="">
        <div class="form-floating mb-3">
            <input type="email" class="form-control" id="email" name="email" placeholder="name@example.com" required>
            <label for="email">Email</label>
        </div>
        <div class="form-floating mb-4">
            <input type="password" class="form-control" id="senha" name="senha" placeholder="Password" required>
            <label for="senha">Senha</label>
        </div>
        <button type="submit" class="btn btn-primary w-100">Entrar</button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>