<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Models\User;
use Carbon\Carbon;
use DateTime;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $user = User::where('name', $request->user)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response([
                'message' => ['Estas credenciales no coinciden con nuestros registros.']
            ], 404);
        }

        $token = $user->createToken('token');

        $created_at = new DateTime($token->accessToken->created_at);
        $minute = env('EXPIRATION_TOKEN');
        $expires_at = $created_at->modify("+".$minute." minute")->format("Y-m-d H:i:s");

        $response = [
            'status' => 201,
            'access_token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'expires_at' => $expires_at,
            'timezone' => 'America/Mexico_City'
        ];

        return response()->json($response, 201);
    }

    /**
     * Cierre de sesión (anular el token)
     */
    public function logout(Request $request)
    {
        $request->user()->token()->revoke();

        return response()->json([
            'message' => 'Successfully logged out'
        ]);
    }

}
