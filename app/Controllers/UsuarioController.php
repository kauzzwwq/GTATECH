<?php
namespace App\Controllers;

use App\Helpers\Helper;
use App\Models\Nota;
use App\Models\Usuario;

class UsuarioController
{
    public function index(): void
    {
        Helper::render('usuarios/todos', [
            'usuarios' => Usuario::listarTodos(),
            'msg'      => $_GET['msg'] ?? '',
            'erro'     => $_GET['erro'] ?? '',
            'meuId'    => Helper::usuarioId(),
        ]);
    }

    public function form(): void
    {
        $id        = (int)($_GET['id'] ?? 0);
        $existente = null;
        $usuario   = ['nome' => '', 'email' => '', 'perfil' => 'produtor', 'telefone' => '', 'cpf_cnpj' => ''];
        $erro      = '';

        if ($id) {
            $existente = Usuario::buscarPorId($id);
            if (!$existente) {
                http_response_code(404);
                exit('Usuário não encontrado.');
            }
            $usuario = $existente;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Helper::csrfCheck();
            $nome     = trim($_POST['nome'] ?? '');
            $email    = trim($_POST['email'] ?? '');
            $perfil   = $_POST['perfil'] ?? '';
            $senha    = $_POST['senha'] ?? '';
            $telefone = trim($_POST['telefone'] ?? '');
            $doc      = trim($_POST['cpf_cnpj'] ?? '');
            $usuario  = ['nome' => $nome, 'email' => $email, 'perfil' => $perfil, 'telefone' => $telefone, 'cpf_cnpj' => $doc];

            if ($nome === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $erro = 'Preencha o nome e um e-mail válido.';
            } elseif (!isset(Usuario::PERFIS[$perfil])) {
                $erro = 'Escolha um perfil válido.';
            } elseif (Usuario::emailEmUso($email, $id)) {
                $erro = 'Este e-mail já está cadastrado.';
            } elseif ($doc !== '' && !Helper::cpfCnpjValido($doc)) {
                $erro = 'O CPF/CNPJ é inválido.';
            } elseif ($telefone !== '' && !in_array(strlen(Helper::somenteDigitos($telefone)), [10, 11], true)) {
                $erro = 'O telefone deve ter DDD + número (10 ou 11 dígitos).';
            } elseif (!$id && strlen($senha) < 6) {
                $erro = 'A senha precisa ter pelo menos 6 caracteres.';
            } elseif ($id && $senha !== '' && strlen($senha) < 6) {
                $erro = 'A nova senha precisa ter pelo menos 6 caracteres.';
            } elseif ($id && $existente['perfil'] === 'admin' && $perfil !== 'admin' && Usuario::totalAdmins() <= 1) {
                $erro = 'O sistema precisa ter pelo menos um administrador.';
            } else {
                $doc      = $doc !== '' ? Helper::formatarCpfCnpj($doc) : '';
                $telefone = $telefone !== '' ? Helper::formatarTelefone($telefone) : '';

                $extras = [
                    'cnh'            => Helper::somenteDigitos($_POST['cnh'] ?? ''),
                    'placa_veiculo'  => Helper::normalizarPlaca($_POST['placa_veiculo'] ?? ''),
                    'modelo_veiculo' => trim($_POST['modelo_veiculo'] ?? ''),
                    'uep'            => trim($_POST['uep'] ?? ''),
                    'codigo_uep'     => trim($_POST['codigo_uep'] ?? ''),
                    'localizacao'    => trim($_POST['localizacao_produtor'] ?? ''),
                    'proprietario'   => trim($_POST['proprietario'] ?? ''),
                    'codigo_oficial' => trim($_POST['codigo_oficial'] ?? ''),
                    'municipio'      => trim($_POST['municipio'] ?? ''),
                    'localizacao_destinatario' => trim($_POST['localizacao_destinatario'] ?? ''),
                ];

                if ($id) {
                    Usuario::atualizar($id, $nome, $email, $perfil, $senha, $telefone, $doc, $extras);
                    $msg = 'Usuário atualizado.';
                } else {
                    Usuario::criar($nome, $email, $senha, $perfil, $telefone, $doc, $extras);
                    $msg = 'Usuário criado.';
                }
                Helper::redirecionar('usuarios', ['msg' => $msg]);
            }
        }

        // Carrega os cadastros vinculados para preencher o formulário
        $motorista = $produtor = $destinatario = null;

        if ($id) {
            $pdo = db();

            $st = $pdo->prepare('SELECT * FROM motoristas WHERE usuario_id = ? LIMIT 1');
            $st->execute([$id]);
            $motorista = $st->fetch() ?: null;

            $st = $pdo->prepare('SELECT * FROM produtores WHERE usuario_id = ? LIMIT 1');
            $st->execute([$id]);
            $produtor = $st->fetch() ?: null;

            $st = $pdo->prepare('SELECT * FROM destinatarios WHERE usuario_id = ? LIMIT 1');
            $st->execute([$id]);
            $destinatario = $st->fetch() ?: null;
        }

        Helper::render('usuarios/cadastro', [
            'id'           => $id,
            'usuario'      => $usuario,
            'motorista'    => $motorista,
            'produtor'     => $produtor,
            'destinatario' => $destinatario,
            'erro'         => $erro,
        ]);
    }

