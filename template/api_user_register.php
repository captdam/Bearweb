<?php
	if (!isset($_POST['ID']) || !isset($_POST['Password']))
		throw new BW_ClientError('Missing POST fields: "ID" and/or "Password"', 400);
	if (!Bearweb::check('UserID', $_POST['ID']) || !Bearweb::check('UserPassword', $_POST['Password']))
		throw new BW_ClientError('Bad user ID and/or password format', 400);
	
	Bearweb_Session::createUser($_POST['ID'], $_POST['ID'], $_POST['Password']);
	$user = Bearweb_Session::getUser($_POST['ID']);
	http_response_code(201);
	return array(
		'User' => array(
			'ID' =>			$user['ID'],
			'Name' =>		$user['Name'],
			'RegisterTime' =>	$user['RegisterTime'],
			'Group' =>		$user['Group']
		)
	);
?>