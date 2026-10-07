<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 收支分类（**全局公共目录**，不属于任何账本/用户）
 *
 * 结构是固定两级：
 *   - 顶层（parent_id = null）：如"餐饮食品""交通出行"
 *   - 子类（parent_id = 顶层 id）：如"生鲜食品""打车"
 *
 * 目录**定死**（由迁移写入），不提供增删改接口；流水记账时选到的是**子类**。
 * 因此 transactions.category_id 指向的是子类（叶子节点）。
 */
class Category extends Model
{
    use HasFactory;

    /** 允许批量赋值的字段（实际只由迁移/Seeder 写入） */
    protected $fillable = ['name', 'type', 'icon', 'sort_order', 'parent_id'];

    /** 顶层分类：categories.parent_id → categories.id（NULL 表示自己就是顶层） */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /** 子类：categories.parent_id → categories.id */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id')->orderBy('sort_order')->orderBy('id');
    }

    /** 该分类下的流水：transactions.category_id → categories.id */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /** 只查顶层（用于构建两级分类树） */
    public function scopeTopLevel($query)
    {
        return $query->whereNull('parent_id');
    }

    /** 只查子类（叶子；记账只能选到这一层） */
    public function scopeLeaf($query)
    {
        return $query->whereNotNull('parent_id');
    }
}
