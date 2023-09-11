<?php
	// Modify user data if POST any
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if (!$BW->session->user['ID']) # False for guest ('')
			throw new BW_ClientError('Login first to modify user data', 401);
		
		$data = array();
		if (isset($_POST['Name'])) {
			if (!Bearweb::check('UserName', $_POST['Name']))
				throw new BW_ClientError('Bad user name', 400);
			$data['Name'] = $_POST['Name'];
		}
		if (isset($_POST['Password'])) {
			if (!Bearweb::check('UserPassword', $_POST['Password']))
				throw new BW_ClientError('Bad user password', 400);
				$data['Password'] = $_POST['Password'];
		}

		if (isset($data['Name']) && isset($data['Password'])) {
			Bearweb_Session::setUser(id: $BW->session->user['ID'], name: $data['Name'], password: $data['Password']);
			http_response_code(202);
			return ['Name', 'Password'];
		} else if (isset($data['Name'])) {
			Bearweb_Session::setUser(id: $BW->session->user['ID'], name: $data['Name']);
			http_response_code(202);
			return ['Name'];
		} else if (isset($data['Password'])) {
			Bearweb_Session::setUser(id: $BW->session->user['ID'], password: $data['Password']);
			http_response_code(202);
			return ['Password'];
		}
		http_response_code(204);
		return null;
	}

	// Return user data for given ID
	if (isset($_GET['ID'])) {
		if (!Bearweb::check('UserID', $_GET['ID']))
			throw new BW_ClientError('Bad user ID', 400);
		$user = Bearweb_Session::getUser($_GET['ID']);
 		return $_GET['ID'] == $BW->session->user['ID'] ? array( 'User' => array(
			'ID' =>			$user['ID'],
			'Name' =>		$user['Name'],
			'RegisterTime' =>	$user['RegisterTime'],
			'Group' =>		$user['Group']
		) ) : array( 'User' => array(
			'ID' =>			$user['ID'],
			'Name' =>		$user['Name']
		) );
	}

	// Return user ID for current user (if login)
	if ($BW->session->user['ID']) {
		return array( 'User' => array(
			'ID' =>			$BW->session->user['ID'],
			'Name' =>		$BW->session->user['Name'],
			'RegisterTime' =>	$BW->session->user['RegisterTime'],
			'Group' =>		$BW->session->user['Group']
		) );
	}

	throw new BW_ClientError('No specific user ID', 400);
?>