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

// Obtém o número do banco do usuário logado
$sql = "SELECT banco FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$banco_num = $user['banco'] ?? null;

// Obtém o CNPJ do usuário logado
$sql = "SELECT CNPJ FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();
$CNPJ_RESULT = $result->fetch_assoc();
$CNPJ = $CNPJ_RESULT['CNPJ'] ?? null;

// Obtém as credenciais do usuário logado
$sql = "SELECT client_id, client_secret, developer_application_key FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    die("Usuário não encontrado.");
}

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
    <script>
        let allPayments = []; // Armazena todos os pagamentos

        async function fetchPayments() {
            try {
                const response = await fetch('http://localhost:3001/banco-do-brasil?' +
                    new URLSearchParams({
                        client_id: '<?php echo $user["client_id"]; ?>',
                        client_secret: '<?php echo $user["client_secret"]; ?>',
                        developer_application_key: '<?php echo $user["developer_application_key"]; ?>'
                    })
                );
                if (!response.ok) {
                    throw new Error('Erro ao buscar pagamentos');
                }
                allPayments = await response.json(); // Armazena os pagamentos na variável
                updateTable(allPayments); // Passa todos os pagamentos para a tabela
            } catch (error) {
                console.error('Erro:', error);
            }
        }

        // Função para formatar valores monetários
function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value);
}

// Função para calcular a soma dos valores
function calculateTotal(payments) {
    const total = payments.reduce((acc, payment) => {
        const value = parseFloat(payment.valor.replace('R$ ', '').replace('.', '').replace(',', '.'));
        return acc + (isNaN(value) ? 0 : value);
    }, 0);

    // Atualiza o campo de total no HTML
    const totalCell = document.getElementById('total');
    totalCell.innerText = formatCurrency(total);
}

// Função para atualizar a tabela e calcular o total
function updateTable(payments) {
    const tableBody = document.querySelector('#paymentsTable tbody');
    tableBody.innerHTML = ''; // Limpa a tabela antes de adicionar novos dados

    payments.forEach(payment => {
        const formattedValue = formatCurrency(parseFloat(payment.valor.replace('R$ ', '').replace('.', '').replace(',', '.')));
        const row = document.createElement('tr');

        row.innerHTML = `
            <td style="text-align: left;">${payment.cliente}</td>
            <td class="coluna-test">${payment.cpf !== 'N/A' ? payment.cpf : payment.cnpj}</td> <!-- Exibe CPF ou CNPJ -->
            <td>${formattedValue}</td>
            <td>${payment.data}</td>
            <td>${payment.hora}</td>
        `;
        tableBody.appendChild(row);
    });

    // Atualiza o total
    calculateTotal(payments);

    console.log('Tabela atualizada com dados:', payments); // Verifica se a tabela foi atualizada
}


function applyFilters() {
    const dateFilter = document.getElementById('dateFilter').value;
    const sortOrder = document.getElementById('sortOrder').value;
    const searchInput = document.getElementById('searchInput').value.toLowerCase();

    let filteredPayments = allPayments;

    // Filtrar por data
    if (dateFilter) {
        const filterDate = new Date(dateFilter); // Converte a data do filtro para o objeto Date
        filteredPayments = filteredPayments.filter(payment => {
            const paymentDate = new Date(payment.data.split('/').reverse().join('-')); // Converte data de pagamento ao objeto Date, assumindo formato DD/MM/AAAA
            return paymentDate.toDateString() === filterDate.toDateString();
        });
    }

    // Filtrar por ID ou Valor
    if (searchInput) {
        filteredPayments = filteredPayments.filter(payment =>
            payment.id.toString().includes(searchInput) || payment.valor.includes(searchInput)
        );
    }

    // Ordenar pagamentos
    if (sortOrder === 'descValue') { // Maior para menor valor
        filteredPayments.sort((a, b) => {
            const aValue = parseFloat(a.valor.replace('R$ ', '').replace('.', '').replace(',', '.'));
            const bValue = parseFloat(b.valor.replace('R$ ', '').replace('.', '').replace(',', '.'));
            return bValue - aValue;
        });
    } else if (sortOrder === 'ascValue') { // Menor para maior valor
        filteredPayments.sort((a, b) => {
            const aValue = parseFloat(a.valor.replace('R$ ', '').replace('.', '').replace(',', '.'));
            const bValue = parseFloat(b.valor.replace('R$ ', '').replace('.', '').replace(',', '.'));
            return aValue - bValue;
        });
    } else if (sortOrder === 'date') { // Ordenar por data e hora
        filteredPayments.sort((a, b) => {
            const aDate = new Date(a.data.split('/').reverse().join('-') + ' ' + a.hora);
            const bDate = new Date(b.data.split('/').reverse().join('-') + ' ' + b.hora);
            return bDate - aDate; // Para ordenar do mais recente para o mais antigo (data + hora)
        });
    }

    updateTable(filteredPayments); // Atualiza a tabela com os pagamentos filtrados
}


document.addEventListener('DOMContentLoaded', function() {
    const button = document.getElementById('fetchPayments');
    button.addEventListener('click', fetchPayments);
    
    // Adiciona o evento de clique para aplicar filtros
    const applyFiltersButton = document.getElementById('applyFilters');
    applyFiltersButton.addEventListener('click', applyFilters);
});

    </script>
</head>
<body>

<div class="nav-top">
    <img src="../img/logo-site.png" class="logo">
    <img src="../img/banco-do-brasil.png" class="banco-conct">
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
    <button id="fetchPayments" class="refresh">Atulizar Lista</button>
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
                <th>Nome</th>
                <th class="coluna-te">CPF/CNPJ</th>
                <th>Valor</th>
                <th>Data</th>
                <th>Hora</th>
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
            <a href="">Pagina Inical</a>
        </div>
        <div class="text-footer">
            <a href="./Termo-de-Uso.html">Termos e Condições</a>
        </div>
        <div class="text-footer">
            <h4>Contato</h4>
            <a href="#">E-mail<br>Whatsapp</a>
        </div>
    </div>
    <footer>ConfiraPix - Copyright © 57.774.809 VICTOR FERNANDO DOS SANTOS</footer>
</div>

<script src="../scripts/printReport.js"></script>

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
</body>
</html>
