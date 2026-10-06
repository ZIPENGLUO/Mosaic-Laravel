# Mosaic 家庭财务系统 — 项目记忆

> 最后更新：前端开始对接 —— 登录/注册页完成、账本列表接真实接口；后端补多语言基础（zh_CN）
> 用途：记录项目全貌、进度、决策与待办，供后续开发/答辩参考

---

## 维护约定（每次较大改动后必做）

> **约定来源：用户明确要求（2026-09）** —— 每次有较大改动，都要回来更新本文件。

1. **较大改动 = 出现下列任一项**：
   - 新增/修改**数据库迁移**（表结构、外键、约束）
   - 新增**接口**或改变已有接口的行为/返回码
   - 新增**模型/关联**或改变归属关系
   - 工程**决策**变化（如"账户从属于账本改为属于个人"）
2. **改完代码 + 测试通过后，同一轮就更新本文件**，别攒着。要同步的位置：
   - 抬头"最后更新" + 第 0 节"当前进度"
   - 第 3 节 Git 提交历史
   - 第 5 节数据库现状（表/外键/设计要点/演示数据）
   - 第 7 节已完成、第 8 节 API 接口表、第 9 节路线图状态
   - 第 10 节关键决策、第 11 节踩过的坑（踩了新坑一定补）
3. **文档更新单独一个 commit**（`docs: ...`），跟代码分开 —— 保持历史可读、可单独回滚。
4. 记**当前状态**和**为什么**，不记流水账。

---

## 0. 一句话概括

AI 生成的家庭记账前端（Vue3），配一套 Laravel 9 后端，做毕业设计。

**当前进度**
- **后端**：阶段 1（模型+关联+Seeder）✅、阶段 2（JWT 认证）✅、阶段 3 基础资源 CRUD —— 账本 5 接口 ✅、账户 4 接口 ✅（属于个人）、分类 4 接口 ✅（属于账本）；剩**家庭成员管理**。另已补**多语言基础**（zh_CN + `Accept-Language`）。
- **前端**（`MosaicwithAi`）：**已开始对接后端** —— API 客户端（axios + 拦截器）✅、登录态 store ✅、路由守卫 ✅、登录页（按 `stitch_ui` 设计稿）✅、注册页 ✅、侧栏真实用户 + 退出 ✅、账本列表接 `GET /api/ledgers` ✅。其他页面（首页/日历/账目/统计/家庭/设置/OCR）**仍是静态 mock**。

---

## 1. 项目目标

家庭财务管理系统的毕业设计，核心功能：
- 家庭多人协同记账（账本、成员、权限、分摊）
- 收支流水的记录、筛选、统计
- 智能票据 OCR 识别入账
- 财务日历、统计分析、预算管理

**明确不做**（时间不够，只写进论文"未来展望"）：资产概览（股票/基金/债券）。

---

## 2. 技术栈

| 层 | 技术 |
|---|---|
| 前端 | Vue 3 + TypeScript + Vuetify 3 + Pinia + ECharts + Vite |
| 后端 | Laravel 9.52 + **JWT 认证（`php-open-source-saver/jwt-auth ^2.2`）** |
| 数据库 | MySQL |
| 服务器 | 阿里云 ECS + nginx + PHP-FPM + MySQL |
| 版本控制 | Git + GitHub |

> **为什么用 JWT 而不是 Sanctum**：前端（`Mosaic-Frontend`）是按 JWT 写的（`token.split('.')[1]` 解 payload），且期望 `{code, data}` 响应信封。用 JWT 对接改动最小。
> 注：`laravel/sanctum` 包仍在，但已不使用。

### 多语言（i18n）基础 —— 中/英/日

| 层 | 现状 | 位置 |
|---|---|---|
| 语言文件 | ✅ `zh_CN`（简体中文，取自 `laravel-lang/lang`）、`en`（Laravel 自带） | `lang/zh_CN/`、`lang/en/` |
| 默认语言 | `APP_LOCALE=zh_CN`，兜底 `APP_FALLBACK_LOCALE=en` | `.env` |
| 语言白名单 | `'supported_locales' => ['zh_CN','en','ja']` | `config/app.php` |
| 运行时切换 | 中间件读 `Accept-Language` 请求头 → `App::setLocale()` | `app/Http/Middleware/SetLocale.php`（注册在全局 `$middleware`） |
| 前端配合 | axios 请求拦截器自动带 `Accept-Language`（默认 `zh-CN`，localStorage 键 `mosaic_locale`） | `src/api/client.ts` |
| 字段中文名 | `lang/zh_CN/validation.php` 里的 `attributes`，让提示显示"邮箱"而非 `email` | 同左 |

**以后要加日文，只需 3 步（代码不用改）**：
1. 在 `lang/` 下新增 `ja/validation.php`（可从 `laravel-lang/lang` 的 `locales/ja/php.json` 转换；⚠️ 日文覆盖率偏低，缺的规则是英文兜底，需要手写）
2. `config/app.php` 的 `supported_locales` 里已经有 `'ja'`，无需改
3. 前端加语言切换（写入 `mosaic_locale`）→ 请求头自动跟随

> ⚠️ 前端界面文字目前是**硬编码中文**，要做界面级多语言需引入 `vue-i18n` 并把文案抽成 key（工作量大，暂列"未来展望"）。目前的多语言只覆盖**后端返回的消息**（校验提示等）。

---

## 3. 仓库与目录地图（⚠️ 有多个重复目录，务必认准）

### ✅ 真正在用的仓库

| 项目 | 本地路径 | 远端 |
|---|---|---|
| **后端** | `C:\Users\admin\Desktop\Phpstudy\Mosicbackend\Mosaic-Laravel` | `github.com/ZIPENGLUO/Mosaic-Laravel.git` (main) |
| **前端** | `C:\Users\admin\Desktop\Mosaic\MosaicwithAi` | `github.com/ZIPENGLUO/MosaicwithAi.git` (main) |

