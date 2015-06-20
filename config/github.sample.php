<?php

define('GITHUB_ACCESS_TOKEN', 'hoge');

// このパターンがタイトルにあるとき、このPRはレビュー対象であるとする
define('GITHUB_PATTERN_TARGET_PR', '/\[prefix\]/i');

// このパターンがコメントにあるとき、このPRはレビュー完了したとする
define('GITHUB_PATTERN_AGREE', '/^:\+1:|\[LGTM\]/');

// このパターンがコメントにあるとき、このPRのレビューokをリセットする
define('GITHUB_PATTERN_RESET_AGREE', '/:0:/');

// このパターンがコメントにあるとき、URLを表示させる
define('GITHUB_PATTERN_SHOW_URL', '/:bow:/');

// このパターンがアカウント名にあるとき、このユーザのコメントは無視する
define('GITHUB_PATTERN_IGNORE_USER', '/jenkinsbot|-bot$/');
