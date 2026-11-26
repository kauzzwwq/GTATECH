<?php
$titulo = 'Painel';
require BASE_PATH . '/views/layouts/header.php';
use App\Helpers\Helper;
use App\Models\Nota;
$perfil = Helper::perfil();
$podeEmitir = in_array($perfil, ['admin', 'produtor'], true);
$frases = [
    'admin'        => 'Visão geral de todas as notas de transporte.',
    'produtor'     => 'Suas notas de transporte: crie, acompanhe e baixe o PDF.',
    'motorista'    => 'Suas viagens: veja a nota, baixe o PDF e siga a rota no Google Maps.',
    'destinatario' => 'Notas que chegam até você: confira e dê baixa no recebimento.',
];
?>
<main class="conteudo">
  <div class="page-head">
    <div>
      <h1>Olá, <?= e(explode(' ', trim($_SESSION['usuario_nome'] ?? ''))[0]) ?></h1>
      <p class="sub"><?= e($frases[$perfil] ?? 'Estas são as notas de transporte atribuídas a você.') ?></p>
    </div>
    <?php if ($podeEmitir): ?>
    <?php endif; ?>
  </div>

  <section class="stats">
    <div class="stat"><span class="stat-num"><?= (int)$total ?></span><span class="stat-rot">Notas</span></div>
    <div class="stat"><span class="stat-num"><?= (int)$andamento ?></span><span class="stat-rot">Em andamento</span></div>
    <div class="stat"><span class="stat-num"><?= (int)$entregues ?></span><span class="stat-rot">Entregues</span></div>
    <div class="stat <?= $vencidas ? 'alerta-stat' : '' ?>"><span class="stat-num"><?= (int)$vencidas ?></span><span class="stat-rot">Vencidas</span></div>
  </section>

  <section class="card">
    <div class="card-topo">
      <h2>Últimas notas</h2>
      <a href="<?= e(url('notas')) ?>">Ver todas →</a>
    </div>
    <?php if (!$recentes): ?>
      <p class="vazio">Nenhuma nota por aqui ainda.</p>
    <?php else: ?>
    <div class="tabela-wrap">
      <table>
        <thead><tr><th>Nº</th><th>Destino</th><th>Município</th><th>Motorista</th><th>Status</th><th>Data</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($recentes as $n): ?>
          <tr>
            <td><strong><?= e(Helper::numeroNota($n)) ?></strong></td>
            <td><?= e($n['destino_estabelecimento']) ?></td>
            <td><?= e($n['destino_municipio']) ?></td>
            <td><?= e($n['motorista']) ?: '—' ?></td>
            <td>
              <span class="badge st-<?= e($n['status']) ?>"><?= e(Nota::STATUS[$n['status']] ?? $n['status']) ?></span>
              <?php if (Nota::vencida($n)): ?><span class="badge st-vencida">Vencida</span><?php endif; ?>
            </td>
            <td><?= e(Helper::data($n['criado_em'])) ?></td>
            <td class="acoes">
              <a href="<?= e(url('nota-ver', ['id' => $n['id']])) ?>">Ver</a>
              <a href="<?= e(url('nota-pdf', ['id' => $n['id']])) ?>" target="_blank">PDF</a>
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
