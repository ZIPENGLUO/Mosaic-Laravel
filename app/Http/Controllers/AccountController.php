<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    /**
     * 我的账户列表
     * GET /api/accounts
     */
    public function index()
    {
        $accounts = auth()->user()->accounts()->orderBy('id')->get();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $accounts,
        ]);
    }

    /**
     * 新增账户
     * POST /api/accounts
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                // 同一用户下账户名不能重复（不同用户互不影响）
                Rule::unique('accounts', 'name')->where('user_id', $user->id),
            ],
            'type' => 'nullable|string|max:32',
            // opening_balance 故意不校验、不接受：本期不做余额功能
        ]);

        $account = Account::create([
            'user_id' => $user->id,          // 归属由后端决定，不信客户端
            'name' => $data['name'],
            'type' => $data['type'] ?? 'cash',
        ]);

        return response()->json([
            'code' => 200, 'message' => '账户创建成功', 'data' => $account,
        ]);
    }

    /**
     * 修改账户（改名/改类型）
     * PUT /api/accounts/{id}
     */
    public function update(Request $request, $id)
    {
        // 用关联查找：只能找到自己的账户，别人的 id 天然 404
        $account = auth()->user()->accounts()->find($id);
        if (! $account) {
            return response()->json([
                'code' => 404, 'message' => '账户不存在',
            ], 404);
        }

        $data = $request->validate([
            'name' => [
                'sometimes', 'string', 'max:100',
                Rule::unique('accounts', 'name')
                    ->where('user_id', auth()->id())
                    ->ignore($account->id),          // 排除自己，否则"只改类型"会被自己拦住
            ],
            'type' => 'sometimes|string|max:32',
        ]);

        $account->update($data);

        return response()->json([
            'code' => 200, 'message' => '账户修改成功', 'data' => $account,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $account = auth()->user()->accounts()->find($id);
        if (! $account) {
            return response()->json([
                'code' => 404, 'message' => '账户不存在',
            ], 404);
        }

        $usedCount = $account->transactions()->count();
        if ($usedCount > 0) {
            $data = $request->validate([
                'confirm' => 'nullable|boolean',
            ]);
            if (empty($data['confirm'])) {
                return response()->json([
                    'code' => 422,
                    'message' => "该账户已被 {$usedCount} 条流水使用，删除后这些流水的付款方式将显示为已删除，请确认后重试",
                    'data' => [
                        'transactions' => $usedCount,
                    ],
                ], 422);
            }
        }

        $account->delete();

        return response()->json([
            'code' => 200, 'message' => '账户已删除', 'data' => null,
        ]);
    }
}
