<?php

/**
 * CURLのヘルパー
 */

namespace Githutil\Infrastructure;

class Curl
{
	/**
	 * 実行
	 *
	 * @param  string $url    URL
	 * @param  array  $post   送信データ
	 * @return string $result 結果
	 */
	public static function execute($url, $post)
	{
		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST,           true);
		curl_setopt($ch, CURLOPT_POSTFIELDS,     json_encode($post));
		curl_setopt($ch, CURLOPT_HTTPHEADER,     ['Content-Type: application/json']);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}
}
