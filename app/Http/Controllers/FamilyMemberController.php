<?php

namespace App\Http\Controllers;

use App\Models\Family;

class FamilyMemberController extends Controller
{
    /**
     * 返回家庭成员列表。
     * GET /api/families/{familyId}/members
     */
    public function index($familyId)
    {
        $user = auth()->user();
        $family = Family::find($familyId);

        if (! $family) {
            return response()->json([
                'code' => 404,
                'message' => '家庭不存在',
                'data' => null,
            ], 404);
        }

        $isMember = $user->familyMemberships()
            ->where('family_id', $family->id)
            ->exists();

        if (! $isMember) {
            return response()->json([
                'code' => 403,
                'message' => '无权查看该家庭成员',
                'data' => null,
            ], 403);
        }

        $members = $family->members()
            ->with('user:id,name')
            ->orderBy('id')
            ->get();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $members,
        ]);
    }
}
