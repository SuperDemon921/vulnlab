<?php
const DB_HOST = '127.0.0.1';
const DB_PORT = 3306;
const DB_NAME = 'vulnlab';
const DB_USER = 'root';
const DB_PASS = '123456';
const DB_CHARSET = 'utf8mb4';

function db_conn():mysqli{
    $conn = new mysqli(DB_HOST,DB_USER,DB_PASS,DB_NAME);
    if ($conn->connect_error){
        die('数据库连接失败' . $conn->connect_error);
    }
    $conn->set_charset(DB_CHARSET);
    return $conn;
}
