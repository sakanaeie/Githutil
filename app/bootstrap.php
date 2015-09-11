<?php

date_default_timezone_set('Asia/Tokyo');

mb_language('japanese');
mb_internal_encoding('utf8');

define('PROJECT_NAME', 'Githutil');
define('ROOT_PATH', dirname(__FILE__) . '/..');

require_once ROOT_PATH . '/vendor/autoload.php';
require_once ROOT_PATH . '/config/common.php';
require_once ROOT_PATH . '/config/github.php';
require_once ROOT_PATH . '/config/gmail.php';
require_once ROOT_PATH . '/config/chatbot.php';
require_once ROOT_PATH . '/config/log.php';

\Githutil\Infrastructure\Logger::singleton();	// 内部でnewする