### ❌ 干扰项（不要用，可清理）

| 路径 | 说明 |
|---|---|
| `C:\Users\admin\Desktop\Mosaic\Mosaic-Laravel` | **无 git 的副本**，价值已被真仓库取代，可删 |
| `C:\Users\admin\Desktop\Phpstudy\Mosaic\Mosaic-Backend` | Node/Koa/TS 学习项目（废弃尝试） |
| `C:\Users\admin\Desktop\Phpstudy\Mosaic\Mosaic-Frontend` | 另一个前端（曾用它查到 401→登录页逻辑，非毕设主线） |
| `C:\Users\admin\Desktop\Phpstudy\front-end` / `Mosaicdemo` / `my-app` | 其它历史目录 |

### Git 提交历史（后端）

```
0df4ef8  new              ← 初始 Laravel 骨架
260dcce  basedata         ← 7 张业务迁移
de9bd09  关联             ← 8 个模型 + 28 个关联
6d392a4  表关联seeder     ← DemoSeeder
d6af93c  jwtauth          ← JWT 认证（阶段 2）
ab37028  docs: 添加项目记忆文档
3c0ec35  ledger接口：index/show + 归属校验
d216536  docs: 更新项目记忆到阶段 3
ec81a15  ledger接口：update/destroy（事务级联删除）
a6d0a5b  ledger删除加二次确认（confirm + 关联数量）
05b5c2b  账户改为属于个人（设置）：/api/accounts + 迁移 + 模型调整
3389674  docs: 记录账户改为属于个人 + 阶段3进度
66bf40d  docs: 增加项目记忆维护约定
a7783fd  style: AccountController 数组对齐整理
c142345  docs: 补齐项目记忆（阶段3已完成项/关键决策/踩坑/编码约定/迁移数）
1ae69b5  账户删除改为二次确认（决策B）：被流水引用时需 confirm:true
7ea0e8c  categories接口：/api/ledgers/{id}/categories + 决策B删除（nullOnDelete）
```
> 约定：**代码一个 commit、文档更新单独一个 `docs:` commit**（见开头「维护约定」）。

---

## 4. 服务器环境（已就绪，但暂不更新）

| 项 | 值 |
|---|---|
| 阿里云 ECS | 公网 IP `<服务器IP>` |
| 访问方式 | **阿里云 Workbench**（网页终端）。本地 SSH 锁死：只认密钥，本地无密钥 |
| 公网带宽 | 已开通（曾踩"带宽为 0"的坑，已解决） |
| PHP | 8.0.30（packages.sury.org 源） |
| MySQL | 已装、初始化，专用账号 `myapp_user`，按 1.6G 小内存调过参数 |
| Composer | 已装 |
| nginx | 已配反向代理，公网 IP 可访问 Laravel 欢迎页 |
| 安全组 | 已放行 80（HTTP） |
| 部署目录 | `/var/www/Mosaic-Laravel` |
| 服务器代码状态 | 已 pull 到 `6d392a4`（含迁移+模型+Seeder），**已跑 seed，有演示数据** |
| 服务器 `.env` | 账号 `myapp_user`（**库名仍未确认**，需跑 `grep -E "^DB_" /var/www/Mosaic-Laravel/.env`） |

> ⚠️ **决定：服务器暂不更新**。等后端功能基本做完再一次性部署。
> 部署 JWT 那批代码时，服务器**额外需要**：`composer install` + `php artisan jwt:secret`（每个环境各自生成，`.env` 不入库）。

---

## 5. 数据库现状

**表结构状态（13 个迁移 / 13 张表）**

| 环境 | 数据库 | 迁移 | 数据 | 状态 |
|---|---|---|---|---|
| 自己电脑 | 本地 MySQL，库 `laravel`，账号 `root` | **13** | ✅ | ✅ 最新（含账户归属 + 分类删除策略重构） |
| 阿里云服务器 | 服务器 MySQL，账号 `myapp_user` | 11 | ✅ | ⏸ 落后两个迁移（按"暂不更新"决策，等统一部署） |
| GitHub 仓库 | — | **13 个文件** | — | ✅ |
| 公司电脑 | 本地 MySQL，账号 `root` | 4 | ⬜ | 需 `git pull` + `migrate` + `db:seed` |

> ⚠️ 两次待补迁移（**任何环境拉取代码后都必须跑 `php artisan migrate`**，否则表结构与代码不匹配）：
> - `2026_09_10_000008_migrate_accounts_to_user_ownership`（账户改为属于个人）
> - `2026_09_10_000009_migrate_transactions_category_id_to_null_on_delete`（删分类时流水置 NULL）

**13 张表：**

```
默认表：users, password_resets, failed_jobs, personal_access_tokens, migrations
业务表：families, family_members, ledgers, accounts, categories, transactions, attachments
```

**外键关系全表：**

| 表 | 外键列 | 指向 |
|---|---|---|
| families | owner_id | users.id |
| family_members | family_id | families.id |
| family_members | user_id | users.id |
| ledgers | owner_id | users.id |
| ledgers | family_id | families.id（NULL = 个人账本） |
| accounts | **user_id** | users.id（⚠️ 2026-09 改为属于个人，原为 ledger_id） |
| categories | ledger_id | ledgers.id |
| transactions | ledger_id | ledgers.id |
| transactions | account_id | accounts.id（可空，删账户时置 NULL） |
| transactions | category_id | categories.id（可空，删分类时置 NULL） |
| transactions | created_by | users.id |
| attachments | ledger_id | ledgers.id |
| attachments | transaction_id | transactions.id（可空） |
| attachments | uploaded_by | users.id |

