<?php

namespace App\Controllers;

use App\Helpers\Helper;
use App\Models\Cadastro;
use App\Models\Motorista;
use App\Models\Nota;
use Dompdf\Dompdf;
use Dompdf\Options;

class NotaController
{
    private function dataValida(string $v): string
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ? $v : '';
    }

    // Cadastro de produtor ligado ao login do usuário atual (para quem é produtor)
    private function meuProdutor(): ?array
    {
        foreach (Cadastro::listar('produtores') as $p) {
            if ((int)$p['usuario_id'] === Helper::usuarioId()) {
                return $p;
            }
        }
        return null;
    }

    // ---------------- Lista ----------------
    public function index(): void
    {
        $admin = Helper::ehAdmin();

        $filtros = [
            'q'         => trim($_GET['q'] ?? ''),
            'status'    => $_GET['status'] ?? '',
            'de'        => $this->dataValida($_GET['de'] ?? ''),
            'ate'       => $this->dataValida($_GET['ate'] ?? ''),
            'motorista' => $admin ? ($_GET['motorista'] ?? '') : '',
        ];

        Helper::render('notas/lista', [
            'notas'      => Nota::listar(Helper::perfil(), Helper::usuarioId(), $filtros),
            'filtros'    => $filtros,
            'motoristas' => $admin ? Motorista::listar() : [],
            'msg'        => $_GET['msg'] ?? '',
        ]);
    }

    // ---------------- Ver (somente leitura) ----------------
    public function ver(): void
    {
        $nota = Nota::buscar((int)($_GET['id'] ?? 0), Helper::perfil(), Helper::usuarioId());

        if (!$nota) {
            http_response_code(404);
            exit('Nota não encontrada.');
        }

        Helper::render('notas/ver', [
            'n'             => $nota,
            'transporte'    => Nota::resumoTransporte($nota),
            'recebimento'   => Nota::resumoRecebimento($nota),
            'rotaMaps'      => Helper::urlRotaMaps($nota),
            'camposDestino' => Nota::CAMPOS_DESTINO,
            'camposOrigem'  => Nota::CAMPOS_ORIGEM,
            'msg'           => $_GET['msg'] ?? '',
            'erro'          => $_GET['erro'] ?? '',
        ]);
    }

    // ---------------- Formulário: lê e valida ----------------
    // Devolve [dados para salvar, erros, valores brutos para reexibir].
    private function coletar(string $perfil, ?array $meuProdutor, ?array $existente): array
    {
        $erros = [];
        $admin = $perfil === 'admin';
        $bruto = [];

        foreach (array_merge(array_keys(Nota::todosCampos()), Nota::CAMPOS_EXTRAS) as $c) {
            $bruto[$c] = trim((string)($_POST[$c] ?? ''));
        }

        $dados = [];
        foreach (array_keys(Nota::todosCampos()) as $c) {
            $dados[$c] = $bruto[$c];
        }

        // CPF/CNPJ
        foreach (['destino_cpf_cnpj' => 'do destino', 'origem_cpf_cnpj' => 'da origem'] as $c => $onde) {
            if ($dados[$c] !== '') {
                if (Helper::cpfCnpjValido($dados[$c])) {
                    $dados[$c] = Helper::formatarCpfCnpj($dados[$c]);
                } else {
                    $erros[] = "O CPF/CNPJ $onde é inválido.";
                }
            }
        }

        // Status: o produtor só pode usar rascunho ou emitida
        $permitidos = $admin ? array_keys(Nota::STATUS) : ['rascunho', 'emitida'];

        if (in_array($bruto['status'], $permitidos, true)) {
            $dados['status'] = $bruto['status'];
        } else {
            $dados['status'] = $existente['status'] ?? 'emitida';
        }

        // Número da GTA (obrigatório ao emitir)
        $dados['gta_numero'] = $bruto['gta_numero'] !== '' ? mb_substr($bruto['gta_numero'], 0, 30) : null;

        if ($dados['status'] === 'emitida' && $dados['gta_numero'] === null) {
            $erros[] = 'Informe o número da GTA para emitir a nota.';
        }

        // Vínculos (produtor / destinatário / motorista)
        $dados['produtor_id'] = null;
        if ($admin) {
            if ($bruto['produtor_id'] !== '' && Cadastro::buscar('produtores', (int)$bruto['produtor_id'])) {
                $dados['produtor_id'] = (int)$bruto['produtor_id'];
            }
        } elseif ($meuProdutor) {
            $dados['produtor_id'] = (int)$meuProdutor['id'];
        }

        $dados['destinatario_id'] = null;
        if ($bruto['destinatario_id'] !== '' && Cadastro::buscar('destinatarios', (int)$bruto['destinatario_id'])) {
            $dados['destinatario_id'] = (int)$bruto['destinatario_id'];
        }

        $dados['motorista_id'] = null;
        $mot = null;
        if ($bruto['motorista_id'] !== '') {
            $mot = Motorista::buscar((int)$bruto['motorista_id']);
            if ($mot) {
                $dados['motorista_id'] = (int)$mot['id'];
            }
        }

        // Para o recebedor e o motorista enxergarem a nota, eles precisam estar escolhidos
        if (!$admin && $dados['status'] === 'emitida') {
            if (!$dados['destinatario_id']) {
                $erros[] = 'Escolha o destinatário (recebedor) cadastrado, senão ele não conseguirá ver nem dar baixa na nota.';
            }
            if (!$dados['motorista_id']) {
                $erros[] = 'Escolha o motorista cadastrado, senão ele não conseguirá ver a nota nem a rota.';
            }
        }

        // Transporte
        $dados['especie']   = $bruto['especie'] !== '' ? $bruto['especie'] : null;
        $dados['motorista'] = $bruto['motorista'] !== '' ? $bruto['motorista'] : ($mot['nome'] ?? null);

        $dados['quantidade'] = null;
        if ($bruto['quantidade'] !== '') {
            if (ctype_digit($bruto['quantidade']) && (int)$bruto['quantidade'] > 0) {
                $dados['quantidade'] = (int)$bruto['quantidade'];
            } else {
                $erros[] = 'A quantidade de animais deve ser um número inteiro maior que zero.';
            }
        }

        $dados['finalidade'] = isset(Nota::FINALIDADES[$bruto['finalidade']]) ? $bruto['finalidade'] : null;

        $dados['data_saida'] = null;
        if ($bruto['data_saida'] !== '') {
            $dt = \DateTime::createFromFormat('Y-m-d\TH:i', str_replace(' ', 'T', substr($bruto['data_saida'], 0, 16)));
            if ($dt) {
                $dados['data_saida'] = $dt->format('Y-m-d H:i:00');
            } else {
                $erros[] = 'A data e hora da saída são inválidas.';
            }
        }

        $dados['placa'] = null;
        $placa = Helper::normalizarPlaca($bruto['placa'] !== '' ? $bruto['placa'] : ($mot['placa_veiculo'] ?? ''));
        if ($placa !== '') {
            if (Helper::placaValida($placa)) {
                $dados['placa'] = $placa;
            } else {
                $erros[] = 'A placa é inválida (use o formato ABC1234 ou ABC1D23).';
            }
        }

        $dados['validade'] = null;
        if ($bruto['validade'] !== '') {
            $d = \DateTime::createFromFormat('Y-m-d', $bruto['validade']);
            if ($d && $d->format('Y-m-d') === $bruto['validade']) {
                $dados['validade'] = $bruto['validade'];
            } else {
                $erros[] = 'A data de validade é inválida.';
            }
        }

        if ($dados['validade'] && $dados['data_saida'] && $dados['validade'] < substr($dados['data_saida'], 0, 10)) {
            $erros[] = 'A validade não pode ser anterior à data de saída.';
        }

        // Localização (endereço ou coordenadas) usada na rota do Google Maps
        foreach (['origem_localizacao', 'destino_localizacao'] as $c) {
            $dados[$c] = $bruto[$c] !== '' ? mb_substr($bruto[$c], 0, 255) : null;
        }

        return [$dados, $erros, $bruto];
    }

    // ---------------- Criar / editar: admin e produtor ----------------
    public function form(): void
    {
        $perfil = Helper::perfil();
        $uid    = Helper::usuarioId();
        $admin  = Helper::ehAdmin();
        $id     = (int)($_GET['id'] ?? 0);
        $erros  = [];

        $meuProdutor = null;
        if (!$admin) {
            $meuProdutor = $this->meuProdutor();
            if (!$meuProdutor) {
                Helper::redirecionar('notas', ['msg' => 'Seu usuário ainda não está ligado a um cadastro de produtor. Peça ao administrador para vincular.']);
            }
        }

        $nota = array_fill_keys(array_merge(array_keys(Nota::todosCampos()), Nota::CAMPOS_EXTRAS), '');
        $nota['status'] = 'emitida';

        $existente = null;
        if ($id) {
            $existente = Nota::buscar($id, $perfil, $uid);
            if (!$existente) {
                http_response_code(404);
                exit('Nota não encontrada.');
            }
            if (!Nota::podeEditar($existente, $perfil)) {
                Helper::redirecionar('nota-ver', ['id' => $id, 'erro' => 'Esta nota não pode mais ser editada (status: ' . (Nota::STATUS[$existente['status']] ?? $existente['status']) . ').']);
            }
            $nota = $existente;
        } elseif ($meuProdutor) {
            // Nota nova de produtor: a origem já vem preenchida com os dados dele
            foreach (Cadastro::TIPOS['produtores']['mapa'] as $campoCadastro => $campoNota) {
                $nota[$campoNota] = (string)($meuProdutor[$campoCadastro] ?? '');
            }
            $nota['produtor_id'] = $meuProdutor['id'];
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Helper::csrfCheck();
            [$dados, $erros, $bruto] = $this->coletar($perfil, $meuProdutor, $existente);

            if (!$erros) {
                if ($id) {
                    Nota::atualizar($id, $dados);
                    $msg = 'Nota atualizada.';
                } else {
                    Nota::criar($uid, $dados);
                    $msg = 'Nota criada.';
                }
                Helper::redirecionar('notas', ['msg' => $msg]);
            }

            $nota = array_merge($nota, $bruto);   // mantém o que a pessoa digitou
        }

        Helper::render('notas/form', [
            'id'            => $id,
            'nota'          => $nota,
            'erros'         => $erros,
            'admin'         => $admin,
            'msg'           => $_GET['msg'] ?? '',
            'camposDestino' => Nota::CAMPOS_DESTINO,
            'camposOrigem'  => Nota::CAMPOS_ORIGEM,
            'status'        => $admin ? Nota::STATUS : array_intersect_key(Nota::STATUS, ['rascunho' => 1, 'emitida' => 1]),
            'produtores'    => $admin ? Cadastro::paraSelecao('produtores') : [],
            'destinatarios' => Cadastro::paraSelecao('destinatarios'),
            'motoristas'    => Motorista::paraSelecao(),
        ]);
    }

    public function excluir(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Helper::csrfCheck();
            $n = Nota::buscar((int)($_POST['id'] ?? 0), Helper::perfil(), Helper::usuarioId());
            if ($n && Nota::podeExcluir($n, Helper::perfil())) {
                Nota::excluir((int)$n['id']);
                Helper::redirecionar('notas', ['msg' => 'Nota excluída.']);
            }
            Helper::redirecionar('notas', ['msg' => 'Você não pode excluir esta nota (produtores só excluem rascunhos).']);
        }
        Helper::redirecionar('notas');
    }

    // Copia a nota como rascunho e abre para edição
    public function duplicar(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Helper::csrfCheck();
            $novo = Nota::duplicar((int)($_POST['id'] ?? 0), Helper::perfil(), Helper::usuarioId());
            if ($novo) {
                Helper::redirecionar('nota-form', ['id' => $novo, 'msg' => 'Nota duplicada como rascunho. Revise os dados e salve.']);
            }
        }
        Helper::redirecionar('notas', ['msg' => 'Não foi possível duplicar a nota.']);
    }

    // Troca rápida de status (somente admin; a rota já exige)
    public function status(): void
    {
        $id = (int)($_POST['id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Helper::csrfCheck();
            $novoStatus = $_POST['status'] ?? '';

            // Não permite emitir sem número de GTA
            if ($novoStatus === 'emitida') {
                $n = Nota::buscar($id, Helper::perfil(), Helper::usuarioId());
                if ($n && trim((string)($n['gta_numero'] ?? '')) === '') {
                    Helper::redirecionar('nota-ver', [
                        'id'   => $id,
                        'erro' => 'Informe o número da GTA antes de emitir a nota.',
                    ]);
                }
            }

            Nota::atualizarStatus($id, $novoStatus);
            Helper::redirecionar('nota-ver', ['id' => $id, 'msg' => 'Status atualizado.']);
        }

        Helper::redirecionar('notas');
    }

    // ---------------- Baixa: o recebedor confirma o recebimento ----------------
    public function baixa(): void
    {
        $id = (int)($_POST['id'] ?? 0);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Helper::redirecionar('notas');
        }

        Helper::csrfCheck();

        $n = Nota::buscar($id, Helper::perfil(), Helper::usuarioId());
        if (!$n) {
            http_response_code(404);
            exit('Nota não encontrada.');
        }

        if (!Nota::podeDarBaixa($n, Helper::perfil())) {
            Helper::redirecionar('nota-ver', ['id' => $id, 'erro' => 'Não é possível dar baixa nesta nota (status atual: ' . (Nota::STATUS[$n['status']] ?? $n['status']) . ').']);
        }

        $qtd = trim($_POST['quantidade'] ?? '');
        $quantidade = null;
        if ($qtd !== '') {
            if (!ctype_digit($qtd) || (int)$qtd < 0) {
                Helper::redirecionar('nota-ver', ['id' => $id, 'erro' => 'A quantidade recebida deve ser um número inteiro.']);
            }
            $quantidade = (int)$qtd;
        }

        $obs = mb_substr(trim($_POST['obs'] ?? ''), 0, 255);

        Nota::darBaixa($id, Helper::usuarioId(), $quantidade, $obs);
        Helper::redirecionar('nota-ver', ['id' => $id, 'msg' => 'Baixa registrada: nota marcada como entregue.']);
    }

    // ---------------- PDF ----------------
    public function pdf(): void
    {
        $lista = Nota::buscarParaPdf([(int)($_GET['id'] ?? 0)], Helper::perfil(), Helper::usuarioId());
        if (!$lista) {
            http_response_code(404);
            exit('Nota não encontrada.');
        }
        $this->gerarPdf($lista, 'nota-' . Helper::numeroArquivo($lista[0]) . '.pdf');
    }

    // PDF de várias notas (marcadas na lista), uma por página
    public function pdfLote(): void
    {
        $ids = $_GET['ids'] ?? [];
        if (!is_array($ids) || !$ids) {
            Helper::redirecionar('notas', ['msg' => 'Marque pelo menos uma nota para gerar o PDF.']);
        }

        $lista = Nota::buscarParaPdf(array_slice($ids, 0, 100), Helper::perfil(), Helper::usuarioId());
        if (!$lista) {
            http_response_code(404);
            exit('Nenhuma nota encontrada.');
        }

        $this->gerarPdf($lista, 'notas-' . date('Ymd-His') . '.pdf');
    }

    private function gerarPdf(array $lista, string $nomeArquivo): void
    {
        $autoload = BASE_PATH . '/vendor/autoload.php';
        if (!file_exists($autoload)) {
            exit('Dompdf não instalado. Abra o terminal na pasta do projeto e rode: composer install');
        }
        require_once $autoload;

        $html = Helper::capturar('notas/pdf', [
            'lista'         => $lista,
            'camposDestino' => Nota::CAMPOS_DESTINO,
            'camposOrigem'  => Nota::CAMPOS_ORIGEM,
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');

        $pdf = new Dompdf($options);
        $pdf->loadHtml($html, 'UTF-8');
        $pdf->setPaper('A4', 'portrait');
        $pdf->render();
        $pdf->stream($nomeArquivo, ['Attachment' => isset($_GET['baixar'])]);
    }
}