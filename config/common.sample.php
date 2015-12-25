<?php

define('SEND_MODE_MAIL',  1);			// メール
define('SEND_MODE_API',   2);			// 外部アプリAPI
define('SEND_MODE_SLACK', 3);			// SlackIncomingWebHooks
define('SEND_MODE', SEND_MODE_SLACK);	// メッセージ送信先
