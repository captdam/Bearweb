<?php
	if ( !in_array(substr($BW->site->state, 0, 1), ['A', 'P']) || !$BW->session->user['ID'] ) { # Explicity check
		throw new BW_ServerError('Fatal config error: This API must not be opened to public', 500);
	}

	if (isset($_GET['URL'])) {
		if (!Bearweb::check('URL', $_GET['URL']))
			throw new BW_ClientError('Bad URL format', 400);

		$resource = new Bearweb_Site($_GET['URL']);
		if (
			$BW->session->user['ID'] == $resource->owner || # Check own resource, note: Bearweb::check('UserID', $_GET['User']) preventing $_GET['User'] to be '', so we don't need to worry about a guest see all system resources
			in_array('ADMIN', $BW->session->user['Group']) # Admin can see all resource of an user
		) return array (
			'URL'		=> $resource->url,
			'Category'	=> $resource->category,
			'Template'	=> $resource->template,
			'Owner'		=> $resource->owner,
			'CreateTime'	=> $resource->create,
			'ModifyTime'	=> $resource->modify,
			'Title'		=> $resource->title,
			'Keywords'	=> $resource->keywords,
			'Description'	=> $resource->description,
			'State'		=> $resource->state,
			'Content'	=> $resource->content,
			'Aux'		=> $resource->aux
		);
		throw new BW_ClientError('Go to: '.$_GET['URL'], 405);

	} else if (isset($_GET['User'])) {
		if (!Bearweb::check('UserID', $_GET['User'])) # '' will fail, so a guest won't see all system resources
			throw new BW_ClientError('Bad User ID format', 400);
		
		$db = Bearweb::db_connect(Bearweb_Site_SiteDB);
		if ( // Admin and owner can get detail info
			$BW->session->user['ID'] == $_GET['User'] || # Check own resource
			in_array('ADMIN', $BW->session->user['Group']) # Admin can see all resource of an user
		) {
			$sql = $db->prepare('SELECT `URL`, `Category`, `Template`, `Create`, `Modify`, `Title`, SUBSTR(`State`, 1, 1) AS `State` FROM `Sitemap` WHERE `Owner` = ? ORDER BY `Modify` DESC');
		} else { // Others get a simple list
			$sql = $db->prepare('SELECT `URL`, `Category`, `Modify`, `Title` FROM `Sitemap` WHERE `Owner` = ? AND `State` = `O` ORDER BY `Modify` DESC');
		}
		
		$sql->bindValue(1, $_GET['User'], PDO::PARAM_STR);
		$sql->execute();
		$resource = $sql->fetchAll();
		$sql->closeCursor();
		return $resource;
	
	} else {
		throw new BW_ClientError('No task to perform', 400);
	}
?>