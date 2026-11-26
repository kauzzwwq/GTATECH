<?php
$titulo = $id ? 'Editar nota' : 'Nova nota';
require BASE_PATH . '/views/layouts/header.php';

use App\Helpers\Helper;
use App\Models\Nota;

// <input> de texto de um campo da nota (campos de CPF/CNPJ ganham máscara automática)
$campo = function (string $nome, string $rotulo) use ($nota): string {
    $classe = substr($nome, -8) === 'cpf_cnpj' ? ' class="cpf-cnpj" inputmode="numeric" maxlength="18"' : '';
    return '<label>' . e($rotulo) . '<input type="text" name="' . e($nome) . '"' . $classe . ' value="' . e($nota[$nome] ?? '') . '"></label>';
};

// <select> ligado a um cadastro: ao escolher, os campos da nota são preenchidos (e continuam editáveis)
$seletor = function (string $nomeCampo, string $titulo, array $itens, string $tipo, $selecionado, string $dica = '') use ($admin): string {
    $h = '<label class="seletor">' . e($titulo) . '<select name="' . e($nomeCampo) . '" data-picker><option value="">— Escolher do cadastro —</option>';
    foreach ($itens as $i) {
        $sel = (string)$selecionado === (string)$i['id'] ? ' selected' : '';
        $h .= '<option value="' . (int)$i['id'] . '"' . $sel . ' data-valores="' . e(json_encode($i['valores'], JSON_UNESCAPED_UNICODE)) . '">' . e($i['rotulo']) . '</option>';
    }
    $h .= '</select></label>';
    if ($dica !== '') {
        $h .= '<p class="dica">' . $dica . '</p>';
    }
    if (!$itens && $admin) {
        $h .= '<p class="dica">Nenhum cadastro ainda. <a href="' . e($tipo === 'motoristas' ? url('motorista-form') : url('cadastro-form', ['tipo' => $tipo])) . '">Cadastrar agora</a></p>';
    }
    return $h;
};
?>
<main class="conteudo">
  <div class="page-head">
    <div>
      <h1><?= $id ? 'Editar nota Nº ' . e(Helper::numeroNota($nota)) : 'Nova nota de transporte' ?></h1>
      <p class="sub"><?= $id ? 'Altere os dados e salve.' : 'O número oficial (ANO/SEQUÊNCIA) é gerado automaticamente ao salvar.' ?></p>
    </div>
  </div>

  <?php if ($msg): ?><div class="ok"><?= e($msg) ?></div><?php endif; ?>
  <?php if ($erros): ?>
    <div class="alerta">
      <strong>Corrija antes de salvar:</strong>
      <ul class="lista-erros"><?php foreach ($erros as $erro): ?><li><?= e($erro) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form method="post">
    <?= csrf_campo() ?>

    <section class="card">
      <h2>Status</h2>
      <div class="grade">
        <label>Status da nota
          <select name="status">
            <?php foreach ($status as $valor => $rotulo): ?>
              <option value="<?= e($valor) ?>" <?= ($nota['status'] ?? 'emitida') === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Número da GTA
          <input type="text" name="gta_numero" maxlength="30" value="<?= e($nota['gta_numero'] ?? '') ?>" placeholder="Ex.: 2026/0042">
        </label>
      </div>
      <p class="dica">
        <strong>Rascunho</strong>: só você vê. <strong>Emitida</strong>: o motorista e o recebedor já enxergam a nota.
        O <strong>número da GTA</strong> é obrigatório para emitir — é ele que aparece no visor do leitor RFID.
        <?php if (!$admin): ?>Depois disso, o andamento (em trânsito, entregue) é feito pelo administrador e pelo recebedor.<?php endif; ?>
      </p>
    </section>

    <section class="card">
      <h2>Destino</h2>
      <?= $seletor('destinatario_id', 'Destinatário (recebedor)', $destinatarios, 'destinatarios', $nota['destinatario_id'] ?? '',
            'Escolha um destinatário cadastrado: só ele poderá ver a nota e dar baixa no recebimento.') ?>
      <div class="grade">
        <?php foreach ($camposDestino as $nome => $rotulo): ?><?= $campo($nome, $rotulo) ?><?php endforeach; ?>
        <label class="largo">Localização do destino (endereço ou coordenadas)
          <input type="text" name="destino_localizacao" maxlength="255" value="<?= e($nota['destino_localizacao'] ?? '') ?>" placeholder="Ex.: Rodovia SC-110, km 5, Treze Tílias  ou  -27.0036,-51.4013">
        </label>
      </div>
    </section>

    <section class="card">
      <h2>Origem (UEP / Produtor)</h2>
      <?php if ($admin): ?>
        <?= $seletor('produtor_id', 'Produtor', $produtores, 'produtores', $nota['produtor_id'] ?? '') ?>
      <?php else: ?>
        <p class="dica">Os dados da origem já vêm preenchidos com o seu cadastro de produtor.</p>
      <?php endif; ?>
      <div class="grade">
        <?php foreach ($camposOrigem as $nome => $rotulo): ?><?= $campo($nome, $rotulo) ?><?php endforeach; ?>
        <label class="largo">Localização da origem (endereço ou coordenadas)
          <input type="text" name="origem_localizacao" maxlength="255" value="<?= e($nota['origem_localizacao'] ?? '') ?>" placeholder="Endereço da propriedade ou coordenadas copiadas do Google Maps">
        </label>
      </div>
    </section>

    <section class="card">
      <h2>Transporte</h2>
      <?= $seletor('motorista_id', 'Motorista', $motoristas, 'motoristas', $nota['motorista_id'] ?? '',
            'Escolha um motorista cadastrado: só ele poderá ver a nota e a rota no Google Maps.') ?>
      <div class="grade">
        <label>Espécie<input type="text" name="especie" value="<?= e($nota['especie'] ?? '') ?>" placeholder="Ex.: Bovinos"></label>
        <label>Quantidade de animais<input type="number" name="quantidade" min="1" step="1" value="<?= e($nota['quantidade'] ?? '') ?>"></label>
        <label>Finalidade
          <select name="finalidade">
            <option value="">—</option>
            <?php foreach (Nota::FINALIDADES as $valor => $rotulo): ?>
              <option value="<?= e($valor) ?>" <?= ($nota['finalidade'] ?? '') === $valor ? 'selected' : '' ?>><?= e($rotulo) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Data e hora da saída<input type="datetime-local" name="data_saida" value="<?= e(str_replace(' ', 'T', substr((string)($nota['data_saida'] ?? ''), 0, 16))) ?>"></label>
        <label>Placa do veículo<input type="text" name="placa" class="placa" maxlength="8" value="<?= e($nota['placa'] ?? '') ?>" placeholder="ABC1D23"></label>
        <label>Nome do motorista (como sai na nota)<input type="text" name="motorista" value="<?= e($nota['motorista'] ?? '') ?>"></label>
        <label>Validade da nota<input type="date" name="validade" value="<?= e($nota['validade'] ?? '') ?>"></label>
      </div>
      <p class="dica">
        <button type="button" class="btn sec sm" id="testar-rota">Testar rota no Google Maps</button>
        Abre o Maps com a origem e o destino que você preencheu acima.
      </p>
    </section>

    <div class="barra-acoes">
      <a class="btn sec" href="<?= e(url('notas')) ?>">Cancelar</a>
      <button class="btn" type="submit">Salvar nota</button>
    </div>
  </form>
</main>
<?php require BASE_PATH . '/views/layouts/footer.php'; ?>