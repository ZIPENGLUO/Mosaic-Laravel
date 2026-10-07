<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;

/**
 * 收支分类（**全局公共目录，定死，只读**）
 *
 * 与之前的区别：分类不再属于账本，而是全系统共用一套两级目录。
 * 由迁移 2026_10_01_000001_convert_categories_to_global_hierarchy 写入，约 80 条。
 *
 * 只提供查询，没有增删改 —— 目录固定，保证所有人的统计口径一致
 * （也避免用户删掉分类导致历史流水的分类悬空）。
 *
 * 前端用法：
 *   记账页选分类 → GET /api/categories?type=expense → 拿 children 作为可选项
 *   分类图标     → 用顶层分类的 icon（子类的 icon 为空）
 */
class CategoryController extends Controller
{
    /**
     * 分类树
     * GET /api/categories?type=expense|income   （不传 type 则返回收支两类）
     */
    public function index(Request $request)
    {
        $type = $request->query('type');

        $categories = Category::query()
            ->topLevel()
            ->when(in_array($type, ['income', 'expense'], true), function ($q) use ($type) {
                $q->where('type', $type);
            })
            ->with(['children' => function ($q) {
                $q->select('id', 'name', 'type', 'icon', 'sort_order', 'parent_id');
            }])
            // 让"支出"排在"收入"前面（按字母序 income 会在前，记账页支出优先更顺手）
            ->orderByRaw("FIELD(type, 'expense', 'income')")
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'name', 'type', 'icon', 'sort_order', 'parent_id']);

        return response()->json([
            'code' => 200,
            'message' => 'success',
            'data' => $categories,
        ]);
    }
}
