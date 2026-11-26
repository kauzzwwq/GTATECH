# Notas de Transporte Animal — SENAI SC · Unidade Videira

## Atualizar no servidor (SSH)
1. Envie esta pasta para o servidor (substitui os arquivos; **não apague** o `config/database.php` do servidor se ele for diferente).
2. Se o banco ainda não tem os campos de localização/baixa, rode uma vez: `atualizacao_banco_3.sql`.
3. Aponte o site para a pasta **`public/`** (recomendado). Se apontar para a raiz, o `.htaccess` já leva para `public/`.
4. Use **HTTPS**: sem ele o navegador não oferece "Instalar app".

## Virar app no celular (PWA)
- Android/Chrome: abra o site → botão **Instalar como app** (no menu) ou menu ⋮ → *Instalar app*.
- iPhone/Safari: **Compartilhar** → *Adicionar à Tela de Início*.
- Ao publicar visual novo, aumente `VERSAO` em `public/sw.js` (ex.: v2) para atualizar o cache.

## Recuperar senha (e-mail)
1. Instale o PHPMailer (uma vez): `composer require phpmailer/phpmailer`
   (ou rode no seu PC e envie `vendor/`, `composer.json` e `composer.lock`).
2. Preencha o SMTP em `config/mail.php` (host, porta, usuário, senha) e o `url_base` com o endereço do site, ex.: `https://seudominio.com.br/notas`.
3. Teste: `php scripts/testar_email.php seu@email.com`
4. Se falhar, veja `storage/emails.log` (linha *** FALHA) — a pasta `storage/` precisa ter permissão de escrita.
5. `mostrar_link_na_tela` deve ficar **false** no servidor.

## Cores e logos
Azul SENAI `#003087` · Laranja `#F47920`. Logos em `public/assets/images/` (`Logo-SENAI_EP.png`, `Logo-S.png`).
Foto do login: salve `login-boiadeiro.jpg` na mesma pasta (se não existir, aparece o desenho).

## Leitor RFID (ESP32 + MFRC522 + LCD)
O RFID integrado usa o mesmo banco do GTATECH. O painel e o cadastro de TAGs ficam disponíveis somente para o perfil Administrador; o ESP32 envia leituras autenticadas para `http://servertecnico.local/api_rfid.php`.

### Ativar no servidor
1. Faça backup do banco `notas_animais` e execute uma vez `atualizacao_banco_4_rfid.sql`. A migração cria tabelas próprias do GTATECH e não apaga nem altera as tabelas antigas do GTA.
2. Abra **Controle RFID** como Administrador, cadastre uma TAG e vincule-a a um motorista. Para o LCD mostrar uma GTA, o motorista precisa constar em uma nota com status **Emitida** ou **Em trânsito**, com validade não vencida.
3. Configure e grave `firmware/esp32_rfid.ino`: preencha `WIFI_SSID` e `WIFI_PASS` com os valores da sua rede. O firmware envia `tag_uid` ao endpoint oficial e mostra no LCD o motorista, GTA e status.

O endpoint do dispositivo não usa token nem sessão do navegador. Use-o somente em uma rede local confiável; não o exponha diretamente à internet. Para acesso fora da rede local, configure autenticação e HTTPS antes de conectar o dispositivo.

As tabelas legadas `cartoes_rfid` e `leituras_rfid` não são removidas nem importadas automaticamente. Se já havia TAGs cadastradas no GTA antigo, recadastre-as no painel GTATECH antes de desativar a pasta antiga.


## Instalar no servidor Docker (Apache+PHP e MySQL em containers)
1. Extraia em `/servidor-web/gtatech/` (fica `/servidor-web/gtatech/notas-senai-app-docker/`).
2. `cd /servidor-web/gtatech/notas-senai-app-docker && sudo bash instalar.sh`
3. Abra `http://servertecnico.local/gtatech/notas-senai-app-docker/`
