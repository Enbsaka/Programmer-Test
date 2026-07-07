<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'document' => ['nullable', 'string', 'max:32', 'unique:customers,document'],
        ]);

        $user = DB::transaction(function () use ($validated) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
            ]);

            $user->customer()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'document' => $validated['document'] ?? null,
            ]);

            return $user->load('customer');
        });

        $token = $user->createToken('auth_token')->plainTextToken;
        Log::info('Novo usuario registrado.', ['user_id' => $user->id]);

        return response()->json([
            'message' => 'Conta criada com sucesso!',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ], 201);
    }

    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $user = User::with('customer')->where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['As credenciais fornecidas estão incorretas.'],
            ]);
        }

        $token = $user->createToken('auth_token')->plainTextToken;
        Log::info('Login realizado com sucesso.', ['user_id' => $user->id]);

        return response()->json([
            'message' => 'Login realizado com sucesso!',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'user' => $user,
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();
        $user?->currentAccessToken()?->delete();

        if ($user) {
            Log::info('Logout realizado com sucesso.', ['user_id' => $user->id]);
        }

        return response()->json([
            'message' => 'Sessao encerrada com sucesso.',
        ]);
    }
}
