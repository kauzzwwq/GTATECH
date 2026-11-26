<?php

namespace App\Models;

class Nota
{
    // Campos de texto da nota (nome_no_banco => rótulo). Mude aqui para mudar formulário, tela e PDF.
    public const CAMPOS_DESTINO = [
        'destino_evento'          => 'Evento',
        'destino_estabelecimento' => 'Estabelecimento',
        'destino_codigo_oficial'  => 'Código Oficial',
        'destino_proprietario'    => 'Proprietário',
        'destino_cpf_cnpj'        => 'CPF/CNPJ',
        'destino_localidade'      => 'Localidade',
        'destino_municipio'       => 'Município',
    ];

    public const CAMPOS_ORIGEM = [
        'origem_uep'        => 'UEP',
        'origem_codigo_uep' => 'Código UEP',
        'origem_numero_uep' => 'N° UEP',
        'origem_produtor'   => 'Produtor',
        'origem_cpf_cnpj'   => 'CPF/CNPJ',
    ];

    // Demais colunas editáveis no formulário
    public const CAMPOS_EXTRAS = [
        'produtor_id', 'destinatario_id', 'motorista_id', 'status',
        'especie', 'quantidade', 'finalidade', 'data_saida', 'placa', 'motorista', 'validade',
        'gta_numero',
        'origem_localizacao', 'destino_localizacao',
    ];

    public const STATUS = [
        'rascunho'    => 'Rascunho',
        'emitida'     => 'Emitida',
        'em_transito' => 'Em trânsito',
        'entregue'    => 'Entregue',
        'concluida'   => 'Concluída',
        'cancelada'   => 'Cancelada',
    ];

    public const FINALIDADES = [
        'abate'   => 'Abate',
        'engorda' => 'Engorda',
        'evento'  => 'Evento',
        'outros'  => 'Outros',
    ];

    private const SELECT = 'SELECT n.*, t.nome AS trabalhador_nome, r.nome AS recebida_por_nome
                            FROM notas n
                            LEFT JOIN usuarios t ON t.id = n.trabalhador_id
                            LEFT JOIN usuarios r ON r.id = n.recebida_por';

    private const SQL_VENCIDA = "n.validade IS NOT NULL AND n.validade < CURDATE() AND n.status IN ('emitida','em_transito')";

    public static function todosCampos(): array
    {
        return self::CAMPOS_DESTINO + self::CAMPOS_ORIGEM;
    }

    // ---------- Quem vê o quê ----------
    //  admin ........ todas
    //  produtor ..... as que ele criou + as que o administrador emitiu para o cadastro dele
    //  motorista .... as em que ele é o motorista (sem rascunho)
    //  destinatario . as em que ele é o recebedor (sem rascunho)
    //  trabalhador .. (perfil antigo) as atribuídas a ele (sem rascunho)
    private static function escopo(string $perfil, int $uid): array
    {
        switch ($perfil) {
            case 'admin':
                return ['1=1', []];
            case 'produtor':
                return ["(n.usuario_id = ? OR (n.status <> 'rascunho' AND n.produtor_id IN (SELECT id FROM produtores WHERE usuario_id = ?)))", [$uid, $uid]];
            case 'motorista':
                return ["(n.status <> 'rascunho' AND n.motorista_id IN (SELECT id FROM motoristas WHERE usuario_id = ?))", [$uid]];
            case 'destinatario':
                return ["(n.status <> 'rascunho' AND n.destinatario_id IN (SELECT id FROM destinatarios WHERE usuario_id = ?))", [$uid]];
            default:
                return ["(n.status <> 'rascunho' AND n.trabalhador_id = ?)", [$uid]];
        }
    }

    // ---------- Quem pode mexer ----------
    public static function podeEditar(array $n, string $perfil): bool
    {
        if ($perfil === 'admin') {
            return true;
        }
        return $perfil === 'produtor' && in_array($n['status'], ['rascunho', 'emitida'], true);
    }

    public static function podeExcluir(array $n, string $perfil): bool
    {
        if ($perfil === 'admin') {
            return true;
        }
        return $perfil === 'produtor' && $n['status'] === 'rascunho';
    }

    public static function podeDarBaixa(array $n, string $perfil): bool
    {
        return in_array($perfil, ['admin', 'destinatario'], true)
            && in_array($n['status'], ['emitida', 'em_transito'], true);
    }

    public static function vencida(array $n): bool
    {
        return !empty($n['validade'])
            && $n['validade'] < date('Y-m-d')
            && in_array($n['status'] ?? '', ['emitida', 'em_transito'], true);
    }

