<?php
$titulo = 'Redefinir senha'; $semTopo = true;
require BASE_PATH . '/views/layouts/header.php';
?>
<main class="card">
  <div class="auth-logos">
    <img src="assets/images/Logo-SENAI_EP.png" alt="SENAI" class="logo-senai">
    <img src="assets/images/Logo-S.png" alt="" class="logo-s">
  </div>
  <span class="eyebrow">Nova senha</span>
  <h1>Redefinir senha</h1>
  <p class="sub">Defina a sua nova senha de acesso.</p>

  <?php if ($erro && !$sucesso): ?><div class="alerta"><?= e($erro) ?></div><?php endif; ?>
  <?php if ($sucesso): ?>
    <div class="ok"><?= e($sucesso) ?></div>
    <p class="rodape"><a class="btn" href="<?= e(url('login')) ?>">Ir para o login</a></p>
  <?php elseif ($rec): ?>
    <form method="post">
      <?= csrf_campo() ?>
      <input type="hidden" name="token" value="<?= e($token) ?>">
      <label>Nova senha
        <span class="senha-wrap">
          <input type="password" name="senha" required minlength="6" placeholder="Mínimo 6 caracteres" autocomplete="new-password">
          <button type="button" class="ver-senha">Mostrar</button>
        </span>
      </label>
      <label>Confirme a nova senha
        <input type="password" name="confirma_senha" required minlength="6" placeholder="Repita a senha" autocomplete="new-password">
      </label>
      <button type="submit">Salvar nova senha</button>
    </form>
  <?php else: ?>
    <p class="rodape"><a class="btn" href="<?= e(url('esqueci-senha')) ?>">Pedir um novo link</a></p>
  <?php endif; ?>
</main>
<?php require BASE_PATH . '/views/layouts/footer.php'; ?>