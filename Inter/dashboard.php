<?php
session_start();

// Verifica se o usuário está logado
if (!isset($_SESSION['login'])) {
    header('Location: ../login.php');
    exit;
}

// Verifica se o tempo de login excede 15 minutos
$timeout_duration = 15 * 60;
if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: ../login.php');
    exit;
}

// Conexão com o banco de dados
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "confirapix";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Conexão falhou: " . $conn->connect_error);
}

// Obtém o nome de usuário do banco do usuário logado
$login = $_SESSION['login'];

// Obtém o CNPJ do usuário logado
$sql = "SELECT CNPJ, client_id, client_secret FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$sql = "SELECT CNPJ FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();
$CNPJ_RESULT = $result->fetch_assoc();
$CNPJ = $CNPJ_RESULT['CNPJ'] ?? null;

if (!$user) {
    die("Usuário não encontrado.");
}

$CNPJ = $user['CNPJ'];
$client_id = $user['client_id'];
$client_secret = $user['client_secret'];

$conn->close();
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - ConfiraPix</title>
    <link rel="stylesheet" href="../style/dashboard.css">
    <link rel="shortcut icon" href="../img/site.png" type="image/x-icon">
</head>
<body>

<div class="nav-top">
    <img src="../img/logo-site.png" class="logo">
    <img src="../img/inter.png" class="banco-conct">
    <div class="user-card">
        <div class="user-top">
            <div class="profile-icon"></div>
            <div class="details">
                <p class="name">
                    <?php echo $_SESSION['login']; ?>
                </p>
                <p class="cnpj">
                    <?php echo $CNPJ_RESULT['CNPJ']?>
                </p>
            </div>
        </div>
        <form id="registerForm" method="POST" action="../logout.php">
            <button class="logout-button">
                <span>Sair</span>
            </button>
        </form>
    </div>
</div>

<div class="filters">
    <div class="filters-teste">
        <p class="titulo-filtro">Filtros</p>
        <div class="inputs">
            <div class="op-filtro">
                <label for="date">Data</label><br>
                <input type="date" id="dateFilter" class="filter-input">
            </div>
            <div class="op-filtro">
                <label for="search">Pesquisar</label><br>
                <input type="text" id="searchInput" class="filter-input" placeholder="Valor ou ID">
            </div>
            <div class="op-filtro">
                <label for="order">Ordenar por</label><br>
                <select id="sortOrder" class="filter-input">
                    <option value="date">Mais Recente</option>
                    <option value="descValue">Maior para o Menor</option>
                    <option value="ascValue">Menor para o Maior</option>
                </select>
            </div>
        </div>
        <button type="submit" id="applyFilters" class="apply-button">Aplicar Filtros</button>
    </div>
</div>

<div class="butons">
    <button id="openModal" class="boletos">Mensalidades</button>
    <button id="fetchPayments" class="refresh">Atualizar Lista</button>
    <button onclick="printReport()" class="print"> Imprimir </button>
</div>

<div id="overlay"></div>
    <div id="modal">
        <span class="close" onclick="closeModal()">×</span>
        <h2>Boletos</h2>
        <div id="boletosContent">Carregando...</div>
    </div>

    <script>
        const modal = document.getElementById('modal');
        const overlay = document.getElementById('overlay');
        const boletosContent = document.getElementById('boletosContent');

        document.getElementById('openModal').addEventListener('click', () => {
            fetch('../get_boletos.php')
                .then(response => response.text())
                .then(data => {
                    boletosContent.innerHTML = data;
                    modal.style.display = 'block';
                    overlay.style.display = 'block';
                })
                .catch(error => {
                    boletosContent.innerHTML = 'Erro ao carregar boletos.';
                    console.error(error);
                });
        });

        function closeModal() {
            modal.style.display = 'none';
            overlay.style.display = 'none';
        }
    </script>

<div class="report">
    <table class="tabelaresult" id="paymentsTable" border="1">
        <thead>
            <tr>
                <th class="coluna-test">ID do Cliente</th>
                <th>Nome</th>
                <th>Valor</th>
                <th>Data</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="2">Total</td>
                <td id="total">R$ 0,00</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</div>

<div class="footer">
    <div class="footer-aling">
        <div class="text-footer">
            <a href="">Página Inicial</a>
        </div>
        <div class="text-footer">
            <a href="./Termo-de-Uso.html">Termos e Condições</a>
        </div>
        <div class="text-footer">
            <h4>Contato</h4>
            <a href="#">E-mail<br>WhatsApp</a>
        </div>
    </div>
    <footer>ConfiraPix - Copyright © 57.774.809 VICTOR FERNANDO DOS SANTOS</footer>
