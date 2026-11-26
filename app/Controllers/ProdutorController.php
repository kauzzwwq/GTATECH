<?php
namespace App\Controllers;

use App\Helpers\Helper;
use App\Models\Cadastro;
use App\Models\Usuario;

// Cadastro de produtores (somente admin; a rota já exige)
class ProdutorController
{
    public function index(): void
    {
        Helper::render('produtores/lista', [
            'tipo'  => 'produtores',
            'cfg'   => Cadastro::TIPOS['produtores'],
            'itens' => Cadastro::listar('produtores'),
            'msg'   => $_GET['msg'] ?? '',
        ]);
    }

    public function form(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $produtor = ['produtor' => '', 'cpf_cnpj' => '', 'uep' => '', 'codigo_uep' => '', 'numero_uep' => '', 'localizacao' => '', 'usuario_id' => null];
        if ($id) {
            $existente = Cadastro::buscar('produtores', $id);
            if (!$existente) {
                http_response_code(404);
                exit('Produtor não encontrado.');
            }
            $produtor = $existente;
        }
        $this->mostrar($id, $produtor, []);
    }

    // Recebe o formulário (POST). Se tiver erro, mostra o formulário de novo com as mensagens.
    public function salvar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Helper::redirecionar('produtores');
        }
        Helper::csrfCheck();

        $id    = (int)($_POST['id'] ?? 0);
        $erros = [];
        $p = [
            'produtor'    => trim($_POST['produtor'] ?? ''),
            'cpf_cnpj'    => trim($_POST['cpf_cnpj'] ?? ''),
            'uep'         => trim($_POST['uep'] ?? ''),
            'codigo_uep'  => trim($_POST['codigo_uep'] ?? ''),
            'numero_uep'  => trim($_POST['numero_uep'] ?? ''),
            'localizacao' => trim($_POST['localizacao'] ?? ''),
            'usuario_id'  => (int)($_POST['usuario_id'] ?? 0) ?: null,
        ];
        $reexibir = $p;   // o que a pessoa digitou, para mostrar de novo se houver erro

        if ($p['produtor'] === '') {
            $erros[] = 'Preencha o nome do produtor.';
        }
        if ($p['cpf_cnpj'] !== '') {
            if (Helper::cpfCnpjValido($p['cpf_cnpj'])) {
                $p['cpf_cnpj'] = Helper::formatarCpfCnpj($p['cpf_cnpj']);
            } else {
                $erros[] = 'O CPF/CNPJ é inválido.';
            }
        }
        if ($p['usuario_id']) {
            $u = Usuario::buscarPorId($p['usuario_id']);
            if (!$u || $u['perfil'] !== 'produtor') {
                $erros[] = 'Escolha um usuário com o perfil Produtor.';
            } elseif (Cadastro::usuarioJaVinculado('produtores', $p['usuario_id'], $id)) {
                $erros[] = 'Este usuário já está ligado a outro produtor.';
            }
        }

        if ($erros) {
            $this->mostrar($id, array_merge($reexibir, ['id' => $id]), $erros);
            return;
        }

        if ($id) {
            Cadastro::atualizar('produtores', $id, $p, $p['usuario_id']);
            $msg = 'Produtor atualizado.';
        } else {
            Cadastro::criar('produtores', $p, $p['usuario_id']);
            $msg = 'Produtor cadastrado.';
        }
        Helper::redirecionar('produtores', ['msg' => $msg]);
    }

    // Exclusão só por POST (com proteção), nunca por link
    public function excluir(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Helper::csrfCheck();
            Cadastro::excluir('produtores', (int)($_POST['id'] ?? 0));
            Helper::redirecionar('produtores', ['msg' => 'Produtor excluído. As notas já criadas não mudam.']);
        }
        Helper::redirecionar('produtores');
    }

    private function mostrar(int $id, array $produtor, array $erros): void
    {
        $usuarios = Usuario::listrarDisponiveis('produtor', 'produtores', 'produtor');

        // Na edição, o login que já está ligado a este produtor precisa continuar na lista
        // (ela só traz usuários livres; sem isso, salvar tiraria o login dele sem avisar).
        $atual   = $id ? Cadastro::buscar('produtores', $id) : null;
        $atualId = (int)($atual['usuario_id'] ?? 0);
        if ($atualId && !in_array($atualId, array_map('intval', array_column($usuarios, 'id')), true)) {
            $ligado = Usuario::buscarPorId($atualId);
            if ($ligado) {
                array_unshift($usuarios, $ligado);
            }
        }

        Helper::render('produtores/form', [
            'tipo'     => 'produtores',
            'cfg'      => Cadastro::TIPOS['produtores'],
            'id'       => $id,
            'item'     => $produtor,
            'erros'    => $erros,
            'usuarios' => $usuarios,
        ]);
    }
}
