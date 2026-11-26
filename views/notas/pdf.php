<?php
use App\Helpers\Helper;
use App\Models\Nota;
?><!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<style>
  @page { margin: 22mm 18mm 18mm 18mm; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
  .quebra { page-break-after: always; }
  .cab { border-bottom: 3px solid #003087; padding-bottom: 8px; margin-bottom: 14px; }
  .cab table { width: 100%; }
  .titulo { font-size: 17px; font-weight: bold; color: #003087; }
  .num { text-align: right; font-size: 13px; }
  .status { font-size: 10px; color: #636366; }
  .cancelada { text-align: center; color: #D63031; border: 2px solid #D63031; font-size: 16px; font-weight: bold; padding: 6px; margin-bottom: 12px; }
  h2 { font-size: 12px; background: #003087; color: #fff; padding: 5px 8px; margin: 16px 0 0; border-left: 4px solid #F47920; }
  table.dados { width: 100%; border-collapse: collapse; }
  table.dados th { width: 32%; text-align: left; background: #F0F4FB; border: 1px solid #E5E5EA; padding: 6px 8px; color: #003087; }
  table.dados td { border: 1px solid #E5E5EA; padding: 6px 8px; }
  .assin { margin-top: 60px; width: 100%; }
  .assin td { width: 50%; text-align: center; padding: 0 20px; }
  .linha { border-top: 1px solid #111; padding-top: 4px; }
  .rota-pdf { margin: 10px 0 0; font-size: 10px; }
  .emissao { margin-top: 26px; text-align: center; font-size: 9px; color: #666; }
</style>
</head>
<body>
<?php foreach ($lista as $i => $n): $transporte = Nota::resumoTransporte($n); $recebimento = Nota::resumoRecebimento($n); $rota = Helper::urlRotaMaps($n); ?>
  <div class="<?= $i < count($lista) - 1 ? 'quebra' : '' ?>">
    <div class="cab">
      <table><tr>
        <td class="titulo">NOTA DE TRANSPORTE ANIMAL</td>
        <td class="num">
          Nº <strong><?= e(Helper::numeroNota($n)) ?></strong><br>
          Data: <?= e(Helper::data($n['criado_em'])) ?><br>
          <span class="status">Status: <?= e(Nota::STATUS[$n['status']] ?? $n['status']) ?></span>
        </td>
      </tr></table>
    </div>

    <?php if ($n['status'] === 'cancelada'): ?><div class="cancelada">NOTA CANCELADA</div><?php endif; ?>

    <h2>DESTINO</h2>
    <table class="dados">
      <?php foreach ($camposDestino as $col => $rotulo): ?>
        <tr><th><?= e($rotulo) ?></th><td><?= e($n[$col]) ?></td></tr>
      <?php endforeach; ?>
    </table>

    <h2>ORIGEM (UEP / PRODUTOR)</h2>
    <table class="dados">
      <?php foreach ($camposOrigem as $col => $rotulo): ?>
        <tr><th><?= e($rotulo) ?></th><td><?= e($n[$col]) ?></td></tr>
      <?php endforeach; ?>
    </table>

    <h2>TRANSPORTE</h2>
    <table class="dados">
      <?php foreach ($transporte as $rotulo => $valor): ?>
        <tr><th><?= e($rotulo) ?></th><td><?= e($valor) ?></td></tr>
      <?php endforeach; ?>
    </table>

    <?php if ($rota): ?>
      <p class="rota-pdf">Rota no Google Maps: <a href="<?= e($rota) ?>">abrir trajeto da origem ao destino</a></p>
    <?php endif; ?>

    <?php if ($recebimento): ?>
      <h2>RECEBIMENTO (BAIXA)</h2>
      <table class="dados">
        <?php foreach ($recebimento as $rotulo => $valor): ?>
          <tr><th><?= e($rotulo) ?></th><td><?= e($valor) ?></td></tr>
        <?php endforeach; ?>
      </table>
    <?php endif; ?>

    <table class="assin"><tr>
      <td><div class="linha">Responsável pela emissão</div></td>
      <td><div class="linha">Produtor / Proprietário</div></td>
    </tr></table>

    <div class="emissao">Emitida por <?= e($n['emitido_por']) ?> em <?= e(Helper::dataHora($n['criado_em'])) ?></div>
  </div>
<?php endforeach; ?>
</body>
</html>