**业务表设计要点：**
- `transactions` 有 `deleted_at`（软删除）、`source` 枚举 `manual`/`ocr`
- `categories` 有 `type` 枚举 `income`/`expense`，唯一约束 `(ledger_id, type, name)`
- `accounts` 唯一约束 **`(user_id, name)`**（同一用户下账户名不重复，不同用户可同名）
- `accounts` 属于**个人**（"我用什么付款"），跨账本复用；`transactions.account_id` 为 `ON DELETE SET NULL`
- `categories` 属于**账本**（记账的聚合维度），`icon`/`sort_order` 可选；`transactions.category_id` 同样 `ON DELETE SET NULL`（决策 B）
- `accounts.opening_balance` 字段保留但**不做余额功能**（不暴露、不计算）
- `transactions.type` 目前只有 `income`/`expense`，**前端还有"转账"类型，待扩展**
- `transactions` 模型**尚未加 `$fillable`**（阶段 4 写流水接口时必须先加，否则 MassAssignmentException）

**演示数据（DemoSeeder，可重复执行，用 `firstOrCreate`/`updateOrCreate`）：**
```
4 用户（林知栖/陈先生/林小满/苏外婆，密码统一 password）
1 家庭（林氏一家）
3 账本（家庭账本 / 个人私密账本 / 海岛游专项基金）
7 账户（属于林知栖个人）、13 分类、14 流水、1 附件
```

---

## 6. 前端盘点（需求来源）

### 现状
- **纯静态，零 API 调用，尚未接入后端**（JWT 也还没接）
- 全局状态 `src/stores/ledger.ts`：一个用户可有多个账本（家庭/个人/基金），**切换账本是全局的** → 几乎所有接口都要带 `ledger_id`
- 注意：真正带登录逻辑的是另一个前端 `Mosaic-Frontend`（配 Node 后端用的），毕设主线前端是 `MosaicwithAi`

### 页面与数据需求

| 页面 | 路由 | 需要的接口数据 |
|---|---|---|
| Home 首页 | `/home` | 收支走势(30/14/7天)、分类支出环形、预算进度、最近流水 |
| OCR 票据 | `/ocr` | 上传文件→解析结果(商户/日期/金额/明细项)、票据队列、确认入账 |
| Calendar 日历 | `/calendar` | 每月每日收支汇总、按日流水、快速记账 |
| Bills 账目明细 | `/bills` | 列表+多维筛选+分页、批量删除、新增、详情(子项/凭证)、导出 |
| Analytics 统计 | `/analytics` | 维度(月/季/年)×成员、KPI、走势、分类构成、成员分摊 |
| Family 家庭协同 | `/family` | 成员+额度、邀请、改额度、待清算分摊、动态流 |
| Settings 设置 | `/settings` | 个人资料/头像、薪资预算、记账偏好 |
| Login 登录 | 未挂载 | 手机号/邮箱 + 密码 |

### 前端待补的表（数据库还没建）
`ledger_members`（账本成员权限）、`budgets`（预算额度）、`transaction_items`（票据子明细）、`recurring_transactions`（周期性收支）、`user_settings`（薪资/偏好）、`settlements`（分摊清算）、`invitations`（邀请）、`activity_logs`（动态流）

---

## 7. 已完成 ✅

### 阶段 1：数据层
1. **7 张业务迁移**：从"悬空"状态抢救 → 提交 → 推送 → 本地+服务器 migrate 成功
2. **8 个 Eloquent 模型**：`Family` `FamilyMember` `Ledger` `Account` `Category` `Transaction` `Attachment`（+ `User`）
3. **28 个关联**：全部验证通过（每个关联的目标模型/关系类型/外键列都实测正确）
4. **`Transaction` 启用 `SoftDeletes`**
5. **DemoSeeder**：本地和服务器都已灌入演示数据

### 阶段 2：JWT 认证
6. **JWT 包**：`php-open-source-saver/jwt-auth ^2.2`（Composer 在 `C:\Users\admin\composer.phar`，PHP 在 `D:\xampp\php`）
7. **配置**：`config/jwt.php`（发布）、`.env` 的 `JWT_SECRET`、`config/auth.php` 加 `api` 守卫（driver=jwt）
8. **`User` 实现 `JWTSubject`**（`getJWTIdentifier` / `getJWTCustomClaims`）
9. **`AuthController`**：register / login / me / logout / refresh 五个方法
10. **路由模块化**：`routes/api.php`（总入口 require）→ `routes/api/auth.php`
11. **修复认证返回**：`Authenticate::redirectTo()` 和 `Handler::unauthenticated()` 都改成返回 401 JSON
12. **全链路实测通过**：登录→发 token、带 token 访问、无 token 401、密码错误 401、refresh 旧 token 作废、logout 作废
13. **已提交**：commit `d6af93c jwtauth`（JWT 那批代码已入库，不再是工作区未提交状态）

### 阶段 3：基础资源 CRUD（🟡 进行中）
14. **账本接口 5 个**（`LedgerController`）：
    - `index`：返回"我创建的 + 我家庭的"账本
    - `show`：先 `find()` 判 404，再判归属 403（所有者或家庭成员可看）
    - `store`：创建；带 `family_id` 时校验是不是该家庭成员（否则 403）；`owner_id` 由后端填
    - `update`：仅所有者；`sometimes` 规则支持局部更新；`$ledger->update($data)`
    - `destroy`：仅所有者 + **`confirm:true` 二次确认**（未确认返回 422 并附关联数量）；`DB::transaction` 级联删除
15. **账户接口 4 个**（`AccountController`）：`/api/accounts` 不嵌套 —— 账户属于个人（见第 10 节决策）。`destroy` 用**决策 B**：被流水引用时需 `confirm:true`
16. **分类接口 4 个**（`CategoryController`）：`/api/ledgers/{id}/categories` **嵌套**在账本下（分类属于账本）；`index` 支持 `?type=expense` 筛选；唯一约束三维 `(ledger_id, type, name)`；`destroy` 用**决策 B**
17. **路由模块化扩展**：新增 `routes/api/ledger.php`、`routes/api/account.php`、`routes/api/category.php`，`routes/api.php` 里 require
18. **两次归属/删除策略重构（迁移）**：
    - `..._000008`：`accounts.user_id` 取代 `ledger_id`；`transactions.account_id` 改可空 + `ON DELETE SET NULL`
    - `..._000009`：`transactions.category_id` 改可空 + `ON DELETE SET NULL`
