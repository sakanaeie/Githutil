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
		$payload = http_build_query([
			'payload' => json_encode([
				'text' => $text,
			]),
		]);

		// APIを叩く
		\Githutil\Infrastructure\Curl::execute($url, $payload, false);
	}
}
