<?php
namespace App\Models;

class Usuario
{
    public const PERFIS = [
        'admin'        => 'Administrador',
        'produtor'     => 'Produtor',
        'motorista'    => 'Motorista',
        'destinatario' => 'Recebedor',
        'trabalhador'  => 'Trabalhador (antigo)',
    ];

    public static function buscarPorEmail(string $email): ?array
    {
        $st = db()->prepare('SELECT * FROM usuarios WHERE email = ?');
        $st->execute([$email]);
        return $st->fetch() ?: null;
    }

    public static function buscarPorId(int $id): ?array
    {
        $st = db()->prepare('SELECT * FROM usuarios WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public static function emailEmUso(string $email, int $ignorarId = 0): bool
    {
        $st = db()->prepare('SELECT id FROM usuarios WHERE email = ? AND id <> ?');
        $st->execute([$email, $ignorarId]);
        return (bool)$st->fetch();
    }

    public static function total(): int
    {
        return (int)db()->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
    }

    public static function totalAdmins(): int
    {
        return (int)db()->query("SELECT COUNT(*) FROM usuarios WHERE perfil = 'admin'")->fetchColumn();
    }

    public static function listarTodos(): array
    {
        return db()->query(
            "SELECT u.*,
                COALESCE(
                    (SELECT produtor FROM produtores WHERE usuario_id = u.id LIMIT 1),
                    (SELECT estabelecimento FROM destinatarios WHERE usuario_id = u.id LIMIT 1),
                    (SELECT nome FROM motoristas WHERE usuario_id = u.id LIMIT 1)
                ) AS vinculo
             FROM usuarios u
             ORDER BY FIELD(u.perfil,'admin','produtor','motorista','destinatario','trabalhador'), u.nome"
        )->fetchAll();
    }

    public static function listarPorPerfil(string $perfil): array
    {
        $st = db()->prepare('SELECT id, nome, email FROM usuarios WHERE perfil = ? ORDER BY nome');
        $st->execute([$perfil]);
        return $st->fetchAll();
    }

    public static function listrarDisponiveis(string $perfil, string $tabela, string $campoNome): array
    {
        $st = db()->prepare(
            "SELECT u.id, u.nome, u.email, c.$campoNome AS vinculo
             FROM usuarios u
             LEFT JOIN $tabela c ON c.usuario_id = u.id
             WHERE u.perfil = ? AND c.usuario_id IS NULL
             ORDER BY u.nome"
        );
        $st->execute([$perfil]);
        return $st->fetchAll();
    }

    // Cria o usuário e o cadastro vinculado (motorista / produtor / destinatário)
    public static function criar(
        string $nome,
        string $email,
        string $senha,
        ?string $perfil = null,
        string $telefone = '',
        string $cpfCnpj = '',
        array $extras = []
    ): int {
        if ($perfil === null) {
            $perfil = self::total() === 0 ? 'admin' : 'trabalhador';
        }

        $pdo = db();
        $pdo->beginTransaction();

        try {
            $pdo->prepare('INSERT INTO usuarios (nome, email, senha_hash, perfil, telefone, cpf_cnpj) VALUES (?,?,?,?,?,?)')
                ->execute([$nome, $email, password_hash($senha, PASSWORD_DEFAULT), $perfil, $telefone ?: null, $cpfCnpj ?: null]);

            $id = (int) $pdo->lastInsertId();

            self::sincronizarCadastro($id);

            if ($extras) {
                self::atualizarCadastroVinculado($id, $nome, $telefone ?: null, $cpfCnpj ?: null, $extras);
            }

            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public static function atualizar(
        int $id,
        string $nome,
        string $email,
        string $perfil,
        ?string $senha = null,
        string $telefone = '',
        string $cpfCnpj = '',
        array $extras = []
    ): void {
        $tel = $telefone ?: null;
        $doc = $cpfCnpj ?: null;

        if ($senha !== null && $senha !== '') {
            db()->prepare('UPDATE usuarios SET nome = ?, email = ?, perfil = ?, telefone = ?, cpf_cnpj = ?, senha_hash = ? WHERE id = ?')
                ->execute([$nome, $email, $perfil, $tel, $doc, password_hash($senha, PASSWORD_DEFAULT), $id]);
        } else {
            db()->prepare('UPDATE usuarios SET nome = ?, email = ?, perfil = ?, telefone = ?, cpf_cnpj = ? WHERE id = ?')
                ->execute([$nome, $email, $perfil, $tel, $doc, $id]);
        }

        self::sincronizarCadastro($id);
        self::atualizarCadastroVinculado($id, $nome, $tel, $doc, $extras);
    }

    public static function atualizarSenha(int $id, string $senha): void
    {
        db()->prepare('UPDATE usuarios SET senha_hash = ? WHERE id = ?')
            ->execute([password_hash($senha, PASSWORD_DEFAULT), $id]);
    }

    public static function excluir(int $id): void
    {
        db()->prepare('DELETE FROM usuarios WHERE id = ?')->execute([$id]);
    }

    // Garante que produtor / motorista / recebedor tenha um cadastro vinculado ao login.
    public static function sincronizarCadastro(int $id): void
    {
        $u = self::buscarPorId($id);
        if (!$u) {
            return;
        }
        $pdo = db();
        $doc = (string)($u['cpf_cnpj'] ?? '');

        if ($u['perfil'] === 'produtor' && !self::temCadastro('produtores', $id)) {
            $pdo->prepare('INSERT INTO produtores (usuario_id, produtor, cpf_cnpj) VALUES (?,?,?)')
                ->execute([$id, $u['nome'], $doc ?: null]);
        } elseif ($u['perfil'] === 'destinatario' && !self::temCadastro('destinatarios', $id)) {
            $pdo->prepare('INSERT INTO destinatarios (usuario_id, estabelecimento, proprietario, cpf_cnpj) VALUES (?,?,?,?)')
                ->execute([$id, $u['nome'], $u['nome'], $doc ?: null]);
        } elseif ($u['perfil'] === 'motorista' && !self::temCadastro('motoristas', $id)) {
            $cpf = \App\Helpers\Helper::cpfValido($doc) ? $doc : null;
            $pdo->prepare('INSERT INTO motoristas (usuario_id, nome, cpf, telefone) VALUES (?,?,?,?)')
                ->execute([$id, $u['nome'], $cpf, $u['telefone'] ?: null]);
        }
    }

    // Mantém os dados sincronizados nas tabelas filhas após uma edição.
    // COALESCE garante que campos não enviados não apaguem o que já existe.
    private static function atualizarCadastroVinculado(
        int $id,
        string $nome,
        ?string $telefone,
        ?string $doc,
        array $extras = []
    ): void {
        $u = self::buscarPorId($id);
        if (!$u) {
            return;
        }

        $pdo = db();

        if ($u['perfil'] === 'motorista') {
            $cpf = \App\Helpers\Helper::cpfValido((string) $doc) ? $doc : null;

            $pdo->prepare(
                'UPDATE motoristas
                 SET nome = ?, cpf = ?, telefone = ?,
                     cnh = COALESCE(?, cnh),
                     placa_veiculo = COALESCE(?, placa_veiculo),
                     modelo_veiculo = COALESCE(?, modelo_veiculo)
                 WHERE usuario_id = ?'
            )->execute([
                $nome,
                $cpf,
                $telefone,
                ($extras['cnh'] ?? '') ?: null,
                ($extras['placa_veiculo'] ?? '') ?: null,
                ($extras['modelo_veiculo'] ?? '') ?: null,
                $id,
            ]);

        } elseif ($u['perfil'] === 'produtor') {
            $pdo->prepare(
                'UPDATE produtores
                 SET produtor = ?, cpf_cnpj = ?,
                     uep = COALESCE(?, uep),
                     codigo_uep = COALESCE(?, codigo_uep),
                     localizacao = COALESCE(?, localizacao)
                 WHERE usuario_id = ?'
            )->execute([
                $nome,
                $doc,
                ($extras['uep'] ?? '') ?: null,
                ($extras['codigo_uep'] ?? '') ?: null,
                ($extras['localizacao'] ?? '') ?: null,
                $id,
            ]);

        } elseif ($u['perfil'] === 'destinatario') {
            $pdo->prepare(
                'UPDATE destinatarios
                 SET estabelecimento = ?, cpf_cnpj = ?,
                     proprietario = COALESCE(?, proprietario),
                     codigo_oficial = COALESCE(?, codigo_oficial),
                     municipio = COALESCE(?, municipio),
                     localizacao = COALESCE(?, localizacao)
                 WHERE usuario_id = ?'
            )->execute([
                $nome,
                $doc,
                ($extras['proprietario'] ?? '') ?: null,
                ($extras['codigo_oficial'] ?? '') ?: null,
                ($extras['municipio'] ?? '') ?: null,
                ($extras['localizacao_destinatario'] ?? '') ?: null,
                $id,
            ]);
        }
    }

    private static function temCadastro(string $tabela, int $usuarioId): bool
    {
        $st = db()->prepare("SELECT id FROM $tabela WHERE usuario_id = ? LIMIT 1");
        $st->execute([$usuarioId]);
        return (bool)$st->fetch();
    }

    // ---------- Recuperação de senha ----------
    public static function criarTokenRecuperacao(int $usuarioId, string $token): void
    {
        db()->prepare('INSERT INTO recuperacao_senha (usuario_id, token, expira_em) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))')
            ->execute([$usuarioId, hash('sha256', $token)]);
    }

    public static function buscarTokenValido(string $token): ?array
    {
        if ($token === '') {
            return null;
        }
        $st = db()->prepare('SELECT * FROM recuperacao_senha WHERE token = ? AND expira_em > NOW()');
        $st->execute([hash('sha256', $token)]);
        return $st->fetch() ?: null;
    }

    public static function pedidoRecente(int $usuarioId): bool
    {
        $st = db()->prepare('SELECT id FROM recuperacao_senha WHERE usuario_id = ? AND criado_em > (NOW() - INTERVAL 60 SECOND) LIMIT 1');
        $st->execute([$usuarioId]);
        return (bool)$st->fetch();
    }

    public static function apagarTokens(int $usuarioId): void
    {
        db()->prepare('DELETE FROM recuperacao_senha WHERE usuario_id = ?')->execute([$usuarioId]);
    }
}