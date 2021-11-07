<?php

namespace Motor\Admin\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Motor\Admin\Models\User;
use Motor\Admin\Traits\ApiResponder;

class AuthController extends Controller
{
    use ApiResponder;

    public function register(Request $request): JsonResponse
    {
        $attr = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name'     => $attr['name'],
            'password' => bcrypt($attr['password']),
            'email'    => $attr['email'],
        ]);

        return $this->success([
            'token' => $user->createToken('API Token')->plainTextToken,
        ]);
    }

    public function login(Request $request): JsonResponse
    {
        $attr = $request->validate([
            'email'    => 'required|string|email|',
            'password' => 'required|string',
        ]);

        if (! Auth::attempt($attr)) {
            return $this->error('Credentials not match', 401);
        }

        return $this->success([
            'token' => auth()
                ->user()
                ->createToken('API Token')->plainTextToken,
        ]);
    }

    public function logout()
    {
        auth()
            ->user()
            ->tokens()
            ->delete();

        return [
            'message' => 'Tokens Revoked',
        ];
    }
}
