// Máscaras, preenchimento por cadastro e seleção em lote
document.addEventListener('DOMContentLoaded', function () {

  // CPF (000.000.000-00) ou CNPJ (00.000.000/0000-00) conforme a quantidade de dígitos
  function mascaraCpfCnpj(valor) {
    var v = valor.replace(/\D/g, '').slice(0, 14);
    if (v.length <= 11) {
      return v.replace(/(\d{3})(\d)/, '$1.$2')
              .replace(/(\d{3})(\d)/, '$1.$2')
              .replace(/(\d{3})(\d{1,2})$/, '$1-$2');
    }
    return v.replace(/^(\d{2})(\d)/, '$1.$2')
            .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/\.(\d{3})(\d)/, '.$1/$2')
            .replace(/(\d{4})(\d)/, '$1-$2');
  }

  document.querySelectorAll('input.cpf-cnpj').forEach(function (el) {
    el.value = mascaraCpfCnpj(el.value);
    el.addEventListener('input', function () { el.value = mascaraCpfCnpj(el.value); });
  });

  // Placa: maiúsculas, só letras e números (ABC1D23)
  document.querySelectorAll('input.placa').forEach(function (el) {
    el.addEventListener('input', function () {
      el.value = el.value.toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 7);
    });
  });

  // Telefone: (00) 00000-0000
  document.querySelectorAll('input.telefone').forEach(function (el) {
    function formatar() {
      var v = el.value.replace(/\D/g, '').slice(0, 11);
      if (v.length > 10) { v = v.replace(/^(\d{2})(\d{5})(\d{4})$/, '($1) $2-$3'); }
      else if (v.length > 6) { v = v.replace(/^(\d{2})(\d{4})(\d{0,4})$/, '($1) $2-$3'); }
      else if (v.length > 2) { v = v.replace(/^(\d{2})(\d{0,5})$/, '($1) $2'); }
      el.value = v;
    }
    formatar();
    el.addEventListener('input', formatar);
  });

  // "Testar rota": abre o Google Maps com a origem e o destino preenchidos no formulário da nota
  var botaoRota = document.getElementById('testar-rota');
  if (botaoRota) {
    botaoRota.addEventListener('click', function () {
      function valor(nome) {
        var el = document.querySelector('[name="' + nome + '"]');
        return el ? el.value.trim() : '';
      }
      var origem = valor('origem_localizacao');
      var destino = valor('destino_localizacao');
      if (destino === '') {
        destino = [valor('destino_estabelecimento'), valor('destino_localidade'), valor('destino_municipio')]
          .filter(function (p) { return p !== ''; }).join(', ');
      }
      if (origem === '' || destino === '') {
        alert('Preencha a localização da origem e do destino (ou ao menos estabelecimento/município do destino) para testar a rota.');
        return;
      }
      window.open('https://www.google.com/maps/dir/?api=1&origin=' + encodeURIComponent(origem) +
        '&destination=' + encodeURIComponent(destino) + '&travelmode=driving', '_blank', 'noopener');
    });
  }

  // Escolher um produtor/destinatário cadastrado preenche os campos da nota
  document.querySelectorAll('select[data-picker]').forEach(function (sel) {
    sel.addEventListener('change', function () {
      var opt = sel.options[sel.selectedIndex];
      if (!opt || !opt.dataset.valores) { return; }
      var valores = JSON.parse(opt.dataset.valores);
      Object.keys(valores).forEach(function (campo) {
        var el = document.querySelector('[name="' + campo + '"]');
        if (el) {
          el.value = valores[campo];
          el.dispatchEvent(new Event('input'));
        }
      });
    });
  });

  // "Marcar todas" na lista de notas
  var marcarTodas = document.getElementById('marcar-todas');
  if (marcarTodas) {
    marcarTodas.addEventListener('change', function () {
      document.querySelectorAll('input[name="ids[]"]').forEach(function (cb) {
        cb.checked = marcarTodas.checked;
      });
    });
  }

  // Mostrar / ocultar a senha
  document.querySelectorAll('.ver-senha').forEach(function (botao) {
    botao.addEventListener('click', function () {
      var campo = botao.parentElement.querySelector('input');
      if (!campo) { return; }
      var oculto = campo.type === 'password';
      campo.type = oculto ? 'text' : 'password';
      botao.textContent = oculto ? 'Ocultar' : 'Mostrar';
    });
  });

  // ---------- Sair da conta: janela de confirmação (celular e computador) ----------
  // Sem JavaScript o link "Sair da conta" continua funcionando (vai direto para o logout).
  var linkSair  = document.querySelector('[data-sair]');
  var modalSair = document.getElementById('modal-sair');
  if (linkSair && modalSair) {
    var botaoFicar   = modalSair.querySelector('[data-sair-cancelar]');
    var botaoSair    = modalSair.querySelector('.btn-sair-confirma');

    var abrirSair = function () {
      modalSair.hidden = false;
      document.body.classList.add('modal-aberto');
      botaoFicar.focus();
    };
    var fecharSair = function () {
      modalSair.hidden = true;
      document.body.classList.remove('modal-aberto');
      linkSair.focus();   // devolve o foco ao botão "Sair da conta"
    };

    linkSair.addEventListener('click', function (e) { e.preventDefault(); abrirSair(); });
    botaoFicar.addEventListener('click', fecharSair);
    modalSair.addEventListener('click', function (e) { if (e.target === modalSair) { fecharSair(); } });
    document.addEventListener('keydown', function (e) {
      if (modalSair.hidden) { return; }
      if (e.key === 'Escape') { fecharSair(); }
      if (e.key === 'Tab') {   // mantém o foco dentro da janela
        if (e.shiftKey && document.activeElement === botaoFicar) { e.preventDefault(); botaoSair.focus(); }
        else if (!e.shiftKey && document.activeElement === botaoSair) { e.preventDefault(); botaoFicar.focus(); }
      }
    });
    // Voltar pelo histórico do navegador não deve trazer a janela aberta de volta
    window.addEventListener('pageshow', function () { if (!modalSair.hidden) { fecharSair(); } });
  }

  // ---------- App instalável (PWA) ----------
  // Registra o service worker (precisa de HTTPS no servidor; em localhost também funciona)
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('sw.js').catch(function () { /* sem suporte: segue como site normal */ });
    });
  }

  // Botão "Instalar como app" (aparece quando o navegador permite instalar)
  var eventoInstalar = null;
  var botaoInstalar = document.getElementById('instalar-app');
  window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    eventoInstalar = e;
    if (botaoInstalar) { botaoInstalar.hidden = false; }
  });
  if (botaoInstalar) {
    botaoInstalar.addEventListener('click', function () {
      if (!eventoInstalar) { return; }
      eventoInstalar.prompt();
      eventoInstalar.userChoice.finally(function () {
        eventoInstalar = null;
        botaoInstalar.hidden = true;
      });
    });
  }
  window.addEventListener('appinstalled', function () {
    if (botaoInstalar) { botaoInstalar.hidden = true; }
  });
});
