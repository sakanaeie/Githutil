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

try {
	// PRを取得する
	$client = new \Githutil\Model\Github\PRWatcher(GITHUB_ACCESS_TOKEN, $repo_owner, $repo_name);
	$pr_arr = $client->getPullRequests();

	// 保存済みPR、コメントidを取得する
	$saved_pr_info         = $client->getSavedPR();
	$saved_comment_id_info = $client->getSavedCommentId();

	// マージ済みPR検知し、メール本文を作成する
	$mail_body = '';
	$merged_pr_number_arr = array_diff(array_keys($saved_pr_info), array_keys($pr_arr));
	foreach ($saved_pr_info as $pr_number => $pr) {
		if (false !== array_search($pr_number, $merged_pr_number_arr)) {
			// マージ済みであるとき
			$mail_body .= sprintf("(beer) Merged %s (%s)\n", $pr['title'], $pr['user']);
			$mail_body .= "--------------------------------------------------------------------------------\n";
		}
	}

	// コメントを取得し、メール本文を作成する
	$all_comment_arr = [];
	foreach ($pr_arr as $pr_number => $pr) {
		// コメントを取得する
		$comment_arr     = $client->getComments($pr_number);
		$all_comment_arr = array_merge($all_comment_arr, $comment_arr);

		// メール本文を作成する
		foreach ($comment_arr as $comment) {
			$type    = $comment['type'];
			$content = $comment['content'];

			if (!isset($saved_comment_id_info[$type]) or false === array_search($content['id'], $saved_comment_id_info[$type])) {
				// 保存済みでないとき
				$is_deco = ($comment['is_new'] or false !== array_search(GITHUB_SHOW_URL_PATTERN_IN_COMMENT, $comment['emo']));

				if ($is_deco) {
					$mail_body .= '(*) ';
				} else {
					$mail_body .= '(mail) ';
				}
				$mail_body .= sprintf("(+%s) %s (%s)\n", $comment['review_status'], $pr['title'], $pr['user']['login']);
				if ($is_deco) {
					$mail_body .= sprintf("%s\n", $pr['html_url']);
				}
				$mail_body .= sprintf("%s (%s)\n", trim($content['body']), $content['user']['login']);
				$mail_body .= "--------------------------------------------------------------------------------\n";
			}
		}
	}

	// 保存する
	// (メール送信したのにPEAR-MAILが例外を投げ、連投される事故があるため、先に保存)
	$client->savePR($pr_arr);
	$client->saveCommentId($all_comment_arr);

	// メールを送信する
	if ('' !== $mail_body) {
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
	}
} catch (\Exception $e) {
	Logger::error($e->getMessage());
}
