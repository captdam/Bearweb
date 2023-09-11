<?php
	// Unlink current session
	if (!isset($_POST['ID']) && !isset($_POST['Password'])) {
		$BW->session->bindUser(''); # Set to guest account ID
		http_response_code(202);
		return array(
			'User' => null
		);
	}

	// Link current session to supplied user ID
	if (!isset($_POST['ID']) || !isset($_POST['Password']))
		throw new BW_ClientError('Missing POST fields: "ID" and/or "Password"', 400);
	if (!Bearweb::check('UserID', $_POST['ID']) || !Bearweb::check('UserPassword', $_POST['Password']))
		throw new BW_ClientError('Bad user ID and/or password format', 400);
	
	$user = Bearweb_Session::getUser($_POST['ID'], $_POST['Password']); # Throws BW_ClientError if password is wrong
	$BW->session->bindUser($user['ID']);
	http_response_code(202);
	return array(
		'User' => array(
			'ID' =>			$user['ID'],
			'Name' =>		$user['Name'],
			'RegisterTime' =>	$user['RegisterTime'],
			'Group' =>		$user['Group']
		)
	);
?>