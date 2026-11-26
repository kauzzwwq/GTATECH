<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title><?= e($titulo ?? 'Início') ?> — Notas de Transporte Animal · SENAI</title>
<meta name="theme-color" content="#003087">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-title" content="Notas Animais">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<link rel="manifest" href="manifest.webmanifest">
<link rel="icon" type="image/png" href="assets/images/Logo-S.png">
<link rel="apple-touch-icon" href="assets/icons/apple-touch-icon.png">
<link rel="stylesheet" href="assets/css/style.css">
<link rel="stylesheet" href="assets/css/responsivo.css">
<script src="assets/js/app.js" defer></script>
</head>
<?php if (!empty($semTopo)): ?>
<body class="auth-body">
<div class="auth">
  <aside class="auth-visual">
    <img src="assets/images/login-boiadeiro.jpg" alt="Caminhão boiadeiro na estrada" onerror="this.onerror=null;this.src='assets/images/login-transporte.svg'">
    <div class="auth-texto">
      <h2>Transporte animal com controle e segurança</h2>
      <p>Emita, acompanhe e baixe suas notas de transporte em um só lugar.</p>
    </div>
  </aside>
  <div class="auth-painel">
<?php else:
  $perfilAtual = $_SESSION['usuario_perfil'] ?? 'trabalhador';
  $nomeAtual   = $_SESSION['usuario_nome'] ?? '';
  $rotaAtual   = $_GET['r'] ?? '';
  $tipoAtual   = $_GET['tipo'] ?? 'produtores';
  $ativo = fn(array $rotas): string => in_array($rotaAtual, $rotas, true) ? 'ativo' : '';
  $svg = 'viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';
  $rotuloPerfil = \App\Models\Usuario::PERFIS[$perfilAtual] ?? 'Usuário';
  $rotuloNotas = ['admin' => 'Notas', 'produtor' => 'Minhas notas', 'motorista' => 'Minhas viagens', 'destinatario' => 'Notas recebidas'][$perfilAtual] ?? 'Minhas notas';
  $podeEmitir = in_array($perfilAtual, ['admin', 'produtor'], true);
?>
<body>
<div class="app">
  <aside class="sidebar">
    <a class="marca" href="<?= e(url('dashboard')) ?>">
      <img src="assets/images/Logo-SENAI_EP.png" alt="SENAI" class="logo-senai">
      <img src="assets/images/Logo-S.png" alt="SENAI" class="logo-compacto">
      <span class="marca-nome">GTA TECH</span>
      <span class="marca-unidade">SENAI SC · Unidade Videira</span>
    </a>

    <nav class="menu" aria-label="Navegação principal">
      <a class="<?= $ativo(['dashboard']) ?>" href="<?= e(url('dashboard')) ?>">
        <svg <?= $svg ?>><path d="M3 11l9-8 9 8v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/></svg>Painel
      </a>
      <a class="<?= $ativo(['notas', 'nota-ver']) ?>" href="<?= e(url('notas')) ?>">
        <svg <?= $svg ?>><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h8"/></svg><?= e($rotuloNotas) ?>
      </a>
      <?php if ($podeEmitir): ?>
      <?php endif; ?>

      <?php if ($perfilAtual === 'admin'): ?>
      <a class="<?= $ativo(['produtores', 'produtor-form', 'produtor-salvar']) ?>" href="<?= e(url('produtores')) ?>">
        <svg <?= $svg ?>><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>Produtores
      </a>
      <a class="<?= $ativo(['destinatarios', 'destinatario-form', 'destinatario-salvar']) ?>" href="<?= e(url('destinatarios')) ?>">
        <svg <?= $svg ?>><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>Destinatários
      </a>
      <a class="<?= $ativo(['motoristas', 'motorista-form', 'motorista-salvar']) ?>" href="<?= e(url('motoristas')) ?>">
        <svg <?= $svg ?>><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3"/><path d="M12 2v7M12 15v7M2 12h7M15 12h7"/></svg>Motoristas
      </a>
      <a class="<?= $ativo(['usuarios', 'usuario-form']) ?>" href="<?= e(url('usuarios')) ?>">
        <svg <?= $svg ?>><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"/></svg>Usuários
      </a>
      <a class="<?= $ativo(['rfid', 'rfid-tags']) ?>" href="<?= e(url('rfid')) ?>">
        <svg <?= $svg ?>><path d="M4 8V6a2 2 0 0 1 2-2h2M4 16v2a2 2 0 0 0 2 2h2M20 8V6a2 2 0 0 0-2-2h-2M20 16v2a2 2 0 0 1-2 2h-2"/><circle cx="12" cy="12" r="2"/><path d="M12 8a4 4 0 0 1 4 4M12 16a4 4 0 0 0 4-4"/></svg>Controle RFID
      </a>
      <?php endif; ?>
    </nav>

    <div class="sair-box">
      <a href="<?= e(url('logout')) ?>" class="btn-sair" data-sair title="Sair da conta" aria-label="Sair da conta">
        <svg class="icone-sair" <?= $svg ?> aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
        <span>Sair da conta</span>
      </a>
    </div>

  </aside>
  <div class="main">

<?php endif; ?>