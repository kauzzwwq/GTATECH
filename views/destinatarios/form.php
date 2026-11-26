<?php
$titulo = ($id ? 'Editar ' : 'Novo ') . mb_strtolower($cfg['singular']);
require BASE_PATH . '/views/layouts/header.php';
use App\Models\Usuario;
?>
<main class="conteudo">
  <div class="page-head">
    <div>
      <h1><?= e($titulo) ?></h1>
      <p class="sub">Esses dados poderão ser escolhidos ao criar uma nota.</p>
    </div>
  </div>

  <?php if ($erros): ?>
    <div class="alerta">
      <ul class="lista-erros"><?php foreach ($erros as $erro): ?><li><?= e($erro) ?></li><?php endforeach; ?></ul>
    </div>
  <?php endif; ?>

  <form action="<?= e(url('destinatario-salvar')) ?>" method="post">
    <?= csrf_campo() ?>
    <input type="hidden" name="id" value="<?= (int)$id ?>">
    <section class="card">
      <div class="grade">
        <?php foreach ($cfg['campos'] as $campo => $rotulo): ?>
          <label <?= $campo === 'localizacao' ? 'class="largo"' : '' ?>><?= e($rotulo) ?>
            <input type="text" name="<?= e($campo) ?>" value="<?= e($item[$campo] ?? '') ?>"
              <?= $campo === 'cpf_cnpj' ? 'class="cpf-cnpj" inputmode="numeric" maxlength="18"' : '' ?>
              <?= $campo === 'localizacao' ? 'maxlength="255" placeholder="Endereço ou coordenadas copiadas do Google Maps"' : '' ?>
              <?= $campo === $cfg['principal'] ? 'required' : '' ?>>
          </label>
        <?php endforeach; ?>
      </div>
    </section>

    <section class="card">
      <h2>Acesso ao sistema (login)</h2>
      <div class="grade">
        <label>Usuário ligado a este cadastro
          <select name="usuario_id">
            <option value="">— Sem login —</option>
            <?php foreach ($usuarios as $u): ?>
              <option value="<?= (int)$u['id'] ?>" <?= (int)($item['usuario_id'] ?? 0) === (int)$u['id'] ? 'selected' : '' ?>><?= e($u['nome']) ?> (<?= e($u['email']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </label>
      </div>
      <p class="dica">
        Só aparecem usuários com o perfil <strong><?= e(Usuario::PERFIS[$cfg['perfil']]) ?></strong> que ainda não estão ligados a outro cadastro.
        <?php if (!$usuarios): ?><strong>Nenhum disponível agora.</strong><?php endif; ?>
        Para criar o login, vá em <a href="<?= e(url('usuario-form')) ?>">Usuários</a> e escolha esse perfil; depois volte aqui e selecione.
      </p>
    </section>

    <div class="barra-acoes">
      <a class="btn sec" href="<?= e(url('destinatarios')) ?>">Cancelar</a>
      <button class="btn" type="submit">Salvar</button>
    </div>
  </form>
</main>
<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
