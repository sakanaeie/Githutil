<?php

namespace Githutil\Controller;

use Githutil\Model\Github\PRWatcher;

class GithubController
{
	/**
	 * index
	 */
	public function index()
	{
		if (!isset($_GET['owner']) or !isset($_GET['repo'])) {
			exit('Invalid Argument');
		}
		$owner = $_GET['owner'];
		$repo  = $_GET['repo'];

		$client = new PRWatcher(GITHUB_ACCESS_TOKEN, $owner, $repo);

		try {
			$pr_arr = $client->getPullRequests();
		} catch (\Exception $e) {
			exit('Repository Access Denied');
		}

		$name_list = $GLOBALS['APP_DEFINE']['GITHUB_USER_NAME_LIST'];
		foreach ($pr_arr as $pr_number => $pr) {
			// コメントを取得する
			$comment_arr = $client->getComments($pr_number, PRWatcher::COMMENT_GET_PR);

			// 最後のコメントを取得する
			$last_comment = end($comment_arr);
			reset($comment_arr);

			// レビュー者を洗い出す
			$review_status = 0;
			$reviewer_arr  = [];
			foreach ($comment_arr as $comment) {
				if (0 === $comment['review_status']) {
					$reviewer_arr = [];
				} elseif ($review_status !== $comment['review_status']) {
					$review_status  = $comment['review_status'];
					$user_name      = $comment['content']['user']['login'];
					$reviewer_arr[] = isset($name_list[$user_name]) ? $name_list[$user_name] : $user_name;
				}
			}
			$reviewer_str = implode(', ', $reviewer_arr);

			printf(
				'<div>(+%s) <a href="%s">%s</a> (%s)<br>(reviewer : %s)<div><br>',
				isset($last_comment['review_status']) ? $last_comment['review_status'] : 0,
				$pr['html_url'],
				$pr['title'],
				isset($name_list[$pr['user']['login']]) ? $name_list[$pr['user']['login']] : $pr['user']['login'],
				$reviewer_str
			);
		}
	}
}
