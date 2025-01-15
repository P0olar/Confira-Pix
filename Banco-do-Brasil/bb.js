const axios = require('axios');
const fs = require('fs');
const https = require('https');
const path = require('path');
const express = require('express');
const cors = require('cors');

const app = express();
const port = 3001;

app.use(cors());

// Caminho para o certificado e a chave privada
const certPath = path.join(__dirname, 'certificate.crt');  // Substitua com o nome correto do seu certificado
const keyPath = path.join(__dirname, 'private.key');       // Substitua com o nome correto da sua chave privada

// Configuração do agente HTTPS para fornecer o certificado e chave
const httpsAgent = new https.Agent({
    cert: fs.readFileSync(certPath),
    key: fs.readFileSync(keyPath)
});

// Função para obter o token de acesso
const getAccessToken = async (client_id, client_secret) => {
    try {
        const response = await axios.post('https://oauth.bb.com.br/oauth/token', null, {
            params: {
                grant_type: 'client_credentials',
            },
            auth: {
                username: client_id,
                password: client_secret,
            },
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            httpsAgent,
        });

        if (response.status === 200) {
            return response.data.access_token;
        } else {
            throw new Error(`Erro ao obter token:`);
        }
    } catch (error) {
        return null;
    }
};

// Função para obter pagamentos Pix
const getAllPixPayments = async (client_id, client_secret, developer_application_key) => {
    try {
        const token = await getAccessToken(client_id, client_secret);
        if (!token) throw new Error('Falha ao obter o token de acesso.');

        const today = new Date();
        const fiveDaysAgo = new Date();
        fiveDaysAgo.setDate(today.getDate() - 4);

        const formattedFromDate = fiveDaysAgo.toISOString();
        const formattedToDate = today.toISOString();

        const response = await axios.get('https://api-pix.bb.com.br/pix/v2/pix', {
            headers: {
                Authorization: `Bearer ${token}`,
                'gw-dev-app-key': developer_application_key,
            },
            params: {
                inicio: formattedFromDate,
                fim: formattedToDate,
            },
            httpsAgent,
        });

        const paymentsList = response.data.pix;

        if (!paymentsList || !Array.isArray(paymentsList)) {
            throw new Error('A resposta da API não contém uma lista de pagamentos.');
        }

        function formatCPF(cpf) {
            if (!cpf) return 'N/A';
            return cpf.replace(/^(\d{3})(\d{3})(\d{3})(\d{2})$/, '$1.$2.$3-$4');
        }

        function formatCNPJ(cnpj) {
            if (!cnpj) return ''; // Retorna vazio se não houver CNPJ
        
            cnpj = cnpj.replace(/\D/g, ''); // Remove todos os caracteres não numéricos
            return cnpj.replace(/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/, '$1.$2.$3/$4-$5'); // Formata como xx.xxx.xxx/xxxx-xx
        }
        


        const formattedPayments = paymentsList.map(payment => {
            const valorFormatado = `R$ ${parseFloat(payment.valor).toFixed(2).replace('.', ',')}`;
            const dataPagamento = payment.horario.split('T')[0];
            const [ano, mes, dia] = dataPagamento.split('-');
            const dataFormatada = `${dia}/${mes}/${ano}`;
            const horaFormatada = payment.horario.split('T')[1].split('.')[0];

            const cpfFormatado = formatCPF(payment.pagador?.cpf); // Formata o CPF

            const cnpjFormatado = formatCNPJ(payment.pagador?.cnpj); // Formata o CNPJ

            // Verifica se o CPF está disponível, caso contrário, usa o CNPJ
            const cpfOuCNPJ = payment.pagador?.cpf ? cpfFormatado : cnpjFormatado;


            return {
                cliente: payment.pagador?.nome || 'N/A',
                cpf: cpfOuCNPJ, // Agora exibe o CPF ou o CNPJ
                id: payment.txid || '',
                valor: valorFormatado,
                data: dataFormatada,
                hora: horaFormatada,
            };
        });




        formattedPayments.sort((a, b) => {
            const dataA = new Date(`${a.data.split('/').reverse().join('-')}T${a.hora}`);
            const dataB = new Date(`${b.data.split('/').reverse().join('-')}T${b.hora}`);
            return dataB - dataA;
        });

        return formattedPayments;

    } catch (error) {
        console.error('Erro ao buscar pagamentos Pix:', error.message);
        return [];
    }
};

app.get('/banco-do-brasil', async (req, res) => {
    // Extrai as credenciais da solicitação
    const { client_id, client_secret, developer_application_key } = req.query;

    const payments = await getAllPixPayments(client_id, client_secret, developer_application_key);
    res.json(payments);
});


// Inicializa o servidor
app.listen(port, () => {
    console.log(`Servidor rodando em http://localhost:${port}`);
});
