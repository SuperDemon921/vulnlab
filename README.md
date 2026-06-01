# VulnLab — PHP 安全加固参考项目

> 一个从"漏洞百出"到"安全加固"的 PHP Web 应用，展示常见 Web 漏洞的修复方案与安全编码最佳实践。

![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)
![License](https://img.shields.io/badge/License-MIT-green)

---

## 项目简介

VulnLab 是一个博客系统，包含用户注册/登录、文章浏览/评论、文件上传、RSS 订阅、后台管理等典型功能。本项目是 [原始漏洞版本](https://github.com/SuperDemon921/vulnlab)（`vulnlab_old`）的**安全加固版**，将原版中刻意保留的 OWASP Top 10 漏洞逐一修复，可作为：

- **安全编码参考** — 了解每种漏洞的正确修复方式
- **代码审计对比** — 通过 `git diff` 对比新旧代码，直观理解漏洞与修复
- **防御方案学习** — 掌握纵深防御（Defense in Depth）的实践方法

---

## 漏洞修复对照表

| # | 漏洞类型 | 原版缺陷 | 修复措施 |
|---|---------|---------|---------|
| 1 | **SQL 注入** | 用户输入直接拼接 SQL 语句 | 全部改用 PDO 预处理语句 + 参数绑定 |
| 2 | **XSS（跨站脚本）** | 输出未转义，数据库内容直接渲染 | 统一使用 `e()` 函数（`htmlspecialchars`）转义所有输出 |
| 3 | **文件上传** | 黑名单过滤（仅拦截 `.php`）、保留原始文件名、未校验文件内容 | 后缀白名单 + MIME 类型校验 + `getimagesize()` 二次校验 + 随机文件名 |
| 4 | **水平越权** | `profile.php` 通过 `?id=` 参数查看任意用户资料 | 个人中心仅展示当前登录用户，移除 `id` 参数 |
| 5 | **垂直越权** | 后台页面无角色校验，任意登录用户可访问 | `require_admin()` 校验 `role === 1`，非管理员返回 403 |
| 6 | **CSRF** | 所有表单无 Token 校验，可被跨站伪造请求 | 所有 POST 表单嵌入 `csrf_token`，服务端 `hash_equals()` 防时序攻击 |
| 7 | **不安全反序列化** | Cookie `user_info` 使用 `serialize()`/`unserialize()`，可伪造角色 | 移除 Cookie 反序列化逻辑，角色信息仅存于服务端 Session |
| 8 | **SSRF** | `file_get_contents()` 无限制请求任意 URL（支持 `file://` 协议） | 协议白名单（仅 http/https）+ 内网/保留地址过滤 + 禁止重定向跟随 + cURL 协议二次限制 |
| 9 | **XXE** | `LIBXML_NOENT` 开启外部实体解析 | 关闭外部实体加载 + 拒绝 DOCTYPE 声明 + `LIBXML_NONET` 禁止网络访问 |
| 10 | **明文密码** | 数据库中密码明文存储 | 使用 `password_hash()`（bcrypt）哈希存储，`password_verify()` 验证 |
| 11 | **暴力破解** | 登录无频率限制 | IP 级别限流：5 次失败锁定 10 分钟（基于 Session 计数） |
| 12 | **会话安全** | Session 无安全属性，存在固定攻击风险 | HttpOnly + SameSite=Lax + Secure（HTTPS 时）+ 登录后 `session_regenerate_id()` |
| 13 | **信息泄露** | 数据库错误直接输出到页面，暴露表结构 | `display_errors=Off`，统一 500 通用错误页，错误写入日志 |
| 14 | **安全响应头缺失** | 无任何安全 Header | CSP + X-Content-Type-Options + X-Frame-Options + Referrer-Policy |
| 15 | **数据库权限** | 使用 `root` 账号连接数据库 | 专用最小权限应用账号 `vulnlab` |

---

## 目录结构

```
vulnlab/
├── conf/
│   └── db.php                  # 数据库连接 + 安全基础设施（鉴权/CSRF/SSRF防护/输出转义）
├── install/
│   ├── init.sql                # 数据库初始化脚本
│   └── seed_passwords.php      # 密码哈希生成工具
├── admin/
│   ├── index.php               # 后台首页（已添加角色鉴权）
│   ├── users.php               # 用户管理（预处理 + 输入校验）
│   ├── articles.php            # 文章管理
│   ├── comments.php            # 评论管理
│   ├── feed.php                # RSS 订阅管理（SSRF/XXE 已修复）
│   ├── delete.php              # 删除路由（CSRF + 权限 + 输入校验）
│   └── delete_feed.php         # 删除订阅处理器
├── uploads/                    # 文件上传目录
├── index.php                   # 首页文章列表
├── login.php                   # 登录（预处理 + 限流 + 会话安全）
├── register.php                # 注册（预处理 + 输入校验 + 密码哈希）
├── logout.php                  # 安全退出（彻底销毁 Session + Cookie）
├── article.php                 # 文章详情（预处理 + 输出转义）
├── comment.php                 # 评论提交（CSRF + 权限校验 + 文章存在性校验）
├── search.php                  # 搜索（预处理 + 输出转义）
├── profile.php                 # 个人中心（水平越权已修复 + 头像路径安全校验）
├── upload.php                  # 文件上传（白名单 + MIME 校验 + 图片解析校验）
├── browse.php                  # 网页浏览（safe_fetch 封装，内网过滤）
└── README.md
```

---

## 快速开始

### 环境要求

| 组件 | 版本要求 |
|------|---------|
| PHP | 7.4 或以上（推荐 8.0+） |
| MySQL / MariaDB | 5.7 / 10.3 或以上 |
| Web 服务器 | Apache / Nginx / phpstudy |

### 部署步骤

**1. 克隆项目**

```bash
git clone https://github.com/SuperDemon921/vulnlab.git
cd vulnlab
```

**2. 初始化数据库**

```bash
mysql -u root -p < install/init.sql
```

或在 phpMyAdmin 中直接执行 `install/init.sql`。

**3. 生成密码哈希**

```bash
php install/seed_passwords.php
```

该脚本会将预设用户的密码统一哈希处理。

**4. 修改数据库配置**

编辑 `conf/db.php`，根据本地环境修改连接信息：

```php
const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'vulnlab';
const DB_USER = 'vulnlab';
const DB_PASS = 'Vulnlab@123';
```

> 建议创建专用的最小权限数据库用户，而非使用 root。

**5. 启动 Web 服务器**

将项目目录配置为 Web 根目录后，浏览器访问：

```
http://localhost/vulnlab/
```

---

## 内置账号

| 用户名 | 密码 | 角色 |
|--------|------|------|
| `admin` | `admin123` | 管理员 |
| `alice` | `alice123` | 普通用户 |
| `bob` | `bob123` | 普通用户 |
| `charlie` | `charlie123` | 普通用户 |

---

## 安全基础设施详解

### 1. 输出转义 — `e()` 函数

```php
function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
```

所有输出到 HTML 的变量均经过该函数处理，阻断 XSS 攻击。`ENT_QUOTES` 同时转义单双引号，`ENT_SUBSTITUTE` 将非法 UTF-8 字符替换为 `?`。

### 2. 鉴权体系

```php
function require_login(): void { /* 检查 session user_id */ }
function require_admin(): void { /* 检查 role === 1 */ }
```

前台需登录的功能调用 `require_login()`，后台页面统一调用 `require_admin()`，从架构上杜绝越权。

### 3. CSRF 防护

```php
function csrf_token(): string { /* 生成随机 token 存入 session */ }
function csrf_field(): string  { /* 输出隐藏表单域 */ }
function csrf_check(): void    { /* hash_equals 防时序攻击比对 */ }
```

所有状态变更操作（POST）均需携带 CSRF Token。

### 4. SSRF 防护 — `safe_fetch()` 函数

多层纵深防御：
- **协议白名单**：仅允许 `http` / `https`
- **DNS 解析**：将域名解析为 IP 后再做内网校验（防止 DNS Rebinding）
- **内网过滤**：使用 `FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE` 拒绝私有/保留地址
- **禁止重定向**：`CURLOPT_FOLLOWLOCATION => false`（防止 30x 跳转到内网）
- **cURL 协议限制**：`CURLPROTO_HTTP | CURLPROTO_HTTPS`
- **响应体大小限制**：最大 1MB

### 5. 登录限流

```php
function login_throttle_check(string $ip): void { /* 检查是否被锁定 */ }
function login_throttle_fail(string $ip): void   { /* 累计失败次数 */ }
function login_throttle_reset(string $ip): void  { /* 成功后清除记录 */ }
```

同一 IP 在 5 次失败后锁定 10 分钟，防止暴力破解。

### 6. Session 安全

- Session 名称自定义（`VULNLAB_NEW`），避免框架默认名暴露
- `httponly: true` — JavaScript 不可读取
- `samesite: Lax` — 防止跨站请求携带 Session
- `secure: true`（HTTPS 时） — 仅加密传输
- 登录成功后 `session_regenerate_id(true)` — 防止 Session 固定

### 7. 安全响应头

```php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: SAMEORIGIN');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; script-src 'self'; style-src 'self' 'unsafe-inline'");
```

### 8. 文件上传安全

```
用户上传 → 后缀白名单校验 → MIME 类型校验 → getimagesize() 二次校验 → 随机文件名 → 仅允许图片格式
```

- **后缀白名单**：仅允许 `jpg / jpeg / png / gif / webp`
- **MIME 校验**：`finfo` 读取文件魔数，检测真实类型
- **图片解析校验**：`getimagesize()` 确认文件为有效图片
- **随机文件名**：`bin2hex(random_bytes(16))` 防止路径遍历和文件名冲突
- **权限控制**：上传后设置为 `0644`

---

## 对比学习建议

1. 将本项目与 [原始漏洞版](https://github.com/SuperDemon921/vulnlab) 的 `vulnlab_old` 分支对照阅读
2. 在 `article.php`、`login.php` 等文件中，原版代码以注释形式保留，方便对比
3. 关注 `conf/db.php`，这是安全基础设施的集中体现
4. 使用 Burp Suite / sqlmap 等工具对两个版本分别测试，体会修复效果

---

## 参考资料

- [OWASP Top 10 (2021)](https://owasp.org/Top10/)
- [OWASP 输入验证速查表](https://cheatsheetseries.owasp.org/cheatsheets/Input_Validation_Cheat_Sheet.html)
- [OWASP 会话管理速查表](https://cheatsheetseries.owasp.org/cheatsheets/Session_Management_Cheat_Sheet.html)
- [PHP 安全编程指南](https://phpsecurity.readthedocs.io/)
- [PortSwigger Web Security Academy](https://portswigger.net/web-security)

---

## License

MIT License — 仅供学习研究，禁止用于非法用途。
