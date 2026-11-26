<?php
$titulo = 'Trocar senha';
require BASE_PATH . '/views/layouts/header.php';
use App\Models\Usuario;
?>
<main class="conteudo">
  <div class="page-head">
    <div>
      <h1>Trocar senha</h1>
      <p class="sub">
        <strong><?= e($alvo['nome']) ?></strong>
        · <?= e($alvo['email']) ?>
        · <span class="badge trab"><?= e(Usuario::PERFIS[$alvo['perfil']] ?? $alvo['perfil']) ?></span>
      </p>
    </div>
  </div>

  <?php if ($erro): ?><div class="alerta"><?= e($erro) ?></div><?php endif; ?>

  <form method="post">
    <?= csrf_campo() ?>
    <input type="hidden" name="id" value="<?= (int)$alvo['id'] ?>">
    <input type="hidden" name="retorno" value="<?= e($retorno) ?>">
    <section class="card">
      <div class="grade">
        <label>Nova senha
          <span class="senha-wrap">
            <input type="password" name="senha" required minlength="6" autofocus autocomplete="new-password" placeholder="Mínimo 6 caracteres">
            <button type="button" class="ver-senha">Mostrar</button>
          </span>
        </label>
        <label>Confirme a nova senha
          <input type="password" name="confirma_senha" required minlength="6" autocomplete="new-password" placeholder="Repita a senha">
        </label>
      </div>
      <p class="dica">Passe a nova senha para a pessoa. Ela vale na hora, e a senha antiga deixa de funcionar.</p>
    </section>
    <div class="barra-acoes">
      <a class="btn sec" href="<?= e($voltar) ?>">Cancelar</a>
      <button class="btn" type="submit">Salvar nova senha</button>
    </div>
  </form>
</main>
<?php require BASE_PATH . '/views/layouts/footer.php'; ?>