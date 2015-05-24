<?php

/**
 * Monologのラッパ
 */

namespace Githutil\Model;

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
}