    public function excluir(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Helper::csrfCheck();
            $id = (int)($_POST['id'] ?? 0);
            $alvo = Usuario::buscarPorId($id);

            if (!$alvo) {
                Helper::redirecionar('usuarios', ['erro' => 'Usuário não encontrado.']);
            }
            if ($id === Helper::usuarioId()) {
                Helper::redirecionar('usuarios', ['erro' => 'Você não pode excluir o seu próprio usuário.']);
            }
            if ($alvo['perfil'] === 'admin' && Usuario::totalAdmins() <= 1) {
                Helper::redirecionar('usuarios', ['erro' => 'O sistema precisa ter pelo menos um administrador.']);
            }

            Nota::reatribuirCriador($id, Helper::usuarioId());
            Usuario::excluir($id);
            Helper::redirecionar('usuarios', ['msg' => 'Usuário excluído. O cadastro vinculado continua, só ficou sem login.']);
        }

        Helper::redirecionar('usuarios');
    }

    public function senha(): void
    {
        $id   = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        $alvo = Usuario::buscarPorId($id);
        if (!$alvo) {
            http_response_code(404);
            exit('Usuário não encontrado.');
        }

        $destinos = [
            'usuarios'      => ['usuarios', []],
            'produtores'    => ['cadastros', ['tipo' => 'produtores']],
            'destinatarios' => ['cadastros', ['tipo' => 'destinatarios']],
            'motoristas'    => ['motoristas', []],
        ];
        $retorno = (string)($_GET['retorno'] ?? $_POST['retorno'] ?? 'usuarios');
        if (!isset($destinos[$retorno])) {
            $retorno = 'usuarios';
        }
        [$rotaVolta, $paramsVolta] = $destinos[$retorno];

        $erro = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Helper::csrfCheck();
            $nova      = (string)($_POST['senha'] ?? '');
            $confirmar = (string)($_POST['confirma_senha'] ?? '');
            if (strlen($nova) < 6) {
                $erro = 'A nova senha precisa ter pelo menos 6 caracteres.';
            } elseif ($nova !== $confirmar) {
                $erro = 'As senhas não coincidem.';
            } else {
                Usuario::atualizarSenha($id, $nova);
                Helper::redirecionar($rotaVolta, $paramsVolta + ['msg' => 'Senha de ' . $alvo['nome'] . ' alterada.']);
            }
        }

        Helper::render('usuarios/senha', [
            'alvo'    => $alvo,
            'retorno' => $retorno,
            'erro'    => $erro,
            'voltar'  => Helper::url($rotaVolta, $paramsVolta),
        ]);
    }
}