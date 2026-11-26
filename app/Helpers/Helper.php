<?php
namespace App\Helpers {

    class Helper
    {
        // ---------- Sessão ----------
        public static function iniciarSessao(): void
        {
            if (session_status() === PHP_SESSION_NONE) {
                session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
                session_start();
            }
        }

        public static function logado(): bool
        {
            return !empty($_SESSION['usuario_id']);
        }

        public static function usuarioId(): int
        {
            return (int)($_SESSION['usuario_id'] ?? 0);
        }

        public static function perfil(): string
        {
            return $_SESSION['usuario_perfil'] ?? 'trabalhador';
        }

        public static function ehAdmin(): bool
        {
            return self::logado() && self::perfil() === 'admin';
        }

        public static function exigirLogin(): void
        {
            if (!self::logado()) {
                self::redirecionar('login');
            }
        }

        public static function exigirAdmin(): void
        {
            self::exigirLogin();
            if (!self::ehAdmin()) {
                http_response_code(403);
                exit('Acesso restrito ao administrador.');
            }
        }

        public static function ehPerfil(string ...$perfis): bool
        {
            return self::logado() && in_array(self::perfil(), $perfis, true);
        }

        public static function exigirPerfil(array $perfis): void
        {
            self::exigirLogin();
            if (!in_array(self::perfil(), $perfis, true)) {
                http_response_code(403);
                exit('Você não tem permissão para acessar esta página.');
            }
        }

        // ---------- Sanitização / URLs ----------
        public static function esc($valor): string
        {
            return htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
        }

        public static function url(string $rota = '', array $params = []): string
        {
            $q = $rota !== '' ? array_merge(['r' => $rota], $params) : $params;
            return 'index.php' . ($q ? '?' . http_build_query($q) : '');
        }

        public static function redirecionar(string $rota, array $params = []): void
        {
            header('Location: ' . self::url($rota, $params));
            exit;
        }

        // ---------- CSRF ----------
        public static function csrfToken(): string
        {
            if (empty($_SESSION['csrf'])) {
                $_SESSION['csrf'] = bin2hex(random_bytes(32));
            }
            return $_SESSION['csrf'];
        }

        public static function csrfCheck(): void
        {
            if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
                http_response_code(400);
                exit('Requisição inválida.');
            }
        }

        // ---------- Formatação ----------
        // Número oficial da nota: ANO/SEQUÊNCIA, ex.: 2026/00001
        public static function numeroNota(array $nota): string
        {
            return sprintf('%04d/%05d', (int)($nota['numero_ano'] ?? 0), (int)($nota['numero_seq'] ?? 0));
        }

        public static function numeroArquivo(array $nota): string
        {
            return str_replace('/', '-', self::numeroNota($nota));
        }

        public static function data(?string $quando): string
        {
            return $quando ? date('d/m/Y', strtotime($quando)) : '';
        }

        public static function dataHora(?string $quando): string
        {
            return $quando ? date('d/m/Y H:i', strtotime($quando)) : '';
        }

        // ---------- CPF / CNPJ ----------
        public static function somenteDigitos(string $valor): string
        {
            return preg_replace('/\D/', '', $valor) ?? '';
        }

        public static function formatarCpfCnpj(string $valor): string
        {
            $d = self::somenteDigitos($valor);
            if (strlen($d) === 11) {
                return substr($d, 0, 3) . '.' . substr($d, 3, 3) . '.' . substr($d, 6, 3) . '-' . substr($d, 9, 2);
            }
            if (strlen($d) === 14) {
                return substr($d, 0, 2) . '.' . substr($d, 2, 3) . '.' . substr($d, 5, 3) . '/' . substr($d, 8, 4) . '-' . substr($d, 12, 2);
            }
            return $valor;
        }

