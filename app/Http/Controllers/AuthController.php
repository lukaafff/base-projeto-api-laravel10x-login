<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Http\Requests\AuthRequest;
use Illuminate\Http\Response;

class AuthController extends Controller
{
    public function register(AuthRequest $request)
    {
        try {
            $user = User::create([
                'role' => $request->role,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            return response()->json([
                'message' => 'Usuário criado com sucesso',
                'user' => $user
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Erro ao criar usuário: ' . $e->getMessage());
            return response()->json([
                'message' => 'Erro ao criar usuário',
                'error' => 'Ocorreu um erro inesperado. Tente novamente mais tarde.'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json(['message' => 'Credenciais de login inválidas'], Response::HTTP_UNAUTHORIZED);
        }

        $user = Auth::user();
        $token = $user->createToken('auth_token')->plainTextToken;

        $expirationMinutes = config('sanctum.expiration', 480);
        $expirationDate = now()->addMinutes($expirationMinutes);

        return response()->json([
            'message' => 'Login realizado com sucesso',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user_name' => $user->name,
            'user_role' => $user->role, 
            'expires_at' => $expirationDate->toDateTimeString()
        ]);
    }
}
