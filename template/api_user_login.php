<?php
	/** Note:  This is no need to modify $BW->user.
	 * This API only needs to modify $BW->session->sessionUser, which link the current session to 
	 * the given user ID, that's the end of this API request. 
	 * On the next request, when load $BW->session, Bearweb will read the $BW->session->sessionUser, 
	 * $BW->user will be loaded with the new user ID.
	 */

	// Unlink current session
	if (!isset($_POST['ID']) && !isset($_POST['Password'])) {
		$BW->session->setUser(''); # Set to guest account ID
		http_response_code(202);
		return array(
			'User' => null
		);
	}

	// Link current session to supplied user ID
	if (!isset($_POST['ID']) || !isset($_POST['Password']))
		throw new BW_ClientError('Missing POST field: "ID", "Password"', 400);

	if (!check('UserID', $_POST['ID']) || !check('UserPassword', $_POST['Password']))
		throw new BW_ClientError('Bad user ID and/or password format', 400);
	
	$user = new Bearweb_User($_POST['ID']);
	if ($user->get(Bearweb_User::FIELD_ID) == '') # User class uses default user ID '' if init fail or no user matched
		throw new BW_ClientError('User ID not found or server busy, please try again.', 404);

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