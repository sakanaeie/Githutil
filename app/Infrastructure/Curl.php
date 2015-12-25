<?php

/**
 * CURLのヘルパー
 */

namespace Githutil\Infrastructure;

use \Githutil\Infrastructure\Logger;

class Curl
{
	/**
	 * 実行
	 *
	 * @param  string $url     URL
	 * @param  array  $post    送信データ
	 * @param  bool   $is_json JSONで送信するかどうか
	 * @return string $result  結果
	 */
	public static function execute($url, $post, $is_json = true)
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST,           true);

		if ($is_json) {
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post));
			curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
		} else {
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}

		$result = curl_exec($ch);
		curl_close($ch);

		Logger::info(print_r($result, true));

		return $result;
	}
}
