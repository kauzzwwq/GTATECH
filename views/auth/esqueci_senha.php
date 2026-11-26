<?php
$titulo = 'Recuperar senha'; $semTopo = true;
require BASE_PATH . '/views/layouts/header.php';
?>
<main class="card">
  <div class="auth-logos">
    <img src="assets/images/Logo-SENAI_EP.png" alt="SENAI" class="logo-senai">
    <img src="assets/images/Logo-S.png" alt="" class="logo-s">
  </div>
  <span class="eyebrow">Recuperação</span>
  <h1>Recuperar senha</h1>
  <p class="sub">Informe o e-mail cadastrado. Enviaremos um link para você criar uma nova senha.</p>
  <?php if ($msg): ?>
    <div class="ok">
      <?= e($msg) ?><br>
      <small>Não chegou? Veja a caixa de spam e aguarde 1 minuto antes de pedir de novo.</small>
    </div>
  <?php endif; ?>
  <?php if (!empty($linkDev)): ?>
    <div class="alerta aviso-dev">
      <strong>Modo de teste (só no seu computador):</strong><br>
      <a href="<?= e($linkDev) ?>">Clique aqui para criar a nova senha</a>
    </div>
  <?php endif; ?>
  <form method="post">
    <?= csrf_campo() ?>
    <label>E-mail<input type="email" name="email" required autofocus placeholder="seu@email.com" autocomplete="username"></label>
    <button type="submit">Enviar link de recuperação</button>
  </form>
  <p class="rodape"><a href="<?= e(url('login')) ?>">← Voltar para o login</a></p>
</main>
<?php require BASE_PATH . '/views/layouts/footer.php'; ?>