    // ---------- Textos prontos para tela e PDF ----------
    public static function resumoTransporte(array $n): array
    {
        $txt = fn($v) => ($v === null || $v === '') ? '—' : (string)$v;

        return [
            'Espécie'               => $txt($n['especie'] ?? null),
            'Quantidade de animais' => $txt($n['quantidade'] ?? null),
            'Finalidade'            => $txt(self::FINALIDADES[$n['finalidade'] ?? ''] ?? null),
            'Data e hora da saída'  => !empty($n['data_saida']) ? date('d/m/Y H:i', strtotime($n['data_saida'])) : '—',
            'Placa do veículo'      => $txt($n['placa'] ?? null),
            'Motorista'             => $txt($n['motorista'] ?? null),
            'Validade'              => !empty($n['validade']) ? date('d/m/Y', strtotime($n['validade'])) : '—',
        ];
    }

    // Dados da baixa (null se a nota ainda não foi recebida)
    public static function resumoRecebimento(array $n): ?array
    {
        if (empty($n['recebida_em'])) {
            return null;
        }

        $txt = fn($v) => ($v === null || $v === '') ? '—' : (string)$v;

        return [
            'Recebida em'         => date('d/m/Y H:i', strtotime($n['recebida_em'])),
            'Baixa feita por'     => $txt($n['recebida_por_nome'] ?? null),
            'Quantidade recebida' => $txt($n['recebida_quantidade'] ?? null),
            'Observações'         => $txt($n['recebimento_obs'] ?? null),
        ];
    }

    // ---------- Contagens ----------
    public static function total(string $perfil, int $uid): int
    {
        [$w, $p] = self::escopo($perfil, $uid);
        $st = db()->prepare("SELECT COUNT(*) FROM notas n WHERE $w");
        $st->execute($p);
        return (int)$st->fetchColumn();
    }

    public static function totalPorStatus(string $perfil, int $uid, array $status): int
    {
        [$w, $p] = self::escopo($perfil, $uid);
        $marcas = implode(',', array_fill(0, count($status), '?'));
        $st = db()->prepare("SELECT COUNT(*) FROM notas n WHERE $w AND n.status IN ($marcas)");
        $st->execute([...$p, ...$status]);
        return (int)$st->fetchColumn();
    }

    public static function totalVencidas(string $perfil, int $uid): int
    {
        [$w, $p] = self::escopo($perfil, $uid);
        $st = db()->prepare('SELECT COUNT(*) FROM notas n WHERE ' . $w . ' AND ' . self::SQL_VENCIDA);
        $st->execute($p);
        return (int)$st->fetchColumn();
    }

    // ---------- Consultas ----------
    public static function recentes(string $perfil, int $uid, int $limite = 5): array
    {
        [$w, $p] = self::escopo($perfil, $uid);
        $st = db()->prepare(self::SELECT . " WHERE $w ORDER BY n.id DESC LIMIT " . (int)$limite);
        $st->execute($p);
        return $st->fetchAll();
    }

    // Filtros: q (texto), status (ou "vencida"), de/ate (data de emissão), motorista (id)
    public static function listar(string $perfil, int $uid, array $f = []): array
    {
        [$w, $p] = self::escopo($perfil, $uid);
        $sql = self::SELECT . " WHERE $w";

        $busca = $f['q'] ?? '';
        if ($busca !== '') {
            $sql .= ' AND (n.destino_estabelecimento LIKE ? OR n.destino_proprietario LIKE ? OR n.destino_municipio LIKE ? OR n.origem_produtor LIKE ? OR n.origem_uep LIKE ? OR n.placa LIKE ? OR n.motorista LIKE ? OR n.gta_numero LIKE ? OR n.numero_seq = ?)';
            $like = "%$busca%";
            array_push($p, $like, $like, $like, $like, $like, $like, $like, $like, (int)$busca);
        }

        $status = $f['status'] ?? '';
        if ($status === 'vencida') {
            $sql .= ' AND ' . self::SQL_VENCIDA;
        } elseif (isset(self::STATUS[$status])) {
            $sql .= ' AND n.status = ?';
            $p[] = $status;
        }

        if (!empty($f['de'])) {
            $sql .= ' AND DATE(n.criado_em) >= ?';
            $p[] = $f['de'];
        }

        if (!empty($f['ate'])) {
            $sql .= ' AND DATE(n.criado_em) <= ?';
            $p[] = $f['ate'];
        }

        if (!empty($f['motorista']) && ctype_digit((string)$f['motorista'])) {
            $sql .= ' AND n.motorista_id = ?';
            $p[] = (int)$f['motorista'];
        }

        $sql .= ' ORDER BY n.numero_ano DESC, n.numero_seq DESC';

        $st = db()->prepare($sql);
        $st->execute($p);
        return $st->fetchAll();
    }

