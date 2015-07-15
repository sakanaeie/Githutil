<?php

namespace Githutil\Command\Controller;

use Githutil\Model\Github\PRWatcher;

class GithubController
{
	/**
	 * @var string レポジトリのオーナー名
	 */
	private $repo_owner;

	/**
	 * @var string レポジトリ名
	 */
	private $repo_name;

	/**
	 * @var \Githutil\Model\Github\PRWatcher
	 */
	private $client;

	/**
	 * コンストラクタ
	 *
	 * @param array $argv コマンドライン引数
	 */
	public function __construct(array $argv)
	{
		// 引数を確認する
		if (!isset($argv[1])) {
			exit("第一引数はレポジトリのオーナー名\n");
		}
		if (!isset($argv[2])) {
			exit("第二引数はレポジトリ名\n");
		}
		$this->repo_owner = $argv[1];
		$this->repo_name  = $argv[2];

		$this->client = new PRWatcher(GITHUB_ACCESS_TOKEN, $this->repo_owner, $this->repo_name);
	}

	/**
	 * PRしてないブランチ一覧をメール送信する
	 */
	public function notPRList()
	{
		try {
			$br_arr = $this->client->getNotPullRequestBranches();
		} catch (\Exception $e) {
			exit("github接続に失敗しました、引数を確認してください\n");
		}

		if (0 < count($br_arr)) {
			// メール本文を作成する
			$mail_body  = "(tumbleweed) github branch list (not in PR) (tumbleweed)\n";
			$mail_body .= "https://github.com/{$this->repo_owner}/{$this->repo_name}/branches\n";
			foreach ($br_arr as $br) {
				$mail_body .= "* {$br}\n";
			}

			// メールを送信する
			$this->client->sendMail($mail_body);
		}
	}

	/**
	 * PR一覧をメール送信する
	 */
	public function prList()
	{
		try {
			// PRを取得する
			$pr_arr = $this->client->getPullRequestsWithBlackListFilter();
		} catch (\Exception $e) {
			exit("github接続に失敗しました、引数を確認してください\n");
		}

		$mail_body = '';
		foreach ($pr_arr as $pr_number => $pr) {
			// 同チームの人間であるかどうか確認する
			$pr_user_name = $pr['user']['login'];
			$is_teammate  = isset($GLOBALS['APP_DEFINE']['GITHUB_USER_NAME_LIST'][$pr_user_name]);

			// コメントを取得する
			$comment_arr = $this->client->getComments($pr_number);

			// コメントを逆順に処理し、レビューリセット後のコメントのみ取得する
			$commented_user_arr = $agreed_user_arr  = [];
			foreach (array_reverse($comment_arr) as $comment) {
				if ($comment['is_reset']) {
					break;
				}
				$name = $comment['content']['user']['login'];

				// コメントしたユーザを取得する
				$commented_user_arr[] = $name;

				// レビューokを出したユーザを取得する
				if ($comment['is_agree']) {
					$agreed_user_arr[] = $name;
				}
			}

			// 最後のコメントを取得する
			$last_comment  = end($comment_arr);
			$review_status = isset($last_comment['review_status']) ? $last_comment['review_status'] : 0;

			// メール本文を作成する
			$mail_body .= sprintf("(+%s) %s (%s)\n", $review_status, $pr['title'], PRWatcher::convertUserName($pr_user_name));
			$mail_body .= sprintf("%s\n", $pr['html_url']);

			foreach ($GLOBALS['APP_DEFINE']['GITHUB_USER_NAME_LIST'] as $name => $disp_name) {
				if ($pr_user_name === $name) {
					continue;
				}

				if (false !== array_search($name, $agreed_user_arr)) {
					$user_status =  'agreed';
				} elseif (false !== array_search($name, $commented_user_arr)) {
					$user_status =  'commented';
				} else {
					$user_status =  '';
				}

				if (!$is_teammate and '' === $user_status) {
					// 同チームのPRでなく、これをレビューしてない人は表示しない
					continue;
				}
				$mail_body .= sprintf("%s : %s\n", $disp_name, $user_status);
			}

			$mail_body .= "--------------------------------------------------------------------------------\n";
		}

		// メールを送信する
		if ('' !== $mail_body) {
			$mail_body = "(waiting) github pull requrest list (waiting)\n\n" . $mail_body;
			$this->client->sendMail($mail_body);
		}
	}

	/**
	 * PRのコメントをメール送信する
	 */
	public function prWatcher()
	{
		try {
			// PRを取得する
			$pr_arr = $this->client->getPullRequests();
		} catch (\Exception $e) {
			exit("github接続に失敗しました、引数を確認してください\n");
		}

		// 保存済みPR、コメントidを取得する
		$saved_pr_number_arr   = $this->client->getSavedPRNumber();
		$saved_comment_id_info = $this->client->getSavedCommentId();

		// マージ済みPRを検知し、メール本文を作成する
		$mail_body = '';
		$merged_pr_number_arr = array_diff($saved_pr_number_arr, array_keys($pr_arr));
		foreach ($saved_pr_number_arr as $pr_number) {
			if (false !== array_search($pr_number, $merged_pr_number_arr)) {
				$pr = $this->client->getOnePullRequest($pr_number);
				if ($pr['merged']) {
					$state = '(beer) merged';
				} else {
					$state = '(ninja) ' . $pr['state'] . ' (not merge)';
				}
				$mail_body .= sprintf("%s %s (%s)\n", $state, $pr['title'], PRWatcher::convertUserName($pr['user']['login']));
				$mail_body .= "--------------------------------------------------------------------------------\n";
			}
		}

		// コメントを取得し、メール本文を作成する
		$all_comment_arr = [];
		foreach ($pr_arr as $pr_number => $pr) {
			// コメントを取得する
			$comment_arr     = $this->client->getComments($pr_number);
			$all_comment_arr = array_merge($all_comment_arr, $comment_arr);

			// メール本文を作成する
			foreach ($comment_arr as $comment) {
				$type    = $comment['type'];
				$content = $comment['content'];

				if (!isset($saved_comment_id_info[$type]) or false === array_search($content['id'], $saved_comment_id_info[$type])) {
					// 保存済みでないとき
					$is_deco    = ($comment['is_new'] or $comment['is_decorate']);
					$mail_body .= $is_deco ? '(*) ' : '(mail) ';
					$mail_body .= sprintf("(+%s) %s (%s)\n", $comment['review_status'], mb_strimwidth($pr['title'], 0, 80, '...'), PRWatcher::convertUserName($pr['user']['login']));
					$mail_body .= $is_deco ? sprintf("%s\n", $pr['html_url']) : '' ;
					$mail_body .= sprintf("%s (%s)\n", trim($content['body']), PRWatcher::convertUserName($content['user']['login']));
					$mail_body .= "--------------------------------------------------------------------------------\n";
				}
			}
		}

		// 保存する
		// (メール送信したのにPEAR-MAILが例外を投げ、連投される事故があるため、先に保存)
		$this->client->savePRNumber($pr_arr);
		$this->client->saveCommentId($all_comment_arr);

		// メールを送信する
		if ('' !== $mail_body) {
			$this->client->sendMail($mail_body);
		}
	}
}
