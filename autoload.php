<?php

function _require_all($path, $depth = 0)
{
	$dirhandle = @opendir($path);
	if ($dirhandle === false) return;
	while (($file = readdir($dirhandle)) !== false) {
		if ($file !== '.' && $file !== '..') {
			$fullfile = $path . '/' . $file;
			if (is_dir($fullfile)) {
				_require_all($fullfile, $depth + 1);
			} else if (strlen($fullfile) > 4 && substr($fullfile, -4) == '.php') {
				require $fullfile;
			}
		}
	}

	closedir($dirhandle);
}

_require_all(__DIR__ . '/utils');
_require_all(__DIR__ . '/bgg');
_require_all(__DIR__ . '/api');
