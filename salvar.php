<?php
session_start();

// Configuração para exibir erros no desenvolvimento (remova em produção)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    // Conexão com o banco de dados
    $conn = new mysqli("localhost", "root", "", "confirapix");

    // Processa o formulário ao enviar
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        // Sanitiza os valores recebidos do formulário
        $razaoSocial = $conn->real_escape_string($_POST['RazaoSocial'] ?? '');
        $cnpj = $conn->real_escape_string($_POST['CNPJ'] ?? '');
        $ie = $conn->real_escape_string($_POST['IE'] ?? null);
        $email = $conn->real_escape_string($_POST['Email'] ?? '');
        $telefone = $conn->real_escape_string($_POST['Telefone'] ?? '');
        $cep = $conn->real_escape_string($_POST['CEP'] ?? '');
        $rua = $conn->real_escape_string($_POST['Rua'] ?? '');
        $numero = $conn->real_escape_string($_POST['Numero'] ?? '');
        $bairro = $conn->real_escape_string($_POST['Bairro'] ?? '');
        $cidade = $conn->real_escape_string($_POST['Cidade'] ?? '');
        $estado = $conn->real_escape_string($_POST['Estado'] ?? '');
        $nomeResponsavel = $conn->real_escape_string($_POST['NomeResponsavel'] ?? '');
        $cpf = $conn->real_escape_string($_POST['CPF'] ?? '');
        $telefoneResponsavel = $conn->real_escape_string($_POST['TelefoneResponsavel'] ?? '');
        $username = $conn->real_escape_string($_POST['username'] ?? '');
        $password = password_hash($_POST['senha'], PASSWORD_DEFAULT);
        $bancoEscolhido = $conn->real_escape_string($_POST['BancoEscolhido'] ?? '');
        $diaVencimento = $conn->real_escape_string($_POST['DiaVencimento'] ?? '');

        // Inicia a transação
        $conn->begin_transaction();

        try {
            // Insere na tabela `cadastro`
            $stmt = $conn->prepare(
                "INSERT INTO cadastro 
                (RazaoSociacadastrol, CNPJ, IE, Email, Telefone, CEP, Rua, Numero, Bairro, Cidade, Estado, NomeResponsavel, CPF, TelefoneResponsavel, username, password, DiaVencimento, BancoEscolhido) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param(
                "ssssssssssssssssss", 
                $razaoSocial, $cnpj, $ie, $email, $telefone, $cep, $rua, $numero, $bairro, $cidade, $estado, 
                $nomeResponsavel, $cpf, $telefoneResponsavel, $username, $password, $diaVencimento, $bancoEscolhido
            );
            $stmt->execute();

            // Insere na tabela `users`
            $stmt = $conn->prepare(
                "INSERT INTO users (CNPJ, username, password, BancoEscolhido) 
                VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param("ssss", $cnpj, $username, $password, $bancoEscolhido);
            $stmt->execute();

            // Confirma a transação
            $conn->commit();
            echo "<script>alert('Cadastro Cirado com Sucesso !');window.location.href = 'login.php';</script>";
        } catch (Exception $e) {
            // Reverte a transação em caso de erro
            $conn->rollback();

            // Armazena os valores na sessão
            $_SESSION['form_data'] = $_POST;
            echo "Erro ao cadastrar: " . $e->getMessage();
        }
    }
} catch (Exception $e) {
    echo "Erro na conexão ou execução: " . $e->getMessage();
}
?>
