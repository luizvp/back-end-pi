<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Include all route files
require __DIR__ . '/api/pacientes.php';
require __DIR__ . '/api/prontuarios.php';
require __DIR__ . '/api/evolucao.php';
require __DIR__ . '/api/agendamentos.php';
require __DIR__ . '/api/pagamentos.php';
require __DIR__ . '/api/usuarios.php';
require __DIR__ . '/api/home.php';
require __DIR__ . '/api/relatorios.php';
require __DIR__ . '/api/dashboard.php';
require __DIR__ . '/api/terminal.php';
