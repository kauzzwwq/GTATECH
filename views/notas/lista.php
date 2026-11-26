<?php
$titulo = 'Notas';
require BASE_PATH . '/views/layouts/header.php';
use App\Helpers\Helper;
use App\Models\Nota;
$perfil = Helper::perfil();
$admin = $perfil === 'admin';
$podeEmitir = in_array($perfil, ['admin', 'produtor'], true);
$filtrando = $filtros['q'] !== '' || $filtros['status'] !== '' || $filtros['de'] !== '' || $filtros['ate'] !== '' || $filtros['motorista'] !== '';
?>
<main class="conteudo">
  <div class="page-head">
    <div>
      <h1>Notas de transporte</h1>
      <p class="sub"><?= $podeEmitir ? 'Crie, edite e duplique notas. Baixe o PDF quando precisar.' : 'Visualize e baixe as notas ligadas a você.' ?></p>
    </div>
    <?php if ($podeEmitir): ?>
    <div class="acoes-topo"><a class="btn" href="<?= e(url('nota-form')) ?>">+ Nova nota</a></div>
    <?php endif; ?>
  </div>

  <?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>

  <section class="card">
    <form method="get" class="filtros">
      <input type="hidden" name="r" value="notas">
      <label class="f-busca">Buscar
        <input type="search" name="q" value="<?= e($filtros['q']) ?>" placeholder="Nº, estabelecimento, proprietário, município, produtor, placa, motorista...">
      </label>
      <label>Status
        <select name="status">
          <option value="">Todos</option>
          <?php foreach (Nota::STATUS as $valor => $rotulo): ?>
            <?php if (!$admin && $valor === 'rascunho') continue; ?>
            <option value="<?= e($valor) ?>" <?= $filtros['status'] === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
          <?php endforeach; ?>
          <option value="vencida" <?= $filtros['status'] === 'vencida' ? 'selected' : '' ?>>Vencidas</option>
        </select>
      </label>
      <label>De
        <input type="date" name="de" value="<?= e($filtros['de']) ?>">
      </label>
      <label>Até
        <input type="date" name="ate" value="<?= e($filtros['ate']) ?>">
      </label>
      <?php if ($admin): ?>
      <label>Motorista
        <select name="motorista">
          <option value="">Todos</option>
          <?php foreach ($motoristas as $m): ?>
            <option value="<?= (int)$m['id'] ?>" <?= $filtros['motorista'] === (string)$m['id'] ? 'selected' : '' ?>><?= e($m['nome']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>
      <?php endif; ?>
      <div class="f-botoes">
        <button class="btn" type="submit">Filtrar</button>
        <?php if ($filtrando): ?><a class="btn sec" href="<?= e(url('notas')) ?>">Limpar</a><?php endif; ?>
      </div>
    </form>

    <?php if (!$notas): ?>
      <p class="vazio"><?= $filtrando ? 'Nenhuma nota encontrada com esses filtros.' : ($podeEmitir ? 'Nenhuma nota cadastrada ainda.' : 'Nenhuma nota chegou até você ainda.') ?></p>
    <?php else: ?>
    <form id="lote" method="get" action="index.php" target="_blank" class="lote">
      <input type="hidden" name="r" value="nota-pdf-lote">
      <label class="marcar"><input type="checkbox" id="marcar-todas"> Marcar todas</label>
      <button class="btn sec" type="submit">PDF das selecionadas</button>
    </form>

    <div class="tabela-wrap">
      <table>
        <thead><tr>
          <th></th><th>Nº</th><th>Destino</th><th>Município</th><th>Motorista</th><th>Status</th>
          <th>Saída</th><th>Ações</th>
        </tr></thead>
        <tbody>
        <?php foreach ($notas as $n): ?>
          <tr>
            <td><input type="checkbox" name="ids[]" value="<?= (int)$n['id'] ?>" form="lote" aria-label="Selecionar nota"></td>
            <td><strong><?= e(Helper::numeroNota($n)) ?></strong></td>
            <td><?= e($n['destino_estabelecimento']) ?></td>
            <td><?= e($n['destino_municipio']) ?></td>
            <td><?= e($n['motorista']) ?: '—' ?></td>
            <td>
              <span class="badge st-<?= e($n['status']) ?>"><?= e(Nota::STATUS[$n['status']] ?? $n['status']) ?></span>
              <?php if (Nota::vencida($n)): ?><span class="badge st-vencida">Vencida</span><?php endif; ?>
            </td>
            <td><?= $n['data_saida'] ? e(Helper::dataHora($n['data_saida'])) : '—' ?></td>
            <td class="acoes">
              <a href="<?= e(url('nota-ver', ['id' => $n['id']])) ?>">Ver</a>
              <a href="<?= e(url('nota-pdf', ['id' => $n['id']])) ?>" target="_blank">PDF</a>
              <a href="<?= e(url('nota-pdf', ['id' => $n['id'], 'baixar' => 1])) ?>">Baixar</a>
              <?php if (Nota::podeEditar($n, $perfil)): ?>
                <a href="<?= e(url('nota-form', ['id' => $n['id']])) ?>">Editar</a>
              <?php endif; ?>
              <?php if ($podeEmitir): ?>
                <form method="post" action="<?= e(url('nota-duplicar')) ?>">
                  <?= csrf_campo() ?>
                  <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                  <button class="link" type="submit">Duplicar</button>
                </form>
              <?php endif; ?>
              <?php if (Nota::podeExcluir($n, $perfil)): ?>
                <form method="post" action="<?= e(url('nota-excluir')) ?>" onsubmit="return confirm('Excluir esta nota?')">
                  <?= csrf_campo() ?>
                  <input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                  <button class="link perigo" type="submit">Excluir</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </section>
</main>
<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
