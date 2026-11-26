 <?php if (empty($semTopo)): ?>
    <footer class="rodape-app">
      <img src="assets/images/Logo-S.png" alt="">
      <span>SENAI Santa Catarina · Unidade Videira — Notas de Transporte Animal</span>
    </footer>
<?php endif; ?>
  </div>
</div>
<?php if (empty($semTopo)): ?>
<div class="modal-sair" id="modal-sair" hidden role="dialog" aria-modal="true" aria-labelledby="modal-sair-titulo" aria-describedby="modal-sair-texto">
  <div class="modal-sair-caixa">
    <div class="modal-sair-icone">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
    </div>
    <h2 id="modal-sair-titulo">Sair da conta?</h2>
    <p id="modal-sair-texto">Você precisará entrar de novo com seu e-mail e senha para voltar ao sistema.</p>
    <div class="modal-sair-botoes">
      <button type="button" class="btn" data-sair-cancelar>Cancelar</button>
      <a class="btn btn-sair-confirma" href="<?= e(url('logout')) ?>">Sair</a>
    </div>
  </div>
</div>
<?php endif; ?>
<script>
/* Rótulos das colunas para as tabelas virarem cartões no celular (responsivo.css) */
(function () {
  function rotular(tabela) {
    var ths = tabela.querySelectorAll('thead th');
    if (!ths.length) return;
    tabela.querySelectorAll('tbody tr').forEach(function (tr) {
      Array.prototype.forEach.call(tr.children, function (td, i) {
        if (td.tagName !== 'TD' || td.hasAttribute('data-label') || td.colSpan > 1) return;
        var t = ths[i] ? ths[i].textContent.trim() : '';
        if (!t && td.querySelector('input[type="checkbox"]')) t = 'Selecionar';
        td.setAttribute('data-label', t);
      });
    });
  }
  function rotularTodas() { document.querySelectorAll('table').forEach(rotular); }
  rotularTodas();
  document.querySelectorAll('tbody').forEach(function (tb) {
    new MutationObserver(function () { rotular(tb.closest('table')); }).observe(tb, { childList: true });
  });
})();
</script>
<script>
/* Confirmação ao apertar "voltar" no celular/tablet (aparece uma janela perguntando se quer sair) */
(function () {
  if (!('pushState' in history)) return;
  var modal = null, armado = false, saindo = false;
  var eventos = ['touchstart', 'pointerdown', 'click', 'keydown'];

  function emTelaDeToque() {
    return window.matchMedia('(pointer: coarse)').matches || window.innerWidth <= 1024;
  }

  function criarModal() {
    var st = document.createElement('style');
    st.textContent =
      '#saida-modal{position:fixed;top:0;right:0;bottom:0;left:0;z-index:99999;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(0,20,60,.55)}' +
      '#saida-modal[hidden]{display:none}' +
      '#saida-modal .caixa{background:#fff;color:#1c1c1e;width:100%;max-width:360px;border-radius:16px;padding:22px 20px;border-top:5px solid #F47920;box-shadow:0 20px 50px rgba(0,0,0,.35);font-family:inherit}' +
      '#saida-modal h2{margin:0 0 8px;font-size:1.15rem;color:#003087}' +
      '#saida-modal p{margin:0 0 18px;font-size:.95rem;line-height:1.4;color:#48484a}' +
      '#saida-modal .botoes{display:flex;gap:10px}' +
      '#saida-modal button{flex:1 1 0;min-height:46px;border-radius:10px;font-size:1rem;font-weight:700;cursor:pointer;font-family:inherit}' +
      '#saida-modal .ficar{background:#003087;color:#fff;border:0}' +
      '#saida-modal .sair{background:#fff;color:#D63031;border:1.5px solid #D63031}';
    document.head.appendChild(st);

    modal = document.createElement('div');
    modal.id = 'saida-modal';
    modal.hidden = true;
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', 'saida-titulo');
    modal.innerHTML =
      '<div class="caixa">' +
        '<h2 id="saida-titulo">Deseja realmente sair?</h2>' +
        '<p>Você apertou o botão de voltar. Se sair agora, deixará esta página.</p>' +
        '<div class="botoes">' +
          '<button type="button" class="ficar">Ficar</button>' +
          '<button type="button" class="sair">Sair</button>' +
        '</div>' +
      '</div>';
    document.body.appendChild(modal);

    modal.querySelector('.ficar').addEventListener('click', function () { fechar(); history.forward(); });
    modal.querySelector('.sair').addEventListener('click', function () { saindo = true; fechar(); history.back(); });
    modal.addEventListener('click', function (e) { if (e.target === modal) { fechar(); history.forward(); } });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) { fechar(); history.forward(); } });
  }

  function abrir() {
    if (!modal) criarModal();
    modal.hidden = false;
    modal.querySelector('.ficar').focus();
  }
  function fechar() { if (modal) modal.hidden = true; }

  /* O navegador só aceita "proteger" o botão voltar depois que a pessoa toca na tela */
  function armar() {
    eventos.forEach(function (ev) { document.removeEventListener(ev, armar, true); });
    if (armado) return;
    armado = true;
    if (!(history.state && history.state.guardaSaida)) {
      history.pushState({ guardaSaida: true }, '', location.href);
    }
  }

  if (history.state && history.state.guardaSaida) {
    armado = true;                       /* a página já foi aberta numa posição protegida */
  } else {
    eventos.forEach(function (ev) { document.addEventListener(ev, armar, true); });
  }

  window.addEventListener('popstate', function (e) {
    if (!emTelaDeToque()) return;
    if (e.state && e.state.guardaSaida) { fechar(); return; }   /* voltou para a posição protegida */
    if (armado && !saindo) abrir();                              /* apertou voltar: pergunta */
  });
})();
</script>
</body>
</html>
