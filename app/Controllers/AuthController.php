<?php
namespace App\Controllers;

use App\Helpers\Helper;
use App\Helpers\Mailer;
use App\Models\Usuario;

class AuthController
{
    public function login(): void
    {
        $erro = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Helper::csrfCheck();
            $email = trim($_POST['email'] ?? '');
            $senha = $_POST['senha'] ?? '';
            $u = Usuario::buscarPorEmail($email);
            if ($u && password_verify($senha, $u['senha_hash'])) {
                session_regenerate_id(true);
                $_SESSION['usuario_id']     = $u['id'];
                $_SESSION['usuario_nome']   = $u['nome'];
                $_SESSION['usuario_perfil'] = $u['perfil'];
                Helper::redirecionar('dashboard');
            }
            $erro = 'E-mail ou senha incorretos.';
        }
        Helper::render('auth/login', ['erro' => $erro]);
    }

    public function logout(): void
    {
        $_SESSION = [];
        session_destroy();
        Helper::redirecionar('login');
    }

    public function esqueciSenha(): void
    {
        $msg     = '';
        $linkDev = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Helper::csrfCheck();
            $email = trim($_POST['email'] ?? '');
            $u = Usuario::buscarPorEmail($email);

            // Só gera um novo link se não houve pedido nos últimos 60 segundos
            if ($u && !Usuario::pedidoRecente((int)$u['id'])) {
                Usuario::apagarTokens((int)$u['id']);          // invalida links antigos
                $token = bin2hex(random_bytes(32));
                Usuario::criarTokenRecuperacao((int)$u['id'], $token);

                $link = Mailer::urlBase() . '/' . Helper::url('redefinir-senha', ['token' => $token]);

                $texto = "Olá, " . $u['nome'] . ".\n\n"
                       . "Recebemos um pedido para redefinir a sua senha no sistema de Notas de Transporte Animal.\n"
                       . "Clique no link abaixo para criar uma nova senha (válido por 1 hora):\n\n"
                       . $link . "\n\n"
                       . "Se você não fez esse pedido, ignore este e-mail: a sua senha continua a mesma.";
                Mailer::enviar($u['email'], $u['nome'], 'Recuperação de senha - Notas de Transporte Animal', $texto);

                // Modo de teste: mostra o link na tela, só quando ligado na config E acessando do próprio computador
                $cfg = Mailer::config();
                $local = in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);
                if (!empty($cfg['mostrar_link_na_tela']) && $local) {
                    $linkDev = $link;
                }
            }

            // Mesma mensagem exista ou não o e-mail (evita descobrir quem tem conta)
            $msg = 'Se o e-mail informado estiver cadastrado, você receberá as instruções de recuperação em instantes.';
        }
        Helper::render('auth/esqueci_senha', ['msg' => $msg, 'linkDev' => $linkDev]);
    }

    public function redefinirSenha(): void
    {
        $token   = $_GET['token'] ?? '';
        $erro    = '';
        $sucesso = '';
        $rec     = Usuario::buscarTokenValido($token);

        if (!$rec && $_SERVER['REQUEST_METHOD'] !== 'POST') {
            $erro = 'Link de recuperação inválido ou expirado.';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Helper::csrfCheck();
            $tokenPost     = $_POST['token'] ?? '';
            $novaSenha     = $_POST['senha'] ?? '';
            $confirmaSenha = $_POST['confirma_senha'] ?? '';
            $recPost       = Usuario::buscarTokenValido($tokenPost);

            if (!$recPost) {
                $erro = 'Link inválido ou expirado.';
            } elseif (strlen($novaSenha) < 6) {
                $erro = 'A senha deve ter pelo menos 6 caracteres.';
            } elseif ($novaSenha !== $confirmaSenha) {
                $erro = 'As senhas não coincidem.';
            } else {
                Usuario::atualizarSenha((int)$recPost['usuario_id'], $novaSenha);
                Usuario::apagarTokens((int)$recPost['usuario_id']);
                $sucesso = 'Senha alterada com sucesso! Você já pode entrar.';
            }
        }

        Helper::render('auth/redefinir_senha', [
            'token'   => $token,
            'erro'    => $erro,
            'sucesso' => $sucesso,
            'rec'     => $rec,
        ]);
    }
}
