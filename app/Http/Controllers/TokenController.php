<?php

namespace App\Http\Controllers;

use App\Mail\AvisoCambioPw;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;

class TokenController extends Controller
{
    /**
     * Cierra sesión API: revoca el token Bearer actual (Sanctum).
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();
        if ($user === null) {
            return response()->json(['message' => 'No autenticado.'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        $bearer = $request->bearerToken();
        if ($bearer !== null) {
            $accessToken = PersonalAccessToken::findToken($bearer);
            if ($accessToken !== null) {
                $accessToken->delete();
            }
        } else {
            $current = $user->currentAccessToken();
            if ($current instanceof PersonalAccessToken) {
                $current->delete();
            }
        }

        return response()->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function createToken(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|exists:users,email',
            'password' => 'required',
        ]);
        $params= $request->only('email', 'password');
        $params['estatus']='activo';
        if (Auth::once($params)) {
            $token = $request->user()->createToken($request->user()->name);

            return response()->json([
                'data' => $token->plainTextToken,
            ], JsonResponse::HTTP_OK);
        }

        throw ValidationException::withMessages(['email' => [trans('auth.failed')]]);
    }

    public function expire(PersonalAccessToken $token): JsonResponse
    {
        $token->update([
            'expires_at' => now(),
        ]);

        return response()->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    public function createResetPW(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|exists:users,email'
        ]);
        $user = User::where(['email'=>$request->email])->first();
        if ($user) {
            $user->update([
                'uuid' => Str::uuid()->toString(),
            ]);
            Mail::to($user->email)->send(new AvisoCambioPw($user));
            return response()->json([
                'data' => true,
            ], JsonResponse::HTTP_OK);
        }

        throw ValidationException::withMessages(['email' => [trans('auth.failed')]]);
    }

    public function saveResetPW(Request $request): JsonResponse
    {
        $request->validate([
            'uuid' => 'required|exists:users,uuid',
            'password' => [
                'required',
                Rules\Password::min(8)->letters(),
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);
        $user = User::where(['uuid'=>$request->uuid])->first();
        if($user){
            $user->update([
                'uuid' => '',
                'password' => Hash::make($request->password),
            ]);
            return response()->json([
                'data' => true,
            ], JsonResponse::HTTP_OK);
        }

        throw ValidationException::withMessages(['email' => [trans('auth.failed')]]);
    }

    public function getUserUuid(Request $request): JsonResponse
    {
        $request->validate([
            'uuid' => 'required|exists:users,uuid'
        ]);
        $user = User::where(['uuid'=>$request->uuid])->first();
        if($user){
            return response()->json([
                'data' => $user,
            ], JsonResponse::HTTP_OK);
        }

        throw ValidationException::withMessages(['email' => [trans('auth.failed')]]);
    }
}
