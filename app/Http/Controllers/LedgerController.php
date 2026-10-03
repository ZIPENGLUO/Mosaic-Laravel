<?php

namespace App\Http\Controllers;

use App\Models\Ledger;  // ← 引入 Ledger 模型
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LedgerController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // 我加入的家庭
        $familyIds = $user->familyMemberships()->pluck('family_id');

        // 我创建的 或 我的家庭的
        $ledgers = Ledger::where('owner_id', $user->id)
            ->when($familyIds->isNotEmpty(), function ($q) use ($familyIds) {
                $q->orWhereIn('family_id', $familyIds);
            })
            ->get();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $ledgers,
        ]);
    }

    public function show($id)
    {
        $user = auth()->user();

        // ① 先直接找（不限范围）
        $ledger = Ledger::find($id);
        if (! $ledger) {
            return response()->json(['code' => 404, 'message' => '账本不存在', 'data' => null], 404);
        }

        // ② 再判断是不是我的
        $isMine = $ledger->owner_id === $user->id;
        $isFamily = $ledger->family_id
            && $user->familyMemberships()->where('family_id', $ledger->family_id)->exists();

        if (! $isMine && ! $isFamily) {
            return response()->json(['code' => 403, 'message' => '无权访问', 'data' => null], 403);
        }

        return response()->json(['code' => 200, 'message' => 'success', 'data' => $ledger]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'currency' => 'nullable|string|size:3',
            'family_id' => 'nullable|integer|exists:families,id',
        ]);
        $user = auth()->user();

        if (! empty($data['family_id'])) {
            $isMember = $user->familyMemberships()->where('family_id', $data['family_id'])->exists();
            if (! $isMember) {
                return response()->json([
                    'code' => 403, 'message' => '你不是该家庭成员，无权创建账本', 'data' => null,
                ], 403);
            }
        }
        $ledger = Ledger::create([
            'name' => $data['name'],
            'currency' => $data['currency'] ?? 'CNY',
            'family_id' => $data['family_id'] ?? null,
            'owner_id' => $user->id,
        ]);

        // ④ 返回
        return response()->json([
            'code' => 200, 'message' => '账本创建成功', 'data' => $ledger,
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $ledger = Ledger::find($id);
        if (! $ledger) {
            return response()->json([
                'code' => 404, 'message' => '没有对应账本',
            ], 404);
        }
        if ($ledger->owner_id !== $user->id) {
            return response()->json([
                'code' => 403, 'message' => '没有权限',
            ], 403);
        }

        $data = $request->validate([
            'name' => 'sometimes', 'string', 'max:100',
            'currency' => 'sometimes|string|size:3',
            'family_id' => 'sometimes|integer|exists:families,id',
        ]);

        $ledger->update($data);

        return response()->json([
            'code' => 200, 'message' => '账本修改成功', 'data' => $ledger,
        ]);
    }

    public function destroy(Request $request, $id)
    {
        $user = auth()->user();
        $ledger = Ledger::find($id);
        if (! $ledger) {
            return response()->json([
                'code' => 404, 'message' => '没有对应账本',
            ], 404);
        }
        if ($ledger->owner_id !== $user->id) {
            return response()->json([
                'code' => 403, 'message' => '没有权限',
            ], 403);
        }

        // 二次确认：放在授权之后，避免把"账本存在、里面有多少数据"泄漏给非本人
        $data = $request->validate([
            'confirm' => 'nullable|boolean',
        ]);
        if (empty($data['confirm'])) {
            return response()->json([
                'code' => 422,
                'message' => '删除不可恢复，请确认后重试',
                'data' => [
                    'transactions' => $ledger->transactions()->count(),
                    'attachments'  => $ledger->attachments()->count(),
                ],
            ], 422);
        }

        DB::transaction(function () use ($ledger) {
            $ledger->attachments()->delete();          // 真删
            $ledger->transactions()->forceDelete();    //  软删模型必须 forceDelete
            // 账户不删：accounts 属于用户（个人设置），跨账本复用，删账本不该连累它
            $ledger->delete();                         // 最后才删账本自己
        });

        return response()->json([
            'code' => 200, 'message' => '账本已删除',
        ]);
    }
}
