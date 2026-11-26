<?php
namespace App\Controllers;

use App\Helpers\Helper;
use App\Models\Cadastro;
use App\Models\Usuario;

// Produtores e destinatários (somente admin). O tipo vem em ?tipo=produtores|destinatarios
class CadastroController
{
    private function tipo(): string
    {
        $tipo = $_GET['tipo'] ?? $_POST['tipo'] ?? 'produtores';
        if (!isset(Cadastro::TIPOS[$tipo])) {
            http_response_code(404);
            exit('Cadastro não encontrado.');
        }
        return $tipo;
    }

    public function index(): void
    {
        $tipo = $this->tipo();
        Helper::render($tipo . '/lista', [
            'tipo'  => $tipo,
            'cfg'   => Cadastro::TIPOS[$tipo],
            'itens' => Cadastro::listar($tipo),
            'msg'   => $_GET['msg'] ?? '',
        ]);
    }

    public function form(): void
    {
        $tipo  = $this->tipo();
        $cfg   = Cadastro::TIPOS[$tipo];
        $id    = (int)($_GET['id'] ?? 0);
        $item  = array_fill_keys(array_keys($cfg['campos']), '');
        $item['usuario_id'] = null;
        $erros = [];

        if ($id) {
            $existente = Cadastro::buscar($tipo, $id);
            if (!$existente) {
                http_response_code(404);
                exit('Registro não encontrado.');
            }
            $item = $existente;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Helper::csrfCheck();
            $dados = [];
            foreach (array_keys($cfg['campos']) as $campo) {
                $dados[$campo] = trim((string)($_POST[$campo] ?? ''));
            }
            $uid = (int)($_POST['usuario_id'] ?? 0);
            $item = array_merge($item, $dados, ['usuario_id' => $uid ?: null]);

            if ($dados[$cfg['principal']] === '') {
                $erros[] = 'Preencha o campo "' . $cfg['campos'][$cfg['principal']] . '".';
            }
            if ($dados['cpf_cnpj'] !== '') {
                if (Helper::cpfCnpjValido($dados['cpf_cnpj'])) {
                    $dados['cpf_cnpj'] = Helper::formatarCpfCnpj($dados['cpf_cnpj']);
                } else {
                    $erros[] = 'O CPF/CNPJ é inválido.';
                }
            }
            if ($uid) {
                $u = Usuario::buscarPorId($uid);
                if (!$u || $u['perfil'] !== $cfg['perfil']) {
                    $erros[] = 'Escolha um usuário com o perfil "' . Usuario::PERFIS[$cfg['perfil']] . '".';
                } elseif (Cadastro::usuarioJaVinculado($tipo, $uid, $id)) {
                    $erros[] = 'Este usuário já está ligado a outro cadastro.';
                }
            }

            if (!$erros) {
                $vinculo = $uid ?: null;
                if ($id) {
                    Cadastro::atualizar($tipo, $id, $dados, $vinculo);
                    $msg = $cfg['singular'] . ' atualizado.';
                } else {
                    Cadastro::criar($tipo, $dados, $vinculo);
                    $msg = $cfg['singular'] . ' cadastrado.';
                }
                Helper::redirecionar('cadastros', ['tipo' => $tipo, 'msg' => $msg]);
            }
        }

        Helper::render($tipo . '/form', [
            'tipo'     => $tipo,
            'cfg'      => $cfg,
            'id'       => $id,
            'item'     => $item,
            'erros'    => $erros,
            'usuarios' => Usuario::listarDisponiveis($cfg['perfil'], $cfg['tabela'], (int)($item['usuario_id'] ?? 0)),
        ]);
    }

    public function excluir(): void
    {
        $tipo = $this->tipo();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Helper::csrfCheck();
            Cadastro::excluir($tipo, (int)($_POST['id'] ?? 0));
        }
        Helper::redirecionar('cadastros', ['tipo' => $tipo, 'msg' => 'Registro excluído.']);
    }
}