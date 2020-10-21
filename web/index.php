<?php
namespace DaIgraem\BggProxyApi;

require __DIR__ . '/../bootstrap.php';

try
{
	new HandleRequest($_GET, new BggClient(), new Cache(), new JsonResponse());
}
catch (\Exception $e)
{
	printf('Error: %s', $e->getMessage());
}
