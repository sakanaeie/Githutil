<?php

require_once 'bootstrap.php';

use \Githutil\Infrastructure\Logger;

// 引数を確認する
if (!isset($argv[1])) {
	exit("第一引数はレポジトリのオーナー名\n");
}
if (!isset($argv[2])) {
	exit("第二引数はレポジトリ名\n");
}
$repo_owner = $argv[1];
$repo_name  = $argv[2];

// PRを取得する
$client = new \Githutil\Model\Github\PRWatcher(GITHUB_ACCESS_TOKEN, $repo_owner, $repo_name);
try {
	$pr_arr = $client->getPullRequests();
} catch (\Exception $e) {
	exit("PR取得に失敗しました、引数を確認してください\n");
}

$mail_body = '';
foreach ($pr_arr as $pr) {
	$mail_body .= sprintf("%s (%s)\n", $pr['title'], $pr['user']['login']);
	$mail_body .= sprintf("%s\n\n", $pr['html_url']);
}

// メールを送信する
if ('' !== $mail_body) {
	$mail_body = "(waiting) github pull requrest list (waiting)\n\n" . $mail_body;
	try {
		$send_status = \Githutil\Infrastructure\Gmail::send(
			GMAIL_ACCOUNT_NAME,
			GMAIL_PASSWORD,
			GMAIL_SUBJECT,
			\Githutil\Model\Github\EmoConv::toSkype($mail_body),
			GMAIL_TO,
			GMAIL_FROM
		);
		if (true !== $send_status) {
			Logger::info($send_status->toString());
		}
	} catch (\Exception $e) {
		Logger::error($e->getMessage());
	}
}
