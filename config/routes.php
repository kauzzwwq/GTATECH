<?php

use App\Controllers\CadastroController;
use App\Controllers\RfidController;
use App\Controllers\UsuarioController;

return [
    // === RFID — dispositivo ESP32 (sem sessão) ===
    'api-rfid'        => [RfidController::class, 'api', 'publica'],

    // === RFID — painel web (requer admin) ===
    'rfid'            => [RfidController::class, 'painel', 'admin'],
    'rfid-tags'       => [RfidController::class, 'tags', 'admin'],
    'rfid-ajax'       => [RfidController::class, 'ajax', 'admin'],
    'rfid-tag-excluir' => [RfidController::class, 'excluirTag', 'admin'],

    // === Usuários ===
    'usuarios'        => [UsuarioController::class, 'index', 'admin'],
    'usuario-form'    => [UsuarioController::class, 'form', 'admin'],
    'usuario-excluir' => [UsuarioController::class, 'excluir', 'admin'],
    'usuario-senha'   => [UsuarioController::class, 'senha', 'admin'],

    // === Cadastros ===
    'cadastros'        => [CadastroController::class, 'index', 'admin'],
    'cadastro-form'    => [CadastroController::class, 'form', 'admin'],
    'cadastro-excluir' => [CadastroController::class, 'excluir', 'admin'],
];