<?php
header('Content-Type: application/json');
require 'db_connection.php'; // Inclua a conexão com o banco de dados

if (isset($_GET['tipo'], $_GET['valor'])) {
    $tipo = $_GET['tipo'];
    $valor = $_GET['valor'];

    $campo = $tipo === 'CNPJ' ? 'CNPJ' : 'username';
    $sql = "SELECT COUNT(*) as total FROM cadastro WHERE $campo = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('s', $valor);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    echo json_encode(['existe' => $row['total'] > 0]);
    exit;
}

echo json_encode(['existe' => false]);
?>