        public static function cpfValido(string $valor): bool
        {
            $d = self::somenteDigitos($valor);
            if (strlen($d) !== 11 || preg_match('/^(\d)\1{10}$/', $d)) {
                return false;
            }
            for ($t = 9; $t < 11; $t++) {
                $soma = 0;
                for ($i = 0; $i < $t; $i++) {
                    $soma += (int)$d[$i] * (($t + 1) - $i);
                }
                if ((int)$d[$t] !== ((10 * $soma) % 11) % 10) {
                    return false;
                }
            }
            return true;
        }

        public static function cnpjValido(string $valor): bool
        {
            $d = self::somenteDigitos($valor);
            if (strlen($d) !== 14 || preg_match('/^(\d)\1{13}$/', $d)) {
                return false;
            }
            $dv = function (string $base, array $pesos): int {
                $soma = 0;
                foreach ($pesos as $i => $peso) {
                    $soma += (int)$base[$i] * $peso;
                }
                $resto = $soma % 11;
                return $resto < 2 ? 0 : 11 - $resto;
            };
            return $dv(substr($d, 0, 12), [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]) === (int)$d[12]
                && $dv(substr($d, 0, 13), [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2]) === (int)$d[13];
        }

        public static function cpfCnpjValido(string $valor): bool
        {
            $tamanho = strlen(self::somenteDigitos($valor));
            return $tamanho === 11 ? self::cpfValido($valor) : ($tamanho === 14 ? self::cnpjValido($valor) : false);
        }

        // ---------- Placa / telefone ----------
        public static function normalizarPlaca(string $valor): string
        {
            return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $valor) ?? '');
        }

        // Aceita ABC1234 (antiga) e ABC1D23 (Mercosul)
        public static function placaValida(string $placa): bool
        {
            return (bool)preg_match('/^[A-Z]{3}[0-9][A-Z0-9][0-9]{2}$/', $placa);
        }

        public static function formatarTelefone(string $valor): string
        {
            $d = self::somenteDigitos($valor);
            if (strlen($d) === 11) {
                return '(' . substr($d, 0, 2) . ') ' . substr($d, 2, 5) . '-' . substr($d, 7);
            }
            if (strlen($d) === 10) {
                return '(' . substr($d, 0, 2) . ') ' . substr($d, 2, 4) . '-' . substr($d, 6);
            }
            return $valor;
        }

        // ---------- Rota no Google Maps ----------
        // Origem: "localização" da origem (endereço ou coordenadas). Destino: localização do destino,
        // ou, se vazia, estabelecimento + localidade + município. Devolve null se faltar a origem ou o destino.
        public static function urlRotaMaps(array $nota): ?string
        {
            $origem  = trim((string)($nota['origem_localizacao'] ?? ''));
            $destino = trim((string)($nota['destino_localizacao'] ?? ''));
            if ($destino === '') {
                $partes = array_filter([
                    $nota['destino_estabelecimento'] ?? '',
                    $nota['destino_localidade'] ?? '',
                    $nota['destino_municipio'] ?? '',
                ], fn($v) => trim((string)$v) !== '');
                $destino = implode(', ', $partes);
            }
            if ($origem === '' || $destino === '') {
                return null;
            }
            return 'https://www.google.com/maps/dir/?api=1&origin=' . rawurlencode($origem)
                 . '&destination=' . rawurlencode($destino) . '&travelmode=driving';
        }

        // ---------- Views ----------
        public static function render(string $view, array $dados = []): void
        {
            extract($dados, EXTR_SKIP);
            require BASE_PATH . '/views/' . $view . '.php';
        }

        public static function capturar(string $view, array $dados = []): string
        {
            ob_start();
            self::render($view, $dados);
            return (string)ob_get_clean();
        }
    }
}

// Atalhos globais usados dentro das views
namespace {
    function e($valor): string
    {
        return \App\Helpers\Helper::esc($valor);
    }

    function url(string $rota = '', array $params = []): string
    {
        return \App\Helpers\Helper::url($rota, $params);
    }

    function eh_admin(): bool
    {
        return \App\Helpers\Helper::ehAdmin();
    }

    function csrf_campo(): string
    {
        return '<input type="hidden" name="csrf" value="' . \App\Helpers\Helper::csrfToken() . '">';
    }
}
