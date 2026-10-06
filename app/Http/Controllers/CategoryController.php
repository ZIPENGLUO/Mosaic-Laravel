<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Ledger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * 收支分类管理。
 *
 * 分类**属于账本**（categories.ledger_id），所以路由嵌套在账本下：
 *   /api/ledgers/{id}/categories
 * 这和"账户属于个人"（/api/accounts）正好相反 —— 分类是记账的聚合维度，
 * 必须和流水同属一个账本。
 *
 * 删除策略（决策 B，与账户一致）：被流水引用时需 confirm:true；
 * 删除后关联流水的 category_id 由数据库置为 NULL（前端显示"未分类"）。
 */
class CategoryController extends Controller
{
    /**
     * 可查看的账本：所有者或家庭成员（只读场景用）
     */
    private function findViewableLedger($id)
    {
        $user = auth()->user();
        $ledger = Ledger::find($id);
        if (! $ledger) {
            return null;
        }

        $isMine = $ledger->owner_id === $user->id;
        $isFamily = $ledger->family_id
            && $user->familyMemberships()->where('family_id', $ledger->family_id)->exists();

        return ($isMine || $isFamily) ? $ledger : null;
    }

    /**
     * 可管理的账本：只有所有者（写操作场景用）
     * 返回 null 表示"不存在或无权"，调用处统一返回 404（不泄漏资源是否存在）
     */
    private function findOwnedLedger($id)
    {
        $ledger = Ledger::find($id);
        if (! $ledger || $ledger->owner_id !== auth()->id()) {
            return null;
        }

        return $ledger;
    }

    /**
     * 账本下的分类列表
     * GET /api/ledgers/{id}/categories?type=expense
     */
    public function index(Request $request, $id)
    {
        $ledger = $this->findViewableLedger($id);
        if (! $ledger) {
            return response()->json([
                'code' => 404, 'message' => '账本不存在或无权访问',
            ], 404);
        }

        // type 可选筛选：记账页要按收入/支出分别拉分类
        $type = $request->query('type');

        $categories = $ledger->categories()
            ->when(in_array($type, ['income', 'expense'], true), function ($q) use ($type) {
                $q->where('type', $type);
            })
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return response()->json([
            'code' => 200, 'message' => 'success', 'data' => $categories,
        ]);
    }

    /**
     * 新增分类
     * POST /api/ledgers/{id}/categories
     */
    public function store(Request $request, $id)
    {
        $ledger = $this->findOwnedLedger($id);
        if (! $ledger) {
            return response()->json([
                'code' => 404, 'message' => '账本不存在或无权访问',
            ], 404);
        }

        $data = $request->validate([
            'name' => [
                'required', 'string', 'max:100',
                // 唯一约束是 (ledger_id, type, name)，所以校验也要三个条件
                Rule::unique('categories', 'name')
                    ->where('ledger_id', $ledger->id)
                    ->where('type', $request->input('type')),
            ],
            // 对应数据库 enum('income','expense')：不校验就会撞约束报 500
            'type' => 'required|in:income,expense',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $category = Category::create([
            'ledger_id' => $ledger->id,          // 归属由后端决定，不信客户端
            'name' => $data['name'],
            'type' => $data['type'],
            'icon' => $data['icon'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        return response()->json([
            'code' => 200, 'message' => '分类创建成功', 'data' => $category,
        ]);
    }

    /**
     * 修改分类
     * PUT /api/ledgers/{id}/categories/{categoryId}
     */
    public function update(Request $request, $id, $categoryId)
    {
        $ledger = $this->findOwnedLedger($id);
        if (! $ledger) {
            return response()->json([
                'code' => 404, 'message' => '账本不存在或无权访问',
            ], 404);
        }

        // 用关联查找：只能找到本账本下的分类，跨账本的 categoryId 天然找不到
        $category = $ledger->categories()->find($categoryId);
        if (! $category) {
            return response()->json([
                'code' => 404, 'message' => '分类不存在',
            ], 404);
        }

        $data = $request->validate([
            'name' => [
                'sometimes', 'string', 'max:100',
                Rule::unique('categories', 'name')
                    ->where('ledger_id', $ledger->id)
                    ->where('type', $request->input('type', $category->type))  // 没传 type 就用原值
                    ->ignore($category->id),                                    // 排除自己
            ],
            'type' => 'sometimes|in:income,expense',
            'icon' => 'nullable|string|max:100',
            'sort_order' => 'sometimes|integer|min:0',
        ]);

        $category->update($data);

        return response()->json([
            'code' => 200, 'message' => '分类修改成功', 'data' => $category,
        ]);
    }

    /**
     * 删除分类
     * DELETE /api/ledgers/{id}/categories/{categoryId}
     *
     * 决策 B：被流水引用时必须 confirm:true；
     * 删除后关联流水的 category_id 由数据库置为 NULL（前端显示"未分类"）。
     */
    public function destroy(Request $request, $id, $categoryId)
    {
        $ledger = $this->findOwnedLedger($id);
        if (! $ledger) {
            return response()->json([
                'code' => 404, 'message' => '账本不存在或无权访问',
            ], 404);
        }

        $category = $ledger->categories()->find($categoryId);
        if (! $category) {
            return response()->json([
                'code' => 404, 'message' => '分类不存在',
            ], 404);
        }

        $usedCount = $category->transactions()->count();
        if ($usedCount > 0) {
            $data = $request->validate([
                'confirm' => 'nullable|boolean',
            ]);
            if (empty($data['confirm'])) {
                return response()->json([
                    'code' => 422,
                    'message' => "该分类已被 {$usedCount} 条流水使用，删除后这些流水将显示为未分类，请确认后重试",
                    'data' => ['transactions' => $usedCount],
                ], 422);
            }
        }

        $category->delete();

        return response()->json([
            'code' => 200, 'message' => '分类已删除', 'data' => null,
        ]);
    }
}
