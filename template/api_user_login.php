<?php
	// Unlink current session
	$BW->session->setUser(''); # Set to guest account ID
	if (!isset($_POST['ID']) && !isset($_POST['Password'])) {
		http_response_code(202);
		return return array(
			'User' => null
			)
		);
	}

	// Link current session to supplied user ID
	if (!isset($_POST['ID']) || !isset($_POST['Password']))
		throw new BW_ClientError('Missing POST field: "ID", "Password"', 400);

	if (!check('UserID', $_POST['ID']))
		throw new BW_ClientError('User ID in bad format: A-Za-z0-9_-', 400);
	
	$user = new Bearweb_User($_POST['ID']);
	if ($user->get(Bearweb_User::FIELD_ID) == '') # User class uses default user ID '' if init fail or no user matched
		throw new BW_ClientError('User ID not found', 404);

	if (!$user->verifyPassword($_POST['Password']))
		throw new BW_ClientError('Bad password', 400);
	
	$BW->session->setUser($user->get(Bearweb_User::FIELD_ID));
	http_response_code(202);
	return array(
		'User' => array(
			'ID' =>			$user->get(Bearweb_User::FIELD_ID),
			'Name' =>		$user->get(Bearweb_User::FIELD_NAME),
			'RegisterTime' =>	$user->get(Bearweb_User::FIELD_REGISTERTIME),
			'Group' =>		$user->get(Bearweb_User::FIELD_GROUP)
		)
	);
?>