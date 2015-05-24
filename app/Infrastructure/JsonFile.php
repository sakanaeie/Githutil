<?php

/**
 * jsonファイル操作
 */

namespace Githutil\Infrastructure;

class JsonFile
{
	private $filename;

	public function __construct($filename)
	{
		$this->filename = $filename;
	}

	public function isExist()
	{
		return file_exists($this->filename);
	}

	public function read()
	{
		$body = file_get_contents($this->filename);
		if (false === $body) {
			throw new \Exception('failed file open');
		}

		$body = json_decode($body, true);
		if (null === $body) {
			throw new \Exception('failed json decode');
		}

		return $body;
	}

	public function overwrite($body)
	{
		$handle = fopen($this->filename, 'w+');
		if (false === $handle) {
			throw new \Exception('failed file open');
		}
		fwrite($handle, json_encode($body));
		fclose($handle);
	}
}
