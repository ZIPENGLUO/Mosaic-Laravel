<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\Family;
use App\Models\FamilyMember;
use App\Models\Ledger;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * 演示数据：把前端（MosaicwithAi）里的设定灌进数据库。
 * 可重复执行：用 firstOrCreate / updateOrCreate，重跑不会产生重复数据。
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ========== 1. 用户（密码统一 password） ==========
        $users = [];
        foreach ([
            'lin'   => ['林知栖', 'lin@mosaic.me'],
            'chen'  => ['陈先生', 'chen@mosaic.me'],
            'child' => ['林小满', 'child@mosaic.me'],
            'elder' => ['苏外婆', 'elder@mosaic.me'],
        ] as $key => [$name, $email]) {
            $users[$key] = User::updateOrCreate(
                ['email' => $email],
                ['name' => $name, 'password' => Hash::make('password')]
            );
        }

        // ========== 2. 家庭 + 成员 ==========
        $family = Family::firstOrCreate(
            ['name' => '林氏一家'],
            ['owner_id' => $users['lin']->id]
        );

        foreach (['lin' => 'owner', 'chen' => 'admin', 'child' => 'member', 'elder' => 'member'] as $key => $role) {
            FamilyMember::updateOrCreate(
                ['family_id' => $family->id, 'user_id' => $users[$key]->id],
                ['role' => $role]
            );
        }

        // ========== 3. 三个账本 ==========
        $familyLedger = Ledger::firstOrCreate(
            ['name' => '林氏一家 账本'],
            ['owner_id' => $users['lin']->id, 'family_id' => $family->id, 'currency' => 'CNY']
        );
        $personalLedger = Ledger::firstOrCreate(
            ['name' => '个人私密账本'],
            ['owner_id' => $users['lin']->id, 'family_id' => null, 'currency' => 'CNY']
        );
        $fundLedger = Ledger::firstOrCreate(
            ['name' => '海岛游专项基金'],
            ['owner_id' => $users['lin']->id, 'family_id' => $family->id, 'currency' => 'CNY']
        );

        // ========== 4. 账户（家庭账本） ==========
        $accounts = [];
        foreach ([
            '现金'           => 'cash',
            '微信支付'       => 'wechat',
            '支付宝'         => 'alipay',
            '招商银行储蓄卡' => 'debit_card',
            '招商银行信用卡' => 'credit_card',
            '建设银行信用卡' => 'credit_card',
            '医保账户'       => 'medical',
        ] as $name => $type) {
            $accounts[$name] = Account::firstOrCreate(
                ['ledger_id' => $familyLedger->id, 'name' => $name],
                ['type' => $type, 'opening_balance' => 0]
            );
        }

        // ========== 5. 分类（家庭账本） ==========
        $categories = [];
        foreach ([
            'expense' => [
                '生鲜食品'   => 'local_grocery_store',
                '餐饮美食'   => 'restaurant',
                '日用百货'   => 'shopping_bag',
                '交通出行'   => 'directions_car',
                '医疗健康'   => 'medical_services',
                '水电物业'   => 'bolt',
                '育儿教育'   => 'school',
                '休闲娱乐'   => 'attractions',
                '车辆养护'   => 'local_gas_station',
                '商务社交'   => 'business_center',
            ],
            'income' => [
                '工资薪酬'   => 'payments',
                '理财收益'   => 'account_balance',
                '其他收入'   => 'savings',
            ],
        ] as $type => $list) {
            $i = 0;
            foreach ($list as $name => $icon) {
                $categories[$name] = Category::firstOrCreate(
                    ['ledger_id' => $familyLedger->id, 'type' => $type, 'name' => $name],
                    ['icon' => $icon, 'sort_order' => $i++]
                );
            }
        }

        // ========== 6. 流水（家庭账本） ==========
        // [发生时间, 商户, 分类, 账户, 类型, 金额, 记账人, 来源, 备注]
        $rows = [
            ['2024-10-28 18:42', '盒马鲜生 (万象天地店)',        '生鲜食品', '微信支付',       'expense', 328.60,   'lin',  'manual', '进口大西洋鲑鱼排、日日鲜生菜、安佳鲜牛奶'],
            ['2024-10-28 12:30', '沃歌斯 Wagas (高新科技园)',     '商务社交', '支付宝',         'expense', 68.00,    'lin',  'manual', '能量色拉配鲜榨果汁 · 团队外出会谈'],
            ['2024-10-27 10:15', '某人工智能科技公司 · 10月薪资', '工资薪酬', '招商银行储蓄卡', 'income',  18500.00, 'chen', 'manual', '基本薪酬+绩效奖金'],
            ['2024-10-27 14:15', '永辉超市 (中环绿地店)',        '生鲜食品', '支付宝',         'expense', 156.40,   'lin',  'ocr',    'AI 票据识别自动入账'],
            ['2024-10-26 19:30', '奈尔宝儿童乐园 (亲子活动票)',  '休闲娱乐', '微信支付',       'expense', 398.00,   'chen', 'manual', '陪伴孩子周末拓展活动'],
            ['2024-10-25 15:20', '中国石化 (深南大道加油站)',    '车辆养护', '建设银行信用卡', 'expense', 420.00,   'chen', 'manual', '95号汽油加满 49.5 升'],
            ['2024-10-24 20:10', '叮当快药 (益生菌与维他命)',    '医疗健康', '医保账户',       'expense', 156.40,   'lin',  'manual', '换季居家常备医药箱补充'],
            ['2024-10-22 09:40', '南方电网 · 10月生活用电扣缴',  '水电物业', '招商银行储蓄卡', 'expense', 245.80,   'chen', 'manual', '南山区金地花园自动代扣'],
            ['2024-10-20 16:10', '山姆会员商店 (前海旗舰店)',    '生鲜食品', '招商银行信用卡', 'expense', 896.00,   'lin',  'manual', '半个月生活大包装物资补齐'],
            ['2024-10-15 10:00', '林知栖 10月薪资入账',          '工资薪酬', '招商银行储蓄卡', 'income',  16500.00, 'lin',  'manual', '设计总监月度薪资与季度奖金'],
            ['2024-10-12 19:15', '太二酸菜鱼聚餐',               '餐饮美食', '支付宝',         'expense', 188.00,   'chen', 'manual', '周末亲友聚餐'],
            ['2024-10-11 18:10', '滴滴特惠快车出行接送',         '交通出行', '支付宝',         'expense', 28.60,    'lin',  'manual', '晚高峰暴雨接送回家'],
            ['2024-10-08 08:30', '中国银行货币基金分红入账',     '理财收益', '招商银行储蓄卡', 'income',  320.50,   'chen', 'manual', '稳健型应急储备金投资分红'],
            ['2024-10-03 11:20', '星巴克甄选 (咖啡轻食)',        '餐饮美食', '微信支付',       'expense', 68.00,    'lin',  'manual', ''],
        ];

        foreach ($rows as [$at, $merchant, $cat, $acc, $type, $amount, $userKey, $source, $note]) {
            Transaction::firstOrCreate(
                ['ledger_id' => $familyLedger->id, 'occurred_at' => $at, 'merchant' => $merchant],
                [
                    'account_id'  => $accounts[$acc]->id,
                    'category_id' => $categories[$cat]->id,
                    'created_by'  => $users[$userKey]->id,
                    'type'        => $type,
                    'amount'      => $amount,
                    'note'        => $note ?: null,
                    'source'      => $source,
                ]
            );
        }

        // ========== 7. 附件（给那笔 OCR 流水挂一张票据） ==========
        $ocrTx = Transaction::where('ledger_id', $familyLedger->id)->where('source', 'ocr')->first();
        if ($ocrTx) {
            Attachment::firstOrCreate(
                ['ledger_id' => $familyLedger->id, 'transaction_id' => $ocrTx->id],
                [
                    'uploaded_by'   => $users['lin']->id,
                    'disk'          => 'local',
                    'path'          => 'attachments/demo-receipt.jpg',
                    'original_name' => 'IMG_20241027_141600.JPG',
                    'mime_type'     => 'image/jpeg',
                    'size'          => 2936012,
                    'ocr_status'    => 'completed',
                    'ocr_result'    => ['merchant' => '永辉超市 (中环绿地店)', 'total' => 156.40, 'confidence' => 0.995],
                ]
            );
        }

        // ========== 完成提示 ==========
        $this->command->info('演示数据已写入：');
        $this->command->info("  用户      : " . User::count());
        $this->command->info("  家庭      : " . Family::count());
        $this->command->info("  家庭账本  : {$familyLedger->name}");
        $this->command->info("  账户      : " . Account::where('ledger_id', $familyLedger->id)->count());
        $this->command->info("  分类      : " . Category::where('ledger_id', $familyLedger->id)->count());
        $this->command->info("  流水      : " . Transaction::where('ledger_id', $familyLedger->id)->count());
        $this->command->info("  附件      : " . Attachment::count());
    }
}
