<?php

require_once dirname(__FILE__) . '/../init/job_init.php';

$client = new \Github\Client();
$client->authenticate(GITHUB_ACCESS_TOKEN, \Github\Client::AUTH_HTTP_TOKEN);

// PRを取得する
$pr_arr = $client
	->api('pull_request')
	->all(GITHUB_REPO_OWNER, GITHUB_REPO_NAME);

foreach ($pr_arr as $pr) {
	if (GITHUB_ACCOUNT_NAME === $pr['user']['login']) {
		continue;
	}

	if (1 === preg_match(GITHUB_EXCLUDE_PR_PATTERN_IN_TITLE, $pr['title'])) {
		continue;
	}

	// コメントを取得する
	$comment_arr = $client
		->api('issue')
		->comments()
		->all(GITHUB_REPO_OWNER, GITHUB_REPO_NAME, $pr['number']);

	$is_reviewed = false;
	foreach ($comment_arr as $comment) {
		if ($comment['user']['login'] === GITHUB_ACCOUNT_NAME and 1 === preg_match(GITHUB_PERMIT_PR_PATTERN_IN_COMMENT, $comment['body'])) {
			// 自分のコメントであり、許可の印があるとき
			$is_reviewed = true;
			break;
		}
	}

	if (!$is_reviewed) {
		printf("%s (%s)\n", $pr['title'], $pr['html_url']);
	}
}