19. 待做：**家庭成员管理**
20. **多语言基础**：`lang/zh_CN/`（校验消息中文）+ `SetLocale` 中间件（按 `Accept-Language` 切换）+ `config('app.supported_locales')`

### 前端对接（MosaicwithAi，2026-09 开始）

21. **依赖与构建**：从 pnpm 转为 **npm**（删 `node_modules` 重装，保留 `pnpm-lock.yaml.pnpm-bak` 备份）；装了 `axios@1.20`
22. **API 层**（`src/api/`）：`client.ts`（axios 实例 + 请求拦截器带 JWT/语言 + 响应拦截器剥信封/统一错误/401 跳登录）、`auth.ts`（login/register/me/logout/refresh）
23. **状态层**：`stores/auth.ts`（登录态 + `fetchMe` 用 token 换用户）、`stores/ledger.ts`（**改为调 `GET /api/ledgers`**，不再写死假数据）
24. **路由**：`/login`、`/register` 独立布局 + 全局守卫（未登录跳登录页，带 `redirect` 回跳）
25. **页面**：`Login.vue`（**按 `stitch_ui/登录与注册` 设计稿重写**：左右分栏 + 密码/验证码 tab）、`Register.vue`（新建，同设计）；`AppLayout.vue`（侧栏真实用户 + 退出登录 + 挂载时拉账本）
26. **待做**：其余页面接后端（首页/日历/账目/统计/家庭/设置/OCR）

### 教学/技术成果（用户是 Laravel 初学者）
- 已理解：模型↔表映射、belongsTo/hasMany、N+1 与 `with()`、JWT 流程、401 vs 403、Model vs Controller 分工
- 阶段 3 新增：`$request->validate()` 与 whitelist、`Rule::unique->where()->ignore()`（含**三维唯一约束**的写法）、`sometimes` 局部更新、`in:` 枚举校验（对应数据库 enum）、`exists:` 关联校验、`DB::transaction` 事务与级联删除、软删除 vs 数据库外键、二次确认（`confirm`）、**私有辅助方法**（`findOwnedLedger` / `findViewableLedger` 区分"可读"与"可写"）、嵌套路由、用关联查找子资源（天然隔离越权与跨账本访问）

---

## 8. 当前 API 接口

统一返回 `{ code, message, data }`，认证失败统一 **401 JSON**（不重定向）。

**请求头约定**

| 头 | 作用 |
|---|---|
| `Authorization: Bearer <token>` | JWT 认证（受保护接口必需） |
| `Accept: application/json` | 让 Laravel 返回 JSON 而不是重定向（前端 axios 已默认带上） |
| `Accept-Language` | **决定返回消息的语言**（`zh-CN` / `en` / `ja`），由 `SetLocale` 中间件处理；不传则用 `APP_LOCALE=zh_CN` |

> JSON 响应里的中文是**直接输出**的（不是 `\uXXXX` 转义），由 `ForceJsonUnicode` 中间件保证。

| 方法 | 地址 | 需要 token | 说明 |
|---|---|---|---|
| POST | `/api/auth/register` | ❌ | 注册（bcrypt 加密 + 发 token） |
| POST | `/api/auth/login` | ❌ | 登录（发 token） |
| GET | `/api/auth/me` | ✅ | 当前登录用户 |
| POST | `/api/auth/logout` | ✅ | 退出（token 作废） |
| POST | `/api/auth/refresh` | ✅ | 刷新（旧 token 作废） |
| GET | `/api/ledgers` | ✅ | 我创建的 + 我加入家庭的账本列表 |
| POST | `/api/ledgers` | ✅ | 创建账本（家庭成员才能建家庭账本，否则 403） |
| GET | `/api/ledgers/{id}` | ✅ | 账本详情（不存在 404 / 非我 403） |
| PUT | `/api/ledgers/{id}` | ✅ | 改账本（仅所有者；`sometimes` 局部更新） |
| DELETE | `/api/ledgers/{id}` | ✅ | 删账本（仅所有者 + `confirm:true` 二次确认；事务级联删流水/分类/附件，**不动账户**） |
| GET | `/api/accounts` | ✅ | 我的支付账户（属于个人，非账本） |
| POST | `/api/accounts` | ✅ | 新增账户（同一用户下重名 422） |
| PUT | `/api/accounts/{id}` | ✅ | 改账户（`Rule::unique` + `ignore`） |
| DELETE | `/api/accounts/{id}` | ✅ | 删账户（**决策 B**：被流水引用时需 `confirm:true`，否则 422 + 引用数量；未被引用可直接删） |
| GET | `/api/ledgers/{id}/categories` | ✅ | 账本下的分类（支持 `?type=income\|expense` 筛选；家庭成员可读） |
| POST | `/api/ledgers/{id}/categories` | ✅ | 新增分类（`name`+`type` 必填；`in:income,expense`；唯一约束 `(ledger_id,type,name)`） |
| PUT | `/api/ledgers/{id}/categories/{categoryId}` | ✅ | 改分类（`sometimes` + `ignore` 排除自己） |
| DELETE | `/api/ledgers/{id}/categories/{categoryId}` | ✅ | 删分类（**决策 B**：被流水引用需 `confirm:true`；删除后流水 `category_id` 置 NULL） |

路由中间件：`auth:api`（用 `api` 守卫，即 JWT）。

**权限规则（阶段 3 统一）**

| 资源 | 读 | 写（增/改/删） |
|---|---|---|
| 账本 | 所有者 + 家庭成员 | 仅 `owner_id` |
| 分类 | 所有者 + 家庭成员 | 仅 `owner_id` |
| 账户 | 仅本人（`user_id`） | 仅本人 |

