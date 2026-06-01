<?php
// session_start();

// $_SESSION = [];
// session_destroy();

// setcookie('user_info', '', time() - 3600);

// header('Location: Login.php');
// exit;
 require_once 'conf/db.php';

  $_SESSION = [];                                      //清除所有 Session 数据
  
  if (ini_get('session.use_cookies')) {    //检查 PHP 配置是否使用 Cookie 存 Session ID，一般都是 true，这里做个判断兼容特殊配置。         
      $params = session_get_cookie_params();   //获取当前 Session Cookie 的配置参数,用原来的参数来删除 Cookie，确保路径、域名等完全一致，不一致的话删除会失败。  
      setcookie(session_name(), '', time() - 42000,  //获取当前session名称并置空，时间设为过去
          $params['path'], $params['domain'],
          $params['secure'], $params['httponly']);
  }                                                  //cookie参数一致，浏览器才能正确删除
  session_destroy();    //销毁服务器中的session文件

  header('Location: login.php');
  exit;