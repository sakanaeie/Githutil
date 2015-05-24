<?php

define('GITHUB_ACCESS_TOKEN', 'hoge');
define('GITHUB_ACCOUNT_NAME', 'fuga');

// このパターンがタイトルにあるとき、このPRはレビュー対象であるとする
define('GITHUB_TARGET_PR_PATTERN_IN_TITLE', '/\[prefix\]/i');

// このパターンがコメントにあるとき、このPRはレビュー完了したとする
define('GITHUB_PERMIT_PR_PATTERN_IN_COMMENT', '/^:\+1:|\[LGTM\]/');

// このパターンがコメントにあるとき、このPRのレビューokをリセットする
define('GITHUB_RESET_PR_PATTERN_IN_COMMENT', '/:0:/');

// このパターンがコメントにあるとき、URLを表示させる
define('GITHUB_SHOW_URL_PATTERN_IN_COMMENT', ':bow:');
