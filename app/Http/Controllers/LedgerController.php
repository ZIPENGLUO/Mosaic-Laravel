<?php

namespace App\Http\Controllers;

use App\Models\Ledger;  // ← 引入 Ledger 模型

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
}
