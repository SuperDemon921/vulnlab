-- ========================================
-- 渗透测试靶场数据库初始化脚本
-- 数据库：sqltest
-- ========================================

CREATE DATABASE IF NOT EXISTS `vulnlab` DEFAULT CHARACTER SET utf8mb4;
USE `vulnlab`;

-- ----------------------------------------
-- 用户表（密码明文存储，故意不加密）
-- ----------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `username`   VARCHAR(50)  NOT NULL UNIQUE,
    `password`   VARCHAR(50)  NOT NULL,
    `email`      VARCHAR(100) NOT NULL,
    `role`       TINYINT      NOT NULL DEFAULT 0 COMMENT '0=普通用户 1=管理员',
    `avatar`     VARCHAR(255) DEFAULT 'uploads/default.png',
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `users` (`username`, `password`, `email`, `role`) VALUES
('admin',   'admin123',    'admin@test.com',   1),
('alice',   'alice123',    'alice@test.com',   0),
('bob',     'bob123',      'bob@test.com',     0),
('charlie', 'charlie123',  'charlie@test.com', 0);

-- ----------------------------------------
-- 文章表
-- ----------------------------------------
DROP TABLE IF EXISTS `articles`;
CREATE TABLE `articles` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `title`      VARCHAR(255) NOT NULL,
    `content`    TEXT         NOT NULL,
    `author_id`  INT          NOT NULL,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `articles` (`title`, `content`, `author_id`) VALUES
('欢迎来到靶场',      '这是一个用于渗透测试练习的模拟系统，包含多种常见漏洞，请勿在生产环境中使用类似代码。', 1),
('PHP 开发常见误区',  '直接拼接用户输入到 SQL 语句是非常危险的行为，本文介绍几种常见的不安全写法。',         2),
('文件上传注意事项',  '上传功能如果只验证文件后缀而不验证文件内容，攻击者可以轻松绕过限制上传恶意文件。',   2),
('XSS 攻击原理',      '跨站脚本攻击（XSS）分为反射型和存储型两种，本文通过示例演示其危害。',               3),
('越权漏洞案例分析',  '水平越权指普通用户访问其他用户的数据；垂直越权指普通用户访问管理员功能。',           1);

-- ----------------------------------------
-- 评论表（内容未转义，故意允许 XSS）
-- ----------------------------------------
DROP TABLE IF EXISTS `comments`;
CREATE TABLE `comments` (
    `id`         INT AUTO_INCREMENT PRIMARY KEY,
    `article_id` INT  NOT NULL,
    `user_id`    INT  NOT NULL,
    `content`    TEXT NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `comments` (`article_id`, `user_id`, `content`) VALUES
(1, 2, '写得不错，学到了很多！'),
(1, 3, '期待更多文章。'),
(2, 4, '直接拼接 SQL 真的太危险了。'),
(3, 3, '文件上传漏洞是重灾区。'),
(4, 2, '存储型 XSS 危害比反射型更大。');
