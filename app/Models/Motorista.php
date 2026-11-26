<?php

namespace App\Models;

class Motorista
{
    public static function listar(): array
    {
        return db()->query(
            'SELECT m.*, u.email AS login_email
             FROM motoristas m
             LEFT JOIN usuarios u ON u.id = m.usuario_id
             ORDER BY m.nome ASC'
        )->fetchAll();
    }

    public static function buscar(int $id): ?array
    {
        $st = db()->prepare('SELECT * FROM motoristas WHERE id = ?');
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public static function usuarioJaVinculado(int $usuarioId, int $ignorarId = 0): bool
    {
        $st = db()->prepare(
            'SELECT id FROM motoristas
             WHERE usuario_id = ? AND id <> ?'
        );
        $st->execute([$usuarioId, $ignorarId]);
        return (bool)$st->fetch();
    }

    public static function criar(array $d): void
    {
        db()->prepare(
            'INSERT INTO motoristas
            (usuario_id, nome, cpf, cnh, telefone, placa_veiculo, modelo_veiculo)
            VALUES (?,?,?,?,?,?,?)'
        )->execute([
            $d['usuario_id'],
            $d['nome'],
            $d['cpf'],
            $d['cnh'],
            $d['telefone'],
            $d['placa_veiculo'],
            $d['modelo_veiculo']
        ]);
    }

    public static function atualizar(int $id, array $d): void
    {
        db()->prepare(
            'UPDATE motoristas
             SET usuario_id = ?, nome = ?, cpf = ?, cnh = ?,
                 telefone = ?, placa_veiculo = ?, modelo_veiculo = ?
             WHERE id = ?'
        )->execute([
            $d['usuario_id'],
            $d['nome'],
            $d['cpf'],
            $d['cnh'],
            $d['telefone'],
            $d['placa_veiculo'],
            $d['modelo_veiculo'],
            $id
        ]);
    }

    public static function excluir(int $id): void
    {
        db()->prepare('DELETE FROM motoristas WHERE id = ?')->execute([$id]);
    }

    public static function paraSelecao(): array
    {
        $itens = [];

        foreach (self::listar() as $m) {
            $itens[] = [
                'id' => $m['id'],
                'rotulo' => $m['nome'] . (
                    $m['placa_veiculo']
                    ? ' — ' . $m['placa_veiculo']
                    : ''
                ),
                'valores' => [
                    'motorista' => (string)$m['nome'],
                    'placa' => (string)($m['placa_veiculo'] ?? '')
                ],
            ];
        }

        return $itens;
    }
}