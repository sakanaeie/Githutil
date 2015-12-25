<?php

/**
 * Incoming WebHooks
 */

namespace Githutil\Model\Slack;

class WebHooks
{
	/**
	 * APIを叩く
	 *
	 * @param string $url  URL
	 * @param string $text 本文
	 */
	public static function callApi($url, $text)
	{
		// json形式のpayloadを構築する
		$json = json_encode([
			'text' => $text,
		]);

		// postデータを構築する
		$payload = "payload={$json}";

		// APIを叩く
		\Githutil\Infrastructure\Curl::execute($url, $payload, false);
	}
}
