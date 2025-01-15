const mysql = require('mysql2/promise');
const fetch = (...args) => import('node-fetch').then(({ default: fetch }) => fetch(...args));

// Configuração do banco de dados
const dbConfig = {
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'confirapix'
};

// Token de acesso da API
const accessToken = '$aact_YTU5YTE0M2M2N2I4MTliNzk0YTI5N2U5MzdjNWZmNDQ6OjAwMDAwMDAwMDAwMDA1MjYxNzc6OiRhYWNoXzcxOWQyNTU0LTkwYWMtNGZmNi1iNDMyLTU1N2Q2ZWQ2NmZmZQ==';

// Função para criar conexão com o banco
async function getConnection() {
    return await mysql.createConnection(dbConfig);
}

// Função para cadastrar cliente
async function cadastrarCliente(nome, cpfCnpj) {
    const url = 'https://api.asaas.com/v3/customers';
    const options = {
        method: 'POST',
        headers: {
            accept: 'application/json',
            'content-type': 'application/json',
            access_token: accessToken
        },
        body: JSON.stringify({
            name: nome,
            cpfCnpj: cpfCnpj
        })
    };

    const response = await fetch(url, options);
    const data = await response.json();

    if (response.ok) {
        return data.id;
    } else {
        throw new Error(`Erro ao cadastrar cliente: ${JSON.stringify(data.errors, null, 2)}`);
    }
}

// Função para verificar se já existe boleto pendente ou vencido para o CNPJ
async function verificarBoletoExistente(cnpj) {
    const connection = await getConnection();
    const query = `
        SELECT * FROM boletos 
        WHERE CNPJ = ? AND (status = 'PENDING' OR status = 'VENCIDO');
    `;
    const [rows] = await connection.execute(query, [cnpj]);
    await connection.end();
    return rows.length > 0;  // Retorna verdadeiro se encontrar boletos pendentes ou vencidos
}

// Função para gerar boleto
async function gerarBoleto(clienteId, dueDate) {
    const url = 'https://api.asaas.com/v3/payments';
    const options = {
        method: 'POST',
        headers: {
            accept: 'application/json',
            'content-type': 'application/json',
            access_token: accessToken
        },
        body: JSON.stringify({
            billingType: 'BOLETO',
            customer: clienteId,
            value: 150,
            dueDate: dueDate,
            description: 'Confira Pix'
        })
    };

    const response = await fetch(url, options);
    const data = await response.json();

    if (response.ok) {
        return {
            id: data.id,
            status: data.status,
            bankSlipUrl: data.bankSlipUrl,
            customer: data.customer,
            originalDueDate: data.originalDueDate,
            netValue: data.netValue
        };
    } else {
        console.error('Erro ao gerar boleto:', JSON.stringify(data.errors, null, 2));
        throw new Error(`Erro ao gerar boleto: ${JSON.stringify(data.errors, null, 2)}`);
    }
}

// Função para salvar boleto no banco
async function salvarBoleto(boleto, razaoSocial, cnpj) {
    const connection = await getConnection();
    const query = `
        INSERT INTO boletos (id, status, bankSlipUrl, customer, originalDueDate, netValue, RazaoSocial, CNPJ)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?);
    `;
    const values = [
        boleto.id, boleto.status, boleto.bankSlipUrl, boleto.customer,
        boleto.originalDueDate, boleto.netValue, razaoSocial, cnpj
    ];
    await connection.execute(query, values);
    await connection.end();
}

// Função para consultar clientes elegíveis
async function consultarClientes() {
    const connection = await getConnection();
    const query = `
        SELECT * FROM cadastro 
        WHERE Status = 'Ativo'
        AND (DiaVencimento - DAY(NOW())) BETWEEN 0 AND 9;
    `;
    const [rows] = await connection.execute(query);
    await connection.end();
    return rows;
}

// Fluxo principal do gerador de boletos
async function gerarBoletosMensais() {
    try {
        const clientes = await consultarClientes();
        for (const cliente of clientes) {
            // Verifica se já existe boleto pendente ou vencido para o CNPJ
            const boletoExistente = await verificarBoletoExistente(cliente.CNPJ);
            if (boletoExistente) {
                continue;  // Pula para o próximo cliente se já existir boleto
            }

            // Cadastrar o cliente se ele ainda não existe ou usar um cliente existente
            const clienteId = await cadastrarCliente(cliente.RazaoSociacadastrol, cliente.CNPJ);

            const dueDate = `${new Date().getFullYear()}-${new Date().getMonth() + 1}-${cliente.DiaVencimento}`;
            const boleto = await gerarBoleto(clienteId, dueDate);
            await salvarBoleto(boleto, cliente.RazaoSociacadastrol, cliente.CNPJ);
        }
    } catch (error) {
        console.error('Erro no processo de geração de boletos:', error.message);
    }
}

// Função para ativação manual da geração de boletos
async function ativarGeracaoManual() {
    await gerarBoletosMensais();
    console.log('Geração de boletos concluída.');
}

// Exemplo de execução manual
(async () => {
    await ativarGeracaoManual();  // Gera boletos manualmente
})();