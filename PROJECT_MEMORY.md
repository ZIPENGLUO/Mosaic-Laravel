# Mosaic 家庭财务系统 — 项目记忆

> 最后更新：JWT 认证阶段完成时
> 用途：记录项目全貌、进度、决策与待办，供后续开发/答辩参考

---

## 0. 一句话概括

AI 生成的家庭记账前端（Vue3，纯静态 mock），配一套 Laravel 9 后端，做毕业设计。
**当前进度：阶段 1（模型+关联+Seeder）和阶段 2（JWT 认证）已完成，下一步进入阶段 3（账本/流水业务接口）。**

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
（JWT 认证尚未提交）
```

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

**三处表结构已对齐（11 个迁移 / 12 张表），本地和服务器都已灌演示数据**

| 环境 | 数据库 | 迁移 | 数据 | 状态 |
|---|---|---|---|---|
| 自己电脑 | 本地 MySQL，库 `laravel`，账号 `root` | 11 | ✅ | ✅ |
| 阿里云服务器 | 服务器 MySQL，账号 `myapp_user` | 11 | ✅ | ✅ |
| GitHub 仓库 | — | 11 个文件 | — | ✅ |
| 公司电脑 | 本地 MySQL，账号 `root` | 4 | ⬜ | 需 `git pull` + `migrate` + `db:seed` |

**12 张表：**

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
| accounts | ledger_id | ledgers.id |
| categories | ledger_id | ledgers.id |
| transactions | ledger_id | ledgers.id |
| transactions | account_id | accounts.id |
| transactions | category_id | categories.id |
| transactions | created_by | users.id |
| attachments | ledger_id | ledgers.id |
| attachments | transaction_id | transactions.id（可空） |
| attachments | uploaded_by | users.id |

**业务表设计要点：**
- `transactions` 有 `deleted_at`（软删除）、`source` 枚举 `manual`/`ocr`
- `categories` 有 `type` 枚举 `income`/`expense`，唯一约束 `(ledger_id, type, name)`
- `accounts` 唯一约束 `(ledger_id, name)`
- `transactions.type` 目前只有 `income`/`expense`，**前端还有"转账"类型，待扩展**

**演示数据（DemoSeeder，可重复执行，用 `firstOrCreate`）：**
```
4 用户（林知栖/陈先生/林小满/苏外婆，密码统一 password）
1 家庭（林氏一家）
3 账本（家庭账本 / 个人私密账本 / 海岛游专项基金）
7 账户、13 分类、14 流水、1 附件
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

### 教学成果（用户是 Laravel 初学者）
- 理解模型↔表映射、belongsTo/hasMany、N+1 与 `with()`、JWT 流程、401 vs 403、Model vs Controller 分工

---

## 8. 当前 API 接口

统一返回 `{ code, message, data }`，认证失败统一 **401 JSON**（不重定向）。

| 方法 | 地址 | 需要 token | 说明 |
|---|---|---|---|
| POST | `/api/auth/register` | ❌ | 注册（bcrypt 加密 + 发 token） |
| POST | `/api/auth/login` | ❌ | 登录（发 token） |
| GET | `/api/auth/me` | ✅ | 当前登录用户 |
| POST | `/api/auth/logout` | ✅ | 退出（token 作废） |
| POST | `/api/auth/refresh` | ✅ | 刷新（旧 token 作废） |

路由中间件：`auth:api`（用 `api` 守卫，即 JWT）。

### 未提交的文件（工作区）
```
 M app/Exceptions/Handler.php        （unauthenticated 返回 JSON）
 M app/Http/Middleware/Authenticate.php （redirectTo 返回 null）
 M app/Models/User.php               （JWTSubject）
 M composer.json / composer.lock     （JWT 包）
 M config/auth.php                   （api 守卫）
 M routes/api.php                    （模块化总入口）
?? app/Http/Controllers/AuthController.php
?? config/jwt.php
?? routes/api/
```

---

## 9. 待办路线图 ⬜

| 阶段 | 内容 | 状态 |
|---|---|---|
| 1 | 模型 + 关联 + Seeder | ✅ |
| 2 | JWT 认证 | ✅ |
| **2.5** | **提交 JWT 那批代码** | 🟡 待做 |
| 3 | 基础资源 CRUD（ledgers/accounts/categories/成员）+ 归属校验(403) | ⬜ 下一步 |
| 4 | 记账核心（transactions CRUD + 筛选/分页/批量删除/子项/转账/周期） | ⬜ |
| 5 | 聚合查询接口（dashboard / calendar / analytics） | ⬜ |
| 6 | 附件与 OCR（上传 + OCR Service 可替换 + 确认入账） | ⬜ |
| 7 | 家庭协同（邀请/额度/分摊/动态流） | ⬜ |
| 8 | 设置（资料/薪资/偏好） | ⬜ |
| 9 | 导出（Excel/CSV、PDF 报告） | ⬜ |
| 10 | 前端逐页对接（含接入 JWT） | ⬜ |
| 11 | 测试 + 答辩材料（Feature test、API 文档、ER 图） | ⬜ |

### 待补迁移（对应第 6 节前端需求）
`ledger_members`、`budgets`、`transaction_items`、`recurring_transactions`、`user_settings`、`settlements`、`invitations`、`activity_logs`
以及：`users` 加 `phone/nickname/avatar/title`；`ledgers` 加 `type/description`；`transactions` 支持转账

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
| **教学方式：用户手写代码，我讲解+验证** | 用户明确要求"不要直接帮我写完"，涉及写代码先问 |

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

**目录分工**
- Model（`app/Models/`）= 表结构 + 关联
- Controller（`app/Http/Controllers/`）= 处理请求
- 判断：方法里有 `$request`/`response()`/`auth()` → Controller

---

## 14. 收尾杂项（待办）

- [ ] 前端仓库 `MosaicwithAi`：提交 `.migration-staging/` 的 7 个 ` D`（迁移已归位后端，可删）
- [ ] 删除无 git 副本 `C:\Users\admin\Desktop\Mosaic\Mosaic-Laravel`
- [ ] 确认服务器 `.env` 的库名
- [ ] 公司电脑 `git pull` + `migrate` + `db:seed`
- [ ] 备份位置记录：模型正确版 `%Temp%\mosaic-model-backup`、JWT 版 `%Temp%\mosaic-jwt-backup`