### 8.1 已完成并提交（阶段 3）
```
3c0ec35  ledger接口：index/show + 归属校验
ec81a15  ledger接口：update/destroy（事务级联删除）
a6d0a5b  ledger删除加二次确认（confirm + 关联数量）
05b5c2b  账户改为属于个人（设置）：/api/accounts + 迁移 + 模型调整
1ae69b5  账户删除改为二次确认（决策B）：被流水引用时需 confirm:true
7ea0e8c  categories接口：/api/ledgers/{id}/categories + 决策B删除（nullOnDelete）
c9ad67b  多语言基础：zh_CN 语言包 + SetLocale 中间件（Accept-Language）
```

> 阶段 2（JWT）那批文件已在 commit `d6af93c` 入库。
> 代码要点：账本 `index` 用 `where('owner_id',$user->id)->when(...orWhereIn('family_id',$familyIds))`；`show` 先 `find()` 判 404，再用 `owner_id` / `familyMemberships()` 判 403。
> 账户接口要点：`auth()->user()->accounts()->find($id)` 天然只能找到自己的账户（越权 id → 404）；`transactions.account_id` 为 `ON DELETE SET NULL`，删账户不毁记账历史。
> 分类接口要点：路由嵌套 `/ledgers/{id}/categories`；`$ledger->categories()->find($categoryId)` 天然隔离**跨账本**访问（用账本1 的路径访问账本4 的分类 → 404）；`index` 用 `findViewableLedger`（家庭成员可读）、写操作用 `findOwnedLedger`（仅所有者）。
> 尚未完成：**家庭成员管理**。

---

## 9. 待办路线图 ⬜

| 阶段 | 内容 | 状态 |
|---|---|---|
| 1 | 模型 + 关联 + Seeder | ✅ |
| 2 | JWT 认证 | ✅ |
| 2.5 | 提交 JWT 那批代码 | ✅ `d6af93c` |
| **3** | 基础资源 CRUD（ledgers/accounts/categories/成员）+ 归属校验(403) | 🟡 **进行中**：ledgers ✅、accounts ✅（属于个人）、categories ✅（属于账本）；**剩家庭成员管理** |
| 4 | 记账核心（transactions CRUD + 筛选/分页/批量删除/子项/转账/周期） | ⬜ |
| 5 | 聚合查询接口（dashboard / calendar / analytics） | ⬜ |
| 6 | 附件与 OCR（上传 + OCR Service 可替换 + 确认入账） | ⬜ **与 AI 智能体共用 AiService** |
| 7 | 家庭协同（邀请/额度/分摊/动态流） | ⬜ |
| 8 | 设置（资料/薪资/偏好） | ⬜ |
| 9 | 导出（Excel/CSV、PDF 报告） | ⬜ |
| 10 | 前端逐页对接（含接入 JWT） | ⬜ |
| 11 | 测试 + 答辩材料（Feature test、API 文档、ER 图） | ⬜ |

### 待补迁移（对应第 6 节前端需求）
`ledger_members`、`budgets`、`transaction_items`、`recurring_transactions`、`user_settings`、`settlements`、`invitations`、`activity_logs`
以及：`users` 加 `phone/nickname/avatar/title`；`ledgers` 加 `type/description`；`transactions` 支持转账

### 阶段插入：AI 智能体（详见第 9.5 节）
**放在阶段 5 之后、阶段 6（OCR）之前做** —— 它依赖流水的 CRUD 与聚合接口作为"工具"；OCR 与本功能共用同一套可替换的 AI Service。

---

## 9.5 AI 智能体接入方案（规划）

> 用户明确要求接入（2026-09）。本节只记方案与决策点，**实现要等阶段 4、5 做完**。

### 目标能力（按优先级）

| # | 能力 | 说明 | 备注 |
|---|---|---|---|
| ① | **自然语言记账** | "昨天盒马买菜 328.6 微信付的" → 解析成流水草稿 → 用户确认入账 | 复用阶段 4 的落库逻辑 |
| ② | **查账问答（核心）** | "这个月外卖花了多少""我跟陈先生谁花得多" → 调工具查库 + 自然语言回答 | 靠 **tool calling**，最能体现"智能体" |
| ③ | **智能分类** | 记账/OCR 时自动建议分类（"盒马鲜生" → 生鲜食品） | 与 OCR 共用 LLM |
| ④ | **洞察/预算提醒** | "本月餐饮超预算 23%" | 基于阶段 5 的聚合接口 + 提示词 |
| ❌ | 多智能体协作、自动执行转账、长期记忆 | 明确不做，写进论文"未来展望" | 时间与风险不划算 |

### 架构

```
前端聊天面板
  ↓ POST /api/ai/chat {message, ledger_id}
AiController
  ↓
AiServiceInterface（★可替换，和 OCR Service 同一套路）
  ↓
LLM API（DeepSeek / 通义千问 / 智谱；国内可直连、便宜、兼容 OpenAI 格式）
  ↓ tool_calls
工具（复用已有接口逻辑，不另写 SQL）
  ├─ getSpendingStats(ledger_id, month, category)   复用阶段 5 聚合
  ├─ getTransactions(ledger_id, filters)            复用阶段 4 筛选
  ├─ createTransaction(...)                         复用阶段 4 store
  └─ getBudgets(ledger_id)                          复用阶段 5
  ↓
工具返回 JSON → 回喂模型 → 生成自然语言答案
```

### 设计要点（面试/答辩会被问）

1. **API Key 只在后端 `.env`**（`AI_API_KEY` / `AI_BASE_URL` / `AI_MODEL`），**绝不暴露前端**，否则被盗刷。
2. **AiService 可替换**：接口 + 实现类，换模型/换厂商只改一个类。**OCR（阶段 6）与智能体共用这套 Service**（一个管文本、一个管图像）。
3. **工具必须复用已有接口/Service 逻辑，不让模型直接写 SQL** —— 这样**权限校验自动继承**（AI 也只能访问用户有权限的账本），也避免注入风险。
4. **危险操作只生成"待确认草稿"**：删除、转账等必须用户点确认后才落库，AI 不能直接执行。
5. **成本与稳定性**：每用户限流（如每天 N 次）、30s 超时、失败降级为友好报错。
6. **多轮对话**（可选）：`ai_conversations` / `ai_messages` 表；不做则只支持单轮。

