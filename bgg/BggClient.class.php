<?php

namespace DaIgraem\BggProxyApi;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Client;

class BggClient
{
	function request(string $endpoint, array $args = []): \SimpleXMLElement
	{
		$uri = sprintf('%s%s?%s', API_URL, $endpoint, http_build_query($args));

		$stack = new HandlerStack();
		$stack->setHandler(new CurlHandler());
		$retryMiddleware = Middleware::retry($this->retryDecider(), $this->retryDelay());
		$stack->push($retryMiddleware);
		$client = new Client(['handler' => $stack]);

		$response = $client->get($uri);

		$xml = simplexml_load_string($response->getBody()->getContents());
		if (!$xml instanceof \SimpleXMLElement) {
			throw new \Exception('API call failed');
		}

		return $xml;
	}

	function retryDecider(): callable
	{
		return function (
			$retries,
			Request $request,
			Response $response = null,
			RequestException $exception = null
		) {
			// Limit the number of retries to 5
			if ($retries >= 5) {
				return false;
			}

			// Retry connection exceptions
			if ($exception instanceof ConnectException) {
				return true;
			}

			if ($response) {
				$code = $response->getStatusCode();

				// Retry on server errors
				if ($code >= 500) {
					return true;
				}

				// Requests for collections get queued by BGG so we need to retry after a while.
				// @url https://boardgamegeek.com/wiki/page/BGG_XML_API2#toc11
				if ($code === 202) {
					return true;
				}
			}

			return false;
		};
	}

	function retryDelay(): callable
	{
		return function ($numberOfRetries) {
			return 1000 * $numberOfRetries;
		};
	}

	function get(string $endpoint, array $args): BggXmlResponse
	{
		switch ($endpoint) {
			case 'thing':
				$xml = $this->request('thing', Utils::parse_args(DEFAULT_ARGS_THING, $args));
				break;
			case 'collection':
				$xml = $this->request('collection', Utils::parse_args(DEFAULT_ARGS_COLLECTION, $args));
				break;
			case 'guild':
				$xml = $this->request('guild', Utils::parse_args(DEFAULT_ARGS_GUILD, $args));
				break;
			default:
				$xml = $this->request($endpoint, $args);
		}

		return new BggXmlResponse($xml);
	}
}
