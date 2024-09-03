<?php
	function checkBadPost(string $type, string $postKey): mixed {
		if (!isset($_POST[$postKey])) {
			http_response_code(400);
			return ['Error' => 'Missing POST fields: '.$postKey];
		}
		$data = $_POST[$postKey];
		if ($type == 'ID') {
			if (!( strlen($data) >= 6 && strlen($data) <= 16 && ctype_alnum(str_replace(['-', '_'], '', $data)) )) {
				http_response_code(400);
				return ['Error' => 'Bad format (ID) POST fields: '.$postKey];
			}
		} else if ($type == 'Pass') {
			if (!( strlen(base64_decode($data, true)) == 48 )) {
				http_response_code(400);
				return ['Error' => 'Bad format (Password = SHA384 base64) POST fields: '.$postKey];
			}
		} else {
			http_response_code(500);
			return ['Error' => 'Unknown type: '.$postKey];
		}
		return null;
	}
	function checkNoUser($user) {
		if (!$user) {
			http_response_code(404);
			return ['Error' => 'No such user'];
		}
		return null;
	}

	switch ($BW->site->meta[0]) {
		case 'My':
			if ($BW->user->isGuest()) {
				http_response_code(401);
				return ['User' => null];
			}
			return ['User' => [
				'ID' => $BW->user->id,
				'Name' => $BW->user->name,
				'RegisterTime' => $BW->user->registerTime,
				'LastActive' => $BW->user->lastActive,
				'Group' => $BW->user->group,
				'Avatar' => $BW->user->avatar
			]];

		case 'Logoff':
			$BW->session->bindUser('');
			http_response_code(401);
			return ['User' => null];

		case 'LoginKey':
			if ($x = checkBadPost('ID', 'ID')) return $x;
			$user = Bearweb_User::query($_POST['ID']);
			if ($x = checkNoUser($user)) return $x;
			return ['User' => [
				'ID' => $user->id,
				'Salt' => $user->salt
			]];

		case 'Login': # Also for password update
			if ($x = checkBadPost('ID', 'ID')) return $x;
			if ($x = checkBadPost('Pass', 'Password')) return $x;
			if ($x = checkBadPost('Pass', 'PasswordNew')) return $x;
			$user = Bearweb_User::query($_POST['ID']);
			if ($x = checkNoUser($user)) return $x;
			if ( hash('sha384', $BW->session->sKey.$user->password, true) != base64_decode($_POST['Password']) ) {
				http_response_code(401);
				$BW->session->updateKey();
				return ['Error' => 'Wrong Password'];
			}
			(new Bearweb_User(
				id: $user->id,
				salt: $BW->session->sKey,
				password: $_POST['PasswordNew'],
				lastActive: Bearweb_User::TIME_CURRENT
			))->update();
			$BW->session->bindUser($user->id);
			http_response_code(202);
			return ['User' => ['ID' => $user->id]];

		case 'Register':
			if ($x = checkBadPost('ID', 'ID')) return $x;
			if ($x = checkBadPost('Pass', 'Password')) return $x;
			if (Bearweb_User::query($_POST['ID'])) {
				http_response_code(409);
				return ['Error' => 'User ID already used'];
			}
			$user = new Bearweb_User(
				id: $_POST['ID'],
				name: $_POST['ID'],
				salt: $BW->session->sKey,
				password: $_POST['Password'],
				registerTime: Bearweb_User::TIME_CURRENT,
				lastActive: Bearweb_User::TIME_CURRENT,
				group: [],
				data: [],
				avatar: null
			);
			$user->insert();
			http_response_code(201);
			return ['User' => ['ID' => $user->id]];

		default:
			throw new BW_WebServerError('Unknown task in Meta', 500);
	}
?>