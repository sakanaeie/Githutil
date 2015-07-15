<?php

define('GITHUB_ACCESS_TOKEN', 'hoge');

// このパターンがタイトルにあるとき、このPRはレビュー対象であるとする
define('GITHUB_PATTERN_TARGET_PR', '/\[prefix\]/i');

// このパターンがタイトルにあるとき、このPRは一覧に表示させない
define('GITHUB_PATTERN_IGNORE_PR_IN_LIST', '/\[DO NOT REVIEW\]/');

// このパターンがコメントにあるとき、このPRはレビュー完了したとする
define('GITHUB_PATTERN_AGREE', '/^:\+1:|\[LGTM\]/');

// このパターンがコメントにあるとき、このPRのレビューokをリセットする
define('GITHUB_PATTERN_RESET_AGREE', '/:0:/');

// このパターンがコメントにあるとき、URLを表示させる
define('GITHUB_PATTERN_SHOW_URL', '/:bow:|:\+1:/');

// このパターンがアカウント名にあるとき、このユーザのコメントは無視する
define('GITHUB_PATTERN_IGNORE_USER', '/jenkinsbot|-bot$/');

// アカウント名対実名
$GLOBALS['APP_DEFINE']['GITHUB_USER_NAME_LIST'] = [
	'sakanaeie' => 'さかな えいえ'
];
