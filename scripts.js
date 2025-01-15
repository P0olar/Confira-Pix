let currentStep = 1;

function showStep(step) {
    document.querySelectorAll('.step').forEach((stepDiv, index) => {
        stepDiv.style.display = index === step - 1 ? 'block' : 'none';
    });
}

function nextStep() {
    if (currentStep < 4) {
        currentStep++;
        showStep(currentStep);
    }
}

function prevStep() {
    if (currentStep > 1) {
        currentStep--;
        showStep(currentStep);
    }
}

showStep(currentStep);


function formatCNPJ(CNPJ) {
    // Remove tudo que não for número
    CNPJ = CNPJ.replace(/\D/g, "");

    // Formata o CNPJ (XX.XXX.XXX/XXXX-XX)
    CNPJ = CNPJ.replace(/^(\d{2})(\d)/, "$1.$2");
    CNPJ = CNPJ.replace(/^(\d{2}\.\d{3})(\d)/, "$1.$2");
    CNPJ = CNPJ.replace(/^(\d{2}\.\d{3}\.\d{3})(\d)/, "$1/$2");
    CNPJ = CNPJ.replace(/^(\d{2}\.\d{3}\.\d{3}\/\d{4})(\d)/, "$1-$2");

    return CNPJ;
}

// Seleciona o campo de CNPJ
const cnpjInput = document.getElementById("CNPJ");

// Adiciona um evento para formatar o CNPJ enquanto o usuário digita
cnpjInput.addEventListener("input", (event) => {
    event.target.value = formatCNPJ(event.target.value);
});

function formatCPF(CPF) {
    CPF = CPF.replace(/\D/g, "");

    CPF = CPF.replace(/^(\d{3})(\d)/, "$1.$2");
    CPF = CPF.replace(/^(\d{3}\.\d{3})(\d)/, "$1.$2");
    CPF = CPF.replace(/^(\d{3}\.\d{3}\.\d{3})(\d)/, "$1-$2");

    return CPF;
}

const cpfinput = document.getElementById("CPF");

cpfinput.addEventListener("input", (event) => {
    event.target.value = formatCPF(event.target.value);
});


function buscarCep() {
    const cep = document.getElementById("CEP").value.replace(/\D/g, ''); // Remove caracteres não numéricos
    if (cep.length === 8) { // Verifica se o CEP tem 8 dígitos
        fetch(`https://viacep.com.br/ws/${cep}/json/`)
            .then(response => {
                if (!response.ok) throw new Error("CEP inválido");
                return response.json();
            })
            .then(data => {
                if (data.erro) {
                    alert("CEP não encontrado.");
                    return;
                }
                document.getElementById("cidade").value = data.localidade;
                document.getElementById("estado").value = data.uf;
            })
            .catch(error => {
                console.error("Erro ao buscar o CEP:", error);
                alert("Erro ao buscar o CEP. Verifique e tente novamente.");
            });
    } else {
        alert("Digite um CEP válido com 8 números.");
    }
}

function formatCEP(CEP) {
    CEP = CEP.replace(/\D/g, "");
    CEP = CEP.replace(/^(\d{5})(\d)/, "$1-$2");
    return CEP;
}

const cepinput = document.getElementById("CEP");
cepinput.addEventListener("input", (event) => {
    event.target.value = formatCEP(event.target.value);
});


function formatTELPJ(Telefone) {

    Telefone = Telefone.replace(/\D/g, "");

    Telefone = Telefone.replace(/^(\d{0})(\d)/, "$1($2");
    Telefone = Telefone.replace(/^(\d{0}.\d{2})(\d)/, "$1)$2");
    Telefone = Telefone.replace(/^(\d{0}.\d{2}.\d{0})(\d)/, "$1 $2");
    Telefone = Telefone.replace(/^(\d{0}.\d{2}.\d{0}.\d{5})(\d)/, "$1-$2");

    return Telefone;
}

const inputtelpj = document.getElementById("Telefone");
inputtelpj.addEventListener("input", (event) => {
    event.target.value = formatTELPJ(event.target.value);
});




function formatTELPF(TelefoneResponsavel) {

    TelefoneResponsavel = TelefoneResponsavel.replace(/\D/g, "");

    TelefoneResponsavel = TelefoneResponsavel.replace(/^(\d{0})(\d)/, "$1($2");
    TelefoneResponsavel = TelefoneResponsavel.replace(/^(\d{0}.\d{2})(\d)/, "$1)$2");
    TelefoneResponsavel = TelefoneResponsavel.replace(/^(\d{0}.\d{2}.\d{0})(\d)/, "$1 $2");
    TelefoneResponsavel = TelefoneResponsavel.replace(/^(\d{0}.\d{2}.\d{0}.\d{5})(\d)/, "$1-$2");

    return TelefoneResponsavel;
}

const inputtelpf = document.getElementById("TelefoneResponsavel");
inputtelpf.addEventListener("input", (event) => {
    event.target.value = formatTELPF(event.target.value);
});

document.getElementById("password").addEventListener("input", () => {
    const senha1 = document.getElementById("senha").value;
    const senha2 = document.getElementById("password").value;
    const mensagemErro = document.getElementById("mensagemErro");

    if (senha2 === "") {
        mensagemErro.textContent = ""; // Limpa a mensagem se o campo estiver vazio
        return;
    }

    if (senha1 !== senha2) {
        mensagemErro.textContent = "As senhas não são iguais!";
        mensagemErro.style.color = "red";
        mensagemErro.style.fontSize = "1rem";
        mensagemErro.style.margin = "0"
    } else {
        mensagemErro.textContent = "As senhas são iguais!";
        mensagemErro.style.color = "green";
        mensagemErro.style.fontSize = "1rem";
        mensagemErro.style.margin = "0"
    }
});

async function verificarCadastro(tipo, valor) {
    const url = `verificarCadastro.php?tipo=${tipo}&valor=${encodeURIComponent(valor)}`;
    try {
        const response = await fetch(url);
        const resultado = await response.json();
        return resultado.existe; // Retorna se o valor já existe no sistema
    } catch (error) {
        console.error("Erro ao verificar cadastro:", error);
        return false;
    }
}

document.getElementById("CNPJ").addEventListener("input", async () => {
    const cnpj = document.getElementById("CNPJ").value.trim();
    const existe = await verificarCadastro("CNPJ", cnpj);
    const mensagemErro = document.getElementById("mensagemErroCNPJ");

    if (existe) {
        mensagemErro.textContent = "CNPJ já cadastrado no sistema.";
        mensagemErro.style.color = "red";
        mensagemErro.style.fontSize = "1rem";
        mensagemErro.style.margin = "0"
    } else {
        mensagemErro.textContent = "";
    }
});

document.getElementById("username").addEventListener("input", async () => {
    const username = document.getElementById("username").value;
    const existe = await verificarCadastro("username", username);
    const mensagemErro = document.getElementById("mensagemErro");

    if (existe) {
        mensagemErro.textContent = "Nome de usuário já cadastrado.";
        mensagemErro.style.color = "red";
        mensagemErro.style.fontSize = "1rem";
        mensagemErro.style.margin = "0"
    } else {
        mensagemErro.textContent = "";
    }
});
