<?php
//conf/db.php
declare(strict_types=1); //禁止php对对象，属性进行强制类型转换    

//全局错误处理基准与会话基线
ini_set('display_errors','0');  //禁止展示报错信息
ini_set('log_errors','1');     //开启日志记录
error_reporting(E_ALL);        //记录所有报错类型

if (session_status() === PHP_SESSION_NONE) {
    session_name('VULNLAB_NEW'); 
    session_set_cookie_params([
        'lifetime' => 0,                            //关闭浏览器session立即失效
        'path'     => '/vulnlab',                          //session生效路径
        'secure'   => !empty($_SERVER['HTTPS']),    //session只通过https协议传输，正常是true
        'httponly' => true,                         //只允许http协议读取session，js无法读取
        'samesite' => 'Lax',                        //session同源策略，Lax:跨站的顶级导航 GET 请求可以带，其他跨站请求不带
    ]);
    session_start();                                //所有session安全配置设置完毕后，才会启动session
}

//安全响应头
header('X-Content-Type-Options: nosniff'); //不允许浏览器MIME嗅探
header('X-Frame-Options: SAMEORIGIN');   //不允许嵌入跨站的iframe，防止点击劫持
header('Referrer-Policy: same-origin');  //跳转外域时不带完整referer，避免完整url信息泄露
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; script-src 'self'; style-src 'self' 'unsafe-inline'");
//规定各类资源只能从本域加载

//数据库全局配置
const DB_HOST     = '127.0.0.1';
const DB_PORT     = 3306;
const DB_NAME     = 'vulnlab';
const DB_USER     = 'vulnlab';     //改为最小权限应用账号
const DB_PASS     = 'Vulnlab@123';
const DB_CHARSET  = 'utf8mb4';

function db(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;                             //先校验pdo对象是否存在，避免重复连接
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=%s',
    DB_HOST, DB_PORT, DB_NAME, DB_CHARSET);
    try{
    $pdo = new PDO($dsn, DB_USER, DB_PASS,[
        PDO::ATTR_ERRMODE            =>PDO::ERRMODE_EXCEPTION,  //规定数据库出错时的处理方式，抛出异常，可以try/catch捕获，易于调试
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       //定义数据库查询结果的返回格式，assoc只返回关联数组，不带列名，省内存
        PDO::ATTR_EMULATE_PREPARES   => false,                  //手动关闭模拟预处理模式，确保真预处理模式
    ]);
    } catch (PDOException $e) {
        error_log('[DB] ' . $e->getMessage());
          http_response_code(500);
          exit('系统繁忙，请稍后重试');
    }
    return $pdo;
}

//转义所有输出
function e(?string $s): string {  //先判断入参是否严格为字符串类型
    return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');   //单双引号也转义，非法UTF-8字符替换成"?"
}

//鉴权
function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: /vulnlab/login.php');
        exit;
    }
}

function require_admin(): void {
    require_login();
    if (($_SESSION['role'] ?? 0) !== 1) {
        http_response_code(403);
        exit('403 Forbidden');
    }
}
function current_user_id(): int {
    return (int)($_SESSION['user_id'] ?? 0);
}

function current_user_role(): int {
      return (int)($_SESSION['role'] ?? 0);
  }

//CSRF
function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32)); //若没有csrf token，则随机生成
    }
    return $_SESSION['_csrf'];
}

function csrf_field(): string {
      return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';    //生成CSRF隐藏表单字段，且转义csrf_token
  }


function csrf_check(): void {
    $t = $_POST['_csrf'] ?? '';
    if (!is_string($t) || !hash_equals($_SESSION['_csrf'] ?? '', $t)) {  //使用hash_equals()比对用户传递的_csrf，防时序攻击
        http_response_code(400);
        exit('CSRF token invalid');
    }
}

