<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class AuthController extends Controller
{
    /**
     * 登录
     * POST /api/auth/login
     */
    public function login(Request $request)
    {
        // ① 验证输入
        $credentials = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        // ② 尝试登录：验证邮箱+密码，成功返回 token，失败返回 false
        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json([
                'code'    => 401,
                'message' => '邮箱或密码错误',
                'data'    => null,
            ], 401);
        }

        // ③ 成功：发 token + 用户信息
        return response()->json([
            'code'    => 200,
            'message' => '登录成功',
            'data'    => [
                'token' => $token,
                'user'  => auth('api')->user(),
            ],
        ]);
    }

    public function register(Request $request)
    {
        $credentials = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        $user = User::create([
            'name'     => $credentials['name'],
            'email'    => $credentials['email'],
            'password' => Hash::make($credentials['password']),
        ]);

        $token = auth('api')->login($user);

        return response()->json([
            'code'    => 200,
            'message' => '注册成功',
            'data'    => [
                'token' => $token,
                'user'  => $user,
            ],
        ]);
    }
    /**
     * 当前登录用户
     * GET /api/auth/me
     */
    public function me()
    {
        return response()->json([
            'code'    => 200,
            'message' => 'ok',
            'data'    => auth('api')->user(),
        ]);
    }

    public function logout()
    {
        auth('api')->logout();   // 让当前 token 失效
        return response()->json([
            'code'    => 200,
            'message' => '已退出登录',
            'data'    => null,
    ]);
    }
    
    public function refresh()
{
    return response()->json([
        'code'    => 200,
        'message' => 'token 已刷新',
        'data'    => [
            'token' => auth('api')->refresh(),
        ],
    ]);
    }
}