### 待建文件（实现时用）

```
app/Services/Ai/AiServiceInterface.php     接口（可替换）
app/Services/Ai/DeepSeekService.php        实现（provider 任选）
app/Services/Ai/Tools/                     工具类（统计/流水/预算…）
app/Http/Controllers/AiController.php      POST /api/ai/chat
routes/api/ai.php                          require 进 routes/api.php
config/ai.php                              provider / model / key / 限流
.env                                       AI_API_KEY / AI_BASE_URL / AI_MODEL
迁移（可选）                                ai_conversations / ai_messages
```

### 接入顺序（重要）

| 顺序 | 事项 | 原因 |
|---|---|---|
| 1 | 阶段 4 流水 CRUD | AI 要记账/查账，先得有流水接口和数据 |
| 2 | 阶段 5 聚合查询 | 直接变成 AI 的"工具"，不必重复实现 |
| 3 | **AI 智能体（本节）** | 站在 4+5 之上，主要是"接模型 + 定义工具" |
| 4 | 阶段 6 OCR | 与智能体共用 AiService |

> ⚠️ **不要先做 AI**：工具函数还是空的时候，模型只能编答案，答辩最容易被问穿。

---

## 10. 关键决策

| 决策 | 理由 |
|---|---|
| **认证用 JWT（非 Sanctum）** | 前端按 JWT + `{code,data}` 信封写的，改动最小 |
| **API 未认证返回 401 JSON，不重定向** | 前后端分离，登录页在前端；后端跳转无意义且会 500 |
| **放弃 SSH 隧道** | 服务器只认密钥认证，本地无密钥；对毕设无必要 |
| **本地开发连本地 MySQL，服务器连阿里云 MySQL** | 两套隔离，靠 Seeder 保证数据一致 |
| **服务器暂不更新** | 后端还在长，频繁部署拖慢开发；等接口做得差不多再一次性部署 |
| **不做"资产概览"功能** | 难度高一个量级（数据建模+定时任务+AI 集成），时间不划算；只写进论文"未来展望" |
| **前端用环境变量切 baseURL** | 开发连本地、演示连服务器，一次配置两处切换（尚未实施） |
| **账户属于个人，不属于账本**（2026-09 改动） | 账户 = "我用什么付款"，是设置里的东西；放账户下会引出"家庭账本要展示谁的银行卡余额"的隐私难题。改为 `accounts.user_id`，跨账本复用；"谁花的钱"由 `transactions.created_by` 负责 |
| **不做账户余额功能** | 个人账户模型下"余额算哪个账本的"无法自洽；`opening_balance` 字段保留但接口不暴露、前端不显示 |
| **删账户不毁记账历史** | `transactions.account_id` 改 `ON DELETE SET NULL`（付款方式已删除，流水保留） |
| **被引用的账户"二次确认后可删"**（决策 B，2026-09 用户决定） | 422 拦住默认删除并回报引用数量；带 `confirm:true` 则真删，关联流水 `account_id` 置 NULL。权衡：允许用户清理不用的支付方式（如已注销的卡），代价是历史流水的付款方式丢失 → **前端必须把 NULL 显示为"已删除"**。（备选方案 A"永不允许删"因体验僵化被否） |
| **被引用的分类同样"二次确认后可删"**（决策 B 保持一致） | 与账户同策略：迁移 `..._000009` 把 `transactions.category_id` 改为 `ON DELETE SET NULL`；删除后流水显示"未分类"。**理由：两套删除规则会让阶段 4 的流水接口难以维护** |
| **账本删除不级联删账户** | 账户是别人的东西，删账本凭什么删我的微信；事务里只删流水/分类/附件 |
| **教学方式：用户手写代码，我讲解+验证** | 用户明确要求"不要直接帮我写完"，涉及写代码先问 |
| **多语言用 `Accept-Language` 请求头，而不是全局切换**（2026-09） | 语言按**每个请求**决定：前端在请求头带 `Accept-Language`，中间件 `SetLocale` 切换。好处：用户切换语言无需重新登录/改后端配置；同一后端可同时服务中英日用户。已预留 `supported_locales = [zh_CN, en, ja]` |
| **前端界面暂不做 i18n** | 界面文案目前硬编码中文；引入 `vue-i18n` 需把几百条文案抽成 key，工作量大 → 列"未来展望"。当前多语言只覆盖**后端消息**（校验提示等） |

---

## 11. 踩过的坑（避免重犯）

