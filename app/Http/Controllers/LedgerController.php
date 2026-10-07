<?php

namespace App\Http\Controllers;

use App\Models\Ledger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * 账本管理
 *
 * 成员模型（2026-10 重构后）：**每个账本自带成员**（ledger_members），
 * 不再有"家庭分组"概念。
 *
 * 权限规则：
 *   查看 / 记账        → 账本所有者 + 账本成员
 *   改 / 删账本本身     → 只有账本所有者（ledgers.owner_id）
 *   改 / 删别人的流水   → 账本所有者 + 角色为 owner/admin 的成员（见 TransactionController）
 */
class LedgerController extends Controller
{
    /**
     * 我参与的全部账本：我自己拥有的 + 我被加入为成员的
     * GET /api/ledgers
     */
    public function index()
    {
        $user = auth()->user();

        $memberLedgerIds = $user->ledgerMemberships()->pluck('ledger_id');

        $ledgers = Ledger::where('owner_id', $user->id)
            ->when($memberLedgerIds->isNotEmpty(), function ($q) use ($memberLedgerIds) {
                $q->orWhereIn('id', $memberLedgerIds);
            })
            ->withCount('members')
            ->orderBy('id')
            ->get();

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $ledgers,
        ]);
    }

    /**
     * 账本详情（所有者或成员可看）
     * GET /api/ledgers/{id}
     */
    public function show($id)
    {
        $ledger = $this->findViewableLedger($id);
        if (! $ledger) {
            return $this->ledgerNotFound();
        }

        return response()->json([
            'code' => 200, 'message' => 'success', 'data' => $ledger->loadCount('members'),
        ]);
    }

    /**
     * 新建账本
     * POST /api/ledgers
     *
     * 新账本默认只有自己一个成员（role = owner）；
     * 邀请他人加入属于后续"账本协同"功能。
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => 'required|string|max:100',
            'currency' => 'nullable|string|size:3',
        ]);

        $ledger = DB::transaction(function () use ($data, $user) {
            $ledger = Ledger::create([
                'name' => $data['name'],
                'currency' => $data['currency'] ?? 'CNY',
                'owner_id' => $user->id,          // 归属由后端决定，不信客户端
            ]);

            // 所有者同时写入成员表：这样"查账本成员"一条 SQL 就能拿到全部人
            $ledger->members()->create([
                'user_id' => $user->id,
                'role' => 'owner',
            ]);

            return $ledger;
        });

        return response()->json([
            'code' => 200, 'message' => '账本创建成功', 'data' => $ledger,
        ]);
    }

    /**
     * 修改账本（只有所有者）
     * PUT /api/ledgers/{id}
     */
    public function update(Request $request, $id)
    {
        $ledger = Ledger::find($id);
        if (! $ledger) {
            return $this->ledgerNotFound();
        }
        if ($ledger->owner_id !== auth()->id()) {
            return response()->json([
                'code' => 403, 'message' => '只有账本所有者可以修改账本',
            ], 403);
        }

        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
            'currency' => 'sometimes|string|size:3',
        ]);

        $ledger->update($data);

        return response()->json([
            'code' => 200, 'message' => '账本修改成功', 'data' => $ledger,
        ]);
    }

    /**
     * 删除账本（只有所有者）
     * DELETE /api/ledgers/{id}
     *
     * 事务级联删除：流水（含软删的 forceDelete）、附件、成员关系、账本本身。
     * 分类是全局公共目录 → 不动；账户属于个人 → 不动。
     */
    public function destroy(Request $request, $id)
    {
        $ledger = Ledger::find($id);
        if (! $ledger) {
            return $this->ledgerNotFound();
        }
        if ($ledger->owner_id !== auth()->id()) {
            return response()->json([
                'code' => 403, 'message' => '只有账本所有者可以删除账本',
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
                    'members'      => $ledger->members()->count(),
                ],
            ], 422);
        }

        DB::transaction(function () use ($ledger) {
            $ledger->attachments()->delete();          // 真删
            $ledger->transactions()->forceDelete();    // 软删模型必须 forceDelete
            $ledger->members()->delete();              // 成员关系一并清理
            $ledger->delete();                         // 最后才删账本自己
        });

        return response()->json([
            'code' => 200, 'message' => '账本已删除',
        ]);
    }

    /**
     * 可查看的账本：所有者 或 账本成员。找不到/无权 → null
     * （调用处统一返回 404，不泄漏资源是否存在）
     */
    private function findViewableLedger($id): ?Ledger
    {
        $ledger = Ledger::find($id);
        if (! $ledger) {
            return null;
        }

        $userId = auth()->id();
        if ($ledger->owner_id === $userId || $ledger->hasMember($userId)) {
            return $ledger;
        }

        return null;
    }

    private function ledgerNotFound()
    {
        return response()->json([
            'code' => 404, 'message' => '账本不存在或无权访问', 'data' => null,
        ], 404);
    }
}
