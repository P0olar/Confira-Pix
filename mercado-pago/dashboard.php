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

// Obtém o mercadoPagoAccessToken do usuário logado
$sql = "SELECT mercadoPagoAccessToken FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();
$token_data = $result->fetch_assoc();
$mercadoPagoAccessToken = $token_data['mercadoPagoAccessToken'] ?? null;


$sql = "SELECT CNPJ FROM users WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $login);
$stmt->execute();
$result = $stmt->get_result();
$CNPJ_RESULT = $result->fetch_assoc();
$CNPJ = $CNPJ_RESULT['CNPJ'] ?? null;

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
    <style>


/* Botão de fechar */

    </style>
</head>

<body>
<div class="nav-top">
    <img src="../img/logo-site.png" class="logo">
    <img src="../img/mercado-pago.png" class="banco-conct">
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
                    <option value="asc">Crescente</option>
                    <option value="desc">Decrescente</option>
                </select>
            </div>
        </div>
        <button type="submit" id="applyFilters" class="apply-button">Aplicar Filtros</button>
    </div>
</div>

<script>
    function filtro() {
        const applyFilters = () => {
            const dateFilter = document.getElementById('dateFilter').value;
            const sortOrder = document.getElementById('sortOrder').value;
            const searchInput = document.getElementById('searchInput').value.toLowerCase();

            let filteredPayments = allPayments;

            // Filtrar por data
            if (dateFilter) {
                filteredPayments = filteredPayments.filter(payment => payment.data === dateFilter.split('-').reverse().join('/'));
            }

            // Filtrar por ID ou Valor
            if (searchInput) {
                filteredPayments = filteredPayments.filter(payment =>
                    payment.id.toString().includes(searchInput) || payment.valor.includes(searchInput)
                );
            }

            // Ordenar pagamentos
            filteredPayments.sort((a, b) => {
                const aValue = parseFloat(a.valor.replace('R$ ', '').replace('.', '').replace(',', '.'));
                const bValue = parseFloat(b.valor.replace('R$ ', '').replace('.', '').replace(',', '.'));
                return sortOrder === 'asc' ? aValue - bValue : bValue - aValue;
            });

            updatePaymentsTable(filteredPayments);
        };
    }
</script>

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
                <th class="coluna-te">ID da Transação</th>
                <th>ID do Cliente</th>
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

<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script>
    const mercadoPagoAccessToken = '<?php echo $token_data['mercadoPagoAccessToken']; ?>';
    let allPayments = [];

    // Função para calcular a soma dos valores
    const calculateTotal = (payments) => {
        const total = payments.reduce((acc, payment) => {
            const value = parseFloat(payment.valor.replace('R$ ', '').replace('.', '').replace(',', '.'));
            return acc + (isNaN(value) ? 0 : value);
        }, 0);

        // Atualiza o campo de total no HTML
        const totalCell = document.getElementById('total');
        totalCell.innerText = `R$ ${total.toFixed(2).replace('.', ',')}`;
    };

    // Função para atualizar a tabela e o total
    const updatePaymentsTable = (payments) => {
        const tableBody = document.querySelector('#paymentsTable tbody');
        tableBody.innerHTML = '';

        payments.forEach(payment => {
            const row = document.createElement('tr');
            row.innerHTML = `
            <td class="coluna-test">${payment.id}</td>
            <td>${payment.cliente}</td>
            <td class="coluna-valor">${payment.valor}</td>
            <td>${payment.data}</td>
            <td>${payment.hora}</td>
        `;
            tableBody.appendChild(row);
        });

        // Atualiza o total
        calculateTotal(payments);

        console.log("Tabela atualizada com dados:", payments); // Confirma se a tabela foi atualizada
    };

    // Função para buscar pagamentos Pix
    const getPixPayments = async () => {
        try {
            const response = await axios.get('https://api.mercadopago.com/v1/payments/search', {
                headers: {
                    Authorization: `Bearer ${mercadoPagoAccessToken}`,
                },
                params: {
                    payment_method_id: 'pix',
                    status: 'approved',
                    sort: 'date_created',
                    criteria: 'desc',
                },
            });

            if (response.status !== 200) {
                throw new Error(`API request failed with status code: ${response.status}`);
            }

            // Processamento dos dados recebidos
            allPayments = response.data.results.map(payment => ({
                cliente: payment.payer?.id || 'N/a',
                email: payment.payer?.email || 'Não informado',
                cpf: payment.payer?.identification?.number || 'Não informado',
                id: payment.id,
                valor: payment.transaction_amount ? `R$ ${payment.transaction_amount.toFixed(2).replace('.', ',')}` : 'R$ 0,00',
                data: payment.date_created ? payment.date_created.split('T')[0].split('-').reverse().join('/') : 'Sem data',
                hora: payment.date_created ? payment.date_created.split('T')[1].split('.')[0] : 'Sem hora',
            }));

            console.log("Dados carregados:", allPayments); // Verifica se os dados foram carregados
            updatePaymentsTable(allPayments);
        } catch (error) {
            console.error('Erro ao buscar pagamentos Pix:', error.message);
            alert('Erro ao buscar pagamentos: ' + error.message);
        }
    };

    document.getElementById('fetchPayments').addEventListener('click', getPixPayments);

    // Função para aplicar filtros e pesquisa
    const applyFilters = () => {
        const dateFilter = document.getElementById('dateFilter').value;
        const sortOrder = document.getElementById('sortOrder').value;
        const searchInput = document.getElementById('searchInput').value.toLowerCase();

        console.log("Filtros aplicados:", { dateFilter, sortOrder, searchInput }); // Verifica valores dos filtros

        let filteredPayments = allPayments;

        // Filtrar por data
        if (dateFilter) {
            filteredPayments = filteredPayments.filter(payment => payment.data === dateFilter.split('-').reverse().join('/'));
            console.log("Dados após filtro de data:", filteredPayments); // Verifica dados após filtro de data
        }

        // Filtrar por ID ou Valor
        if (searchInput) {
            filteredPayments = filteredPayments.filter(payment =>
                payment.id.toString().includes(searchInput) || payment.valor.replace('R$ ', '').replace('.', '').replace(',', '.').includes(searchInput)
            );
            console.log("Dados após filtro de pesquisa:", filteredPayments); // Verifica dados após filtro de pesquisa
        }

        // Ordenar pagamentos
        filteredPayments.sort((a, b) => {
            const aValue = parseFloat(a.valor.replace('R$ ', '').replace('.', '').replace(',', '.'));
            const bValue = parseFloat(b.valor.replace('R$ ', '').replace('.', '').replace(',', '.'));
            return sortOrder === 'asc' ? aValue - bValue : bValue - aValue;
        });

        console.log("Dados após ordenação:", filteredPayments); // Verifica dados após ordenação
        updatePaymentsTable(filteredPayments);
    };

    document.getElementById('fetchPayments').addEventListener('click', getPixPayments);
    document.getElementById('applyFilters').addEventListener('click', applyFilters);
</script>


<script src="../scripts/printReport.js"></script>

</body>


</html>