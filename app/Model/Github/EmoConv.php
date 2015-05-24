<?php

/**
 * githubエモーティコン変換器
 */

namespace Githutil\Model\Github;

class EmoConv
{
	const SKYPE = [
		':+1:'    => '(y)',
		'![LGTM]' => '(y)',
		':0:'     => '(*)',
	];

	public static function toSkype($str)
	{
		foreach (self::SKYPE as $search => $replace) {
			$str = str_replace($search, $replace, $str);
		}
		return $str;
	}
}
