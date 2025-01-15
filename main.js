const { spawn } = require('child_process');
const { schedule } = require('node-cron');

const boletos = spawn('node', ['scheduler.js'], { stdio: 'inherit' });
const bb = spawn('node', ['Banco-do-Brasil/bb.js'], { stdio: 'inherit' });
const inter = spawn('node', ['Inter/inter.js'], { stdio: 'inherit' });

bb.on('close', (code) => {
    console.log(`bb.js exited with code ${code}`);
});

inter.on('close', (code) => {
    console.log(`inter.js exited with code ${code}`);
});

boletos.on('close', (code) => {
    console.log(`schedule.js exited with code ${code}`);
});