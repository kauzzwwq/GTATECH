<?php
use App\Models\Usuario;

$titulo = $id ? 'Editar usuário' : 'Novo usuário';
require BASE_PATH . '/views/layouts/header.php';

$extra = $_POST;

// Fonte dos valores: POST (quando houve erro) > cadastro vinculado > vazio
$val = function (string $campo, ?array $registro, string $coluna = null) use ($extra) {
    if (isset($extra[$campo]) && $extra[$campo] !== '') {
        return $extra[$campo];
    }
    if ($registro) {
        return $registro[$coluna ?? $campo] ?? '';
    }
    return '';
};
?>

<main class="conteudo">
  <div class="page-head">
    <div>
      <h1><?= $id ? 'Editar usuário' : 'Novo usuário' ?></h1>
      <p class="sub">Cadastre a pessoa e escolha seu tipo de usuário.</p>
    </div>
  </div>

  <?php if ($erro): ?>
    <div class="alerta"><?= e($erro) ?></div>
  <?php endif; ?>

  <form method="post">
    <?= csrf_campo() ?>

    <section class="card">
      <h2>Dados básicos</h2>

      <div class="grade">
        <label id="label-nome">
          <span id="rotulo-nome">Nome</span>
          <input type="text" name="nome" id="campo-nome" required
            placeholder="Ex.: João da Silva"
            value="<?= e($usuario['nome'] ?? '') ?>">
        </label>

        <label>
          E-mail (usado para entrar)
          <input type="email" name="email" required
            placeholder="nome@empresa.com.br"
            value="<?= e($usuario['email'] ?? '') ?>">
        </label>

        <label>
          Perfil
          <select name="perfil" id="perfil" required>
            <?php foreach (Usuario::PERFIS as $valor => $rotulo): ?>
              <?php if ($valor === 'trabalhador' && ($usuario['perfil'] ?? '') !== 'trabalhador') continue; ?>

              <option value="<?= e($valor) ?>"
                <?= ($usuario['perfil'] ?? 'produtor') === $valor ? 'selected' : '' ?>>
                <?= e($rotulo) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </label>

        <label>
          <?= $id ? 'Nova senha (deixe em branco para manter)' : 'Senha' ?>
          <input type="password" name="senha" minlength="6"
            placeholder="Mínimo 6 caracteres"
            <?= $id ? '' : 'required' ?> autocomplete="new-password">
        </label>

        <label>
          CPF/CNPJ
          <input type="text" name="cpf_cnpj" class="cpf-cnpj"
            inputmode="numeric" maxlength="18"
            placeholder="000.000.000-00"
            value="<?= e($usuario['cpf_cnpj'] ?? '') ?>">
        </label>

        <label>
          Telefone
          <input type="tel" name="telefone" class="telefone"
            maxlength="15" value="<?= e($usuario['telefone'] ?? '') ?>"
            placeholder="(00) 00000-0000">
        </label>
      </div>
    </section>

    <!-- DADOS DO MOTORISTA -->
    <section class="card" id="campos-motorista" hidden>
      <h2>Dados do motorista</h2>

      <div class="grade">
        <label>
          Número da CNH
          <input type="text" name="cnh"
            placeholder="Ex.: 12345678900"
            value="<?= e($val('cnh', $motorista)) ?>">
          <span class="ajuda">Número de registro da Carteira Nacional de Habilitação.</span>
        </label>

        <label>
          Placa do veículo
          <input type="text" name="placa_veiculo"
            placeholder="ABC1D23"
            maxlength="8"
            value="<?= e($val('placa_veiculo', $motorista)) ?>">
        </label>

        <label>
          Modelo do veículo
          <input type="text" name="modelo_veiculo"
            placeholder="Ex.: Fiat Fiorino"
            value="<?= e($val('modelo_veiculo', $motorista)) ?>">
        </label>
      </div>
    </section>

    <!-- DADOS DO PRODUTOR -->
    <section class="card" id="campos-produtor" hidden>
      <h2>Dados do produtor</h2>

      <div class="grade">
        <label>
          UEP
          <input type="text" name="uep"
            placeholder="Ex.: UEP-001"
            value="<?= e($val('uep', $produtor)) ?>">
          <span class="ajuda">Unidade de Exploração Pecuária — código da propriedade na defesa agropecuária.</span>
        </label>

        <label>
          Código UEP
          <input type="text" name="codigo_uep"
            placeholder="Ex.: 123456"
            value="<?= e($val('codigo_uep', $produtor)) ?>">
          <span class="ajuda">Código numérico oficial da UEP no sistema estadual.</span>
        </label>

        <label>
          Localização
          <input type="text" name="localizacao_produtor"
            placeholder="Ex.: Zona rural, km 12"
            value="<?= e($val('localizacao', $produtor)) ?>">
        </label>
      </div>
    </section>

    <!-- DADOS DO DESTINATÁRIO -->
    <section class="card" id="campos-destinatario" hidden>
      <h2>Dados do destinatário</h2>

      <div class="grade">
        <label>
          Proprietário
          <input type="text" name="proprietario"
            placeholder="Ex.: Carlos Pereira"
            value="<?= e($val('proprietario', $destinatario)) ?>">
        </label>

        <label>
          Código oficial
          <input type="text" name="codigo_oficial"
            placeholder="Ex.: 987654"
            value="<?= e($val('codigo_oficial', $destinatario)) ?>">
          <span class="ajuda">Código de registro do estabelecimento no órgão oficial.</span>
        </label>

        <label>
          Município
          <input type="text" name="municipio"
            placeholder="Ex.: Cascavel"
            value="<?= e($val('municipio', $destinatario)) ?>">
        </label>

        <label>
          Localização
          <input type="text" name="localizacao_destinatario"
            placeholder="Ex.: Rod. BR-277, km 500"
            value="<?= e($val('localizacao', $destinatario)) ?>">
        </label>
      </div>
    </section>

    <div class="barra-acoes">
      <a class="btn sec" href="<?= e(url('usuarios')) ?>">Cancelar</a>
      <button class="btn" type="submit">Salvar usuário</button>
    </div>
  </form>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const perfil = document.getElementById('perfil');
    const rotuloNome = document.getElementById('rotulo-nome');
    const campoNome = document.getElementById('campo-nome');

    const grupos = {
        motorista:    document.getElementById('campos-motorista'),
        produtor:     document.getElementById('campos-produtor'),
        destinatario: document.getElementById('campos-destinatario')
    };

    const rotulosNome = {
        motorista:    { rotulo: 'Nome', placeholder: 'Ex.: João da Silva' },
        produtor:     { rotulo: 'Nome do produtor', placeholder: 'Ex.: Fazenda Boa Vista' },
        destinatario: { rotulo: 'Nome do estabelecimento', placeholder: 'Ex.: Frigorífico Central' },
        admin:        { rotulo: 'Nome', placeholder: 'Ex.: João da Silva' },
        trabalhador:  { rotulo: 'Nome', placeholder: 'Ex.: João da Silva' }
    };

    function atualizarCampos() {
        Object.keys(grupos).forEach(function (tipo) {
            grupos[tipo].hidden = perfil.value !== tipo;
        });

        const cfg = rotulosNome[perfil.value] || rotulosNome.admin;
        rotuloNome.textContent = cfg.rotulo;
        campoNome.placeholder = cfg.placeholder;
    }

    perfil.addEventListener('change', atualizarCampos);
    atualizarCampos();
});
</script>

<?php require BASE_PATH . '/views/layouts/footer.php'; ?>