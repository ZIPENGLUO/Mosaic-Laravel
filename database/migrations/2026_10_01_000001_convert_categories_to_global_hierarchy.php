<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('parent_id')->nullable()->constrained('categories')->restrictOnDelete();
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['ledger_id']);
            $table->dropUnique(['ledger_id', 'type', 'name']);
            $table->dropColumn('ledger_id');
        });

        // Snapshot of the fixed catalog; future catalog changes belong in new migrations.
        $catalog = [
            'expense' => [
                ['餐饮食品', 'food', ['生鲜食品', '餐饮美食', '外卖', '早餐', '零食饮料', '粮油调味']],
                ['交通出行', 'transport', ['公交地铁', '打车', '燃油', '充电', '停车过路', '车辆养护', '交通出行']],
                ['日常购物', 'shopping', ['服饰鞋包', '日用百货', '数码电器', '家具家居', '美妆护理']],
                ['住房缴费', 'housing', ['房租', '水费', '电费', '燃气费', '物业费', '网络通信', '水电物业']],
                ['医疗健康', 'health', ['门诊治疗', '药品', '体检', '医疗器械', '医疗健康']],
                ['教育学习', 'education', ['学费', '培训课程', '书籍文具', '考试报名', '育儿教育']],
                ['娱乐休闲', 'entertainment', ['电影演出', '游戏', '运动健身', '会员订阅', '休闲娱乐']],
                ['旅行度假', 'travel', ['机票车票', '住宿', '景点门票', '旅行团']],
                ['家庭关爱', 'family', ['育儿用品', '长辈赡养', '宠物用品', '宠物医疗']],
                ['人情往来', 'gifts', ['红包支出', '礼物', '请客聚会', '公益捐赠', '商务社交']],
                ['其他支出', 'other_expense', ['其他']],
            ],
            'income' => [
                ['工作收入', 'work_income', ['工资薪酬', '奖金', '兼职', '劳务收入']],
                ['投资收入', 'investment_income', ['利息', '股息分红', '理财收益']],
                ['其他收入', 'other_income', ['红包礼金', '报销', '闲置出售', '其他', '其他收入']],
            ],
        ];

        DB::transaction(function () use ($catalog) {
            $oldCategories = DB::table('categories')->get();
            $leafIds = [];
            $otherParents = [];
            $groupIds = [];
            $now = now();

            foreach ($catalog as $type => $groups) {
                foreach ($groups as $sort => [$name, $icon, $children]) {
                    $parentId = DB::table('categories')->insertGetId([
                        'name' => $name, 'type' => $type, 'icon' => $icon,
                        'parent_id' => null, 'sort_order' => $sort,
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                    $groupIds[$type][$icon] = $parentId;
                    if ($icon === 'other_expense' || $icon === 'other_income') {
                        $otherParents[$type] = $parentId;
                    }
                    foreach ($children as $childSort => $childName) {
                        $leafIds[$type][$childName] = DB::table('categories')->insertGetId([
                            'name' => $childName, 'type' => $type, 'icon' => null,
                            'parent_id' => $parentId, 'sort_order' => $childSort,
                            'created_at' => $now, 'updated_at' => $now,
                        ]);
                    }
                }
            }

            $legacyGroups = [
                '交通出行' => 'transport', '医疗健康' => 'health',
                '水电物业' => 'housing', '育儿教育' => 'education',
                '休闲娱乐' => 'entertainment', '商务社交' => 'gifts',
            ];

            foreach ($oldCategories as $old) {
                // Keep broad legacy labels instead of guessing a more specific meaning.
                if (! isset($leafIds[$old->type][$old->name])) {
                    $parentId = $groupIds[$old->type][$legacyGroups[$old->name] ?? '']
                        ?? $otherParents[$old->type];
                    $leafIds[$old->type][$old->name] = DB::table('categories')->insertGetId([
                        'name' => $old->name, 'type' => $old->type, 'icon' => null,
                        'parent_id' => $parentId, 'sort_order' => 100 + $old->id,
                        'created_at' => $now, 'updated_at' => $now,
                    ]);
                }
                $targetId = $leafIds[$old->type][$old->name];
                // Query builder includes soft-deleted transactions as well.
                DB::table('transactions')->where('category_id', $old->id)->update(['category_id' => $targetId]);
                DB::table('categories')->where('id', $old->id)->delete();
            }
        });
    }

    public function down()
    {
        throw new RuntimeException('Global categories cannot restore original ledger ownership. Restore a database backup to reverse this migration.');
    }
};
