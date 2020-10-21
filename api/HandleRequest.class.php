<?php

namespace DaIgraem\BggProxyApi;

class HandleRequest
{
	private $request;
	private $endpoint;
	private $hash;
	private $validEndpoints = [
		'collection',
		'thing',
		'guild',
		'guild-collection',
	];
	private $client;
	private $cache;
	private $json;

	function __construct(array $request, BggClient $bggBggClient, Cache $cache, JsonResponse $json)
	{
		$this->request = $request;
		$this->client = $bggBggClient;
		$this->cache = $cache;
		$this->json = $json;

		$this->check();
		$this->hash();
		$this->do();
	}

	private function hash(): void
	{
		// TODO: Add the default query args to the hash
		$this->hash = md5(http_build_query($this->request));
	}

	private function check(): void
	{
		if (empty($this->request)) {
			throw new \Exception('GET request has no arguments.');
		} else if (empty($this->request['endpoint'])) {
			throw new \Exception(sprintf('Required argument "%s" is missing or empty.', 'endpoint'));
		} else if (!in_array($this->request['endpoint'], $this->validEndpoints)) {
			throw new \Exception(sprintf('Invalid endpoint: %s', $this->request['endpoint']));
		}

		$this->endpoint = $this->request['endpoint'];
		unset($this->request['endpoint']);

		switch ($this->endpoint) {
			case 'collection':
				if (empty($this->request['username'])) {
					throw new \Exception(sprintf('Required argument "%s" is missing or empty.', 'username'));
				}
				break;
			case 'thing':
				if (empty($this->request['id'])) {
					throw new \Exception(sprintf('Required argument "%s" is missing or empty.', 'id'));
				}
				break;
			case 'guild':
				if (empty($this->request['id'])) {
					throw new \Exception(sprintf('Required argument "%s" is missing or empty.', 'id'));
				}
				break;
			case 'guild-collection':
				if (empty($this->request['id'])) {
					throw new \Exception(sprintf('Required argument "%s" is missing or empty.', 'id'));
				}
				break;
		}
	}

	private function do(): void
	{
		$this->cache->init("{$this->endpoint}.{$this->hash}");

		if ($this->cache->isValid()) {
			$this->json->setData($this->cache->get())->setSuccess(true);
		} else {
			try {
				// HACK: BGG API incorrectly gives subtype=boardgame for the expansions.
				// Workaround is to use excludesubtype=boardgameexpansion and make a 2nd call asking for subtype=boardgameexpansion
				// @url https://boardgamegeek.com/wiki/page/BGG_XML_API2#toc11
				// @url https://boardgamegeek.com/thread/1583046/article/22807790
				if ($this->endpoint === 'collection' && isset($this->request['subtype-workaround'])) {
					$data = $this->getCollectionWithWorkaround($this->request);
				} else if ($this->endpoint === 'guild-collection') {
					$data = $this->getGuildCollection();
				} else {
					$data = $this->client->get($this->endpoint, $this->request)->asArray();
				}

				$this->json->setData($data)->setSuccess(true);
				$this->cache->write($this->json->getData());
			} catch (\Exception $e) {
				$this->json->setErrorMessage($e->getMessage())->setSuccess(false);
			}
		}

		$this->json->send();
	}

	private function getCollectionWithWorkaround(array $args): array
	{
		$expansionsResponse = $this->client->get(
			'collection',
			array_merge(['subtype' => 'boardgameexpansion'], $args)
		);
		$gamesResponse = $this->client->get(
			'collection',
			array_merge(['excludesubtype' => 'boardgameexpansion'], $args)
		);

		$response = (array) $gamesResponse->asArray();
		$expansions = (array) $expansionsResponse->asArray();

		$totalGames = (int) $response['items']['_totalitems'];
		$totalExpansions = (int) $expansions['items']['_totalitems'];

		$response['items']['_totalitems'] = $totalGames + $totalExpansions;

		if ($totalExpansions > 0) {
			$response['items']['item'] = array_merge((array) $response['items']['item'], (array) $expansions['items']['item']);
		}

		return $response;
	}

	private function getGuildCollection(): array
	{
		$guild = $this->client->get('guild', array_merge(['members' => 1], $this->request))->asArray();
		$guildCollection = [];

		foreach ($guild['guild']['members']['member'] as $member) {
			$memberCollection = $this->getCollectionWithWorkaround(['username' => (string) $member['_name']]);

			if (empty($guildCollection)) {
				$guildCollection = $memberCollection;
			} else if ((int) $memberCollection['items']['_totalitems'] > 0) {
				$guildCollection['items']['item'] = array_merge((array) $guildCollection['items']['item'], (array) $memberCollection['items']['item']);
				$guildCollection['items']['_totalitems'] = (int) $guildCollection['items']['_totalitems'] + (int) $memberCollection['items']['_totalitems'];
			}
		}

		return $guildCollection;
	}
}
