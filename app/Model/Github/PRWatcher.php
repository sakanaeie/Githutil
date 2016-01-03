<?php

namespace Githutil\Model\Github;

use \Githutil\Infrastructure\JsonFile;
use \Githutil\Infrastructure\Logger;

class PRWatcher
{
	/**
	 * jsonファイルの格納場所
	 */
	const PR_SAVE_FILE_PATH_TPL      = ROOT_PATH . '/storage/json/%s_%s_PRWatcher_pr_number.json';
	const COMMENT_SAVE_FILE_PATH_TPL = ROOT_PATH . '/storage/json/%s_%s_PRWatcher_comment_id.json';

	/**
	 * コメント取得モード
	 */
	const COMMENT_GET_ALL  = 1;
	const COMMENT_GET_PR   = 2;	// PRページのみ
	const COMMENT_GET_FILE = 3;	// ファイルページのみ

	/**
	 * @var \Github\Client
	 */
	private $client;

	/**
	 * @var string レポジトリのオーナー名
	 */
	private $repo_owner;

	/**
	 * @var string レポジトリ名
	 */
	private $repo_name;

	/**
	 * @var JsonFile jsonファイル操作インスタンス
	 */
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
	 * PRしてないブランチを取得する
	 *
	 * @return string[] $return_arr ブランチ名
	 */
	public function getNotPullRequestBranches()
	{
		$br_arr = $this->client
			->api('repository')
			->branches($this->repo_owner, $this->repo_name);

		$pr_arr = $this->client
			->api('pull_request')
			->all($this->repo_owner, $this->repo_name);

		$pr_branch_name_arr   = array_column(array_column($pr_arr, 'head'), 'ref');
		$pr_branch_name_arr[] = 'master';
		$return_arr = [];
		foreach ($br_arr as $br) {
			if (!in_array($br['name'], $pr_branch_name_arr)) {
				$return_arr[] = $br['name'];
			}
		}

		return $return_arr;
	}

	/**
	 * PRを取得する
	 *
	 * @param  int   $pr_number PR番号
	 * @return array            GithubAPI-PRのレスポンス
	 */
	public function getOnePullRequest($pr_number)
	{
		return $this->client
			->api('pull_request')
			->show($this->repo_owner, $this->repo_name, $pr_number);
	}

	/**
	 * PRを全て取得する
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
			if (1 === preg_match(GITHUB_PATTERN_TARGET_PR, $pr['title'])) {
				// レビュー対象であるとき
				$return_arr[$pr['number']] = $pr;
			}
		}

		return $return_arr;
	}

	/**
	 * PRを全て取得する (ブラックリスト利用)
	 *
	 * @return array $return_arr GithubAPI-PRのレスポンスから、不要なPRを除去したもの
	 */
	public function getPullRequestsWithBlackListFilter()
	{
		$pr_arr = $this->getPullRequests();

		foreach ($pr_arr as $pr_number => $pr) {
			if (1 === preg_match(GITHUB_PATTERN_IGNORE_PR_IN_LIST, $pr['title'])) {
				unset($pr_arr[$pr_number]);;
			}
		}

		return $pr_arr;
	}

