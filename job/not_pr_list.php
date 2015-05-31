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

// PRしてないブランチを取得する
$client = new \Githutil\Model\Github\PRWatcher(GITHUB_ACCESS_TOKEN, $repo_owner, $repo_name) ;
try {
	$br_arr = $client->getNotPullRequestBranches();
} catch (\Exception $e) {
	exit("ブランチ取得に失敗しました、引数を確認してください\n");
}

// メールを送信する
if (0 < count($br_arr)) {
	$mail_body  = "(tumbleweed) github branch list (NotInPR) (tumbleweed)\n";
	$mail_body .= "https://github.com/${repo_owner}/${repo_name}/branches\n";
	$mail_body .= implode("\n", $br_arr) . "\n";
	\Githutil\Infrastructure\Gmail::send(
		GMAIL_ACCOUNT_NAME,
		GMAIL_PASSWORD,
		GMAIL_SUBJECT,
		\Githutil\Model\Github\EmoConv::toSkype($mail_body),
		GMAIL_TO,
		GMAIL_FROM
	);
}
