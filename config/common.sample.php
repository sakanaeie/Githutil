<?php

// TODO
// * config体系の変更
// * gmailを利用しないようにする
// * controllerの処理を別の場所に移動
//   * slackでのみ通したい処理があるので、メッセージ生成処理を独立させる

define('SEND_MODE_MAIL',  1);			// メール
define('SEND_MODE_API',   2);			// 外部アプリAPI
define('SEND_MODE_SLACK', 3);			// SlackIncomingWebHooks
define('SEND_MODE', SEND_MODE_SLACK);	// メッセージ送信先
