<?php
	if (!isset($_POST['ID']) || !isset($_POST['Password']))
		throw new BW_ClientError('Missing POST field: "ID", "Password"', 400);

	if (!check('UserID', $_POST['ID']) || !check('UserPassword', $_POST['Password']))
		throw new BW_ClientError('Bad user ID and/or password format', 400);
	
	$user = new Bearweb_User($_POST['ID'], $_POST['Password']); #If fail, this will throw an error and terminate the current script

	http_response_code(201);
	return array(
		'User' => array(
			'ID' =>			$user->get(Bearweb_User::FIELD_ID),
			'Name' =>		$user->get(Bearweb_User::FIELD_NAME),
			'RegisterTime' =>	$user->get(Bearweb_User::FIELD_REGISTERTIME),
			'Group' =>		$user->get(Bearweb_User::FIELD_GROUP)
		)
	);
?>