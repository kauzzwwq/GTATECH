<?php
$titulo = $id ? 'Editar motorista' : 'Cadastrar motorista';
require BASE_PATH . '/views/layouts/header.php';
?>
<main class="conteudo">
  <div class="page-head">
    <div>
      <h1><?= e($titulo) ?></h1>
      <p class="sub"><?= $id ? 'Altere os dados do motorista e salve.' : 'Preencha os dados do motorista e do veículo.' ?></p>
    </div>
  </div>

  <?php if ($erros): ?>
    <div class="alerta">
      <strong>Corrija antes de salvar:</strong>
      <ul class="lista-erros"><?php foreach ($erros as $erro): ?><li><?= e($erro) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form action="<?= e(url('motorista-salvar')) ?>" method="post">
    <?= csrf_campo() ?>
    <input type="hidden" name="id" value="<?= (int)$id ?>">

    <section class="card">
      <h2>Dados do motorista</h2>
      <div class="grade">
        <label>Nome completo
          <input type="text" name="nome" required value="<?= e($motorista['nome'] ?? '') ?>" placeholder="Digite o nome completo">
        </label>
        <label>CPF
          <input type="text" name="cpf" class="cpf-cnpj" inputmode="numeric" maxlength="14" value="<?= e($motorista['cpf'] ?? '') ?>" placeholder="000.000.000-00">
        </label>
        <label>CNH
          <input type="text" name="cnh" inputmode="numeric" maxlength="11" value="<?= e($motorista['cnh'] ?? '') ?>" placeholder="11 números">
        </label>
        <label>Telefone
          <input type="tel" name="telefone" class="telefone" maxlength="15" value="<?= e($motorista['telefone'] ?? '') ?>" placeholder="(00) 00000-0000">
        </label>
      </div>
    </section>

    <section class="card">
      <h2>Dados do veículo</h2>
      <div class="grade">
        <label>Modelo do veículo
          <input type="text" name="modelo_veiculo" value="<?= e($motorista['modelo_veiculo'] ?? '') ?>" placeholder="Ex.: Caminhão boiadeiro">
        </label>
        <label>Placa do veículo
          <input type="text" name="placa_veiculo" class="placa" maxlength="8" value="<?= e($motorista['placa_veiculo'] ?? '') ?>" placeholder="ABC1D23">
        </label>
      </div>
    </section>

    <section class="card">
      <h2>Acesso ao sistema (login)</h2>
      <div class="grade">
        <label>Usuário ligado a este motorista
          <select name="usuario_id">
            <option value="">— Sem login —</option>
            <?php foreach ($usuarios as $u): ?>
              <option value="<?= (int)$u['id'] ?>" <?= (int)($motorista['usuario_id'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>><?= e($u['nome']) ?> (<?= e($u['email']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <p class="dica">
        Com login, o motorista entra no sistema e vê só as notas dele (e a rota no Google Maps).
        Só aparecem usuários com perfil Motorista que ainda não estão ligados a outro motorista.
        <?php if (!$usuarios): ?><strong>Nenhum disponível agora.</strong><?php endif; ?>
        Para criar o login, vá em <a href="<?= e(url('usuario-form')) ?>">Usuários</a> com o perfil Motorista; depois volte aqui e selecione.
      </p>
    </section>

    <div class="barra-acoes">
      <a class="btn sec" href="<?= e(url('motoristas')) ?>">Cancelar</a>
      <button class="btn" type="submit">Salvar motorista</button>
    </div>
  </form>
</main>
<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
