<?php
session_start();
include('db_connection.php'); // Conexão com o banco de dados

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $novaSenha = trim($_POST['novaSenha']);
    $novaSenhaConfirmacao = trim($_POST['novaSenhaConfirmacao']);

    if (empty($username) || empty($novaSenha) || empty($novaSenhaConfirmacao)) {
        echo "Todos os campos são obrigatórios.";
        exit;
    }

    if ($novaSenha !== $novaSenhaConfirmacao) {
        echo "As senhas não conferem.";
        exit;
    }

    // Consultar o banco para verificar se o e-mail existe
    $query = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo "E-mail não encontrado.";
        exit;
    }

    // Atualizar senha na tabela Users
    $novaSenhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
    $updateUsersQuery = "UPDATE users SET password = ? WHERE username = ?";
    $stmt = $conn->prepare($updateUsersQuery);
    $stmt->bind_param("ss", $novaSenhaHash, $username);
    if (!$stmt->execute()) {
        echo "Erro ao atualizar a senha na tabela Users.";
        exit;
    }

    // Atualizar senha na tabela cadastro
    $updateCadastroQuery = "UPDATE cadastro SET password = ? WHERE username = ?";
    $stmt = $conn->prepare($updateCadastroQuery);
    $stmt->bind_param("ss", $novaSenhaHash, $username);
    if (!$stmt->execute()) {
        echo "Erro ao atualizar a senha na tabela cadastro.";
        exit;
    }
    echo "<script>alert('Senha redefinida com sucesso !');window.location.href = 'login.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cadastro - Confira PIX</title>
    <link rel="shortcut icon" href="img/site.png" type="image/x-icon">
    <link rel="stylesheet" href="style/resetar.css">
</head>
<body>
    <div class="nav-top">
        <a href="login.php"><img src="img/logo-site.png" class="logo"></a>
    </div>

    <div class="container">
        <div class="box">
    <p>Redefinir Senha</p>
    <form method="POST" action="">
        <label for="username">Usuário:</label>
        <input type="text" id="username" name="username" placeholder="Digite o E-mail do Cadastro " required><br>

        <label for="novaSenha">Nova Senha:</label>
        <input type="password" id="novaSenha" name="novaSenha" placeholder="Digite a Senha Nova" required><br>

        <label for="novaSenhaConfirmacao">Confirme a Nova Senha:</label>
        <input type="password" id="novaSenhaConfirmacao" placeholder="Confirme a Nova Senha" name="novaSenhaConfirmacao" required>

        <button type="submit">Redefinir Senha</button>
    </form>
    </div>
    </div>

    <div class="footer">
        <div class="footer-aling">
            <div class="text-footer">
                <a href="">Pagina Inical</a>
            </div>
            <div class="text-footer">
                <a href="#">Termos e Condições</a>
            </div>
            <div class="text-footer">
                <h4>Contato</h4>
                <a href="#">E-mail<br>Whatsapp</a>
            </div>
        </div>
        <footer>ConfiraPix - Copyright © 57.774.809 VICTOR FERNANDO DOS SANTOS</footer>
</body>
</html>
