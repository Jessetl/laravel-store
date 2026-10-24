<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests\LoginRequest;

class AuthController extends Controller
{
    /**
     * Handle user login and generate an access token.
     */
    /**
     * Iniciar sesión
     *
     * Devuelve un token Bearer. Limitado a 5 intentos por minuto por email + IP.
     */
    public function login(LoginRequest $request)
    {
        $credentials = $request->validated();

        if (auth()->attempt($credentials)) {
            $user = auth()->user();
            $user->tokens()->where('name', 'auth_token')->delete();

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'access_token' => $token,
                'token_type' => 'Bearer',
            ]);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    /**
     * Handle user logout and revoke the access token.
     */
    /**
     * Cerrar sesión
     *
     * Revoca el token con el que se hizo la petición.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->noContent();
    }
}
