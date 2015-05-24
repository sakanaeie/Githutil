<?php

/**
 * Gmail送信
 */

namespace Githutil\Infrastructure;

class Gmail
{
	public static function send($account_name, $password, $subject, $body, $to = '', $from = '')
	{
		$params = [
			'host'     => 'tls://smtp.gmail.com',
			'port'     => 465,
			'auth'     => true,
			'protocol' => 'SMTP_AUTH',
			'debug'    => false,
			'username' => $account_name,
			'password' => $password,
		];

		$headers = [
			'To'      => $to,
			'From'    => $from,
			'Subject' => mb_encode_mimeheader($subject),
		];

		$body = mb_convert_encoding($body, 'ISO-2022-JP', 'auto');

		// 大変遺憾ながら、一旦一部エラーを無視させる
		$e_level = error_reporting();
		error_reporting($e_level & ~E_STRICT & ~E_DEPRECATED);
		$smtp = \Mail::factory('smtp',  $params);
		$smtp->send($to,  $headers,  $body);
		error_reporting($e_level);
	}
}
