const express = require('express');
const axios = require('axios');
const https = require('https');
const fs = require('fs');
const qs = require('qs');
const cors = require('cors');

const app = express();
const port = 3002;

// Ativa o CORS para permitir requisições de diferentes origens
app.use(cors({ origin: 'http://localhost' })); // Substitua 'http://localhost' pela URL do seu site em produção

// Calcula as datas de início e fim (últimos 30 dias)
const today = new Date();
const thirtyDaysAgo = new Date();
thirtyDaysAgo.setDate(today.getDate() - 31);

// Formata as datas para o formato 'yyyy-mm-dd'
const formatDate = (date) => date.toISOString().split('T')[0];
const dataInicio = formatDate(thirtyDaysAgo);
const dataFim = formatDate(today);

// Configuração do cliente HTTPS com certificado e chave
const cert = fs.readFileSync('./Inter/Inter_Certificado.crt');
const key = fs.readFileSync('./Inter/Inter_Chave.key');

const agent = new https.Agent({
    cert: cert,
    key: key,
    rejectUnauthorized: false // Use com cautela em produção
});

// Função para obter o token com client_id e client_secret dinâmicos
async function getToken(client_id, client_secret) {
    const authData = qs.stringify({
        'client_id': client_id,
        'client_secret': client_secret,
        'scope': 'pix.read pagamento-pix.read extrato.read',
        'grant_type': 'client_credentials'
    });

    try {
        const response = await axios.post('https://cdpj.partners.bancointer.com.br/oauth/v2/token', authData, {
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            httpsAgent: agent
        });
        return response.data.access_token;
    } catch (error) {
        throw error;
    }
}

// Função para obter transações PIX
async function getPixTransactions(token) {
    const config = {
        method: 'get',
        maxBodyLength: Infinity,
        url: `https://cdpj.partners.bancointer.com.br/banking/v2/extrato?dataInicio=${dataInicio}&dataFim=${dataFim}&tipoTransacao=PIX&tipoOperacao=C`,
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'Authorization': `Bearer ${token}`
        },
        httpsAgent: agent
    };

    try {
        const response = await axios.request(config);
        return response.data; // Retorna os dados do extrato PIX
    } catch (error) {
        console.error("Erro ao obter extrato PIX:", error);
        throw error;
    }
}

// Endpoint para obter o extrato PIX, recebendo client_id e client_secret via query
app.get('/pix-extrato', async (req, res) => {
    const { client_id, client_secret } = req.query;

    if (!client_id || !client_secret) {
        return res.status(400).send("Faltando client_id ou client_secret");
    }

    try {
        const token = await getToken(client_id, client_secret);
        const transactions = await getPixTransactions(token);
        res.json(transactions); // Envia os dados PIX como JSON para o frontend
    } catch (error) {
        res.status(500).send("Erro ao obter o extrato PIX.");
    }
});

// Inicializa o servidor
app.listen(port, () => {
    console.log(`Servidor Inter rodando`);
});
