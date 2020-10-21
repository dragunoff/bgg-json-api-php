<?php

namespace DaIgraem\BggProxyApi;

require __DIR__ . '/../bootstrap.php';

$files = glob(CACHE_DIR . '*.json');

error_log(sprintf('Deleting *.json files in "%s"', CACHE_DIR));

foreach ($files as $file) {
	if (is_file($file)) {
		$deleted = unlink($file);

		if ($deleted) {
			error_log(sprintf('Deleted "%s"', $file));
		} else {
			error_log(sprintf('Could not delete "%s"', $file));
		}
	}
}

error_log('SUCCESS: Cache cleared.');