//登录限制
 function login_throttle_check(string $ip): void {      //检查当前ip是否被限制
      $key  = 'login_fail_' . hash('sha256', $ip);      //生成ip的key值，并存入session，默认都是0
      $info = $_SESSION[$key] ?? ['n' => 0, 'until' => 0];
      if ($info['until'] > time()) {
          http_response_code(429);
          exit('登录尝试过于频繁，请 ' . ($info['until'] - time()) . ' 秒后再试');
      }
  }
  function login_throttle_fail(string $ip, int $max = 5, int $lockSec = 600): void {     //锁定ip方法，默认5次机会，锁定10分钟
      $key  = 'login_fail_' . hash('sha256', $ip);
      $info = $_SESSION[$key] ?? ['n' => 0, 'until' => 0];
      $info['n']++;
      if ($info['n'] >= $max) {
          $info['until'] = time() + $lockSec;
          $info['n']     = 0;
      }
      $_SESSION[$key] = $info;
  }
  function login_throttle_reset(string $ip): void {    //清除失败记录方法
      unset($_SESSION['login_fail_' . hash('sha256', $ip)]);
  }

//SSRF  
function safe_fetch(string $url, int $maxBytes = 1048576, int $timeout = 5): string {
      $parts = parse_url($url);                         //拆分url，返回关联数组
      if (!$parts || empty($parts['scheme']) || empty($parts['host'])) {     //检查sheme和host必须存在
          throw new RuntimeException('URL 不合法');
      }
      if (!in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {    
        //协议白名单，先转小写再检查是否为http/https,true为严格比较===，值和类型均得相同
          throw new RuntimeException('仅允许 http/https 协议');
      }

       $host = $parts['host'];

  // 去掉 IPv6 字面量的中括号 [::1] → ::1
  if (strlen($host) > 2 && $host[0] === '[' && substr($host, -1) === ']') {
      $host = substr($host, 1, -1);
  }

  // 如果 host 本身就是 IP，直接用它做后续黑名单校验
  if (filter_var($host, FILTER_VALIDATE_IP)) {      //判断host是否是ip
      $ips = [$host];
  } else {
      $ips = [];
      foreach ((array)@dns_get_record($host, DNS_A + DNS_AAAA) as $r) {
          if (!empty($r['ip']))   $ips[] = $r['ip'];
          if (!empty($r['ipv6'])) $ips[] = $r['ipv6'];
      }
      if (!$ips) throw new RuntimeException('DNS 解析失败');
  }
      //内网ip过滤
      foreach ($ips as $ip) {                 
          if (!filter_var($ip, FILTER_VALIDATE_IP,
                  FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
/*
FILTER_FLAG_NO_PRIV_RANGE 拒绝私有地址范围：
    10.0.0.0/8
    172.16.0.0/12
    192.168.0.0/16
    ::1（IPv6本地）

FILTER_FLAG_NO_RES_RANGE 拒绝保留地址范围：
    127.0.0.0/8（localhost）
    0.0.0.0
    169.254.0.0/16（链路本地）
    其他特殊保留段
 */
              throw new RuntimeException('禁止访问内网/保留地址');
          }
      }

      $ch = curl_init($url);          //初始化，创建请求句柄
      curl_setopt_array($ch, [
          CURLOPT_RETURNTRANSFER => true,                              //把响应内容作为字符串返回，存到变量里
          CURLOPT_FOLLOWLOCATION => false,                             //禁止跟随 302/301 重定向,防止重定向绕过SSRF
          CURLOPT_CONNECTTIMEOUT => $timeout,                          //TCP连接超时5秒，超过5秒直接放弃
          CURLOPT_TIMEOUT        => $timeout,                          //整个请求的超时时间5秒
          CURLOPT_PROTOCOLS      => CURLPROTO_HTTP | CURLPROTO_HTTPS,  //在 cURL 层面再限制一次协议http/https
          CURLOPT_MAXFILESIZE    => $maxBytes,                         //限制响应体最大 1MB，防止服务器返回超大文件耗尽内存
          CURLOPT_USERAGENT      => 'VulnLab-Fetcher/1.0',             //设置请求的 User-Agent 标识,告诉目标服务器这个请求来自 VulnLab-Fetcher，不设置的话默认是 curl/版本号
      ]);
      $body = curl_exec($ch);           //执行fetch请求
      if ($body === false) {            //请求失败处理，严格判断响应体
          $err = curl_error($ch);
          curl_close($ch);
          error_log('[safe_fetch] ' . $err);
          throw new RuntimeException('请求失败');
      }
      curl_close($ch);            //关闭 cURL 句柄，释放内存，无论成功失败都要关。
      return substr((string)$body, 0, $maxBytes);              //最后强转string返回body内容，并再次截取长度
  }
