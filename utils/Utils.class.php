<?php

namespace DaIgraem\BggProxyApi;

class Utils
{
	static function parse_args($args, $defaults = '')
	{
		if (is_object($args)) {
			$parsed_args = get_object_vars($args);
		} elseif (is_array($args)) {
			$parsed_args = &$args;
		} else {
			parse_str($args, $parsed_args);
		}

		if (is_array($defaults)) {
			return array_merge($defaults, $parsed_args);
		}
		return $parsed_args;
	}

	/**
	 * Convert XML to an array in a sensible manner.
	 *
	 * Credits for this function go to Tamlyn Rhodes of Outlandish.
	 * @url https://outlandish.com/blog/tutorial/xml-to-json/
	 */
	static function xmlToArray(\SimpleXMLElement $xml, array $options = []) : array
	{
		$defaults = array(
			'namespaceSeparator' => ':', // you may want this to be something other than a colon
			'attributePrefix' => '@', // to distinguish between attributes and nodes with the same name
			'alwaysArray' => [], // array of xml tag names which should always become arrays
			'textContent' => '$', // key used for the text content of elements
		);
		$options = Utils::parse_args($options, $defaults);
		$namespaces = $xml->getDocNamespaces();
		$namespaces[''] = null; // add base (empty) namespace

		// get attributes from all namespaces
		$attributesArray = array();
		foreach ($namespaces as $prefix => $namespace) {
			foreach ($xml->attributes($namespace) as $attributeName => $attribute) {
				$attributeKey = $options['attributePrefix']
					. ($prefix ? $prefix . $options['namespaceSeparator'] : '')
					. $attributeName;
				$attributesArray[$attributeKey] = (string) $attribute;
			}
		}

		// get child nodes from all namespaces
		$tagsArray = array();
		foreach ($namespaces as $prefix => $namespace) {
			foreach ($xml->children($namespace) as $childXml) {
				// recurse into child nodes
				$childArray = self::xmlToArray($childXml, $options);
				list($childTagName, $childProperties) = each($childArray);

				// add namespace prefix, if any
				if ($prefix) $childTagName = $prefix . $options['namespaceSeparator'] . $childTagName;

				if (!isset($tagsArray[$childTagName])) {
					// only entry with this key
					// test if tags of this type should always be arrays, no matter the element count
					$tagsArray[$childTagName] =
						in_array($childTagName, $options['alwaysArray']) ? array($childProperties) : $childProperties;
				} elseif (
					is_array($tagsArray[$childTagName]) && array_keys($tagsArray[$childTagName])
					=== range(0, count($tagsArray[$childTagName]) - 1)
				) {
					// key already exists and is integer indexed array
					$tagsArray[$childTagName][] = $childProperties;
				} else {
					// key exists so convert to integer indexed array with previous value in position 0
					$tagsArray[$childTagName] = array($tagsArray[$childTagName], $childProperties);
				}
			}
		}

		// get text content of node
		$textContentArray = array();
		$plainText = trim((string) $xml);
		if ($plainText !== '') $textContentArray[$options['textContent']] = $plainText;

		// stick it all together
		$propertiesArray = $attributesArray || $tagsArray || ($plainText === '')
			? array_merge($attributesArray, $tagsArray, $textContentArray) : $plainText;

		// return node as array
		return array(
			$xml->getName() => $propertiesArray
		);
	}
}