| 坑 | 症状 | 解决 |
|---|---|---|
| 公网带宽为 0 | 访问不了 | 已开通带宽 |
| Phpstudy 误绑定外层目录 | 路径混乱 | 已清理 |
| 7 个迁移"悬空" | 只在前端仓库 staging + 无 git 副本里 | 从 git 历史抢救 → 归位真仓库 |
| `migrate:status` 分不清本地/线上库 | 都显示 Ran | 用 `tinker` 查 `select @@hostname`，看是否 `iZ` 开头 |
| `php artisan db:show` 报错 | 要装 doctrine/dbal | 跳过；改用 `tinker` |
| `.env` 改 3307 但没起隧道 | "目标计算机积极拒绝" | 改回 3306 |
| 服务器 `git pull` 超时 | `Failed to connect to github.com` | 用代理/Gitee 中转/GitHub 加速 |
| **`Route [login] not defined` → 500** | 无 token 访问受保护接口 | **中间件 `redirectTo()` 和 `Handler::unauthenticated()` 两处**都要改成返回 401 JSON |
| Postman 报 "reserved address" | 用的是 Cloud Agent | 切换到 Desktop Agent |
| 405 Method Not Allowed | 用 GET 发了 POST 接口 | 改对方法（logout/refresh 是 POST） |
| curl 发含中文的 JSON 失败 | 字段全变 required | Windows 终端编码问题；**用 `-d @文件.json` 或英文测试** |
| Model 和 Controller 搞混 | 方法贴错文件 | Model 放关联/属性；含 `$request`/`response()`/`auth()` 的是 Controller |
| `orWhere` 不加分组 | 多个 or 条件会绕过前面的筛选（权限泄漏） | 用闭包包起来：`->where(fn($q) => $q->where(...)->orWhere(...))` |
| **`MassAssignmentException: Add [x] to fillable`** | `create()` 报 500 | 模型加 `protected $fillable = [...]`；**每个新模型都要加**（`Ledger` `Account` 都踩过；`Transaction` 还没加，阶段 4 必踩） |
| **MySQL errno 1553** | 迁移里 `dropUnique` 报 "Cannot drop index: needed in a foreign key constraint" | 外键依赖索引，必须**先 `dropForeign` 再 `dropUnique`**（`..._000008` 迁移里有注释） |
| **`response()->json()` 状态码写成数组元素** | 返回 HTTP 200 但 body 里 `code:404`，还多出 `"0":404` | 状态码是 `json()` 的**第二个参数**：`response()->json([...], 404)` |
| **`Ledger::update($data)` 静态调用** | `Non-static method ... cannot be called statically` | 新建用 `Model::create()`（静态），修改用 `$model->update()`（实例） |
| **变量先用后定义** | `Undefined variable $ledger` / `$id` | PHP 逐行执行；`find($id)` 必须在用到 `$ledger` 之前；方法要用路由参数就得在签名里写 `$id` |
| **二次确认放在授权之前** | 非本人请求也返回 422 + 关联数量 → **泄漏"账本存在、有多少数据"** | 顺序：find → 404 → 归属 403 → confirm 422 → 事务删除 |
| **PHP 里用 `=` 当比较**（测试脚本） | 误用他人 token 跑了删除用例，真删了数据 | 比较用 `==`/`-eq`；测试脚本也要 review，破坏性用例先备份/可重跑种子 |
| **删用户前没处理他的账户** | `Cannot delete or update a parent row`（1451） | `accounts.user_id` 是 `ON DELETE RESTRICT`；删用户要先删/转他的账户 |
| **`php artisan tinker` 报 PsySH 写历史失败** | `Writing to .../psysh_history is not allowed` | 沙箱环境下 tinker 用不了；改用它 `DB::select` 的独立 PHP 脚本或直接 PDO 查数据 |
| **语言文件键名用点号扁平写法** | 生成 `lang/zh_CN/validation.php` 得到 `'between.array' => ...`，而 Laravel 要求**嵌套数组** `'between' => ['array' => ...]` → 该规则找不到消息、**静默回退英文** | 转换 JSON 语言包时必须把点号键**重新嵌套**；生成后实测一条带子键的规则（`between`/`min`） |
| **`:attribute` 显示英文字段名** | 提示是"email 已经存在。"而不是"邮箱 已经存在。" | 字段中文名要放进 `lang/zh_CN/validation.php` 的 **`attributes` 数组**；单独建 `validation.attributes.php` 文件**不生效** |
| 照 Laravel 10+ 文档装 `laravel-lang/lang` | v15 面向 L10+，会带入大量依赖更新 | 本项目只需语言文件：直接取仓库 `locales/zh_CN/php.json` 转成 PHP 文件即可，**不动 composer 依赖** |
| **JSON 响应中文被转义成 `\u6797\u6c0f`** | Postman Raw 视图/日志里看不到中文 | 原因：本项目 Laravel 9.52 装的是 **symfony/http-foundation 6.0**，其 `JsonResponse` 默认 `encodingOptions = 0`（不含 `JSON_UNESCAPED_UNICODE`）。**前端不受影响**（JSON 解析自动还原），只是人肉看难读 |
| ↳ 试过但**无效**的修法（别再试） | — | ① `JsonResponse::setEncodingOptions()` —— 是**实例方法**，静态调用直接报错；② `Response::macro('json', ...)` —— Laravel 9 的 `response()->json()` **不走这个宏**，无效果 |
| ↳ **有效修法** | — | 中间件 `ForceJsonUnicode`：拿到响应后调 `$response->setEncodingOptions($response->getEncodingOptions() \| JSON_UNESCAPED_UNICODE \| JSON_UNESCAPED_SLASHES)`（注册在全局 `$middleware` 最后） |
| PowerShell 里看响应中文变乱码（`ç»å½æå`） | 明明后端已不转义 | 是 **`Invoke-WebRequest` 的 `.Content` 按 Latin-1 解码 UTF-8** 所致，不是服务端问题。测试时用 `[Text.Encoding]::UTF8.GetString($r.RawContentStream.ToArray())`，或直接 `ConvertFrom-Json` 后看字段 |
| **`if` 块"吞掉"了后续语句**（最隐蔽） | 缩进把 `$account->delete()` 放进了 `if ($usedCount > 0) {}` 里 → 未被引用的账户**删不掉，接口却返回 200 成功** | 结构约定：`if` 只负责"拦"（return 错误），**正文动作放在 `if` 外面**。这类 bug 不报错、返回成功、数据没变，**必须靠"删完再查一次"的测试才能发现** |
| 接口返回成功 ≠ 数据真的变了 | 同上 | 测试用例要带"**操作后再查询确认**"这一步，不能只看 HTTP 200 |
| 测试脚本自身的判断条件写错 | 用"名字包含 Probe"判断是否删除，匹配到了上一轮的残留记录，误报"没删掉" | 断言要针对**精确的 id**，不要用模糊匹配；测试前后都查一次库 |
| 测试脚本取错 id | 想删"本次新建的分类"，却取了列表第一个 id（那是个有流水的旧分类）→ 误判用例失败 | 新建接口返回的 `data.id` 要**存下来**再用；不要"取列表第一条"当目标 |
| 删分类/账户后流水引用变 NULL | 直接影响统计与展示 | **前端必须把 NULL 显示为"已删除"/"未分类"**（已列入第 14 节待办）；后端查询用 `with('category')` 时也要容忍 NULL |

