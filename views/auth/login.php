<?php
$titulo = 'Login'; $semTopo = true;
require BASE_PATH . '/views/layouts/header.php';
?>
<main class="card">
  <div class="auth-logos">
    <img src="assets/images/Logo-SENAI_EP.png" alt="SENAI" class="logo-senai">
  </div>
  <span class="eyebrow">Área restrita</span>
  <h1>Acesso ao sistema</h1>
  <p class="sub">Faça login para gerenciar as notas de transporte animal.</p>
  <?php if ($erro): ?><div class="alerta"><?= e($erro) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_campo() ?>
    <label>E-mail<input type="email" name="email" required autofocus placeholder="seu@email.com" autocomplete="username"></label>
    <label>Senha
      <span class="senha-wrap">
        <input type="password" name="senha" required placeholder="••••••••" autocomplete="current-password">
        <button type="button" class="ver-senha">mostrar</button>
      </span>
    </label>
    <button type="submit">Entrar</button>
  </form>
  <div class="links-uteis">
    <a href="<?= e(url('esqueci-senha')) ?>">Esqueceu a senha?</a>
  </div>
</main>
  <style>
    
    .links-uteis {
    display: flex;
    justify-content: center;
    }
  </style>



<?php require BASE_PATH . '/views/layouts/footer.php'; ?>

