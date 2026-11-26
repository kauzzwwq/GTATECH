<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

error_reporting(E_ALL);
// Ponto de entrada: todas as páginas passam por aqui (index.php?r=nome-da-rota)
define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/config/database.php';
require BASE_PATH . '/app/Helpers/Helper.php';

// Autoload simples: App\Models\Nota => app/Models/Nota.php
spl_autoload_register(function (string $classe): void {
    $prefixo = 'App\\';
    if (strncmp($classe, $prefixo, strlen($prefixo)) !== 0) {
        return;
    }
    $arquivo = BASE_PATH . '/app/' . str_replace('\\', '/', substr($classe, strlen($prefixo))) . '.php';
    if (is_file($arquivo)) {
        require $arquivo;
    }
});

use App\Helpers\Helper;
use App\Models\Usuario;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\MotoristaController;
use App\Controllers\ProdutorController;
use App\Controllers\DestinatarioController;
use App\Controllers\NotaController;
use App\Controllers\UsuarioController;
use App\Controllers\CadastroController;

Helper::iniciarSessao();

// Revalida o usuário logado a cada acesso: se foi excluído ou mudou de perfil, vale na hora.
if (Helper::logado()) {
    $atual = Usuario::buscarPorId(Helper::usuarioId());
    if (!$atual) {
        $_SESSION = [];
        session_destroy();
        Helper::redirecionar('login');
    }
    $_SESSION['usuario_nome']   = $atual['nome'];
    $_SESSION['usuario_perfil'] = $atual['perfil'];
}

// rota => [Controller, método, nível de acesso]
//   publica .. qualquer pessoa          logado ... qualquer usuário logado
//   admin .... só administrador         emissor .. administrador e produtor (criam notas)
//   baixa .... administrador e recebedor (dão baixa na nota recebida)
$rotas = [
    'login'             => [AuthController::class,      'login',          'publica'],
    'esqueci-senha'     => [AuthController::class,      'esqueciSenha',   'publica'],
    'redefinir-senha'   => [AuthController::class,      'redefinirSenha', 'publica'],
    'logout'            => [AuthController::class,      'logout',         'logado'],
    'dashboard'         => [DashboardController::class, 'index',          'logado'],
    'notas'             => [NotaController::class,      'index',          'logado'],
    'nota-ver'          => [NotaController::class,      'ver',            'logado'],
    'nota-pdf'          => [NotaController::class,      'pdf',            'logado'],
    'nota-pdf-lote'     => [NotaController::class,      'pdfLote',        'logado'],
    'nota-form'         => [NotaController::class,      'form',           'emissor'],
    'nota-excluir'      => [NotaController::class,      'excluir',        'emissor'],
    'nota-duplicar'     => [NotaController::class,      'duplicar',       'emissor'],
    'nota-baixa'        => [NotaController::class,      'baixa',          'baixa'],
    'nota-status'       => [NotaController::class,      'status',         'admin'],
    'cadastros'         => [CadastroController::class,  'index',          'admin'],
    'cadastro-form'     => [CadastroController::class,  'form',           'admin'],
    'cadastro-excluir'  => [CadastroController::class,  'excluir',        'admin'],
    'motoristas'        => [MotoristaController::class, 'index',          'admin'],
    'motorista-form'    => [MotoristaController::class, 'form',           'admin'],
    'motorista-salvar'  => [MotoristaController::class, 'salvar',         'admin'],
    'motorista-excluir' => [MotoristaController::class, 'excluir',        'admin'],
    'produtores'            => [ProdutorController::class,     'index',   'admin'],
    'produtor-form'         => [ProdutorController::class,     'form',    'admin'],
    'produtor-salvar'       => [ProdutorController::class,     'salvar',  'admin'],
    'produtor-excluir'      => [ProdutorController::class,     'excluir', 'admin'],
    'destinatarios'         => [DestinatarioController::class, 'index',   'admin'],
    'destinatario-form'     => [DestinatarioController::class, 'form',    'admin'],
    'destinatario-salvar'   => [DestinatarioController::class, 'salvar',  'admin'],
    'destinatario-excluir'  => [DestinatarioController::class, 'excluir', 'admin'],
    'usuarios'          => [UsuarioController::class,   'index',          'admin'],
    'usuario-form'      => [UsuarioController::class,   'form',           'admin'],
    'usuario-excluir'   => [UsuarioController::class,   'excluir',        'admin'],
    'usuario-senha'     => [UsuarioController::class,   'senha',          'admin'],
];
$rotas += require BASE_PATH . '/config/routes.php';

// quem pode usar cada nível restrito
$permissoes = [
    'admin'   => ['admin'],
    'emissor' => ['admin', 'produtor'],
    'baixa'   => ['admin', 'destinatario'],
];

$rota = $_GET['r'] ?? '';

if ($rota === '') {
    Helper::redirecionar(Helper::logado() ? 'dashboard' : 'login');
}

if (!isset($rotas[$rota])) {
    http_response_code(404);
    exit('Página não encontrada.');
}

[$classe, $metodo, $nivel] = $rotas[$rota];

if ($nivel === 'logado') {
    Helper::exigirLogin();
} elseif (isset($permissoes[$nivel])) {
    Helper::exigirPerfil($permissoes[$nivel]);
}

(new $classe())->$metodo();
