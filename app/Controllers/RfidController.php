<?php

namespace App\Controllers;

use App\Helpers\Helper;
use App\Models\Rfid;

class RfidController
{
    private ?Rfid $model = null;

    private function model(): Rfid
    {
        return $this->model ??= new Rfid(db());
    }

    public function painel(): void
    {
        Helper::exigirAdmin();

        $ultima   = $this->model()->ultimaLeitura();
        $leituras = $this->model()->ultimasLeituras(15);
        $status   = $this->model()->statusLeitor();
        $titulo   = 'Controle RFID';

        require __DIR__ . '/../../views/rfid/painel.php';
    }

    public function tags(): void
    {
        Helper::exigirAdmin();

        $mensagem = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            Helper::csrfCheck();
            $acao = $_POST['acao'] ?? '';

            if ($acao === 'criar') {
                $mensagem = $this->model()->criarCartao(
                    (string) ($_POST['uid'] ?? ''),
                    (string) ($_POST['descricao'] ?? '')
                );

                $motoristaId = (int) ($_POST['motorista_id'] ?? 0);
                if ($mensagem['ok'] && $motoristaId > 0) {
                    $uid = strtolower(trim((string) ($_POST['uid'] ?? '')));
                    foreach ($this->model()->listarCartoes() as $c) {
                        if (strtolower($c['uid']) === $uid) {
                            $this->model()->vincularMotorista((int) $c['id'], $motoristaId);
                            $mensagem['msg'] = 'TAG ' . strtoupper($uid) . ' cadastrada e vinculada ao motorista.';
                            break;
                        }
                    }
                }
            } elseif ($acao === 'vincular') {
                $mensagem = $this->model()->vincularMotorista(
                    (int) ($_POST['cartao_id'] ?? 0),
                    (int) ($_POST['motorista_id'] ?? 0)
                );
            } elseif ($acao === 'alternar') {
                $mensagem = $this->model()->alternarCartao((int) ($_POST['cartao_id'] ?? 0));
            } elseif ($acao === 'excluir') {
                $mensagem = $this->model()->excluirCartao((int) ($_POST['cartao_id'] ?? 0));
            } elseif ($acao === 'vincular_ultima') {
                $mensagem = $this->vincularUltima();
            } else {
                $mensagem = ['ok' => false, 'msg' => 'Ação inválida.'];
            }
        }

        $cartoes    = $this->model()->listarCartoes();
        $motoristas = $this->model()->listarMotoristas();
        $ultima     = $this->model()->ultimaLeitura();
        $titulo     = 'TAGs RFID';

        require __DIR__ . '/../../views/rfid/tags.php';
    }

    private function vincularUltima(): array
    {
        $uid         = strtolower(trim((string) ($_POST['uid'] ?? '')));
        $motoristaId = (int) ($_POST['motorista_id'] ?? 0);
        $descricao   = trim((string) ($_POST['descricao'] ?? ''));

        if (!preg_match('/\A[0-9a-f]{4,64}\z/', $uid)) {
            return ['ok' => false, 'msg' => 'UID inválido.'];
        }
        if ($motoristaId <= 0) {
            return ['ok' => false, 'msg' => 'Selecione um motorista.'];
        }

        $cartao = null;
        foreach ($this->model()->listarCartoes() as $c) {
            if (strtolower($c['uid']) === $uid) {
                $cartao = $c;
                break;
            }
        }

        if (!$cartao) {
            $novo = $this->model()->criarCartao(
                $uid,
                $descricao !== '' ? $descricao : 'TAG ' . strtoupper($uid)
            );
            if (!$novo['ok']) {
                return $novo;
            }
            foreach ($this->model()->listarCartoes() as $c) {
                if (strtolower($c['uid']) === $uid) {
                    $cartao = $c;
                    break;
                }
            }
            if (!$cartao) {
                return ['ok' => false, 'msg' => 'Não foi possível localizar a TAG após cadastrar.'];
            }
        }

        $res = $this->model()->vincularMotorista((int) $cartao['id'], $motoristaId);
        if ($res['ok']) {
            $res['msg'] = 'TAG ' . strtoupper($uid) . ' vinculada ao motorista.';
        }
        return $res;
    }

    public function api(): void
    {
        header('Content-Type: text/plain; charset=utf-8');
        header('Cache-Control: no-store');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            header('Allow: POST');
            exit('ERRO|Metodo invalido|Use POST|OFFLINE');
        }

        if (!isset($_POST['tag_uid'])) {
            http_response_code(400);
            exit('ERRO|Requisicao invalida|Use POST tag_uid|OFFLINE');
        }

        $dados = $this->model()->processarLeitura((string) $_POST['tag_uid']);

        if ($dados['linhas'] === null) {
            http_response_code(400);
            exit('ERRO|UID invalido|Verifique a TAG|OFFLINE');
        }

        echo implode('|', $dados['linhas']);
    }

    public function ajax(): void
    {
        Helper::exigirAdmin();
        header('Content-Type: application/json; charset=utf-8');

        echo json_encode([
            'ultima'   => $this->model()->ultimaLeitura(),
            'leituras' => $this->model()->ultimasLeituras(15),
            'status'   => $this->model()->statusLeitor(),
        ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }
}