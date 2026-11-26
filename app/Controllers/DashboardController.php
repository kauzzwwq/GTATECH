<?php
namespace App\Controllers;

use App\Helpers\Helper;
use App\Models\Nota;

class DashboardController
{
    public function index(): void
    {
        $perfil = Helper::perfil();
        $uid    = Helper::usuarioId();

        Helper::render('dashboard/painel', [
            'total'      => Nota::total($perfil, $uid),
            'andamento'  => Nota::totalPorStatus($perfil, $uid, ['emitida', 'em_transito']),
            'entregues'  => Nota::totalPorStatus($perfil, $uid, ['entregue', 'concluida']),
            'vencidas'   => Nota::totalVencidas($perfil, $uid),
            'recentes'   => Nota::recentes($perfil, $uid, 5),
        ]);
    }
}
