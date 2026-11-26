<?php
use App\Helpers\Helper;
use App\Models\Nota;
$titulo = 'Nota ' . Helper::numeroNota($n);
require BASE_PATH . '/views/layouts/header.php';
$perfil = Helper::perfil();
$admin = $perfil === 'admin';
$podeEmitir = in_array($perfil, ['admin', 'produtor'], true);
?>
<main class="conteudo">
  <div class="page-head">
    <div>
      <h1>Nota Nº <?= e(Helper::numeroNota($n)) ?></h1>
      <p class="sub">
        <span class="badge st-<?= e($n['status']) ?>"><?= e(Nota::STATUS[$n['status']] ?? $n['status']) ?></span>
        <?php if (Nota::vencida($n)): ?><span class="badge st-vencida">Vencida</span><?php endif; ?>
        Emitida em <?= e(Helper::data($n['criado_em'])) ?>
      </p>
    </div>
    <div class="acoes-topo">
      <a class="btn sec" href="<?= e(url('notas')) ?>">← Voltar</a>
      <a class="btn sec" href="<?= e(url('nota-pdf', ['id' => $n['id']])) ?>" target="_blank">Ver PDF</a>
      <a class="btn" href="<?= e(url('nota-pdf', ['id' => $n['id'], 'baixar' => 1])) ?>">Baixar PDF</a>
      <?php if (Nota::podeEditar($n, $perfil)): ?>
        <a class="btn sec" href="<?= e(url('nota-form', ['id' => $n['id']])) ?>">Editar</a>
      <?php endif; ?>
      <?php if ($podeEmitir): ?>
        <form method="post" action="<?= e(url('nota-duplicar')) ?>" class="inline">
          <?= csrf_campo() ?>
          <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
          <button class="btn sec" type="submit">Duplicar</button>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>
  <?php if ($erro): ?><div class="alerta"><?= e($erro) ?></div><?php endif; ?>

  <?php /* ---- Rota no Google Maps ---- */ ?>
  <section class="card rota">
    <div>
      <h2>Rota da viagem</h2>
      <?php if ($rotaMaps): ?>
        <p class="sub">Do produtor até o destinatário. Toque no botão para abrir no Google Maps e seguir o trajeto.</p>
      <?php else: ?>
        <p class="sub">A rota ainda não pode ser gerada: falta informar a localização da origem e do destino na nota.</p>
      <?php endif; ?>
    </div>
    <?php if ($rotaMaps): ?>
      <a class="btn mapa" href="<?= e($rotaMaps) ?>" target="_blank" rel="noopener">Abrir rota no Google Maps</a>
    <?php endif; ?>
  </section>

  <?php /* ---- Baixa (recebedor) ---- */ ?>
  <?php if (Nota::podeDarBaixa($n, $perfil)): ?>
  <section class="card baixa">
    <h2>Dar baixa no recebimento</h2>
    <p class="sub">Confirme que os animais desta nota chegaram. A nota passa para <strong>Entregue</strong>.</p>
    <form method="post" action="<?= e(url('nota-baixa')) ?>" onsubmit="return confirm('Confirmar o recebimento desta nota?')">
      <?= csrf_campo() ?>
      <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
      <div class="grade">
        <label>Quantidade recebida<input type="number" name="quantidade" min="0" step="1" placeholder="<?= e($n['quantidade'] ?? '') ?>"></label>
        <label>Observações (opcional)<input type="text" name="obs" maxlength="255" placeholder="Ex.: 1 animal chegou machucado"></label>
      </div>
      <button class="btn" type="submit">Confirmar recebimento</button>
    </form>
  </section>
  <?php endif; ?>

  <?php if ($recebimento): ?>
  <section class="card">
    <h2>Recebimento</h2>
    <dl class="detalhes duas-col">
      <?php foreach ($recebimento as $rotulo => $valor): ?>
        <div><dt><?= e($rotulo) ?></dt><dd><?= e($valor) ?></dd></div>
      <?php endforeach; ?>
    </dl>
  </section>
  <?php endif; ?>

  <?php if ($admin): ?>
  <section class="card">
    <form method="post" action="<?= e(url('nota-status')) ?>" class="status-form">
      <?= csrf_campo() ?>
      <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
      <label>Alterar status (administrador)
        <select name="status">
          <?php foreach (Nota::STATUS as $valor => $rotulo): ?>
            <option value="<?= e($valor) ?>" <?= $n['status'] === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <button class="btn" type="submit">Atualizar</button>
    </form>
  </section>
  <?php endif; ?>

  <div class="duas-colunas">
    <section class="card">
      <h2>Destino</h2>
      <dl class="detalhes">
        <?php foreach ($camposDestino as $col => $rotulo): ?>
          <div><dt><?= e($rotulo) ?></dt><dd><?= $n[$col] !== null && $n[$col] !== '' ? e($n[$col]) : '—' ?></dd></div>
        <?php endforeach; ?>
        <div><dt>Localização</dt><dd><?= !empty($n['destino_localizacao']) ? e($n['destino_localizacao']) : '—' ?></dd></div>
      </dl>
    </section>
    <section class="card">
      <h2>Origem (UEP / Produtor)</h2>
      <dl class="detalhes">
        <?php foreach ($camposOrigem as $col => $rotulo): ?>
          <div><dt><?= e($rotulo) ?></dt><dd><?= $n[$col] !== null && $n[$col] !== '' ? e($n[$col]) : '—' ?></dd></div>
        <?php endforeach; ?>
        <div><dt>Localização</dt><dd><?= !empty($n['origem_localizacao']) ? e($n['origem_localizacao']) : '—' ?></dd></div>
      </dl>
    </section>
  </div>

  <section class="card espaco-topo">
    <h2>Transporte</h2>
    <dl class="detalhes duas-col">
      <?php foreach ($transporte as $rotulo => $valor): ?>
        <div><dt><?= e($rotulo) ?></dt><dd><?= e($valor) ?></dd></div>
      <?php endforeach; ?>
    </dl>
  </section>
</main>
<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
