<?php
$titulo = 'Controle RFID';
require __DIR__ . '/../layouts/header.php';

// Como cada resultado da leitura aparece na tela: [texto, classe do selo]
$resultados = [
    'autorizado'   => ['Autorizado', 'rf-autorizado'],
    'sem_gta'      => ['Sem GTA', 'rf-aviso'],
    'sem_cadastro' => ['Sem cadastro', 'rf-aviso'],
    'desconhecido' => ['TAG desconhecida', 'rf-negado'],
];
$rotuloResultado = fn(string $r): array => $resultados[$r] ?? [strtoupper($r), 'rf-negado'];
?>
<style>
  /* Estilos só do painel RFID. Usam as cores e classes do próprio sistema (style.css). */
  .rfid-topo { display: grid; grid-template-columns: 2fr 1fr; gap: 18px; margin-bottom: 18px; }
  .rfid-topo .card { margin-bottom: 0; }
  .rfid-ultima { border-left: 5px solid var(--laranja); }
  .rfid-rotulo { display: block; color: var(--suave); font-size: .76rem; font-weight: 800; text-transform: uppercase; letter-spacing: .08em; margin-bottom: 8px; }
  .rfid-uid { font-size: 1.9rem; font-weight: 800; color: var(--azul-esc); letter-spacing: .06em; line-height: 1.15; margin: 0; word-break: break-all; }
  .rfid-detalhe { color: var(--suave); margin: 10px 0 0; font-size: .95rem; }
  .rfid-detalhe strong { color: var(--texto); }
  .rfid-status { display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; border-radius: 999px; font-size: .85rem; font-weight: 700; }
  .rfid-status::before { content: ""; width: 9px; height: 9px; border-radius: 50%; background: currentColor; flex: none; }
  .rfid-status.online  { background: var(--verde-bg);  color: var(--verde); }
  .rfid-status.offline { background: var(--perigo-bg); color: var(--perigo); }
  .badge.rf-autorizado { background: var(--verde-bg);  color: var(--verde); }
  .badge.rf-aviso      { background: var(--laranja-claro); color: #8a4300; }
  .badge.rf-negado     { background: var(--perigo-bg); color: var(--perigo); }
  td.uid { font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-weight: 700; letter-spacing: .04em; white-space: nowrap; }
  @media (max-width: 900px) {
    .rfid-topo { grid-template-columns: 1fr; }
    .rfid-uid { font-size: 1.4rem; }
  }
</style>

<main class="conteudo">
  <div class="page-head">
    <div>
      <h1>Controle RFID</h1>
      <p class="sub">Acompanhe em tempo real as leituras do leitor instalado na entrada.</p>
    </div>
    <div class="acoes-topo"><a class="btn" href="<?= e(url('rfid-tags')) ?>">Gerenciar TAGs e vínculos</a></div>
  </div>

  <div class="rfid-topo">
    <section class="card rfid-ultima">
      <span class="rfid-rotulo">Última leitura</span>
      <p class="rfid-uid" id="ultima-tag"><?= $ultima ? e(strtoupper($ultima['tag_uid'])) : 'Aguardando leitura...' ?></p>
      <p class="rfid-detalhe" id="ultimo-motorista">
        Motorista: <strong><?= $ultima ? e($ultima['motorista']) : '—' ?></strong>
        &nbsp;·&nbsp; GTA: <strong><?= $ultima ? e($ultima['gta_numero']) : '—' ?></strong>
      </p>
    </section>

    <section class="card">
      <span class="rfid-rotulo">Leitor ESP32</span>
      <span class="rfid-status <?= $status['online'] ? 'online' : 'offline' ?>" id="status-leitor"><?= e($status['texto']) ?></span>
      <p class="dica" id="atualizado-em">Atualiza sozinho a cada 3 segundos.</p>
    </section>
  </div>

  <section class="card">
    <div class="card-topo">
      <h2>Histórico de leituras</h2>
      <span class="badge">Últimas 15</span>
    </div>
    <div class="tabela-wrap">
      <table>
        <thead>
          <tr>
            <th>ID</th>
            <th>TAG</th>
            <th>Motorista</th>
            <th>GTA</th>
            <th>Resultado</th>
            <th>Data / hora</th>
          </tr>
        </thead>
        <tbody id="corpo-tabela">
          <?php if (empty($leituras)): ?>
            <tr><td colspan="6" class="vazio">Nenhuma leitura registrada.</td></tr>
          <?php else: ?>
            <?php foreach ($leituras as $l): ?>
              <?php [$texto, $classe] = $rotuloResultado((string)$l['resultado']); ?>
              <tr>
                <td><?= (int)$l['id'] ?></td>
                <td class="uid"><?= e(strtoupper($l['tag_uid'])) ?></td>
                <td><?= e($l['motorista']) ?></td>
                <td><?= e($l['gta_numero']) ?></td>
                <td><span class="badge <?= e($classe) ?>"><?= e($texto) ?></span></td>
                <td><?= e($l['data_hora']) ?></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </section>
</main>

<script>
(function () {
  var URL_AJAX = <?= json_encode(url('rfid-ajax')) ?>;
  var RESULTADOS = <?= json_encode($resultados, JSON_UNESCAPED_UNICODE) ?>;

  function escapar(valor) {
    return String(valor == null ? '' : valor).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }

  function selo(resultado) {
    var r = RESULTADOS[resultado] || [String(resultado).toUpperCase(), 'rf-negado'];
    return '<span class="badge ' + escapar(r[1]) + '">' + escapar(r[0]) + '</span>';
  }

  var ocupado = false;

  function atualizar() {
    if (ocupado || document.hidden) { return; }   // não consulta com a aba escondida
    ocupado = true;
    fetch(URL_AJAX, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
      .then(function (r) {
        if (!r.ok) { throw new Error('HTTP ' + r.status); }
        return r.json();
      })
      .then(function (d) {
        var tag = document.getElementById('ultima-tag');
        var mot = document.getElementById('ultimo-motorista');
        var st  = document.getElementById('status-leitor');
        var tb  = document.getElementById('corpo-tabela');

        if (d.ultima) {
          tag.textContent = String(d.ultima.tag_uid).toUpperCase();
          mot.innerHTML = 'Motorista: <strong>' + escapar(d.ultima.motorista) + '</strong> &nbsp;·&nbsp; GTA: <strong>' + escapar(d.ultima.gta_numero) + '</strong>';
        } else {
          tag.textContent = 'Aguardando leitura...';
          mot.innerHTML = 'Motorista: <strong>—</strong> &nbsp;·&nbsp; GTA: <strong>—</strong>';
        }

        st.textContent = d.status.texto;
        st.className = 'rfid-status ' + (d.status.online ? 'online' : 'offline');

        if (!d.leituras.length) {
          tb.innerHTML = '<tr><td colspan="6" class="vazio">Nenhuma leitura registrada.</td></tr>';
        } else {
          tb.innerHTML = d.leituras.map(function (l) {
            return '<tr>' +
              '<td>' + escapar(l.id) + '</td>' +
              '<td class="uid">' + escapar(String(l.tag_uid).toUpperCase()) + '</td>' +
              '<td>' + escapar(l.motorista) + '</td>' +
              '<td>' + escapar(l.gta_numero) + '</td>' +
              '<td>' + selo(l.resultado) + '</td>' +
              '<td>' + escapar(l.data_hora) + '</td>' +
              '</tr>';
          }).join('');
        }
        document.getElementById('atualizado-em').textContent = 'Atualizado às ' + new Date().toLocaleTimeString('pt-BR') + ' (a cada 3 segundos).';
      })
      .catch(function (e) {
        document.getElementById('atualizado-em').textContent = 'Sem resposta do servidor. Tentando de novo...';
        console.error('Falha ao atualizar painel RFID', e);
      })
      .then(function () { ocupado = false; });
  }

  setInterval(atualizar, 3000);
  document.addEventListener('visibilitychange', function () { if (!document.hidden) { atualizar(); } });
})();
</script>

<?php require __DIR__ . '/../layouts/footer.php'; ?>