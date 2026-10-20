// Mostrar/esconder o campo .FIT dependendo da atividade escolhida
var tipo = document.getElementById('tipo');
if (tipo) {
    tipo.addEventListener('change', function () {
        document.getElementById('campoFit').style.display = (this.value === 'corrida') ? 'block' : 'none';
    });
}
