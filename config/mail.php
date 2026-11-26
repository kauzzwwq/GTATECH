<?php
// Configuração de e-mail (usada na recuperação de senha).
// Pode preencher aqui ou pelas variáveis de ambiente do servidor.
return [
    // Endereço público do sistema, sem barra no final. Ex.: https://notas.seudominio.com.br
    // Deixe vazio para detectar pelo navegador (ok para testes locais).
    'url_base' => getenv('APP_URL') ?: '',

    'remetente_email' => getenv('MAIL_FROM') ?: 'no-reply@localhost',
    'remetente_nome'  => 'Notas de Transporte Animal',

    // SMTP: preencha para enviar e-mails de verdade (Gmail, Outlook, Hostinger, etc.).
    // Precisa instalar o PHPMailer uma vez:  composer require phpmailer/phpmailer
    'smtp' => [
        'host'      => getenv('SMTP_HOST') ?: '',          // ex.: smtp.gmail.com
        'porta'     => (int)(getenv('SMTP_PORT') ?: 587),  // 587 (tls) ou 465 (ssl)
        'usuario'   => getenv('SMTP_USER') ?: '',
        'senha'     => getenv('SMTP_PASS') ?: '',
        'seguranca' => getenv('SMTP_SEGURANCA') ?: 'tls', // 'tls' ou 'ssl'
    ],

    // Grava uma cópia de cada e-mail em storage/emails.log (ajuda a testar).
    'registrar_em_arquivo' => true,

    // SÓ PARA TESTES NO SEU COMPUTADOR: mostra o link de recuperação na própria tela
    // (e apenas quando o acesso vem do próprio computador). No servidor, deixe false.
    'mostrar_link_na_tela' => false,
];
