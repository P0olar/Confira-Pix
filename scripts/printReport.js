function printReport() {
    try {
        var reportContent = document.querySelector('.report').innerHTML;
        var printWindow = window.open('', '_blank');

        if (!printWindow) {
            alert('Não foi possível abrir a janela de impressão. Verifique se o bloqueador de pop-ups está ativo.');
            return;
        }

        printWindow.document.write(`
            <!DOCTYPE html>
            <html lang="pt-BR">
                <head>
                    <meta charset="UTF-8">
                    <meta name="viewport" content="width=device-width, initial-scale=1.0">
                    <title>Imprimir Relatório</title>
                    <link rel="stylesheet" href="../style/dashboard.css">
                    <style>
                
                        .nav-top {
                            height: 7rem;
                            border-radius: 0rem 0rem 3rem 3rem;
                            margin: 0rem 0rem 0rem 0rem;
                        }
                        .logo {
                            width: 8rem;
                        }
                    </style>
                </head>

                <body>
                    <div class="nav-top">
                        <img src="../img/logo-site.png" class="logo">
                    </div>
                    ${reportContent}
                    <script>
                        window.onload = function() {
                            window.print();
                        };
                        window.onafterprint = function() {
                            window.close();
                        };
                    </script>
                </body>
            </html>
        `);

        printWindow.document.close();
    } catch (error) {
        console.error('Erro ao tentar imprimir:', error);
        alert('Ocorreu um erro durante o processo de impressão.');
    }
}
