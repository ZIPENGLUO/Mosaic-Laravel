<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\Category;
use App\Models\Ledger;
use App\Models\Transaction;
use Illuminate\Http\Request;

/**
 * 收支流水
 *
 * 归属：流水属于**账本**（transactions.ledger_id），所以路由嵌套在账本下：
 *   /api/ledgers/{id}/transactions
 *
 * 权限规则：
 *   查看 / 记账        → 账本所有者 + 账本成员（ledger_members）
 *   改 / 删某条流水     → 自己记的（created_by）/ 账本所有者 / 角色为 owner|admin 的成员
 *   改 / 删账本本身     → 只有账本所有者（在 LedgerController 里）
 *
 * 删除是**软删除**（Transaction 用了 SoftDeletes），数据不真丢。
 */
class TransactionController extends Controller
{
    // ============================================================
    //  列表：多维筛选 + 分页
    //  GET /api/ledgers/{id}/transactions
    // ============================================================
    public function index(Request $request, $id)
    {
        $ledger = $this->findViewableLedger($id);
        if (! $ledger) {
            return $this->ledgerNotFound();
        }

        $query = $ledger->transactions()
            // 预加载关联，避免 N+1
            ->with([
                'account:id,name,type',
                'category:id,name,parent_id,type',
                'creator:id,name',
            ]);

        // ---- 日期区间 ----
        if ($request->filled('from')) {
            $query->where('occurred_at', '>=', $request->date('from')->startOfDay());
        }
        if ($request->filled('to')) {
            // ⚠️ 用 endOfDay：否则选"10月31日"会漏掉当天 00:00 之后的记录
            $query->where('occurred_at', '<=', $request->date('to')->endOfDay());
        }

        // ---- 收支类型 ----
        $query->when(
            in_array($request->query('type'), ['income', 'expense'], true),
            fn ($q) => $q->where('type', $request->query('type'))
        );

        // ---- 账户 / 分类 / 记账人 ----
        $query->when($request->filled('account_id'), fn ($q) => $q->where('account_id', $request->integer('account_id')));
        $query->when($request->filled('category_id'), fn ($q) => $q->where('category_id', $request->integer('category_id')));
        $query->when($request->filled('created_by'), fn ($q) => $q->where('created_by', $request->integer('created_by')));

        // ---- 关键词（商户或备注）----
        // ⚠️ orWhere 必须用闭包包起来，否则会突破前面的筛选条件
        $query->when($request->filled('keyword'), function ($q) use ($request) {
            $keyword = $request->query('keyword');
            $q->where(function ($sub) use ($keyword) {
                $sub->where('merchant', 'like', "%{$keyword}%")
                    ->orWhere('note', 'like', "%{$keyword}%");
            });
        });

        $perPage = min(max($request->integer('per_page', 20), 1), 100);   // 限制 1~100，防被刷

        $paginator = $query->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($perPage);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            // data.data = 当页列表；data.total / current_page / last_page 供分页组件使用
            'data' => $paginator->toArray(),
        ]);
    }

    // ============================================================
    //  详情
    //  GET /api/ledgers/{id}/transactions/{txId}
    // ============================================================
    public function show($id, $txId)
    {
        $ledger = $this->findViewableLedger($id);
        if (! $ledger) {
            return $this->ledgerNotFound();
        }

        // 用关联查找：自动限定在本账本内，跨账本的 txId 天然 404
        $tx = $ledger->transactions()
            ->with(['account:id,name,type', 'category:id,name,parent_id,type', 'creator:id,name'])
            ->find($txId);

        if (! $tx) {
            return $this->transactionNotFound();
        }

        return response()->json([
            'code' => 200, 'message' => 'success', 'data' => $tx,
        ]);
    }

    // ============================================================
    //  记账
    //  POST /api/ledgers/{id}/transactions
    // ============================================================
    public function store(Request $request, $id)
    {
        $ledger = $this->findViewableLedger($id);
        if (! $ledger) {
            return $this->ledgerNotFound();
        }

        $data = $request->validate([
            'type'        => 'required|in:income,expense',
            // 迁移里写明"金额 > 0 由应用层校验"，数据库没有约束，只能这里拦
            'amount'      => 'required|numeric|min:0.01',
            'account_id'  => 'required|integer|exists:accounts,id',
            'category_id' => 'required|integer|exists:categories,id',
            'occurred_at' => 'required|date',
            'merchant'    => 'nullable|string|max:255',
            'note'        => 'nullable|string',
            'source'      => 'nullable|in:manual,ocr',
        ]);

        // ① 账户必须是"我的"（账户属于个人，跨账本复用）
        $account = Account::find($data['account_id']);
        if (! $account || $account->user_id !== auth()->id()) {
            return $this->fail(422, '不能使用他人的支付账户');
        }

        // ② 分类必须是子类（叶子），且收支类型要和流水一致
        $category = Category::find($data['category_id']);
        if (! $category || $category->parent_id === null) {
            return $this->fail(422, '请选择具体的子分类（不能选顶层大类）');
        }
        if ($category->type !== $data['type']) {
            return $this->fail(422, '分类的收支类型与记账类型不一致');
        }

        $tx = Transaction::create([
            'ledger_id'   => $ledger->id,      // 来自 URL，不信客户端
            'created_by'  => auth()->id(),     // 记账人 = 当前用户
            'type'        => $data['type'],
            'amount'      => $data['amount'],
            'account_id'  => $data['account_id'],
            'category_id' => $data['category_id'],
            'occurred_at' => $data['occurred_at'],
            'merchant'    => $data['merchant'] ?? null,
            'note'        => $data['note'] ?? null,
            'source'      => $data['source'] ?? 'manual',
        ]);

        return response()->json([
            'code' => 200,
            'message' => '记账成功',
            'data' => $tx->load(['account:id,name,type', 'category:id,name,parent_id,type', 'creator:id,name']),
        ]);
    }

    // ============================================================
    //  修改
    //  PUT /api/ledgers/{id}/transactions/{txId}
    // ============================================================
    public function update(Request $request, $id, $txId)
    {
        $ledger = $this->findViewableLedger($id);
        if (! $ledger) {
            return $this->ledgerNotFound();
        }

        $tx = $ledger->transactions()->find($txId);
        if (! $tx) {
            return $this->transactionNotFound();
        }

        if (! $this->canModifyTransaction($tx, $ledger)) {
            return $this->fail(403, '只能修改或删除自己记录的流水');
        }

        $data = $request->validate([
            'type'        => 'sometimes|in:income,expense',
            'amount'      => 'sometimes|numeric|min:0.01',
            'account_id'  => 'sometimes|integer|exists:accounts,id',
            'category_id' => 'sometimes|integer|exists:categories,id',
            'occurred_at' => 'sometimes|date',
            'merchant'    => 'nullable|string|max:255',
            'note'        => 'nullable|string',
            'source'      => 'sometimes|in:manual,ocr',
        ]);

        $newType = $data['type'] ?? $tx->type;

        // ① 改账户 → 同样必须是自己的
        if (array_key_exists('account_id', $data)) {
            $account = Account::find($data['account_id']);
            if (! $account || $account->user_id !== auth()->id()) {
                return $this->fail(422, '不能使用他人的支付账户');
            }
        }

        // ② 分类校验：传了就用新的，没传就用现有的（但类型必须和最终的 type 一致）
        $categoryId = $data['category_id'] ?? $tx->category_id;
        $category = Category::find($categoryId);
        if (! $category || $category->parent_id === null) {
            return $this->fail(422, '请选择具体的子分类（不能选顶层大类）');
        }
        if ($category->type !== $newType) {
            return $this->fail(422, '分类的收支类型与记账类型不一致');
        }

        $tx->update($data);

        return response()->json([
            'code' => 200,
            'message' => '流水修改成功',
            'data' => $tx->fresh(['account:id,name,type', 'category:id,name,parent_id,type', 'creator:id,name']),
        ]);
    }

    // ============================================================
    //  删除（软删）
    //  DELETE /api/ledgers/{id}/transactions/{txId}
    // ============================================================
    public function destroy($id, $txId)
    {
        $ledger = $this->findViewableLedger($id);
        if (! $ledger) {
            return $this->ledgerNotFound();
        }

        $tx = $ledger->transactions()->find($txId);
        if (! $tx) {
            return $this->transactionNotFound();
        }

        if (! $this->canModifyTransaction($tx, $ledger)) {
            return $this->fail(403, '只能修改或删除自己记录的流水');
        }

        $tx->delete();   // SoftDeletes → 只打 deleted_at 标记

        return response()->json([
            'code' => 200, 'message' => '流水已删除', 'data' => null,
        ]);
    }

    // ============================================================
    //  批量删除（软删）
    //  POST /api/ledgers/{id}/transactions/batch-delete
    // ============================================================
    public function batchDestroy(Request $request, $id)
    {
        $ledger = $this->findViewableLedger($id);
        if (! $ledger) {
            return $this->ledgerNotFound();
        }

        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $txs = $ledger->transactions()->whereIn('id', $data['ids'])->get();

        if ($txs->isEmpty()) {
            return $this->fail(404, '没有找到可删除的流水');
        }

        // 权限：只要有一条不允许删，就**整体拒绝**（财务数据，宁可让用户明确知道自己删了什么）
        $forbidden = $txs->reject(fn ($tx) => $this->canModifyTransaction($tx, $ledger));
        if ($forbidden->isNotEmpty()) {
            return $this->fail(422, "选中的流水中有 {$forbidden->count()} 条你无权删除");
        }

        Transaction::whereIn('id', $txs->pluck('id'))->delete();   // 软删

        return response()->json([
            'code' => 200,
            'message' => "已删除 {$txs->count()} 条流水",
            'data' => ['deleted' => $txs->count()],
        ]);
    }

    // ============================================================
    //  私有辅助
    // ============================================================

    /**
     * 能否修改/删除这条流水
     * 规则：自己记的 / 账本所有者 / 账本里角色为 owner|admin 的成员
     */
    private function canModifyTransaction(Transaction $tx, Ledger $ledger): bool
    {
        $userId = auth()->id();

        if ($tx->created_by === $userId) {
            return true;   // ① 自己记的
        }
        if ($ledger->owner_id === $userId) {
            return true;   // ② 账本所有者
        }

        // ③ 账本成员里 role 为 owner 或 admin（Ledger::roleOf 已在模型里提供）
        return in_array($ledger->roleOf($userId), ['owner', 'admin'], true);
    }

    /** 可查看的账本：所有者 或 账本成员。找不到/无权 → null */
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
        return $this->fail(404, '账本不存在或无权访问');
    }

    private function transactionNotFound()
    {
        return $this->fail(404, '流水不存在');
    }

    /** 统一错误响应 */
    private function fail(int $status, string $message)
    {
        return response()->json([
            'code' => $status, 'message' => $message, 'data' => null,
        ], $status);
    }
}
