<?php

namespace Githutil\Model\Github;

use \Githutil\Infrastructure\JsonFile;

class PRWatcher
{
	const PR_SAVE_FILE_PATH_TPL      = ROOT_PATH . '/storage/json/%s_%s_PRWatcher_pr_number.json';
	const COMMENT_SAVE_FILE_PATH_TPL = ROOT_PATH . '/storage/json/%s_%s_PRWatcher_comment_id.json';

	private $client;

	private $repo_owner;
	private $repo_name;

	private $fh_pr;
	private $fh_comment;

	/**
	 * コンストラクタ
	 *
	 * アクセストークンを利用して認証する
	 *
	 * @param string $access_token githubのアクセストークン
	 * @param string $repo_owner   リポジトリのオーナー名
	 * @param string $repo_name    リポジトリ名
	 */
	public function __construct($access_token, $repo_owner, $repo_name)
	{
		$this->client = new \Github\Client();
		$this->client->authenticate($access_token, \Github\Client::AUTH_HTTP_TOKEN);

		$this->repo_owner = $repo_owner;
		$this->repo_name  = $repo_name;

		$this->fh_pr      = new JsonFile(sprintf(self::PR_SAVE_FILE_PATH_TPL, $repo_owner, $repo_name));
		$this->fh_comment = new JsonFile(sprintf(self::COMMENT_SAVE_FILE_PATH_TPL, $repo_owner, $repo_name));
	}

	/**
	 * PRを取得する
	 *
	 * @return array $return_arr GithubAPI-PRのレスポンスから、不要なPRを除去したもの
	 */
	public function getPullRequests()
	{
		$pr_arr = $this->client
			->api('pull_request')
			->all($this->repo_owner, $this->repo_name);

		$return_arr = [];
		foreach ($pr_arr as $pr) {
			if (1 === preg_match(GITHUB_TARGET_PR_PATTERN_IN_TITLE, $pr['title'])) {
				// レビュー対象であるとき
				$return_arr[$pr['number']] = $pr;
			}
		}

		return $return_arr;
	}

	/**
	 * コメントを取得する
	 *
	 * PR内のファイルに対するもの、PR自体に対するもの、両方を取得しマージする
	 *
	 * @param  int   $pr_number  PR番号
	 * @return array $return_arr GithubAPI-Commentのレスポンスと、追加情報を含む配列
	 */
	public function getComments($pr_number)
	{
		// コメントを取得する (PR内のファイルに対するもの)
		$pr_comments = $this->client
			->api('pull_request')
			->comments()
			->all($this->repo_owner, $this->repo_name, $pr_number);

		// コメントを取得する (PR自体に対するもの)
		$issue_comments = $this->client
			->api('issue')
			->comments()
			->all($this->repo_owner, $this->repo_name, $pr_number);

		// コメントをマージして、日付昇順にする
		$comment_arr = array_merge($pr_comments, $issue_comments);
		usort($comment_arr, 'self::dateAscSort');

		// 情報を追加する
		$is_new = true;
		$review_status = 0;
		$return_arr    = [];
		foreach ($comment_arr as $comment) {
			if (false !== strpos($comment['user']['login'], 'jenkinsbot')) {
				// ジェンキンスポットのときは飛ばす
				continue;
			}

			if (isset($comment['diff_hunk'])) {
				// PR内のファイルに対するものであるとき
				$type = 'file';
			} else {
				// PR自体に対するものであるとき
				$type = 'pr';
				if (1 === preg_match(GITHUB_PERMIT_PR_PATTERN_IN_COMMENT, $comment['body'])) {
					$review_status += 1;
				}
				if (1 === preg_match(GITHUB_RESET_PR_PATTERN_IN_COMMENT, $comment['body'])) {
					$review_status = 0;
				}
			}

			// emoticonをすべて取得する
			$emo = [];
			preg_match('/:[^\s]+:/', $comment['body'], $emo);

			$return_arr[] = [
				'is_new'        => $is_new,
				'review_status' => $review_status,
				'type'          => $type,
				'content'       => $comment,
				'emo'           => $emo,
			];

			$is_new = false;
		}

		return $return_arr;
	}

	/**
	 * 保存済みPRを取得する
	 *
	 * @return array PR情報
	 */
	public function getSavedPR()
	{
		if ($this->fh_pr->isExist()) {
			return $this->fh_pr->read();
		}
		return [];
	}

	/**
	 * 保存済みコメントidを取得する
	 *
	 * @return array コメントid
	 */
	public function getSavedCommentId()
	{
		if ($this->fh_comment->isExist()) {
			return $this->fh_comment->read();
		}
		return [];
	}

	/**
	 * PRを保存する
	 *
	 * @param array $this->getPullRequestsの返り値
	 */
	public function savePR(array $pr_arr)
	{
		$data = [];
		foreach ($pr_arr as $pr_number => $pr) {
			$data[$pr_number] = [
				'title' => $pr['title'],
				'user'  => $pr['user']['login'],
			];
		}
		$this->fh_pr->overwrite($data);
	}

	/**
	 * コメントidを保存する
	 *
	 * @param array $this->getCommentsの返り値
	 */
	public function saveCommentId(array $comment_arr)
	{
		$data = [];
		foreach ($comment_arr as $comment) {
			if (!isset($data[$comment['type']])) {
				$data[$comment['type']] = [];
			}
			$data[$comment['type']][] = $comment['content']['id'];
		}
		$this->fh_comment->overwrite($data);
	}

	/**
	 * usort用関数, 日付昇順にする
	 */
	private static function dateAscSort($a, $b)
	{
		return ($a['created_at'] >= $b['created_at']) ? 1 : -1;	// 日付昇順
	}
}
