<?php
	try {
		[$_POST, $_FILES] = request_parse_body([		
			'max_file_uploads'	=> '1',
			'max_input_vars'	=> '20',
			'post_max_size'		=> '4M',
			'upload_max_filesize'	=> '32M'
		]);
	} catch (Exception $e) {
		http_response_code(400);
		return ['error' => 'Invalid post data'];
	}

	if (!isset($_POST['url']) || !Bearweb_Site::validURL($_POST['url'])) {
		http_response_code(400);
		return ['error' => 'Missing or bad URL'];
	}
	$encode = $_GET['encode'] ?? '';

	switch ($BW->site->meta['task']) {
		case 'get':
			$resource = Bearweb_Site::query($_POST['url']);
			if (!$resource) {
				http_response_code(404);
				return ['error' => 'No such resource'];
			} else if ($resource->access($BW->user) != Bearweb_Site::ACCESS_RW) {
				http_response_code(403);
				return ['error' => 'No write access'];
			}
			if ($encode == 'b64') {
				$resource->content = base64_encode($resource->content);
			}
			http_response_code(200);
			return $resource;
		
		case 'create':
			try {
				$resource = new Bearweb_Site(
					url: $_POST['url'],
					template: ['object', 'blob'],
					owner: $BW->user->id,
					create:		Bearweb_Site::TIME_CURRENT,
					modify:		Bearweb_Site::TIME_CURRENT,
					meta:		[
						'access' => []
					]
				);
				$resource->insert();
				http_response_code(201);
				return $resource;
			} catch (BW_DatabaseServerError $e) {
				if (strpos($e->getMessage(), 'UNIQUE')) {
					http_response_code(409);
					return ['error' => 'Resource already existed'];
				}
				throw $e;
			}

		case 'update':
			if (!isset($_POST['category']) || !array_key_exists($_POST['category'], $BW->site->aux['type'])) {
				http_response_code(400);
				return ['error' => 'Type undefined or not allowed'];
			}
			if (!isset($_POST['meta']) || !json_validate($_POST['meta']) || !isset($_POST['aux']) || !json_validate($_POST['aux'])) {
				http_response_code(400);
				return ['error' => 'Missing or bad field(s): meta, aux'];
			}
			if ($_FILES['content']['error'] ?? '-1' || is_uploaded_file($_FILES['content']['tmp_name'])) { // content.error must existed, error must be 0
				http_response_code(400);
				return ['error' => 'Missing or bad content'];
			}
			$content = $encode == 'b64' ? base64_decode(file_get_contents($_FILES['content']['tmp_name'])) : file_get_contents($_FILES['content']['tmp_name']);
			$resource = Bearweb_Site::query($_POST['url']);
			if (!$resource) {
				http_response_code(404);
				return ['error' => 'No such resource'];
			} else if ($resource->access($BW->user) != Bearweb_Site::ACCESS_RW) {
				http_response_code(403);
				return ['error' => 'No write access'];
			}
			$resource->category	= $BW->site->aux['type'][$_POST['category']][0];
			$resource->template	= $BW->site->aux['type'][$_POST['category']][1];
			$resource->modify	= Bearweb_Site::TIME_CURRENT;
			$resource->meta		= $_POST['meta'];
			$resource->content	= $content;
			$resource->aux		= $_POST['aux'];
			$resource->update();
			http_response_code(202);
			return [];
		
		case 'delete':
			$resource = Bearweb_Site::query($_POST['url']);
			if (!$resource) {
				http_response_code(404);
				return ['error' => 'No such resource'];
			} else if ($resource->access($BW->user) != Bearweb_Site::ACCESS_RW) {
				http_response_code(403);
				return ['error' => 'No write access'];
			}
			$resource->delete();
			http_response_code(410);
			return [];
		
		case 'reindex':
			bear_reindex();
			http_response_code(202);
			return [];

		default:
			throw new BW_WebServerError('Unknown task in meta', 500);
	}

	function timetable($timestamp) { return [ '$Modify_RFC7231' => date(DATE_RFC7231,$timestamp) , '$Modify_RSS' => date(DATE_RSS,$timestamp) , '$Modify_W3C' =>  date(DATE_W3C,$timestamp) , '$Modify_Date' =>  date('M j, Y',$timestamp) ]; }
?>