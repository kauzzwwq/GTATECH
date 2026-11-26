<?php

namespace App\Models;

class Rfid
{
    private \PDO $db;

    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    public function processarLeitura(string $uid): array
    {
        $uid = strtolower(trim($uid));

        if (!preg_match('/\A[0-9a-f]{4,64}\z/', $uid)) {
            return ['resultado' => 'desconhecido', 'linhas' => null];
        }

        $sql = "SELECT
                    c.id AS cartao_id,
                    m.id AS motorista_id,
                    m.nome AS motorista,
                    n.id AS nota_id,
                    n.gta_numero,
                    n.status AS nota_status
                FROM rfid_cartoes c
                LEFT JOIN motoristas m ON m.id = c.motorista_id
                LEFT JOIN notas n ON n.motorista_id = m.id
                    AND n.status IN ('emitida', 'em_transito')
                    AND (n.validade IS NULL OR n.validade >= CURDATE())
                WHERE c.uid = ? AND c.ativo = 1
                ORDER BY n.id DESC
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$uid]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$row) {
            $this->registrarLeitura($uid, null, null, null, 'desconhecido');
            return [
                'resultado' => 'desconhecido',
                'linhas' => ['ID: ' . strtoupper($uid), 'TAG desconhecida', 'Nao cadastrada', 'ACESSO NEGADO'],
            ];
        }

        if (empty($row['motorista'])) {
            $this->registrarLeitura($uid, $row['cartao_id'], null, null, 'sem_cadastro');
            return [
                'resultado' => 'sem_cadastro',
                'linhas' => ['ID: ' . strtoupper($uid), 'Cartao sem cadastro', 'Vincule no painel', 'ACESSO NEGADO'],
            ];
        }

        $motorista = mb_substr($this->limparNome($row['motorista']), 0, 20);

        if (empty($row['gta_numero'])) {
            $this->registrarLeitura($uid, $row['cartao_id'], $row['motorista_id'], null, 'sem_gta');
            return [
                'resultado' => 'sem_gta',
                'linhas' => ['ID: ' . strtoupper($uid), $motorista, 'SEM GTA ATIVA', 'STATUS: ' . strtoupper($row['nota_status'] ?? 'N/A')],
            ];
        }

        $this->registrarLeitura($uid, $row['cartao_id'], $row['motorista_id'], $row['nota_id'], 'autorizado');

        return [
            'resultado' => 'autorizado',
            'linhas' => [
                'ID: ' . strtoupper($uid),
                $motorista,
                'GTA: ' . $row['gta_numero'],
                'ST: ' . strtoupper(str_replace('_', ' ', $row['nota_status'])),
            ],
        ];
    }

    private function registrarLeitura(string $uid, ?int $cartaoId, ?int $motoristaId, ?int $notaId, string $resultado): void
    {
        $sql = "INSERT INTO rfid_leituras
                    (tag_uid, cartao_id, motorista_id, nota_id, resultado, ip_leitor)
                VALUES (?, ?, ?, ?, ?, ?)";

        $this->db->prepare($sql)->execute([
            $uid,
            $cartaoId,
            $motoristaId,
            $notaId,
            $resultado,
            $_SERVER['REMOTE_ADDR'] ?? null,
        ]);
    }

    public function ultimasLeituras(int $limite = 15): array
    {
        $limite = max(1, min($limite, 100));

        $sql = "SELECT
                    l.id,
                    l.tag_uid,
                    l.resultado,
                    DATE_FORMAT(l.data_hora, '%d/%m/%Y %H:%i:%s') AS data_hora,
                    m.nome AS motorista,
                    n.gta_numero
                FROM rfid_leituras l
                LEFT JOIN motoristas m ON m.id = l.motorista_id
                LEFT JOIN notas n ON n.id = l.nota_id
                ORDER BY l.id DESC
                LIMIT ?";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(1, $limite, \PDO::PARAM_INT);
        $stmt->execute();
        $lista = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($lista as &$r) {
            $r['motorista'] = $r['motorista'] ? $this->limparNome($r['motorista']) : 'Não cadastrado';
            $r['gta_numero'] = $r['gta_numero'] ?: 'N/A';
        }
        unset($r);

        return $lista;
    }

    public function ultimaLeitura(): ?array
    {
        $lista = $this->ultimasLeituras(1);
        return $lista[0] ?? null;
    }

    public function statusLeitor(): array
    {
        $segundos = $this->db->query(
            "SELECT TIMESTAMPDIFF(SECOND, MAX(data_hora), NOW()) FROM rfid_leituras"
        )->fetchColumn();

        if ($segundos === false || $segundos === null) {
            return ['online' => false, 'texto' => 'Nenhuma leitura registrada'];
        }

        $segundos = (int) $segundos;

        if ($segundos <= 120) {
            return ['online' => true, 'texto' => 'Online — última leitura há ' . $segundos . 's'];
        }

        if ($segundos <= 3600) {
            return ['online' => true, 'texto' => 'Aguardando TAG — sem leituras há ' . floor($segundos / 60) . ' min'];
        }

        return ['online' => false, 'texto' => 'Sem leituras há ' . floor($segundos / 3600) . ' h'];
    }

    public function listarCartoes(): array
    {
        return $this->db->query(
            "SELECT c.id, c.uid, c.descricao, c.ativo,
                    m.id AS motorista_id, m.nome AS motorista
             FROM rfid_cartoes c
             LEFT JOIN motoristas m ON m.id = c.motorista_id
             ORDER BY c.id DESC"
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function listarMotoristas(): array
    {
        return $this->db->query(
            "SELECT id, nome FROM motoristas ORDER BY nome"
        )->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function criarCartao(string $uid, string $descricao): array
    {
        $uid = strtolower(trim($uid));
        $descricao = trim($descricao);

        if (!preg_match('/\A[0-9a-f]{4,64}\z/', $uid)) {
            return ['ok' => false, 'msg' => 'UID inválido. Use apenas hexadecimal.'];
        }
        if (mb_strlen($descricao) > 150) {
            return ['ok' => false, 'msg' => 'A descrição deve ter no máximo 150 caracteres.'];
        }

        try {
            $this->db->prepare("INSERT INTO rfid_cartoes (uid, descricao) VALUES (?, ?)")
                ->execute([$uid, $descricao !== '' ? $descricao : null]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                return ['ok' => false, 'msg' => 'Esta TAG já está cadastrada.'];
            }
            throw $e;
        }

        return ['ok' => true, 'msg' => 'TAG cadastrada.'];
    }

    public function vincularMotorista(int $cartaoId, int $motoristaId): array
    {
        if ($cartaoId <= 0 || $motoristaId < 0) {
            return ['ok' => false, 'msg' => 'Selecione uma TAG e um motorista válidos.'];
        }

        try {
            $this->db->beginTransaction();
            if ($motoristaId > 0) {
                $this->db->prepare("UPDATE rfid_cartoes SET motorista_id = NULL WHERE motorista_id = ?")
                    ->execute([$motoristaId]);
            }
            $stmt = $this->db->prepare("UPDATE rfid_cartoes SET motorista_id = ? WHERE id = ?");
            $stmt->execute([$motoristaId ?: null, $cartaoId]);
            if ($stmt->rowCount() === 0 && !$this->cartaoExiste($cartaoId)) {
                $this->db->rollBack();
                return ['ok' => false, 'msg' => 'TAG não encontrada.'];
            }
            $this->db->commit();
        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Falha ao vincular TAG RFID: ' . $e->getMessage());
            return ['ok' => false, 'msg' => 'Não foi possível atualizar o vínculo da TAG.'];
        }

        return ['ok' => true, 'msg' => 'Vínculo atualizado.'];
    }

    public function alternarCartao(int $cartaoId): array
    {
        $stmt = $this->db->prepare("UPDATE rfid_cartoes SET ativo = 1 - ativo WHERE id = ?");
        $stmt->execute([$cartaoId]);

        return $stmt->rowCount() > 0
            ? ['ok' => true, 'msg' => 'Status alterado.']
            : ['ok' => false, 'msg' => 'TAG não encontrada.'];
    }

    public function excluirCartao(int $cartaoId): array
    {
        if ($cartaoId <= 0) {
            return ['ok' => false, 'msg' => 'TAG inválida.'];
        }

        $stmt = $this->db->prepare("SELECT uid FROM rfid_cartoes WHERE id = ?");
        $stmt->execute([$cartaoId]);
        $uid = $stmt->fetchColumn();

        if ($uid === false) {
            return ['ok' => false, 'msg' => 'TAG não encontrada.'];
        }

        try {
            $this->db->beginTransaction();

            // Desvincula as leituras do histórico antes de apagar a TAG,
            // preservando o registro da leitura (tag_uid continua gravado).
            $this->db->prepare("UPDATE rfid_leituras SET cartao_id = NULL WHERE cartao_id = ?")
                ->execute([$cartaoId]);

            $this->db->prepare("DELETE FROM rfid_cartoes WHERE id = ?")
                ->execute([$cartaoId]);

            $this->db->commit();
        } catch (\PDOException $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Falha ao excluir TAG RFID: ' . $e->getMessage());
            return ['ok' => false, 'msg' => 'Não foi possível excluir a TAG.'];
        }

        return ['ok' => true, 'msg' => 'TAG ' . strtoupper((string) $uid) . ' excluída.'];
    }

    private function cartaoExiste(int $cartaoId): bool
    {
        $stmt = $this->db->prepare("SELECT 1 FROM rfid_cartoes WHERE id = ?");
        $stmt->execute([$cartaoId]);
        return (bool) $stmt->fetchColumn();
    }

    private function limparNome(string $nome): string
    {
        $nome = trim((string) preg_replace('/\s*\(teste\)/i', '', $nome));
        return str_replace(['|', "\r", "\n"], ' ', $nome);
    }
}