    public static function buscar(int $id, string $perfil, int $uid): ?array
    {
        [$w, $p] = self::escopo($perfil, $uid);
        $st = db()->prepare(self::SELECT . " WHERE n.id = ? AND $w");
        $st->execute([$id, ...$p]);
        return $st->fetch() ?: null;
    }

    // Uma ou várias notas para o PDF (respeitando o que a pessoa pode ver)
    public static function buscarParaPdf(array $ids, string $perfil, int $uid): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if (!$ids) {
            return [];
        }

        [$w, $p] = self::escopo($perfil, $uid);
        $marcas = implode(',', array_fill(0, count($ids), '?'));

        $st = db()->prepare(
            "SELECT n.*, u.nome AS emitido_por, r.nome AS recebida_por_nome
             FROM notas n
             JOIN usuarios u ON u.id = n.usuario_id
             LEFT JOIN usuarios r ON r.id = n.recebida_por
             WHERE n.id IN ($marcas) AND $w
             ORDER BY n.numero_ano, n.numero_seq"
        );
        $st->execute([...$ids, ...$p]);
        return $st->fetchAll();
    }

    // ---------- Gravação ----------
    // Cria a nota já com o próximo número do ano (2026/00001, 2026/00002...). Devolve o id.
    public static function criar(int $usuarioId, array $dados): int
    {
        $pdo = db();
        $pdo->beginTransaction();

        try {
            $ano = (int)date('Y');
            $st = $pdo->prepare('SELECT COALESCE(MAX(numero_seq), 0) + 1 FROM notas WHERE numero_ano = ? FOR UPDATE');
            $st->execute([$ano]);

            $dados['numero_ano'] = $ano;
            $dados['numero_seq'] = (int)$st->fetchColumn();

            $cols  = implode(', ', array_keys($dados));
            $marks = implode(', ', array_fill(0, count($dados), '?'));

            $ins = $pdo->prepare("INSERT INTO notas (usuario_id, $cols) VALUES (?, $marks)");
            $ins->execute([$usuarioId, ...array_values($dados)]);

            $id = (int)$pdo->lastInsertId();
            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public static function atualizar(int $id, array $dados): void
    {
        $set = implode(', ', array_map(fn($c) => "$c = ?", array_keys($dados)));
        $st = db()->prepare("UPDATE notas SET $set WHERE id = ?");
        $st->execute([...array_values($dados), $id]);
    }

    public static function atualizarStatus(int $id, string $status): void
    {
        if (isset(self::STATUS[$status])) {
            db()->prepare('UPDATE notas SET status = ? WHERE id = ?')->execute([$status, $id]);
        }
    }

    // Baixa: o recebedor confirma que recebeu. Só vale para notas emitidas ou em trânsito.
    public static function darBaixa(int $id, int $usuarioId, ?int $quantidade, string $obs): bool
    {
        $st = db()->prepare(
            "UPDATE notas SET status = 'entregue', recebida_em = NOW(), recebida_por = ?, recebida_quantidade = ?, recebimento_obs = ?
             WHERE id = ? AND status IN ('emitida','em_transito')"
        );
        $st->execute([$usuarioId, $quantidade, $obs !== '' ? $obs : null, $id]);
        return $st->rowCount() > 0;
    }

    public static function excluir(int $id): void
    {
        db()->prepare('DELETE FROM notas WHERE id = ?')->execute([$id]);
    }

    // Copia os dados para uma nota nova (rascunho, sem data de saída e sem validade). Devolve o novo id.
    public static function duplicar(int $id, string $perfil, int $usuarioId): ?int
    {
        $orig = self::buscar($id, $perfil, $usuarioId);
        if (!$orig) {
            return null;
        }

        $dados = [];
        foreach (array_keys(self::todosCampos()) as $c) {
            $dados[$c] = $orig[$c];
        }

        foreach (['produtor_id', 'destinatario_id', 'motorista_id', 'especie', 'quantidade', 'finalidade',
                  'placa', 'motorista', 'gta_numero', 'origem_localizacao', 'destino_localizacao'] as $c) {
            $dados[$c] = $orig[$c];
        }

        $dados['status']     = 'rascunho';
        $dados['data_saida'] = null;
        $dados['validade']   = null;

        return self::criar($usuarioId, $dados);
    }

    // Ao excluir um usuário, as notas que ele criou passam para quem está excluindo.
    public static function reatribuirCriador(int $deUsuarioId, int $paraUsuarioId): void
    {
        db()->prepare('UPDATE notas SET usuario_id = ? WHERE usuario_id = ?')->execute([$paraUsuarioId, $deUsuarioId]);
    }
}