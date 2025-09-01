<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Tymon\JWTAuth\Exceptions\JWTException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users',
            'company_name' => 'nullable|string',
            'role_id' => 'required|exists:roles,id',
            'password' => 'required|confirmed|min:6',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'company_name' => $validated['company_name'],
            'role_id' => $validated['role_id'],
            'password' => Hash::make($validated['password']),
        ]);

        $token = JWTAuth::fromUser($user);
        return response()->json(compact('user', 'token'));
    }


    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        $user = User::where('email', $credentials['email'])->first();
        if (!$user) {
            return response()->json(['error' => 'Email tidak terdaftar'], 404);
        }

        if (!Hash::check($credentials['password'], $user->password)) {
            return response()->json(['error' => 'Password salah'], 401);
        }

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json(['error' => 'Gagal membuat token'], 500);
        }

        return response()->json(compact('token'));
    }


    public function logout(Request $request)
    {
        try {
            JWTAuth::invalidate(JWTAuth::getToken());
            return response()->json(['message' => 'Successfully logged out']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Failed to logout, please try again'], 500);
        }
    }

    public function socialLogin(Request $request, string $provider)
    {
        $request->validate([

            'access_token' => 'nullable|string',
            'id_token'     => 'nullable|string',
            'name'         => 'nullable|string', 
        ]);

        if (!in_array($provider, ['google', 'apple'])) {
            return response()->json(['error' => 'Unsupported provider'], 422);
        }

        try {
            if ($provider === 'google') {
                $socialUser = $this->getGoogleUser($request);
            } else {
                $socialUser = $this->getAppleUser($request);
            }
        } catch (\Throwable $e) {

            return response()->json([
                'errorCode' => 'ERR_SOCIAL_VERIFY',
                'message'   => 'Failed to verify social token: ' . $e->getMessage(),
                'data'      => null,
            ], 401);
        }

        if (empty($socialUser['email'])) {
            return response()->json([
                'errorCode' => 'ERR_EMAIL_REQUIRED',
                'message'   => 'Email is required from provider',
                'data'      => null,
            ], 422);
        }

        $user = User::where('email', $socialUser['email'])->first();

        if (!$user) {
            $user = User::create([
                'name'         => $socialUser['name'] ?? (explode('@', $socialUser['email'])[0]),
                'email'        => $socialUser['email'],
                'company_name' => null,
                'role_id'      => $this->defaultRoleId(), 
                'password'     => Hash::make(Str::random(32)),
                'avatar'       => $socialUser['avatar'] ?? null,
                'provider'     => $provider,
                'provider_id'  => $socialUser['id'] ?? null,
                'email_verified_at' => now(),  
            ]);
        } else {
            $user->forceFill([
                'avatar'      => $user->avatar ?: ($socialUser['avatar'] ?? null),
                'provider'    => $user->provider ?: $provider,
                'provider_id' => $user->provider_id ?: ($socialUser['id'] ?? null),
            ])->save();
        }

        $token = JWTAuth::fromUser($user);

        return response()->json([
            'user'  => $user,
            'token' => $token,
        ]);
    }

    private function defaultRoleId(): int
    {
        return 2;
    }

    private function getGoogleUser(Request $request): array
    {
        if ($idToken = $request->string('id_token')->toString()) {
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($idToken);
        } elseif ($accessToken = $request->string('access_token')->toString()) {
            $googleUser = Socialite::driver('google')->stateless()->userFromToken($accessToken);
        } else {
            abort(422, 'Google token is required');
        }

        return [
            'id'     => $googleUser->getId(),
            'email'  => $googleUser->getEmail(),
            'name'   => $googleUser->getName(),
            'avatar' => $googleUser->getAvatar(),
        ];
    }

    private function getAppleUser(Request $request): array
    {
        // Apple paling aman kirimkan **id_token** dari FE (hasil Sign in with Apple).
        $idToken = $request->string('id_token')->toString();
        if (!$idToken) {
            abort(422, 'Apple id_token is required');
        }

        // Dengan SocialiteProviders/Apple, kita bisa:
        $appleUser = Socialite::driver('apple')->stateless()->userFromToken($idToken);

        // Catatan: Apple kadang tidak selalu mengembalikan nama; ambil dari request kalau ada.
        $nameFromRequest = $request->string('name')->toString();

        return [
            'id'     => $appleUser->getId(),
            'email'  => $appleUser->getEmail(),
            'name'   => $appleUser->getName() ?: $nameFromRequest ?: (explode('@', $appleUser->getEmail())[0] ?? 'Apple User'),
            'avatar' => null, // Apple tidak menyediakan avatar
        ];
    }
}
