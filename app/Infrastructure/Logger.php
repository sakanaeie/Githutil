<?php

/**
 * Monologのラッパ
 */

namespace Githutil\Infrastructure;

use \Monolog\Logger as MonoLogger;
use \Monolog\Handler\RotatingFileHandler;

class Logger
{
	private static $log = null;

	private function __construct()
	{
	}

	public static function singleton()
	{
		if (is_null(self::$log)) {
			self::$log = new MonoLogger(LOG_CHANNEL);
			self::$log->pushHandler(new RotatingFileHandler(
				LOG_FILE_NAME,
				LOG_FILE_NUMBER,
				constant('\Monolog\Logger::' . LOG_LEVEL),
				true,
				0666
			));
		}

		return self::$log;
	}

	public static function build($args)
	{
		return call_user_func_array('sprintf', $args);
	}

	public static function debug()
	{
		self::$log->debug(self::build(func_get_args()));
	}

	public static function info()
	{
		self::$log->info(self::build(func_get_args()));
	}

	public static function notice()
	{
		self::$log->notice(self::build(func_get_args()));
	}

	public static function warning()
	{
		self::$log->warning(self::build(func_get_args()));
	}

	public static function error()
	{
		self::$log->error(self::build(func_get_args()));
	}

	public static function critical()
	{
		self::$log->critical(self::build(func_get_args()));
	}

	public static function alert()
	{
		self::$log->alert(self::build(func_get_args()));
	}

	public static function emergency()
	{
		self::$log->emergency(self::build(func_get_args()));
	}
}