	/**
	 * コメントを取得する
	 *
	 * PR内のファイルに対するもの、PR自体に対するもの、両方を取得しマージする
	 *
	 * @param  int   $pr_number  PR番号
	 * @return array $return_arr GithubAPI-Commentのレスポンスと、追加情報を含む配列
	 */
	public function getComments($pr_number, $mode = self::COMMENT_GET_ALL)
	{
		// コメントは30個でページングされるため、それらを一気に取得するためのインスタンスを生成する
		$pager = new \Github\ResultPager($this->client);

		// コメントを取得する (PR内のファイルに対するもの)
		$pr_comments = [];
		if (self::COMMENT_GET_ALL === $mode or self::COMMENT_GET_FILE === $mode) {
			$pr_comments = $pager->fetchAll(
				$this->client->api('pull_request')->comments(),
				'all',
				array($this->repo_owner, $this->repo_name, $pr_number)
			);
		}

		// コメントを取得する (PR自体に対するもの)
		$issue_comments = [];
		if (self::COMMENT_GET_ALL === $mode or self::COMMENT_GET_PR === $mode) {
			$issue_comments = $pager->fetchAll(
				$this->client->api('issue')->comments(),
				'all',
				array($this->repo_owner, $this->repo_name, $pr_number)
			);
		}

		// コメントをマージして、日付昇順にする
		$comment_arr = array_merge($pr_comments, $issue_comments);
		usort($comment_arr, 'self::dateAscSort');

		// 情報を追加する
		$is_new = true;
		$review_status = 0;
		$return_arr    = [];
		foreach ($comment_arr as $comment) {
			if (1 === preg_match(GITHUB_PATTERN_IGNORE_USER, $comment['user']['login'])) {
				// このユーザのコメントは無視する
				continue;
			}

			$is_agree = $is_reset = $is_deco = $is_memo = false;
			if (isset($comment['diff_hunk'])) {
				// PR内のファイルに対するものであるとき
				$type = 'file';
			} else {
				// PR自体に対するものであるとき
				$type = 'pr';
				if (1 === preg_match(GITHUB_PATTERN_AGREE, $comment['body'])) {
					$is_agree = true;
					$review_status += 1;
				}
				if (1 === preg_match(GITHUB_PATTERN_RESET_AGREE,  $comment['body'])) {
					$is_reset = true;
					$review_status = 0;
				}
				if (1 === preg_match(GITHUB_PATTERN_SHOW_URL, $comment['body'])) {
					$is_deco  = true;
				}
			}
			if (1 === preg_match(GITHUB_PATTERN_MEMO, $comment['body'])) {
				$is_memo = true;
			}

			$return_arr[] = [
				'is_new'        => $is_new,
				'is_decorate'   => $is_deco,
				'is_agree'      => $is_agree,
				'is_reset'      => $is_reset,
				'is_memo'       => $is_memo,
				'review_status' => $review_status,
				'type'          => $type,
				'content'       => $comment,
			];

			$is_new = false;
		}

		return $return_arr;
	}

	/**
	 * 保存済みPR番号を取得する
	 *
	 * @return array PR情報
	 */
	public function getSavedPRNumber()
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
	 * PR番号を保存する
	 *
	 * @param array $pr_arr $this->getPullRequestsの返り値
	 */
	public function savePRNumber(array $pr_arr)
	{
		$this->fh_pr->overwrite(array_keys($pr_arr));
	}

	/**
	 * コメントidを保存する
	 *
	 * @param array $comment_arr $this->getCommentsの返り値
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
	 * メール送信やAPIアクセスにより、メッセージ送信する
	 *
	 * @param string $message_body 本文
	 */
	public function sendMessage($message_body)
	{
		switch (SEND_MODE) {
		case SEND_MODE_MAIL:
			$this->sendMail($message_body);
			break;
		case SEND_MODE_API:
			$this->callApi($message_body);
			break;
		case SEND_MODE_SLACK:
			\Githutil\Model\Slack\WebHooks::send(SLACK_API_URL, $message_body, [
				channel    => SLACK_CHANNEL,
				username   => SLACK_SHOW_NAME,
				icon_emoji => SLACK_SHOW_ICON,
			]);
			break;
		}
	}

	/**
	 * メールを送信する
	 *
	 * @param string $message_body 本文
	 */
	private function sendMail($message_body)
	{
		try {
			$send_status = \Githutil\Infrastructure\Gmail::send(
				GMAIL_ACCOUNT_NAME,
				GMAIL_PASSWORD,
				GMAIL_SUBJECT,
				\Githutil\Model\Github\EmoConv::toSkype($message_body),
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

	/**
	 * 外部アプリのAPIを叩く
	 *
	 * @param string $message_body 本文
	 */
	private function callApi($message_body)
	{
		$data = [
			'room'    => CHAT_BOT_API_ROOM,
			'message' => \Githutil\Model\Github\EmoConv::toSkype($message_body),
		];

		\Githutil\Infrastructure\Curl::execute(CHAT_BOT_API_URL, $data);
	}

	/**
	 * ユーザ名を変換する
	 *
	 * @param  string $name ユーザ名
	 * @return string       変換後ユーザ名
	 */
	public static function convertUserName($name)
	{
		return isset($GLOBALS['APP_DEFINE']['GITHUB_USER_NAME_LIST'][$name]) ? $GLOBALS['APP_DEFINE']['GITHUB_USER_NAME_LIST'][$name] : $name;
	}

	/**
	 * usort用関数, 日付昇順にする
	 */
	private static function dateAscSort($a, $b)
	{
		return ($a['created_at'] >= $b['created_at']) ? 1 : -1;	// 日付昇順
	}
}
