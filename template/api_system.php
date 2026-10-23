<?php
	// Ensure this resource is jailed in sitemap: {access: []}

	switch ($BW->site->meta['task']) {
		case 'mem':
			$data = [];
			foreach(file('/proc/meminfo', FILE_SKIP_EMPTY_LINES) as $x) {
				[$name, $value] = explode(':', $x);
				switch (trim($name)) {
					case 'MemTotal':	$data['total'] = getSizeRaw($value);		break;
					case 'MemFree':		$data['free'] = getSizeRaw($value);		break;
					case 'MemAvailable':	$data['avail'] = getSizeRaw($value);		break;
					case 'SwapTotal':	$data['swaptotal'] = getSizeRaw($value);	break;
					case 'SwapFree':	$data['swapfree'] = getSizeRaw($value);		break;
				}
			}
			return $data + [
				'disktotal' => disk_total_space('/'),
				'diskfree' => disk_free_space('/')
			];

		case 'fpm':
			if (!is_callable('fpm_get_status')) {
				http_response_code(501);
				return ['error' => 'Not FPM server'];
			}
			return fpm_get_status();

		default:
			throw new BW_WebServerError('Unknown task in meta', 500);
	}

	function getSizeRaw(string $value): int {
		$t = trim($value);
		if (substr($t, -2) == 'kB') {
			return 1024 * substr($t, 0, -2);
		} else if (substr($t, -2) == 'MB') {
			return 1024 * 1024 * substr($t, 0, -2);
		} else if (substr($t, -2) == 'GB') {
			return 1024 * 1024 * 1024 * substr($t, 0, -2);
		}
		return (int)$t;
	}
?>