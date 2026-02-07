<?php
	try {
		[$_POST, $_FILES] = request_parse_body([		
			'max_file_uploads'	=> '0',
			'max_input_vars'	=> '5',
			'post_max_size'		=> '2048',
			'upload_max_filesize'	=> '0'
		]);
	} catch (Exception $e) {
		http_response_code(400);
		return ['error' => 'Invalid post data'];
	}
	
	if (!isset($_POST['id']) || !Bearweb_User::validID(($_POST['id']))) {
		http_response_code(400);
		return ['error' => 'Missing or bad ID'];
	}

	switch ($BW->site->meta['task']) {
		case 'whoami':
			http_response_code(200);
			return [ 'id' => $BW->session->sUser ];

		case 'get':
			$user = Bearweb_User::query($_POST['id']);
			if (!$user) {
				http_response_code(404);
				return ['error' => 'No such user'];
			}
			http_response_code(200);
			return [
				'id'		=> $user->id,
				'name'		=> $user->name,
				'salt'		=> $user->salt, # Anyone can access the salt as pre condition of login, so it is ok to expose it in this API
				'registertime'	=> $user->registertime,
				'lastactive'	=> $user->lastactive,
				'group'		=> $user->group
			] + ($user->id == $_POST['id'] ? [
				'data'		=> $user->data
			] : []);

		case 'logoff':
			$BW->session->bindUser('');
			http_response_code(401);
			return [];

		case 'login': # Also for password update
			if (!isset($_POST['password']) || !Bearweb_User::validPassword(($_POST['password']))) {
				http_response_code(400);
				return ['error' => 'Missing or bad Password'];
			}
			if (!isset($_POST['passwordnew']) || !Bearweb_User::validPassword(($_POST['passwordnew']))) {
				http_response_code(400);
				return ['error' => 'Missing or bad Password'];
			}
			$user = Bearweb_User::query($_POST['id']);
			if (!$user) {
				http_response_code(404);
				return ['error' => 'No such user'];
			}
			if ( hash('sha384', $BW->session->sKey.$user->password, true) != base64_decode($_POST['password']) ) {
				http_response_code(401);
				$BW->session->updateKey();
				return ['error' => 'Wrong Password'];
			}
			$user->salt = $BW->session->sKey;
			$user->password = $_POST['passwordnew'];
			$user->lastactive = Bearweb_User::TIME_CURRENT;
			$user->update(); // If failed, user password and salt remains unchange and session will not bind to user
			$BW->session->bindUser($user->id);
			http_response_code(202);
			return ['id' => $user->id];

		case 'register':
			if (!isset($_POST['password']) || !Bearweb_User::validPassword(($_POST['password']))) {
				http_response_code(400);
				return ['error' => 'Missing or bad Password'];
			}
			if (Bearweb_User::query($_POST['id'])) {
				http_response_code(409);
				return ['error' => 'User ID already used'];
			}
			$user = new Bearweb_User(
				id: $_POST['id'],
				name: $_POST['id'],
				salt: $BW->session->sKey,
				password: $_POST['password']
			);
			$user->insert();
			http_response_code(201);
			return ['id' => $user->id];

		default:
			throw new BW_WebServerError('Unknown task in Meta', 500);
	}
?>