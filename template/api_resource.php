<?php
	if (!isset($_POST['URL']) || !Bearweb_Site::validURL($_SERVER["SCRIPT_URL"])) {
		http_response_code(400);
		return ['Error' => 'Bad URL'];
	}

	switch ($BW->site->meta[0]) {
		case 'Get':
			$resource = Bearweb_Site::query($_POST['URL']);
			if (!$resource) {
				http_response_code(404);
				return ['Error' => 'No such resource'];
			} else if ($resource->access($BW->user) != Bearweb_Site::ACCESS_RW) {
				http_response_code(403);
				return ['Error' => 'No write access'];
			}
			$x = (array)$resource;
			$headers = getallheaders();
			if (isset($headers['X-Content-Encoding']) && $headers['X-Content-Encoding'] == 'base64')
				$x['content'] = base64_encode($x['content']);
			return $x;

		case 'My':
			if (!$BW->user->id) {
				http_response_code(401);
				return ['Error' => 'Auth first to get my list'];
			}
			$sql = Bearweb_Site::$db->prepare('SELECT `URL`, `Category`, `Create`, `Modify`, CASE WHEN INSTR(`Meta`, CHAR(10)) THEN SUBSTR(`Meta`, 1, INSTR(`Meta`, CHAR(10)) - 1) ELSE `Meta` END AS `Title`, SUBSTR(`State`, 1, 1) AS `State` FROM `Sitemap` WHERE `Owner` = ?');
			$sql->bindValue(1, $BW->user->id, PDO::PARAM_STR);
			$sql->execute();
			$resource = $sql->fetchAll();
			$sql->closeCursor();
			return $resource;
		
		case 'Create':
			if (Bearweb_Site::query($_POST['URL'])) {
				http_response_code(409);
				return ['Error' => 'Resource already existed'];
			}
			(new Bearweb_Site(
				url: $_POST['URL'],
				owner: $BW->user->id,
			))->insert();
			http_response_code(201);
			return [];

		case 'Update':
			if (!isset($_POST['Category']) || !array_key_exists($_POST['Category'], $BW->site->aux['type'])) {
				http_response_code(403);
				return ['Error' => 'Type undefined or not allowed'];
			}
			if (!isset($_POST['Title']) || !isset($_POST['Keywords']) || !isset($_POST['Description']) || !isset($_POST['State']) || !isset($_POST['Content']) || !isset($_POST['Aux'])) {
				http_response_code(403);
				return ['Error' => 'Missing field(s): Title, Keywords, Description, State, Content'];
			}
			$resource = Bearweb_Site::query($_POST['URL']);
			if (!$resource) {
				http_response_code(404);
				return ['Error' => 'No such resource'];
			} else if ($resource->access($BW->user) != Bearweb_Site::ACCESS_RW) {
				http_response_code(403);
				return ['Error' => 'No write access'];
			}
			$headers = getallheaders();
			(new Bearweb_Site(
				url:		$_POST['URL'],
				category:	$BW->site->aux['type'][$_POST['Category']][0],
				template:	$BW->site->aux['type'][$_POST['Category']][1],
				owner:		$BW->user->id,
				create:		null,
				modify:		Bearweb_Site::TIME_CURRENT,
				meta:		[$_POST['Title'], $_POST['Keywords'], $_POST['Description']],
				state:		$_POST['State'],
				content:	isset($headers['X-Content-Encoding']) && $headers['X-Content-Encoding'] == 'base64' ? base64_decode($_POST['Content']) : $_POST['Content'],
				aux:		json_decode($_POST['Aux'], true)
			))->update();

			reindex();
			http_response_code(202);
			return [];

		default:
			throw new BW_WebServerError('Unknown task in Meta', 500);
	}

	function timetable($timestamp) { return [ '$Modify_RFC7231' => date(DATE_RFC7231,$timestamp) , '$Modify_RSS' => date(DATE_RSS,$timestamp) , '$Modify_W3C' =>  date(DATE_W3C,$timestamp) , '$Modify_Date' =>  date('M j, Y',$timestamp) ]; }
	function reindex() {
		$index = Bearweb_Site::$db->query('SELECT `URL`, `Aux` FROM `Sitemap` WHERE `Category` = \'Index\'')->fetchAll();
		foreach ($index as &$x) {
			$lookup = timetable($_SERVER['REQUEST_TIME']);
			$x['Aux'] = json_decode($x['Aux'], true);
			$x['Content'] = str_replace(array_keys($lookup), array_values($lookup), $x['Aux']['pre']);
		}; unset($x);
		foreach(Bearweb_Site::$db->query('SELECT `URL`, `Category`, `Owner`, `Create`, `Modify`, `Meta`,
			CASE WHEN json_valid(Aux) THEN json_extract(Aux, \'$.bgimg\') ELSE null END AS `Bgimg`,
			CASE WHEN json_valid(Aux) THEN json_extract(Aux, \'$.lang\') ELSE null END AS `Lang`
		FROM `Sitemap` WHERE `State` = \'O\' ORDER BY `Modify` DESC') as $r) {
			$r['Meta'] = explode("\n", $r['Meta']);
			$lookup = timetable($r['Modify']) + [
				'$URL'			=> $r['URL'],
				'$Category'		=> $r['Category'],
				'$Owner'		=> $r['Owner'],
				'$Create'		=> $r['Create'],
				'$Modify'		=> $r['Modify'],
				'$Title'		=> $r['Meta'][0] ?? '',
				'$Keywords'		=> $r['Meta'][1] ?? '',
				'$Description'		=> $r['Meta'][2] ?? '',
				'$Bgimg'		=> $r['Bgimg'] ?? '',
				'$Lang'			=> $r['Lang'] ?? '',
			];
			foreach ($index as &$x) {
				if (in_array( $r['Category'] , $x['Aux']['category'] ))
					$x['Content'] .= str_replace(array_keys($lookup), array_values($lookup), $x['Aux']['main']);
			}; unset($x);
		}
		foreach ($index as &$x) {
			$x['Content'] .= $x['Aux']['post'];
			(new Bearweb_Site(
				url:		$x['URL'],
				category:	null,
				template:	null,
				owner:		null,
				create:		null,
				modify:		Bearweb_Site::TIME_CURRENT,
				meta:		null,
				state:		null,
				content:	$x['Content'],
				aux:		null
			))->update();
		}; unset($x);
	}
?>