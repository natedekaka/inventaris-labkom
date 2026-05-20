<?php

$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASS') ?: 'rootpass';
$database = getenv('DB_NAME') ?: 'inventaris_labkom';
$port = getenv('DB_PORT') ?: '3306';

define('DB_HOST', $host);
define('DB_USER', $user);
define('DB_PASS', $password);
define('DB_NAME', $database);
define('DB_PORT', $port);
