<?php
namespace App\Controllers;

use App\Helpers\Helper;
use App\Models\Motorista;
use App\Models\Usuario;

// Cadastro de motoristas (somente admin; a rota já exige)
class MotoristaController
{
    public function index(): void
    {
        Helper::render('motoristas/index', [
            'motoristas' => Motorista::listar(),
            'msg'        => $_GET['msg'] ?? '',
        ]);
    }

    public function form(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        $motorista = ['nome' => '', 'cpf' => '', 'cnh' => '', 'telefone' => '', 'placa_veiculo' => '', 'modelo_veiculo' => '', 'usuario_id' => null];
        if ($id) {
            $existente = Motorista::buscar($id);
            if (!$existente) {
                http_response_code(404);
                exit('Motorista não encontrado.');
            }
            $motorista = $existente;
        }
        $this->mostrar($id, $motorista, []);
    }

    // Recebe o formulário (POST). Se tiver erro, mostra o formulário de novo com as mensagens.
    public function salvar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Helper::redirecionar('motoristas');
        }
        Helper::csrfCheck();
        echo '<pre>';
        var_dump($_POST);
        echo '</pre>';
        exit;

        $id    = (int)($_POST['id'] ?? 0);
        $erros = [];
        $m = [
            'nome'           => trim($_POST['nome'] ?? ''),
            'cpf'            => trim($_POST['cpf'] ?? ''),
            'cnh'            => Helper::somenteDigitos($_POST['cnh'] ?? ''),
            'telefone'       => trim($_POST['telefone'] ?? ''),
            'placa_veiculo'  => Helper::normalizarPlaca($_POST['placa_veiculo'] ?? ''),
            'modelo_veiculo' => trim($_POST['modelo_veiculo'] ?? ''),
            'usuario_id'     => (int)($_POST['usuario_id'] ?? 0) ?: null,
        ];
        $reexibir = $m;   // o que a pessoa digitou, para mostrar de novo se houver erro

        if ($m['nome'] === '') {
            $erros[] = 'Preencha o nome do motorista.';
        }
        if ($m['cpf'] !== '') {
            if (Helper::cpfValido($m['cpf'])) {
                $m['cpf'] = Helper::formatarCpfCnpj($m['cpf']);
            } else {
                $erros[] = 'O CPF é inválido.';
            }
        }
        if ($m['cnh'] !== '' && strlen($m['cnh']) !== 11) {
            $erros[] = 'A CNH deve ter 11 números.';
        }
        if ($m['telefone'] !== '') {
            if (in_array(strlen(Helper::somenteDigitos($m['telefone'])), [10, 11], true)) {
                $m['telefone'] = Helper::formatarTelefone($m['telefone']);
            } else {
                $erros[] = 'O telefone deve ter DDD + número (10 ou 11 dígitos).';
            }
        }
        if ($m['placa_veiculo'] !== '' && !Helper::placaValida($m['placa_veiculo'])) {
            $erros[] = 'A placa é inválida (use o formato ABC1234 ou ABC1D23).';
        }
        if ($m['usuario_id']) {
            $u = Usuario::buscarPorId($m['usuario_id']);
            if (!$u || $u['perfil'] !== 'motorista') {
                $erros[] = 'Escolha um usuário com o perfil Motorista.';
            } elseif (Motorista::usuarioJaVinculado($m['usuario_id'], $id)) {
                $erros[] = 'Este usuário já está ligado a outro motorista.';
            }
        }

        if ($erros) {
            $this->mostrar($id, array_merge($reexibir, ['id' => $id]), $erros);
            return;
        }

        foreach (['cpf', 'cnh', 'telefone', 'placa_veiculo', 'modelo_veiculo'] as $campo) {
            $m[$campo] = $m[$campo] !== '' ? $m[$campo] : null;
        }
        if ($id) {
            Motorista::atualizar($id, $m);
            $msg = 'Motorista atualizado.';
        } else {
            Motorista::criar($m);
            $msg = 'Motorista cadastrado.';
        }
        Helper::redirecionar('motoristas', ['msg' => $msg]);
    }

    // Exclusão só por POST (com proteção), nunca por link
    public function excluir(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Helper::csrfCheck();
            Motorista::excluir((int)($_POST['id'] ?? 0));
            Helper::redirecionar('motoristas', ['msg' => 'Motorista excluído. As notas antigas continuam com o nome dele.']);
        }
        Helper::redirecionar('motoristas');
    }

    private function mostrar(int $id, array $motorista, array $erros): void
    {
        Helper::render('motoristas/form', [
            'id'        => $id,
            'motorista' => $motorista,
            'erros'     => $erros,
            'usuarios'  => Usuario::listrarDisponiveis('motorista', 'motoristas', 'nome'),
        ]);
    }
}
