<?php

require_once 'bootstrap.php';

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
$client = new \Githutil\Model\Github\PRWatcher(GITHUB_ACCESS_TOKEN, $repo_owner, $repo_name) ;
try {
	$pr_arr = $client->getPullRequests();
} catch (\Exception $e) {
	exit("PR取得に失敗しました、引数を確認してください\n");
}

// 保存済みPR、コメントidを取得する
$saved_pr_info         = $client->getSavedPR();
$saved_comment_id_info = $client->getSavedCommentId();

// マージ済みPR検知し、メール本文を作成する
$mail_body = '';
$merged_pr_number_arr = array_diff(array_keys($saved_pr_info), array_keys($pr_arr));
foreach ($saved_pr_info as $pr_number => $pr) {
	if (false !== array_search($pr_number, $merged_pr_number_arr)) {
		// マージ済みであるとき
		$mail_body .= sprintf("(beer) Merged *%s*, author:%s\n", $pr['title'], $pr['user']);
		$mail_body .= "\n----------------------------------------\n";
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

		if (false !== strpos($content['user']['login'], 'jenkinsbot')) {
			// ジェンキンスポットのときは飛ばす
			continue;
		}

		if (!isset($saved_comment_id_info[$type]) or false === array_search($content['id'], $saved_comment_id_info[$type])) {
			// 保存済みでないとき
			if ($comment['is_new']) {
				$mail_body .= '(*) ';
			}
			$mail_body .= sprintf("(+%s) *%s*, %s < %s\n", $comment['review_status'], $pr['title'], $pr['user']['login'], $content['user']['login']);
			$mail_body .= sprintf("%s\n\n", $content['html_url']);
			$mail_body .= sprintf("%s\n\n", trim($content['body']));
			$mail_body .= "\n----------------------------------------\n";
		}
	}
}

// メールを送信する
if ('' !== $mail_body) {
	\Githutil\Infrastructure\Gmail::send(
		GMAIL_ACCOUNT_NAME,
		GMAIL_PASSWORD,
		GMAIL_SUBJECT,
		\Githutil\Model\Github\GithubEmoticon::toSkype($mail_body),
		GMAIL_TO,
		GMAIL_FROM
	);
}

$client->savePR($pr_arr);
$client->saveCommentId($all_comment_arr);
