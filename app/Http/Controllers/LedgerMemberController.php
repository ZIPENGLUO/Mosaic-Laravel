<?php

namespace App\Http\Controllers;

use App\Models\Ledger;

/**
 * 账本成员列表
 *
 * 取代了旧的 /api/families/{familyId}/members：
 * 成员现在挂在**账本**上（一个账本一套成员），不再有家庭分组。
 */
class LedgerMemberController extends Controller
{
    /**
     * 账本成员
     * GET /api/ledgers/{id}/members
     *
     * 权限：账本所有者或该账本成员可看
     */
    public function index($id)
    {
        $ledger = Ledger::find($id);
        if (! $ledger) {
            return response()->json([
                'code' => 404, 'message' => '账本不存在', 'data' => null,
            ], 404);
        }

        $userId = auth()->id();
        if ($ledger->owner_id !== $userId && ! $ledger->hasMember($userId)) {
            return response()->json([
                'code' => 403, 'message' => '无权查看该账本成员', 'data' => null,
            ], 403);
        }

        $members = $ledger->members()
            ->with('user:id,name')
            ->orderByRaw("FIELD(role, 'owner', 'admin', 'member')")
            ->orderBy('id')
            ->get();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $members,
        ]);
    }
}
