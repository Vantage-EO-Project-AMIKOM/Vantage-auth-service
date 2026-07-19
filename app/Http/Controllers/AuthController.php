<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = User::create([
            ...$data,
            'email' => strtolower($data['email']),
            'role' => 'user',
        ]);

        $token = $this->issueToken($user);

        return response()->json([
            'message' => 'Account created successfully',
            ...$token,
            'user' => $this->userData($user),
        ], 201);
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', strtolower($request->email))->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // A user may have only one active API session. This immediately
        // invalidates forgotten, copied, or previously issued tokens.
        $user->tokens()->delete();
        $token = $this->issueToken($user);

        return response()->json([
            ...$token,
            'user' => $this->userData($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json($this->userData($request->user()));
    }

    private function userData(User $user): array
    {
        return $user->only(['id', 'name', 'email', 'role']);
    }

    private function issueToken(User $user): array
    {
        $expirationMinutes = (int) config('sanctum.expiration', 120);
        $expiresAt = now()->addMinutes($expirationMinutes);
        $token = $user->createToken('auth-token', ['*'], $expiresAt);

        return [
            'token' => $token->plainTextToken,
            'expires_at' => $expiresAt->toIso8601String(),
        ];
    }
}
