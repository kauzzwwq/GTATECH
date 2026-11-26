<?php
namespace App\Helpers;

// Envio de e-mail. Usa SMTP (PHPMailer) se estiver configurado; senão tenta o mail() do PHP.
// Se registrar_em_arquivo = true, grava uma cópia em storage/emails.log.
class Mailer
{
    public static function config(): array
    {
        static $cfg = null;
        if ($cfg === null) {
            $cfg = require BASE_PATH . '/config/mail.php';
        }
        return $cfg;
    }

    // Endereço do sistema para montar links (config 'url_base' ou o endereço do navegador)
    public static function urlBase(): string
    {
        $base = rtrim((string)(self::config()['url_base'] ?? ''), '/');
        if ($base !== '') {
            return $base;
        }
        $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['SERVER_PORT'] ?? '') == 443;
        $pasta = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/\\');
        return ($https ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $pasta;
    }

    public static function enviar(string $para, string $nome, string $assunto, string $texto): bool
    {
        $cfg = self::config();

        if (!empty($cfg['registrar_em_arquivo'])) {
            self::registrar($para, $assunto, $texto);
        }

        $smtp = $cfg['smtp'] ?? [];
        if (!empty($smtp['host'])) {
            $autoload = BASE_PATH . '/vendor/autoload.php';
            if (is_file($autoload)) {
                require_once $autoload;
            }
            if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
                try {
                    $m = new \PHPMailer\PHPMailer\PHPMailer(true);
                    $m->isSMTP();
                    $m->Host       = $smtp['host'];
                    $m->Port       = (int)($smtp['porta'] ?? 587);
                    $m->SMTPAuth   = ($smtp['usuario'] ?? '') !== '';
                    $m->Username   = $smtp['usuario'] ?? '';
                    $m->Password   = $smtp['senha'] ?? '';
                    $m->SMTPSecure = ($smtp['seguranca'] ?? 'tls') === 'ssl' ? 'ssl' : 'tls';
                    $m->CharSet    = 'UTF-8';
                    $m->setFrom($cfg['remetente_email'], $cfg['remetente_nome']);
                    $m->addAddress($para, $nome);
                    $m->Subject = $assunto;
                    $m->Body    = $texto;
                    $m->isHTML(false);
                    $m->send();
                    return true;
                } catch (\Throwable $e) {
                    error_log('Erro ao enviar e-mail (SMTP): ' . $e->getMessage());
                    self::registrarFalha($para, 'SMTP: ' . $e->getMessage());
                    return false;
                }
            }
            error_log('SMTP configurado, mas o PHPMailer não está instalado. Rode: composer require phpmailer/phpmailer');
            self::registrarFalha($para, 'SMTP configurado, mas o PHPMailer não está instalado (rode: composer require phpmailer/phpmailer)');
            return false;
        }

        // Sem SMTP: tenta o mail() nativo (no Laragon normalmente não envia nada)
        $cab = 'From: ' . $cfg['remetente_nome'] . ' <' . $cfg['remetente_email'] . ">\r\nContent-Type: text/plain; charset=UTF-8";
        return @mail($para, '=?UTF-8?B?' . base64_encode($assunto) . '?=', $texto, $cab);
    }

    // Anota no storage/emails.log por que o envio falhou (facilita descobrir o problema no servidor)
    private static function registrarFalha(string $para, string $motivo): void
    {
        $pasta = BASE_PATH . '/storage';
        if (!is_dir($pasta)) {
            @mkdir($pasta, 0775, true);
        }
        @file_put_contents($pasta . '/emails.log', '[' . date('Y-m-d H:i:s') . "] *** FALHA ao enviar para $para: $motivo\n" . str_repeat('-', 60) . "\n", FILE_APPEND | LOCK_EX);
    }

    private static function registrar(string $para, string $assunto, string $texto): void
    {
        $pasta = BASE_PATH . '/storage';
        if (!is_dir($pasta)) {
            @mkdir($pasta, 0775, true);
        }
        $linha = '[' . date('Y-m-d H:i:s') . "] Para: $para | Assunto: $assunto\n$texto\n" . str_repeat('-', 60) . "\n";
        @file_put_contents($pasta . '/emails.log', $linha, FILE_APPEND | LOCK_EX);
    }
}
