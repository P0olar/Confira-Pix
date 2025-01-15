<?php
session_start();

if (!isset($_SESSION['login'])) {
    echo "Você precisa estar logado para ver os boletos.";
    exit;
}

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "confirapix";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Erro de conexão: " . $conn->connect_error);
}

$login = $_SESSION['login'];

$sql = "SELECT CNPJ FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$CNPJ = $user['CNPJ'] ?? null;

if (!$CNPJ) {
    echo "CNPJ não encontrado.";
    exit;
}

$sql = "SELECT CNPJ, netValue, originalDueDate, status, bankSlipUrl FROM boletos WHERE CNPJ = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $CNPJ);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<table>
            <tr>
                <th>CNPJ</th>
                <th>Valor</th>
                <th>Vencimento</th>
                <th>Status</th>
                <th>Boleto</th>
            </tr>";
    while ($row = $result->fetch_assoc()) {
        $valorCorrigido = $row['netValue'] + .99;
        $valorCorrigido = number_format($valorCorrigido, 2, ',', '.'); // Formatação BRL

        $dataFormatada = date("d/m/Y", strtotime($row['originalDueDate']));
        echo "<tr>
                <td>{$row['CNPJ']}</td>
                <td>R$ {$valorCorrigido}</td>
                <td>{$dataFormatada}</td>
                <td>{$row['status']}</td>
                <td><a href='{$row['bankSlipUrl']}' target='_blank'>📄</a></td>
            </tr>";
    }
    echo "</table>";
} else {
    echo "Nenhum boleto encontrado.";
}

$conn->close();
?>
