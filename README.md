# 烽火棋 · GMOK

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
![PHP](https://img.shields.io/badge/PHP-7.2%2B-777bb4)
![Node](https://img.shields.io/badge/Node-16%2B-5fa04e)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479a1)

30×30 大棋盘战棋。带迷雾、探棋许可区、渡河与核心区攻防的原创回合制棋类，附完整的人机 / 联机 / 排位 / 录像 / 教学关卡与元数据驱动后台。

前端零框架零构建，联机服务端 Node + socket.io，账号与排位用 PHP + MySQL。三档部署，纯静态即可运行单机功能。

---

## 目录

- [玩法简介](#玩法简介)
- [功能一览](#功能一览)
- [快速开始](#快速开始)
- [配置说明](#配置说明)
- [Web 服务器配置](#web-服务器配置)
- [后台管理](#后台管理)
- [登录网关](#登录网关)
- [目录结构](#目录结构)
- [技术要点](#技术要点)
- [安全须知](#安全须知)
- [许可证](#许可证)

---

## 玩法简介

双方各 20 枚棋子，红蓝对坐 30×30 棋盘。

| 棋子 | 走法 | 吃子 | 说明 |
|------|------|------|------|
| 母棋 母 | 斜向 1 格 | 否 | 未继位前只能待在本方核心区 |
| 子棋 子 | 八向 2 格 | 否 | 母棋阵亡后继位为母棋 |
| 军棋 军 | 八向 3 格 | 是 | 唯一能渡河的棋子；每消灭 2 枚敌方军棋，步长 +1 |
| 探棋 探 | 横纵 4 格 | 否 | 提供视野与许可区，是全盘发动机 |
| 盾棋 盾 | 横纵 1 格 | 是 | 本方子棋在场时只能被横向吃掉，并保护同列前方 1–3 格 |
| 白板 白 | 横纵 1 格 | 否 | 复活产生的降级棋子 |

**核心机制**

- **迷雾**：开局双方只能看见本方半场。母棋阵亡、或全场棋子 ≤20 时全局解除；子棋阵亡则对方单方解除。
- **许可区**：军棋和盾棋开局全部锁死。探棋落点会在其周围形成 2×3 许可区，只有被许可区覆盖**且**在视野内的重子才能行动。因此进攻必须由探棋逐步跳跃开路，对局呈反复拉锯态势。
- **渡河**：中央 14/15 两行为河道。只有军棋能过，且须走直线（纵向步长 ≥3、横向 ≥4），同一河段内不能有其他棋子。河道之间的缺口通道则要求周围 2×2 无子。
- **核心区惩罚**：全场棋子 >26 且对方尚有盾棋时强攻对方核心区，会被罚下一枚探棋或军棋（无子可罚则罚停一回合）。
- **区块上限**：本方半场左右两区，各最多容纳 9 枚非王棋子。
- **继位与复活**：母棋阵亡后子棋继位（横纵 2 格、可吃子）；继位母棋每次吃子可在原位复活一枚阵亡棋子为白板。

**胜负**：一方母棋与子棋皆阵亡，**且**军棋加盾棋合计 ≤4 时，判负。

---

## 功能一览

- **人机对战** — 4 档难度（新手 / 棋手 / 大师 / 炼狱），αβ 迭代加深 + 历史启发热力图，硬实时预算（常规 ≤200ms）
- **本地双人** — 同屏对弈
- **联机娱乐** — 房间制，支持密码、观战、实时大厅列表
- **排位竞技** — 积分匹配池，按分差撮合并随等待时长放宽窗口
- **赛后评分** — 六维度评分（杀伤 / 攻势 / 生存 / 资源 / 谋略 / 纪律）+ 评级动画
- **对局录像** — 二进制 FHQR 格式存档与回放
- **教学关卡** — 5 关新手教程 + 10 关官方残局
- **残局编辑器** — 自由摆子、导出 FEN、本地草稿
- **排行榜** — 综合 / 积分 / 胜率 / 连胜 / S+ 五榜
- **后台管理** — 元数据驱动，7 张表共用一套引擎

---

## 快速开始

三档部署，按需选择。仅需单机功能时只做档一即可，无须任何后端。

### 档一：纯静态（零后端）

人机、本地双人、教学关卡、残局编辑器完全跑在浏览器里，不需要 PHP、MySQL、Node。

```bash
git clone https://gitee.com/xihe-yh/fhqbzb.git
cd fhqbzb
# 任意静态服务器即可
python -m http.server 8000
```

打开 `http://localhost:8000/bot.html` 就能玩。可用页面：

| 页面 | 内容 |
|------|------|
| `bot.html` | 人机对战 |
| `localversus.html` | 本地双人 |
| `tutorial.html` | 新手教程 |
| `endgame.html` | 残局与编辑器 |

### 档二：加联机（Node，无数据库）

```bash
npm install
cp .env .env      # 按需修改端口
npm start                 # 默认 8080
```

此时 `online.php` 的娱乐房可用，允许匿名对战。**不配 `API_SECRET_KEY_mok` 时结算回调会静默跳过**，对局本身不受影响。注意这一档仍需 PHP 环境来跑 `.php` 页面；只要不建库，账号与排位相关功能自动降级。

### 档三：完整部署（账号 + 排位 + 后台）

需要 PHP 7.2+（含 mysqli、openssl）、MySQL 5.7+、Node 16+。

**方式 A：安装向导（推荐）**

```bash
composer install
npm install
```

然后浏览器打开 `http://your-site/install/`。向导会依次收集数据库连接与后台管理员凭据，自动建表、生成 `config.php`、生成 `.env` 并随机化 API 密钥。

装完**立刻删掉安装目录**：

```bash
rm -rf install/
npm start
```

**方式 B：手工部署**

```bash
# 1. 依赖
composer install
npm install

# 2. 建库
mysql -u root -p your_db < install/inl.sql

# 3. 数据库连接
cp config.example.php config.php   # 填写 $db_host/$db_user/$db_pass/$db_name

# 4. 服务端配置
cp .env.example .env               # 见下方「配置说明」，务必生成随机 API 密钥

# 5. 插入后台管理员（见下方「后台管理员」）

# 6. 启动 Node
npm start
# 生产环境建议用 pm2
# pm2 start server.js --name fenghuoqi

# 7. 删除安装目录
rm -rf install/
```

### 后台管理员

**不预置任何默认账号密码。** `install/inl.sql` 中的 `mok_admin` 表为空，此时后台无法登录，属预期行为。

用安装向导（浏览器打开 `/install/`）的，向导会让你填账号密码并写入 bcrypt 哈希。要求：账号 3–50 位字母数字下划线；密码至少 10 位，且包含大写、小写、数字、符号中的至少三类。

手工导入 SQL 的，自行插入一条：

```bash
php -r "echo password_hash('你的密码', PASSWORD_BCRYPT, ['cost'=>12]), PHP_EOL;"
```

```sql
INSERT INTO `mok_admin` (`username`, `password`) VALUES ('你的账号', '上面输出的哈希');
```

---

## 配置说明

### `.env` — 服务端配置

被 `server.js` 与 PHP 同时读取。

| 键 | 说明 |
|----|------|
| `WS_LINKING_ADDRESS` | 浏览器要连的 socket.io 地址，如 `http://1.2.3.4:8080` |
| `port` | Node 监听端口，默认 8080 |
| `API_SECRET_KEY_mok` | Node ↔ PHP 共享密钥，同时用于签发身份票据 |
| `PHP_API_BASE` | Node 回调 PHP 的基址，同机填 `http://127.0.0.1` |
| `CORS_ORIGIN` | `*` 不限，多个用逗号分隔 |
| `TURN_AFK_MS` | 回合挂机判负阈值，默认 120000 |
| `ROOM_IDLE_MS` | 空房回收，默认 600000 |
| `LOBBY_PUSH_MS` | 大厅推送间隔，默认 5000 |
| `REPORT_MAX_ATTEMPTS` | 排位结算重试次数，默认 6 |

生成密钥：

```bash
php -r "echo bin2hex(random_bytes(18)), PHP_EOL;"
```

> **注意**：值里不能出现 `#`。`server.js` 的 env 解析按第一个 `#` 截断，加引号也无效。
> 改动 WS 端口 / CORS / 各类超时后**需要重启 Node** 才生效。

### `config.php` — 数据库连接

```php
<?php
$db_host = 'localhost';
$db_user = 'your_user';
$db_pass = 'your_password';
$db_name = 'your_database';
```

### `setting.php` — 站点信息

站点标题、备案号、联系邮箱、SMTP、对象存储。可在后台「站点配置」里可视化编辑，无需手改。

---

## Web 服务器配置

`.env`、`admin/lib/`、`cofd/` 等路径**必须禁止 HTTP 访问**。`.env` 一旦被当作静态文件读取，数据库密码与 API 密钥将同时泄漏。

### Nginx

把 `install/nginx-rewrite.conf` 整段内容粘进面板的**伪静态**框（宝塔 / aaPanel / 1Panel 均有此项），保存即生效。手工配置的放进站点 `server { }` 内。

该文件还附带一段可选的 socket.io 反代配置：启用后前端可走同源 `wss://`，不必对外开放 8080，也避免 HTTPS 页面连 `ws://` 的混合内容拦截。

### Apache

仓库根目录已带 `.htaccess`，确保站点开启了 `AllowOverride All` 即可。

### 自查

部署完用浏览器访问下列地址，**都应该是 403 或 404**：

```
https://your-site/.env
https://your-site/.env.bak
https://your-site/admin/lib/db.php
https://your-site/cofd/common.php
https://your-site/install/inl.sql
```

`config.php` 是 PHP 文件，直接访问会被解析执行、返回空白页，凭据不会外泄，因此不在上面的清单里。真正危险的是 `.env` 这类**不经 PHP 解析、被当作静态文件直接吐出全文**的配置。

---

## 后台管理

访问 `/admin/`，用 `mok_admin` 表里的账号登录。

后台不为每张表单独写页面，而是从 `INFORMATION_SCHEMA` 读取列结构，并解析列注释得到中文标签与枚举映射。7 张表共用同一个模板，配置文件中无需声明字段类型。

例如库中定义：

```sql
`isban` int(1) COMMENT '状态\r\n0正常\r\n1封禁\r\n2注销'
```

后台自动得到：表头"状态"、编辑页一个三项下拉、列表页彩色徽章。加一张新表只需在 `admin/tables.php` 里补几行：

```php
'mok_user' => array(
    'label'   => '用户',
    'group'   => '用户管理',
    'pk'      => 'id',
    'list'    => array('id', 'uname', 'credit', 'isban', 'regtime'),
    'search'  => array('id', 'uname', 'bdmail'),
    'edit'    => array('uname', 'sayed', 'credit', 'isban'),   // 空数组 = 只读表
    'actions' => array('ban', 'unban', 'resetPwd'),
    'hide'    => array('password'),                            // 永不读取、永不显示
),
```

**功能**

| 模块 | 能力 |
|------|------|
| 总览 | 用户数 / 封禁数 / 对局数 / 今日对局 / 录像数 + 最近 10 场 |
| 用户管理 | 列表搜索、改昵称信誉分、封禁解封、重置密码（随机生成并显示一次） |
| 排位与对局 | 积分改判、按对局记录重算场次与连胜；对局记录只读 |
| 录像与文件 | 录像列表与删除、文件软删除（不动 S3 对象） |
| 站点配置 | 可视化编辑 `setting.php` |
| 服务端配置 | 可视化编辑 `.env`，自动清理重复键 |
| 操作日志 | 所有写操作留痕，按月分文件 |

**安全机制**：bcrypt 登录 + 失败 5 次锁 15 分钟（按 IP 落文件，换 session 绕不过）、会话绑定 UA、2 小时闲置登出、全部写操作校验 CSRF、表名与列名双层白名单、值一律走预处理占位符、`hide` 列从不进 SELECT。

`admin/lib/` 下的文件顶部都有 `FHQ_ADMIN` 常量守卫，即使 Web 服务器规则漏配也无法被直接请求。

---

## 登录网关

`use/user/` 是整个站点唯一的身份入口。注册、登录、找回、邮箱验证四条路径全部收口在这里，其余页面（大厅、排位、录像、设置）只读它签发的凭据，不自己做认证

### 为什么是「身份验证码」而不是账号密码

传统做法是浏览器每次提交 `username + password`。**注册成功时一次性签发一串加密的身份验证码，之后登录只提交这一串**。

验证码的明文是四段拼接：

```
username <:> password <:> userid <:> email
```

用 `cofd/functions.php` 里的 `encrypt()`（RC4 + MD5 前缀校验）加密，密钥是 `generateAutoWebsiteIdentifier(true)` —— 由协议、域名、端口、主机名、`SERVER_ADDR`、运行用户等七项拼接后取 SHA-256 前 8 位。

由此得到三个性质：

- **换站即废**。验证码里带站点指纹，从别处拖库拿到的码在本站解不开，反之亦然。
- **无服务端会话**。身份完全装在密文里，PHP 侧不存登录态，重启不掉线，也不必为分布式共享 session。
- **密码不落地**。登录请求里没有裸密码字段，用户平时也不需要记密码，只保管这一串。

代价是这串码等价于账号本身，泄漏即失陷 —— 页面上因此专门弹一个不可复制过的表格提示妥善保管，找回接口也支持凭 `用户名 + 密码 + 邮箱` 重新签发。

### 双信封：一次输入，两个时效

前端拿到用户输入的验证码后，不直接发出去，而是用 `assets/authwrite.js` 的 `tmdbaseauthdownyho` 加密两份（[use/user/index.php:809-810](use/user/index.php#L809-L810)）：

```js
const newcontroler  = new tmdbaseauthdownyho();              // 60s  窗口 → authdata
const newcontroler2 = new tmdbaseauthdownyho(60000 * 60 * 2); // 2h   窗口 → authdata2
```

两份都是 AES-256-GCM，密钥由**时间块**经 HKDF-SHA256 派生（`floor(now / timeWindow)`），服务端 `cofd/tauth.php` 的 `TmdbaseauthdownyhoDecrypt` 用同一算法还原，允许 ±1 个时间块的时钟漂移。

分工是关键：

| 信封 | 窗口 | 用途 |
|------|------|------|
| `authdata` | 60 秒 | 本次登录请求的凭证。过期即失效，抓包重放没有意义 |
| `authdata2` | 2 小时 | 登录成功后原封不动写进 Cookie，成为会话凭据 |

也就是说 **Cookie 里存的就是那个 2 小时信封本身**，服务端不额外发 token。会话到期由信封的时间块自然决定，不需要服务端记录过期表。

> 注意这层信封的密钥只由时间块派生，不含服务端密钥，设计意图是**把重放窗口收窄**并劝退直接改前端，不是机密性边界。真正的身份机密性由内层 `encrypt()` 的站点指纹密钥提供，请求真伪由下面的签名层保证。

### 请求链路

以登录为例，`logs/getlog.php` 从上到下依次是：

```
SecuritySigner::verifyRequest(null, true)   签名 + 设备凭证 + CSRF，失败 403
  ↓
读 JSON / POST body，取 authdata、authdata2
  ↓
TmdbaseauthdownyhoDecrypt->writebacknewwords()   拆 60s 信封      失败 301
  ↓
encrypt($authcode, 'D', 站点指纹)                拆身份验证码     失败 302
  ↓
explode('<:>')  四段齐全性校验                                    失败 303/304
  ↓
mok_user 预处理查询 → password_verify → isban 判定                失败 305/306/307/308
  ↓
（若开启邮箱验证且 bdmail 为空）签发 15 分钟令牌 + 发信，中止      返回 309/310
  ↓
setcookie(站点指纹 + "_log", authdata2, +2h)  ← 会话就此建立      返回 200
```

状态码是稳定契约，前端按码分支：

| 码 | 含义 | 码 | 含义 |
|----|------|----|------|
| 200 | 成功 | 305 | 账号不存在 |
| 300 | 数据缺失 | 306 | 密码错误 |
| 301 | 信封失效（超时/篡改） | 307 | 账号已封禁 |
| 302 | 验证码解不开（换站/损坏） | 308 | 账号已注销 |
| 303 | 验证码缺分隔符 | 309 | 验证邮件发送失败 |
| 304 | 四段不齐 | 310 | 验证邮件已发出，待验证 |
| 403 | 签名层拒绝（附 `VS/DC/HE` 子码） | | |

三个接口的入口写法一致，区别只在业务：

| 接口 | 输入 | 行为 |
|------|------|------|
| [logs/getlog.php](use/user/logs/getlog.php) | `authdata` + `authdata2` | 校验并下发 4 个 Cookie |
| [logs/getreg.php](use/user/logs/getreg.php) | `authdata`(用户名) + `password` + `email` | 建号（`u` + 8 位随机 ID、随机中文昵称、信誉分 80）、事务内写验证令牌、回吐新签发的验证码 |
| [logs/getretrieve.php](use/user/logs/getretrieve.php) | `authdata`(用户名) + `password` + `email` | 校验密码后重新签发验证码，不改库 |

### 登录成功写下的 Cookie

| Cookie | 内容 | 谁读 |
|--------|------|------|
| `<站点指纹>_log` | 2 小时信封（**唯一有效凭据**） | `cofd/tauth.php`、各页面网关段 |
| `mokim_log_expire` | 过期时间戳 | 前端倒计时、`YhMokTTisWithin180s()` 续期提示 |
| `mokim_usergname` | 昵称 | 页面直接展示，省一次查库 |
| `mokim_useremail` | 绑定邮箱 | 设置页展示 |

后三个纯属展示缓存，**篡改它们不影响鉴权**：所有权限判定只认第一个。`logout.php` 也只需把第一个置空过期。

### 下游怎么用这份凭据

两种消费方式，都在 `cofd/tauth.php` 里：

- **页面网关段** — `index.php`、`online.php`、`ranking.php`、`use/setting/`、`use/matchs/` 顶部都有一段相同的解码逻辑：取 Cookie → 拆 2 小时信封 → `encrypt(..., D)` → 取第三段得 `userid`，落到 `$qx_max_tmp1`（是否登录）与 `$q_suname`（用户 ID）两个变量上。失败不跳转，而是降级为游客视图（`getStableVisitorName()` 生成稳定游客名）。
- **接口双向校验** — `conbine_auth_towdouble($userid)` 供写接口使用（如 [api/applyinfo/index.php](api/applyinfo/index.php)）：请求体里的用户 ID 也是一个信封，解出来后必须与 Cookie 解出的 ID **完全相等**才放行，防止拿着自己的 Cookie 去改别人的资料。

排位身份也从这里派生：`index.php` 用 `$q_suname` 调 `make_identity_ticket()` 签 HMAC 票据交给 Node，联机侧因此不需要读任何 Cookie。

### 安全中间件

登录页是全站唯一挂满三层中间件的页面。`use/user/index.php` 开头两行、结尾三个 `script` 就是全部接线：

```php
require($_SERVER['DOCUMENT_ROOT'] . '/cofd/SilentVerify.php');
SilentVerify::protect();   // 必须在任何输出之前
```

```html
<script src="../../assets/console.js"></script>          <!-- 反调试 -->
<script src="../../assets/authwrite.js"></script>        <!-- 信封加密 -->
<script src="../../assets/YHMOLKFETCH_SDK.js"></script>  <!-- 签名请求 -->
```

#### `cofd/SilentVerify.php` — 页面级人机验证

无感式挑战，站位在**页面渲染之前**。未通过时直接输出验证页并 `exit`，登录表单根本不会出现在 HTML 里。

打分制，四类信号累加，阈值 35：

| 维度 | 权重 | 看什么 |
|------|------|--------|
| 行为 | ≤100 | 访问次数、停留时长、鼠标移动、点击、滚动深度、访问频率 |
| PoW | +30 | Web Worker 暴力搜 `sha256(challenge + nonce)` 前 5 位为 0，超时 5 秒 |
| 浏览器 | ≤60 | Canvas / WebGL / AudioContext / 字体指纹是否可取；命中 `HeadlessChrome`、`PhantomJS` 扣 50，命中 `bot`、`curl`、`python` 等扣 30 |
| 环境 | ≤30 | 语言与时区是否自洽（`zh` ↔ `Asia/Shanghai`）、三个 `Accept` 头是否齐全、Referer 是否同站 |
| 时序 | ≤15 | 请求间隔是否落在 1–600 秒，页面加载耗时是否落在 100–5000ms |

分数不够会退让三次（延迟 500/1000/1500ms 重试），仍不过则降级为算术验证码（两位数加减乘）。通过后写 session，**2 小时内不再验**。同指纹 5 分钟内失败 3 次，封 30 分钟。

配置项可在调用时覆盖：`SilentVerify::protect(['pow_difficulty' => 6, 'behavior_threshold' => 45])`。

#### `cofd/SecuritySigner.php` + `assets/YHMOLKFETCH_SDK.js` — 接口级签名

三个 `logs/*.php` 的第一行都是它。一次调用同时验四样东西：**请求头加密、设备凭证、HMAC 签名、CSRF 令牌**。

密钥是**每会话动态生成、90 秒过期**的：`bin2hex(random_bytes(32))` 做密钥，8 字节盐，算法从 `hmac_sha256 / sha384 / sha512` 里随机挑一个。前端不内置任何密钥，而是向 [api/authsign/gettoken.php](api/authsign/gettoken.php) 取一段**当场生成并混淆**的签名函数：

```
GET /api/authsign/gettoken.php
  ├─ 同源校验（Referer / Origin / Sec-Fetch-Site）
  ├─ 浏览器特征评分（Accept 系列 + UA + Cookie）
  └─ 返回 { signer: <混淆后的 JS>, expire, csrft, encrypt_key }
```

`signer` 由 `octha/obfuscator` 混淆，并被绑上**域名白名单**与**过期时间戳** —— 拷到别的域名或过期后执行会直接抛异常。前端 `eval` 它拿到签名函数，密钥只在闭包里存活 90 秒。混淆库缺失时回落到 `basicLock()` 的域名 + 时间双重 `if` 包裹。

单次请求前端做四步（`securedApiCallInternal`）：

1. **设备凭证** — Canvas / WebGL / Audio / 字体 + 屏幕 + 时区 + 硬件并发等拼接后 SHA-256，取前 32 位，有效期 24 小时。
2. **签名** — 参数按 key 排序、`k=urlencode(v)` 拼接、尾接盐，HMAC 后得 `X-API-Signature`，附带 `Timestamp`、`Nonce`。签名缓存 30 秒，同参并发请求共用一份。
3. **头部混淆** — 上述 7 个头的**键名随机重命名、值随机洗牌**，洗牌轨迹与字段映射写进 `meta`。
4. **头部加密** — 混淆结果 + `meta` 整体 AES-256-GCM 加密，最终只发出两个头：

```http
X-Secure-Payload: <base64(iv + ciphertext + tag)>
X-Protocol-Version: 1
```

服务端 `decryptAndRestoreHeaders()` 解密后逆放洗牌、按 `fieldMap` 还原键名，写回 `$_SERVER`，后续逻辑照常读 `HTTP_X_API_SIGNATURE`。**中间人看不到有哪些安全头，更看不出哪个值属于哪个字段。**

其余校验：

- 签名时间戳偏差 > 60 秒拒绝；`nonce` 按 `md5(nonce + IP)` 存 session 5 分钟，重复即判重放（`VS007`）。
- 设备凭证 10 分钟内有效、按凭证限速 60 次/分钟、10 秒内换 IP 判异常（`DC005`–`DC008`）。
- CSRF 令牌 = `HMAC(设备指纹 + 时间槽)` + 一位时间槽字母（A–Z），120 秒一槽，接受当前与上一槽。设备指纹变了令牌自动失效，无需服务端存表。

错误码按层分组，便于定位：`HE***` 头部解密、`DC***` 设备凭证、`VS***` 签名与 CSRF。SDK 认得其中几类并**自动自愈**：撞上 `HEADER_DECRYPT_FAILED`、`SESSION_EXPIRED`、`DEVICE_CREDENTIAL_EXPIRED` 时重取签名器或重算设备凭证后原样重放一次，用户无感。

`window.yhmolk_fetchpull(url, data)` 是唯一出口，内部串行化 —— 同一时刻只有一个请求在飞，避免并发把 nonce 与签名缓存搅乱。

#### `assets/console.js` — 反调试

`ConsoleDetector` 经 obfuscator 混淆，靠 `debugger` 语句耗时与窗口尺寸差判断开发者工具是否打开。登录页同时把 `console.log/info/warn/error` 覆盖成空函数（[use/user/index.php:802-807](use/user/index.php#L802-L807)），压掉信封与签名过程中的一切痕迹。

这是**提高门槛**，不是安全边界 —— 真正的防线是服务端的签名校验与库里的 `password_verify`。

### 邮箱验证

`setting.php` 里 `$setting_mail_array['valid'] == 1` 时启用。注册与登录都会触发：

- **注册** — 建号事务内写 `mok_email_verify`（15 分钟令牌），提交后发信。发信失败不回滚账号，只是 `need_verify` 为 `false`。
- **登录** — 若 `bdmail` 为空，复用未过期的令牌、或删旧建新，发信后返回 310 **中止登录**，不下发任何 Cookie。
- **点链接** — [verify_email.php](use/user/verify_email.php) 按 `uid + token` 查表，事务内 `UPDATE mok_user SET bdmail` 并删掉验证记录，输出结果页。过期或令牌不存在都给明确文案。

`mok_email_verify.user_id` 上有唯一索引，一个用户同时只可能有一条待验证记录。

> **运维注意**：开了邮箱验证但 SMTP 没配好，未绑定邮箱的账号会卡在 309 无法登录。要么把 SMTP 配通，要么在后台「站点配置」里关掉邮箱验证。

### 部署要点

- `cofd/` 必须禁止 HTTP 访问（`.htaccess` 已带 `RedirectMatch 403 ^/(cofd|vendor|phpmailer|node_modules)/`，Nginx 见 `install/nginx-rewrite.conf`）。
- `SecuritySigner` 与 `SilentVerify` 都**依赖 PHP session**。多机部署要么开会话粘滞，要么把 session 换成 Redis，否则会随机 403。
- `octha/obfuscator` 由 `composer install` 装好；缺了不会挂，只是签名函数退化为基础锁。
- 签名层依赖客户端时钟，偏差超 60 秒会一直 `VS006`，建议服务器开 NTP。
- 登录页的四位图形验证码是**纯前端**的，只防误点；真正的人机与自动化防护由 `SilentVerify` 和 `SecuritySigner` 承担。

---

## 目录结构

```
.
├── assets/core/            引擎与核心模块（浏览器 + Node 双端可用）
│   ├── engine.js           规则引擎：走法、胜负、迷雾、序列化
│   ├── ai.js               人机：αβ 搜索 + 历史启发，4 档难度
│   ├── ui.js               棋盘渲染、音效、结算编排
│   ├── rating.js           六维度赛后评分
│   ├── anim.js             胜负动画
│   ├── scorefx.js          评级揭示动画
│   ├── replay.js           录像编解码（FHQR）
│   ├── fen.js              局面序列化（FHQ1）
│   ├── levels.js           教程与残局关卡数据
│   ├── goal.js             关卡目标 DSL
│   ├── play.js             关卡运行器
│   ├── endgame.js          残局页逻辑
│   ├── editor.js           摆子编辑器
│   ├── puzzle.js           关卡校验
│   └── wsmanager.js        全局 socket 单例
│
├── server.js               联机服务端：房间、匹配池、限流、结算队列
├── api/                    PHP 接口（票据签发、排位读写、排行榜）
├── cofd/                   服务端公共库（仅 require，不可 HTTP 访问）
├── admin/                  后台（元数据驱动）
│   ├── tables.php          ★ 表配置，唯一需要维护的文件
│   ├── lib/                引擎、introspect、鉴权、配置读写
│   └── views/              页面模板
├── install/
│   ├── inl.sql             建库脚本
│   └── nginx-rewrite.conf  Nginx 伪静态规则
│
├── index.php               大厅（房间列表、排行榜、菜单）
├── bot.html                人机
├── localversus.html        本地双人
├── online.php              联机娱乐
├── ranking.php             排位竞技
├── replay.php              录像回放
├── tutorial.html           新手教程
└── endgame.html            残局与编辑器
```

---

## 技术要点

**零构建** — 所有核心模块都是 IIFE，同时挂 `window` 和 `module.exports`，浏览器 `script` 直接引、Node `require` 也能用。没有打包器、没有编译步骤，改完刷新即生效。

**权威服务端** — 客户端不算规则。所有走子交给 `server.js` 上的引擎实例判定，再把结果按视角投影下发。

**按视角投影的二进制协议** — `netEncode(viewer)` 把局面压成字节流：40 枚棋子每枚 2 字节（类型 3 bit、存活 / 继位 / 复活各 1 bit、坐标各 5 bit），不可见的棋子坐标写越界哨兵 31,31。加上标志位与许可区共约 100 字节，Base64 后下发。当前视角不可见的信息不包含在报文中，修改前端无法获取。

**评分只在服务端算** — 引擎在既有事件点累加计数器，对局结束时一次性汇总。客户端由 `netApply` 重建的局面没有统计数据，因此算不出评分，只负责渲染。

**AI 硬实时** — 自适应预算按战术紧要度（有子可吃 / 残局 / 王被将）判定，而非按分支数：本棋分支因子恒定约 110，无法据此区分局面复杂度。超时返回当前最优，异常回落随机合法着法，`decide` 全程不改动引擎状态。

**分级限流** — 握手阶段按 IP 拦连接洪泛（访客 / 登录不同配额），每连接再套令牌桶按事件限速，低频无害事件豁免。超频丢弃并回 ack，不断连。

---

## 安全须知

部署前请逐条确认：

- [ ] `.env`、`config.php` 已填真实值
- [ ] 已按上文配好 Nginx 伪静态或确认 `.htaccess` 生效，并自查
- [ ] `mok_admin` 里已有账号，且设置了强口令（不预置默认密码）
- [ ] `install/` 目录已删除
- [ ] `API_SECRET_KEY_mok` 是随机生成的，不是示例值
- [ ] 生产环境关闭 PHP 的 `display_errors`

## 常见问题

**联机连不上？**
检查 `.env` 里 `WS_LINKING_ADDRESS` 是否填的是**浏览器能访问到的地址**（不是 `127.0.0.1`），以及防火墙有没有放行该端口。HTTPS 站点必须用 `wss://`，建议直接启用 `install/nginx-rewrite.conf` 里的反代段。

**排位不入库？**
`API_SECRET_KEY_mok` 必须在 `.env` 里配好，且 Node 与 PHP 读的是同一份。`PHP_API_BASE` 要能从 Node 所在机器访问到。结算走异步队列并带指数退避重试，短暂失败会自动补上。

**改了 `.env` 没生效？**
Node 只在启动时读取。改完要重启：`pm2 restart fenghuoqi`。PHP 侧是每次请求读取，即时生效。

**后台表头显示的是英文列名？**
说明那一列的 `COMMENT` 里没有可识别的标签。可以直接给列加注释，或在 `admin/tables.php` 的 `labels` 里覆盖。

---

## 许可证

[MIT](LICENSE)

---

## 致谢

第三方依赖：[socket.io](https://socket.io/)、[Express](https://expressjs.com/)、[PHPMailer](https://github.com/PHPMailer/PHPMailer)、[AWS SDK for PHP](https://github.com/aws/aws-sdk-php)、[phpdotenv](https://github.com/vlucas/phpdotenv)、[Chart.js](https://www.chartjs.org/)、[Font Awesome](https://fontawesome.com/)