<?php
namespace App\Controllers;

use App\Helpers\Helper;
use App\Models\Cadastro;
use App\Models\Usuario;

// Cadastro de destinatários / recebedores (somente admin; a rota já exige)
class DestinatarioController
{
    public function index(): void
    {
        Helper::render('destinatarios/lista', [
            'tipo'  => 'destinatarios',
            'cfg'   => Cadastro::TIPOS['destinatarios'],
            'itens' => Cadastro::listar('destinatarios'),
            'msg'   => $_GET['msg'] ?? '',
        ]);
    }

    public function form(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $destinatario = ['estabelecimento' => '', 'codigo_oficial' => '', 'proprietario' => '', 'cpf_cnpj' => '', 'localidade' => '', 'municipio' => '', 'localizacao' => '', 'usuario_id' => null];
        if ($id) {
            $existente = Cadastro::buscar('destinatarios', $id);
            if (!$existente) {
                http_response_code(404);
                exit('Destinatário não encontrado.');
            }
            $destinatario = $existente;
        }
        $this->mostrar($id, $destinatario, []);
    }

    // Recebe o formulário (POST). Se tiver erro, mostra o formulário de novo com as mensagens.
    public function salvar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Helper::redirecionar('destinatarios');
        }
        Helper::csrfCheck();

        $id    = (int)($_POST['id'] ?? 0);
        $erros = [];
        $d = [
            'estabelecimento' => trim($_POST['estabelecimento'] ?? ''),
            'codigo_oficial'  => trim($_POST['codigo_oficial'] ?? ''),
            'proprietario'    => trim($_POST['proprietario'] ?? ''),
            'cpf_cnpj'        => trim($_POST['cpf_cnpj'] ?? ''),
            'localidade'      => trim($_POST['localidade'] ?? ''),
            'municipio'       => trim($_POST['municipio'] ?? ''),
            'localizacao'     => trim($_POST['localizacao'] ?? ''),
            'usuario_id'      => (int)($_POST['usuario_id'] ?? 0) ?: null,
        ];
        $reexibir = $d;   // o que a pessoa digitou, para mostrar de novo se houver erro

        if ($d['estabelecimento'] === '') {
            $erros[] = 'Preencha o nome do estabelecimento.';
        }
        if ($d['cpf_cnpj'] !== '') {
            if (Helper::cpfCnpjValido($d['cpf_cnpj'])) {
                $d['cpf_cnpj'] = Helper::formatarCpfCnpj($d['cpf_cnpj']);
            } else {
                $erros[] = 'O CPF/CNPJ é inválido.';
            }
        }
        if ($d['usuario_id']) {
            $u = Usuario::buscarPorId($d['usuario_id']);
            if (!$u || $u['perfil'] !== 'destinatario') {
                $erros[] = 'Escolha um usuário com o perfil Recebedor.';
            } elseif (Cadastro::usuarioJaVinculado('destinatarios', $d['usuario_id'], $id)) {
                $erros[] = 'Este usuário já está ligado a outro destinatário.';
            }
        }

        if ($erros) {
            $this->mostrar($id, array_merge($reexibir, ['id' => $id]), $erros);
            return;
        }

        if ($id) {
            Cadastro::atualizar('destinatarios', $id, $d, $d['usuario_id']);
            $msg = 'Destinatário atualizado.';
        } else {
            Cadastro::criar('destinatarios', $d, $d['usuario_id']);
            $msg = 'Destinatário cadastrado.';
        }
        Helper::redirecionar('destinatarios', ['msg' => $msg]);
    }

    // Exclusão só por POST (com proteção), nunca por link
    public function excluir(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Helper::csrfCheck();
            Cadastro::excluir('destinatarios', (int)($_POST['id'] ?? 0));
            Helper::redirecionar('destinatarios', ['msg' => 'Destinatário excluído. As notas já criadas não mudam.']);
        }
        Helper::redirecionar('destinatarios');
    }

    private function mostrar(int $id, array $destinatario, array $erros): void
    {
        $usuarios = Usuario::listrarDisponiveis('destinatario', 'destinatarios', 'estabelecimento');

        // Na edição, o login que já está ligado a este destinatário precisa continuar na lista
        // (ela só traz usuários livres; sem isso, salvar tiraria o login dele sem avisar).
        $atual   = $id ? Cadastro::buscar('destinatarios', $id) : null;
        $atualId = (int)($atual['usuario_id'] ?? 0);
        if ($atualId && !in_array($atualId, array_map('intval', array_column($usuarios, 'id')), true)) {
            $ligado = Usuario::buscarPorId($atualId);
            if ($ligado) {
                array_unshift($usuarios, $ligado);
            }
        }

        Helper::render('destinatarios/form', [
            'tipo'     => 'destinatarios',
            'cfg'      => Cadastro::TIPOS['destinatarios'],
            'id'       => $id,
            'item'     => $destinatario,
            'erros'    => $erros,
            'usuarios' => $usuarios,
        ]);
    }
}
