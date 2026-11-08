<?php

namespace App\Http\Controllers;

use App\Models\AuthToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $v = Validator::make($request->all(), [
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'max:128'],
        ]);

        if ($v->fails()) {
            return response()->json(['error' => $this->firstError($v)], 400);
        }

        $name = trim($request->input('name'));
        $email = strtolower(trim($request->input('email')));

        if (User::where('email', $email)->exists()) {
            return response()->json(['error' => 'An account with this email already exists. Please log in instead.'], 409);
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password_hash' => Hash::make($request->input('password')),
            'onboarding_completed' => 0,
        ]);

        $session = AuthToken::issue($user, (int) env('SESSION_TTL_HOURS', 168));

        return response()->json([
            'user' => $this->userPayload($user),
            'token' => $session['token'],
            'expiresAt' => $session['expiresAt'],
        ], 201);
    }

    public function login(Request $request)
    {
        $v = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if ($v->fails()) {
            return response()->json(['error' => 'Email and password are required.'], 400);
        }

        $user = User::where('email', strtolower(trim($request->input('email'))))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password_hash)) {
            return response()->json(['error' => 'Invalid email or password.'], 401);
        }

        $session = AuthToken::issue($user, (int) env('SESSION_TTL_HOURS', 168));

        return response()->json([
            'user' => $this->userPayload($user),
            'token' => $session['token'],
            'expiresAt' => $session['expiresAt'],
        ]);
    }

    public function logout(Request $request)
    {
        $header = $request->header('Authorization', '');
        if (str_starts_with($header, 'Bearer ')) {
            AuthToken::revoke(trim(substr($header, 7)));
        }

        return response()->json(['ok' => true]);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    public function onboarding(Request $request)
    {
        $request->user()->update(['onboarding_completed' => 1]);

        return response()->json(['ok' => true, 'onboarding_completed' => 1]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'onboarding_completed' => $user->onboarding_completed,
        ];
    }

    private function firstError(\Illuminate\Contracts\Validation\Validator $v): string
    {
        return $v->errors()->first();
    }
}
