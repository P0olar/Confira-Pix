const schedule = require('node-schedule');
const { exec } = require('child_process');

// Função para executar um arquivo Node.js
function executarArquivo(caminho) {
    exec(`node ${caminho}`, (error, stdout, stderr) => {
        if (error) {
            console.error(`Erro ao executar ${caminho}:`, error.message);
            return;
        }
        if (stderr) {
            console.error(`Stderr de ${caminho}:`, stderr);
            return;
        }
        console.log(`Saída de ${caminho}:
${stdout}`);
    });
}

// Agendamento para gerar boletos todo dia 1º e 19 às 08:00
schedule.scheduleJob({ dayOfMonth: [1, 19], hour: 8, minute: 0 }, () => {
    console.log("Executando geração de boletos (gerar-manual.js)...");
    executarArquivo('confirapix/gerar-manual.js');
});

// Agendamento para consultar boletos 3 vezes ao dia (08:00, 14:00 e 20:00)
const horariosConsulta = [
    { hour: 8, minute: 0 },
    { hour: 13, minute: 0 },
    { hour: 20, minute: 0 }
];

horariosConsulta.forEach(horario => {
    schedule.scheduleJob(horario, () => {
        console.log("Executando consulta de boletos (consulta-manual.js)...");
        executarArquivo('confirapix/consulta-manual.js');
    });
});

console.log("Gerenciador de APIs iniciado. Os processos serão executados automaticamente nos horários definidos.");
