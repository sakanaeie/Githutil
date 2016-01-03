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
	 * @param string $url            URL
	 * @param string $text           本文
	 * @param array  $extend_payload 拡張ペイロード
	 */
	public static function send($url, $text, $extend_payload = null)
	{
		$payload = [];
		if (!is_null($extend_payload)) {
			$payload = $extend_payload;
		}
		$payload['text'] = $text;

		$query = http_build_query([
			'payload' => json_encode($payload)
		]);

		// APIを叩く
		\Githutil\Infrastructure\Curl::execute($url, $query, false);
	}
}
