const mysql = require('mysql2/promise');
const fetch = (...args) => import('node-fetch').then(({ default: fetch }) => fetch(...args));

const accessToken = '$aact_YTU5YTE0M2M2N2I4MTliNzk0YTI5N2U5MzdjNWZmNDQ6OjAwMDAwMDAwMDAwMDA1MjYxNzc6OiRhYWNoXzcxOWQyNTU0LTkwYWMtNGZmNi1iNDMyLTU1N2Q2ZWQ2NmZmZQ==';

// Configuração do banco de dados
const dbConfig = {
    host: 'localhost',
    user: 'root',
    password: '',
    database: 'confirapix'
};

async function atualizarBoletos() {
    const connection = await mysql.createConnection(dbConfig);

    try {
        // Busca os boletos da tabela
        const [boletos] = await connection.query('SELECT * FROM boletos');

        for (const boleto of boletos) {
            const customer = boleto.customer;
            const url = `https://api.asaas.com/v3/payments?customer=${customer}`;
            const options = {
                method: 'GET',
                headers: {
                    accept: 'application/json',
                    access_token: accessToken
                }
            };

            const response = await fetch(url, options);
            const data = await response.json();

            if (data && data.data) {
                for (const payment of data.data) {
                    if (payment.id === boleto.id) {
                        let statusAtualizado = payment.status;

                        // Verifica alterações nos dados
                        if (statusAtualizado === 'OVERDUE' && boleto.status !== 'OVERDUE') {
                            statusAtualizado = 'VENCIDO';

                            // Atualiza o status na tabela users e cadastro
                            await connection.query(
                                'UPDATE users SET status = ? WHERE CNPJ = ?',
                                ['Bloqueado', boleto.CNPJ]
                            );

                            await connection.query(
                                'UPDATE cadastro SET status = ? WHERE CNPJ = ?',
                                ['Bloqueado', boleto.CNPJ]
                            );
                        }

                        // Atualiza o boleto na tabela boletos
                        await connection.query(
                            'UPDATE boletos SET status = ?, netValue = ?, originalDueDate = ? WHERE id = ?',
                            [
                                statusAtualizado,
                                payment.netValue,
                                payment.dueDate,
                                boleto.id
                            ]
                        );

                        console.log(`Boleto ID ${boleto.id} atualizado.`);
                    }
                }
            }
        }
    } catch (error) {
        console.error('Erro ao atualizar boletos:', error);
    } finally {
        await connection.end();
    }
}

atualizarBoletos();