</div>

<script src="../scripts/printReport.js"></script>
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    const client_id = "<?php echo $client_id; ?>";
    const client_secret = "<?php echo $client_secret; ?>";
    const CNPJ = "<?php echo $CNPJ; ?>";

    let allPayments = []; // Array global para armazenar todos os pagamentos

    async function fetchPayments() {
        try {
            const response = await fetch(`http://localhost:3002/pix-extrato?` +
                new URLSearchParams({
                    client_id: client_id,
                    client_secret: client_secret
                })
            );

            if (!response.ok) {
                throw new Error('Erro ao buscar pagamentos');
            }

            const data = await response.json();

            const payments = data.transacoes || [];
            if (payments.length > 0) {
                const receivedPayments = payments.filter(payment => payment.titulo === "Pix recebido");
                receivedPayments.sort((a, b) => new Date(b.dataEntrada) - new Date(a.dataEntrada));

                allPayments = receivedPayments; // Atualiza o array global
                updateTable(receivedPayments);
            } else {
                console.error("Nenhum pagamento encontrado na resposta:", data);
            }
        } catch (error) {
            console.error('Erro:', error);
        }
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return `${day}/${month}/${year}`;
    }

    function formatValue(value) {
        const numValue = typeof value === 'number' ? value : parseFloat(value.replace('R$ ', '').replace(',', '.'));
        return `R$ ${numValue.toLocaleString('pt-BR', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
    }

    function updateTable(payments) {
        const tableBody = document.querySelector('#paymentsTable tbody');
        tableBody.innerHTML = '';
        let totalValue = 0;

        payments.forEach(payment => {
            const descricao = payment.descricao || '';
            const regex = /Cp :(\d+)-(.+)$/;
            const match = descricao.match(regex);
            const transactionNumber = match ? match[1] : 'N/A';
            const institutionName = match ? match[2] : 'N/A';

            if (payment.titulo === "Pix recebido") {
                const numericValue = parseFloat(payment.valor.replace('R$ ', '').replace(',', '.'));
                totalValue += numericValue;
            }

            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="coluna-test">${transactionNumber}</td>
                <td style="text-align: left;">${institutionName}</td> 
                <td>${formatValue(payment.valor)}</td>
                <td>${formatDate(payment.dataEntrada)}</td>
            `;
            tableBody.appendChild(row);
        });

        const totalCell = document.querySelector('#total');
        totalCell.textContent = formatValue(totalValue);
    }

    function applyFilters() {
        const dateFilter = document.getElementById('dateFilter').value;
        const sortOrder = document.getElementById('sortOrder').value;
        const searchInput = document.getElementById('searchInput').value.toLowerCase();


        let filteredPayments = [...allPayments];

        if (dateFilter) {
            const [year, month, day] = dateFilter.split('-');
            filteredPayments = filteredPayments.filter(payment => {
                const paymentDate = new Date(payment.dataEntrada);
                return (
                    paymentDate.getFullYear() === parseInt(year) &&
                    paymentDate.getMonth() === parseInt(month) - 1 &&
                    paymentDate.getDate() === parseInt(day)
                );
            });
        }

        if (searchInput) {
            filteredPayments = filteredPayments.filter(payment =>
                (payment.descricao || '').toLowerCase().includes(searchInput) ||
                payment.valor.toString().includes(searchInput)
            );
        }

        if (sortOrder === 'descValue') {
            filteredPayments.sort((a, b) => parseFloat(b.valor.replace('R$ ', '').replace(',', '.')) - parseFloat(a.valor.replace('R$ ', '').replace(',', '.')));
        } else if (sortOrder === 'ascValue') {
            filteredPayments.sort((a, b) => parseFloat(a.valor.replace('R$ ', '').replace(',', '.')) - parseFloat(b.valor.replace('R$ ', '').replace(',', '.')));
        } else if (sortOrder === 'date') {
            filteredPayments.sort((a, b) => new Date(b.dataEntrada) - new Date(a.dataEntrada));
        }

        updateTable(filteredPayments);
    }

    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('fetchPayments').addEventListener('click', fetchPayments);
        document.getElementById('applyFilters').addEventListener('click', applyFilters);
    });
</script>


</body>
</html>
