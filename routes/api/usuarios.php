<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

Route::prefix('usuarios')->group(function () {
    Route::post('/login', function (Request $request) {
        try {
            $user = DB::table('usuarios')->where('usuario', $request->usuario)->first();

            if ($user && $request->senha === $user->senha) {
                return response()->json(['message' => 'Login successful', 'user' => $user], 200);
            } else {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error during login', 'error' => $e->getMessage()], 500);
        }
    });

    Route::get('/', function (Request $request) {
        return DB::table('usuarios')->get();
    });

    Route::get('/{id}', function ($id) {
        try {
            $usuario = DB::table('usuarios')->where('id', $id)->first();

            if ($usuario) {
                return response()->json(['usuario' => $usuario], 200);
            } else {
                return response()->json(['message' => 'Usuario não encontrado'], 404);
            }
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao buscar usuario', 'error' => $e->getMessage()], 500);
        }
    });

    Route::post('/', function (Request $request) {
        try {
            $request->validate([
                'usuario' => 'required|unique:usuarios,usuario',
                'senha' => 'required|min:6',
                'admin' => 'required|boolean',
            ]);

            DB::table('usuarios')->insert([
                'usuario' => $request->usuario,
                'senha' => $request->senha,
                'admin' => $request->admin,
            ]);

            return response()->json(['message' => 'User created successfully'], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['message' => 'Validation error', 'errors' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Error creating user', 'error' => $e->getMessage()], 500);
        }
    });

    Route::put('/{id}', function (Request $request, $id) {
        try {
            DB::table('usuarios')->where('id', $id)->update($request->FormData);

            return response()->json(['message' => 'Usuario atualizado com sucesso'], 200);
        } catch (ValidationException $e) {
            return response()->json(['message' => 'Erro de validação', 'errors' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Erro ao atualizar usuario', 'error' => $e->getMessage()], 500);
        }
    });
});