---

## 12. 常用命令速查

```bash
# 后端仓库位置
cd C:\Users\admin\Desktop\Phpstudy\Mosicbackend\Mosaic-Laravel

# 语法检查
php -l app/Http/Controllers/AuthController.php

# 起服务（测试用，默认 8000）
php artisan serve

# 路由列表
php artisan route:list --path=api

# 清缓存（改了 config/ 后必跑）
php artisan config:clear

# tinker 一行式
php artisan tinker --execute="dump(App\Models\User::count());"

# 查看当前连哪个库
php artisan tinker --execute="dump(DB::selectOne('select @@hostname as host, database() as db, current_user() as user'));"

# 迁移 + 种子
php artisan migrate
php artisan db:seed
php artisan migrate:fresh --seed     # 一键重置所有表+数据
```

```bash
# Composer（不在 PATH，用完整路径）
php C:/Users/admin/composer.phar require 包名

# 服务器侧（Workbench 里）
cd /var/www/Mosaic-Laravel
git pull origin main
php artisan migrate
php artisan db:seed
grep -E "^DB_" .env
```

---

## 13. 编码约定

**模型/关联**
- 模型放 `app/Models/`，类名单数大驼峰（`Family`）↔ 表名复数小写下划线（`families`）
- **关联口诀：外键在谁表里，谁就 `belongsTo`；被指向的一方写 `hasMany`**
- 列名 ≠ 方法名+`_id` 时写第二个参数（`owner_id`/`created_by`/`uploaded_by`）
- `Transaction` 用 `SoftDeletes`
- 取关联数据用属性（`$t->category`），加条件用方法（`$t->category()->where(...)`）
- 循环访问关联必须 `with()` 预加载，避免 N+1

**API**
- 路由模块化：`routes/api.php` 只做 require，具体在 `routes/api/*.php`
- 统一响应：`{ code, message, data }`
- 受保护路由用 `auth:api`（不是裸 `auth`）
- 未认证 → 401 JSON；无权限 → 403；验证失败 → 422（Laravel 默认）
- 密码一律 `Hash::make()`（默认 bcrypt），**绝不存明文**
- 控制器方法名（login/me/register…）是自定义的，不是框架自带的
- 资源接口按 `index / show / store / update / destroy` 命名（REST 惯例）
- **每个涉及资源 id 的方法都要做归属校验**：先 `find()` 判 404（资源不存在），再判 403（存在但无权）。顺序不能反，否则会把"别人的资源是否存在"泄漏出去
- **更省事的写法：用关联查找子资源** —— `auth()->user()->accounts()->find($id)`，越权 id 天然 404，不用另写归属判断
- **可见权限 ≠ 可写权限**：`show` 允许家庭成员看（`$isMine || $isFamily`），`update/destroy` 只允许所有者（`$ledger->owner_id !== $user->id`）。这两套规则要分开，不能共用一个校验
- **破坏性接口加二次确认**：`confirm` 字段 + 未确认时返回 422 并附"会删掉多少"的统计（如 `{'transactions':14}`），供前端做确认弹窗
- **`if` 只负责"拦"，正文动作放 `if` 外**：`if (有条件) { 校验并 return 错误 }` 之后才是 `$model->delete()`。若把删除写进 `if` 内，未被引用的资源会"返回成功但没删"（不报错，极难发现）
- **测试要"操作后回查"**：HTTP 200 不代表数据变了；破坏性操作的用例必须再查一次库/接口确认结果
- **失败顺序**：404（不存在）→ 403（无权）→ 422（业务规则/确认）→ 成功
- 模型的 `$fillable` 只放允许批量赋值的列；**归属字段（`owner_id`/`user_id`）由后端填，绝不接受客户端传入**

**迁移（本项目 MySQL 特性）**
- 不用 `doctrine/dbal`：改列类型/加外键用 `DB::statement('ALTER TABLE ...')`
- **删外键列的顺序**：先 `dropForeign`，再 `dropUnique`，最后 `DROP COLUMN`（反过来会报 errno 1553）
- `restrictOnDelete` = 有子数据时禁止删父行（应用层要先检查并给 422），`nullOnDelete`/`cascadeOnDelete` 按语义选

**目录分工**
- Model（`app/Models/`）= 表结构 + 关联
- Controller（`app/Http/Controllers/`）= 处理请求
- 判断：方法里有 `$request`/`response()`/`auth()` → Controller

---

## 14. 收尾杂项（待办）

- [ ] **前端需要一个"账户管理"入口**（账户已属于个人/设置）；新账本可考虑自动带默认账户（现金/微信/支付宝）或前端提供"推荐账户"
- [ ] **前端：流水的付款方式为 NULL 时显示"已删除"**（决策 B 的配套要求：账户被删后，历史流水的 `account_id` 为 NULL，不能显示空白）
- [ ] 可选增强：账户"停用"（`is_active` 字段，列表不显示但历史照旧）—— 比直接删除更温和的替代方案
- [ ] 前端仓库 `MosaicwithAi`：提交 `.migration-staging/` 的 7 个 ` D`（迁移已归位后端，可删）
- [ ] 删除无 git 副本 `C:\Users\admin\Desktop\Mosaic\Mosaic-Laravel`（⚠️ 目前仍存在）
- [ ] 确认服务器 `.env` 的库名
- [ ] 公司电脑 `git pull` + `migrate` + `db:seed`（注意：本次有**新迁移**，必须跑 `php artisan migrate`）
- [ ] 备份位置记录：模型正确版 `%Temp%\mosaic-model-backup`、JWT 版 `%Temp%\mosaic-jwt-backup`
