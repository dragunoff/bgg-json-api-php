<?php

namespace DaIgraem\BggProxyApi;

class Cache
{
	private $filename;
	private $file;
	private $exists;
	private $contents;

	function init(string $filename)
	{
		$this->filename = $filename;
		$this->file = CACHE_DIR . "{$this->filename}.json";
		$this->exists = file_exists($this->file);

		if ($this->exists)
			$this->contents = file_get_contents($this->file) ?: '';
	}

	function isValid(): bool
	{
		if (CACHE && $this->exists && $this->contents && filemtime($this->file) > time() - CACHE_LIFETIME)
			return true;

		return false;
	}

	function get(): object
	{
		return json_decode($this->contents);
	}

	function write($data): void
	{
		if (CACHE) {
			file_put_contents($this->file, json_encode($data));
		}
	}
}
