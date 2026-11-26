<?php
namespace App\Models;

// Cadastros reutilizáveis (produtores e destinatários). Para adicionar um campo, mude aqui e no SQL.
class Cadastro
{
    public const TIPOS = [
        'produtores' => [
            'titulo'    => 'Produtores',
            'singular'  => 'Produtor',
            'tabela'    => 'produtores',
            'principal' => 'produtor',
            'perfil'    => 'produtor',
            'campos'    => [
                'produtor'   => 'Produtor',
                'cpf_cnpj'   => 'CPF/CNPJ',
                'uep'        => 'UEP',
                'codigo_uep' => 'Código UEP',
                'numero_uep' => 'N° UEP',
                'localizacao' => 'Localização (endereço ou coordenadas)',
            ],
            // campo do cadastro => campo da nota que ele preenche
            'mapa'      => [
                'produtor'   => 'origem_produtor',
                'cpf_cnpj'   => 'origem_cpf_cnpj',
                'uep'        => 'origem_uep',
                'codigo_uep' => 'origem_codigo_uep',
                'numero_uep' => 'origem_numero_uep',
                'localizacao' => 'origem_localizacao',
            ],
            'colunas'   => ['produtor', 'cpf_cnpj', 'uep'],
        ],
        'destinatarios' => [
            'titulo'    => 'Destinatários',
            'singular'  => 'Destinatário',
            'tabela'    => 'destinatarios',
            'principal' => 'estabelecimento',
            'perfil'    => 'destinatario',
            'campos'    => [
                'estabelecimento' => 'Estabelecimento',
                'codigo_oficial'  => 'Código Oficial',
                'proprietario'    => 'Proprietário',
                'cpf_cnpj'        => 'CPF/CNPJ',
                'localidade'      => 'Localidade',
                'municipio'       => 'Município',
                'localizacao'     => 'Localização (endereço ou coordenadas)',
            ],
            'mapa'      => [
                'estabelecimento' => 'destino_estabelecimento',
                'codigo_oficial'  => 'destino_codigo_oficial',
                'proprietario'    => 'destino_proprietario',
                'cpf_cnpj'        => 'destino_cpf_cnpj',
                'localidade'      => 'destino_localidade',
                'municipio'       => 'destino_municipio',
                'localizacao'     => 'destino_localizacao',
            ],
            'colunas'   => ['estabelecimento', 'proprietario', 'municipio'],
        ],
    ];

    public static function listar(string $tipo): array
    {
        $c = self::TIPOS[$tipo];
        return db()->query("SELECT t.*, u.email AS login_email FROM {$c['tabela']} t LEFT JOIN usuarios u ON u.id = t.usuario_id ORDER BY t.{$c['principal']}")->fetchAll();
    }

    public static function buscar(string $tipo, int $id): ?array
    {
        $c = self::TIPOS[$tipo];
        $st = db()->prepare("SELECT * FROM {$c['tabela']} WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public static function usuarioJaVinculado(string $tipo, int $usuarioId, int $ignorarId = 0): bool
    {
        $c = self::TIPOS[$tipo];
        $st = db()->prepare("SELECT id FROM {$c['tabela']} WHERE usuario_id = ? AND id <> ?");
        $st->execute([$usuarioId, $ignorarId]);
        return (bool)$st->fetch();
    }

    public static function criar(string $tipo, array $dados, ?int $usuarioId = null): void
    {
        $c = self::TIPOS[$tipo];
        $cols  = implode(', ', array_keys($c['campos']));
        $marks = implode(', ', array_fill(0, count($c['campos']), '?'));
        $vals  = [];
        foreach (array_keys($c['campos']) as $campo) {
            $vals[] = $dados[$campo] ?? '';
        }
        $vals[] = $usuarioId;
        db()->prepare("INSERT INTO {$c['tabela']} ($cols, usuario_id) VALUES ($marks, ?)")->execute($vals);
    }

    public static function atualizar(string $tipo, int $id, array $dados, ?int $usuarioId = null): void
    {
        $c = self::TIPOS[$tipo];
        $set  = implode(', ', array_map(fn($campo) => "$campo = ?", array_keys($c['campos'])));
        $vals = [];
        foreach (array_keys($c['campos']) as $campo) {
            $vals[] = $dados[$campo] ?? '';
        }
        $vals[] = $usuarioId;
        $vals[] = $id;
        db()->prepare("UPDATE {$c['tabela']} SET $set, usuario_id = ? WHERE id = ?")->execute($vals);
    }

    public static function excluir(string $tipo, int $id): void
    {
        $c = self::TIPOS[$tipo];
        db()->prepare("DELETE FROM {$c['tabela']} WHERE id = ?")->execute([$id]);
    }

    // Lista pronta para o <select> da nota: rótulo + valores já nos nomes dos campos da nota
    public static function paraSelecao(string $tipo): array
    {
        $c = self::TIPOS[$tipo];
        $itens = [];
        foreach (self::listar($tipo) as $linha) {
            $valores = [];
            foreach ($c['mapa'] as $campoCadastro => $campoNota) {
                $valores[$campoNota] = (string)($linha[$campoCadastro] ?? '');
            }
            $itens[] = ['id' => $linha['id'], 'rotulo' => $linha[$c['principal']], 'valores' => $valores];
        }
        return $itens;
    }
}
