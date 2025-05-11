<?php
	if (!isset($_POST['url']) || !Bearweb_Site::validURL($_POST['url'])) {
		http_response_code(400);
		return ['error' => 'Bad URL'];
	}

	switch ($BW->site->meta[0]) {
		case 'Get':
			$resource = Bearweb_Site::query($_POST['url']);
			if (!$resource) {
				http_response_code(404);
				return ['error' => 'No such resource'];
			} else if ($resource->access($BW->user) != Bearweb_Site::ACCESS_RW) {
				http_response_code(403);
				return ['error' => 'No write access'];
			}
			$x = (array)$resource;
			$headers = getallheaders();
			if (isset($headers['X-Content-Encoding']) && $headers['X-Content-Encoding'] == 'base64')
				$x['content'] = base64_encode($x['content']);
			return $x;

		case 'My':
			if (!$BW->user->id) {
				http_response_code(401);
				return ['error' => 'Auth first to get my list'];
			}
			$sql = Bearweb_Site::$db->prepare('SELECT `url`, `category`, `create`, `modify`, CASE WHEN instr(`meta`, char(10)) > 0 THEN substr(`meta`, 0, instr(`meta`, char(10))) ELSE `meta` END AS `meta`, substr(`state`, 1, 1) AS `state` FROM `Sitemap` WHERE `Owner` = ?');
			$sql->bindValue(1, $BW->user->id, PDO::PARAM_STR);
			$sql->execute();
			$resource = $sql->fetchAll();
			$sql->closeCursor();
			return $resource;
		
		case 'Create':
			if (Bearweb_Site::query($_POST['url'])) {
				http_response_code(409);
				return ['error' => 'Resource already existed'];
			}
			(new Bearweb_Site(
				url: $_POST['url'],
				template: ['object', 'blob'],
				owner: $BW->user->id,
			))->insert();
			http_response_code(201);
			return [];

		case 'Update':
			if (!isset($_POST['category']) || !array_key_exists($_POST['category'], $BW->site->aux['type'])) {
				http_response_code(403);
				return ['error' => 'Type undefined or not allowed'];
			}
			if (!isset($_POST['meta']) || !isset($_POST['state']) || !isset($_POST['content']) || !isset($_POST['aux'])) {
				http_response_code(403);
				return ['error' => 'Missing field(s): meta, state, content, aux'];
			}
			$resource = Bearweb_Site::query($_POST['url']);
			if (!$resource) {
				http_response_code(404);
				return ['error' => 'No such resource'];
			} else if ($resource->access($BW->user) != Bearweb_Site::ACCESS_RW) {
				http_response_code(403);
				return ['error' => 'No write access'];
			}
			$headers = getallheaders();
			(new Bearweb_Site(
				url:		$_POST['url'],
				category:	$BW->site->aux['type'][$_POST['category']][0],
				template:	$BW->site->aux['type'][$_POST['category']][1],
				owner:		null,
				create:		null,
				modify:		Bearweb_Site::TIME_CURRENT,
				meta:		$_POST['meta'],
				state:		$_POST['state'],
				content:	isset($headers['X-Content-Encoding']) && $headers['X-Content-Encoding'] == 'base64' ? base64_decode($_POST['content']) : $_POST['content'],
				aux:		$_POST['aux']
			))->update();

			reindex();
			http_response_code(202);
			return [];

		default:
			throw new BW_WebServerError('Unknown task in meta', 500);
	}

	function timetable($timestamp) { return [ '$Modify_RFC7231' => date(DATE_RFC7231,$timestamp) , '$Modify_RSS' => date(DATE_RSS,$timestamp) , '$Modify_W3C' =>  date(DATE_W3C,$timestamp) , '$Modify_Date' =>  date('M j, Y',$timestamp) ]; }
	function reindex() {
		$index = Bearweb_Site::$db->query('SELECT `url`, `aux` FROM `Sitemap` WHERE `category` = \'Index\'')->fetchAll();
		foreach ($index as &$x) {
			$lookup = timetable($_SERVER['REQUEST_TIME']);
			$x['aux'] = json_decode($x['aux'], true);
			$x['content'] = str_replace(array_keys($lookup), array_values($lookup), $x['aux']['pre']);
		}; unset($x);
		foreach(Bearweb_Site::$db->query('SELECT `url`, `category`, `owner`, `create`, `modify`, `meta`,
			CASE WHEN json_valid(aux) THEN json_extract(aux, \'$.bgimg\') ELSE null END AS `bgimg`,
			CASE WHEN json_valid(aux) THEN json_extract(aux, \'$.lang\') ELSE null END AS `lang`
		FROM `Sitemap` WHERE `state` = \'O\' ORDER BY `create` DESC') as $r) {
			$r['meta'] = explode("\n", $r['meta']);
			$lookup = timetable($r['modify']) + [
				'$URL'			=> $r['url'],
				'$Category'		=> $r['category'],
				'$Owner'		=> $r['owner'],
				'$Create'		=> $r['create'],
				'$Modify'		=> $r['modify'],
				'$Title'		=> $r['meta'][0] ?? '',
				'$Keywords'		=> $r['meta'][1] ?? '',
				'$Description'		=> $r['meta'][2] ?? '',
				'$Bgimg'		=> $r['bgimg'] ?? '',
				'$Lang'			=> $r['lang'] ?? '',
			];
			foreach ($index as &$x) {
				if (in_array( $r['category'] , $x['aux']['category'] ))
					$x['content'] .= str_replace(array_keys($lookup), array_values($lookup), $x['aux']['main']);
			}; unset($x);
		}
		foreach ($index as &$x) {
			$x['content'] .= $x['aux']['post'];
			(new Bearweb_Site(
				url:		$x['url'],
				category:	null,
				template:	null,
				owner:		null,
				create:		null,
				modify:		Bearweb_Site::TIME_CURRENT,
				meta:		null,
				state:		null,
				content:	$x['content'],
				aux:		null
			))->update();
		}; unset($x);
	}
?>