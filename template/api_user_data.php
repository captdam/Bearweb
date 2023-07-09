<?php
	// Modify user data
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if ($BW->user->get(Bearweb_User::FIELD_ID) == '')
			throw new BW_ClientError('Login first to modify user data', 401);
		
		$data = array();
		if (isset($_POST['Name'])) {
			if (!check('UserName', $_POST['Name']))
				throw new BW_ClientError('Bad user name', 400);
			$data[Bearweb_User::FIELD_NAME] = $_POST['Name'];
		}
		if (isset($_POST['Avatar'])) {
			if (!check('Avatar', $_POST['UserAvatar']))
				throw new BW_ClientError('Bad user avatar', 400);
			$data[Bearweb_User::FIELD_AVATAR] = base64_decode($_POST['Avatar']);
		}
		if ($data)
			$BW->user->set($data);

		if (isset($_POST['Password'])) {
			if (!check('UserPassword', $_POST['Password']))
				throw new BW_ClientError('Bad user password', 400);
			$BW->user->updatePassword($_POST['Password']);
		}

		http_response_code(202);
		return null;
	}

	// Return user data for given ID
	if (isset($_GET['ID'])) {
		if (!check('UserID', $_GET['ID']))
			throw new BW_ClientError('Bad user ID', 400);

		$user = new Bearweb_User($_GET['ID']);
		if ($user->get(Bearweb_User::FIELD_ID) == '') {
			http_response_code(404);
			return null;
		}

		$x = array(
			'ID' =>			$user->get(Bearweb_User::FIELD_ID),
			'Name' =>		$user->get(Bearweb_User::FIELD_NAME),
			//'RegisterTime' =>	$user->get(Bearweb_User::FIELD_REGISTERTIME),
			//'Group' =>		$user->get(Bearweb_User::FIELD_GROUP)
		);
		if (isset($_GET['Avatar']))
			$x['Avatar'] = base64_encode($user->get(Bearweb_User::FIELD_AVATAR));
		
		http_response_code(200);
		return $x;
	}

	// Return user ID for current user
	if ($BW->user->get(Bearweb_User::FIELD_ID)) {
		$x = array(
			'ID' =>			$BW->user->get(Bearweb_User::FIELD_ID),
			'Name' =>		$BW->user->get(Bearweb_User::FIELD_NAME),
			'RegisterTime' =>	$BW->user->get(Bearweb_User::FIELD_REGISTERTIME),
			'Group' =>		$BW->user->get(Bearweb_User::FIELD_GROUP)
		);
		if (isset($_GET['Avatar']))
			$x['Avatar'] = base64_encode($BW->user->get(Bearweb_User::FIELD_AVATAR));
		
		http_response_code(200);
		return $x;
	}

	//No ID specified and current user is guest
	throw new BW_ClientError('No specific user ID', 400);
?>