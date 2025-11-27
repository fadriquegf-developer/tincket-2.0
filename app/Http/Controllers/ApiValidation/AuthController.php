<?php

namespace App\Http\Controllers\ApiValidation;

use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;

class AuthController extends Controller
{
    const APP_NAME = 'YWT-Validation';

    public function login(Request $request)
    {
        $validator = \Validator::make($request->all(), [
            'email' => 'required',
            'password' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => true, 'message' => $validator->errors()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $password = request('password');
        if ($password !== null && Auth::attempt(['email' => request('email'), 'password' => $password])) {
            $user = Auth::user();
            return $this->issueToken($user);
        } else {
            return response()->json(['error' => true, 'message' => 'Unauthorised'], Response::HTTP_UNAUTHORIZED);
        }
    }

    private function issueToken(User $user)
    {
        $userToken = $user->token() ?? $user->createToken(self::APP_NAME);
        $brands = $user->brands;

        $response = [
            'error' => false,
            'token_type' => 'Bearer',
            'token' => $userToken->accessToken,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'brand' => [
                'name' => $brands->count() === 1 ? $brands->first()->name : null,
                'logo' => $brands->count() === 1 ? $brands->first()->logo_url : null,
            ],
        ];

        return response()->json($response, Response::HTTP_OK);
    }

    public function logout()
    {
        Auth::user()->token()->revoke();
        return response()->json(null, Response::HTTP_NO_CONTENT);
    }
}
