<?php

namespace DaIgraem\BggProxyApi;

class BggXmlResponse
{
	/** @var \SimpleXMLElement */
	public $root;

	function __construct(\SimpleXMLElement $xml)
	{
		$this->root = $xml;
	}

	function asArray(): array
	{
		$options = [
			'alwaysArray' => ['item', 'member'],
			'attributePrefix' => '_',
		];
		return Utils::xmlToArray($this->root, $options);
	}
}
