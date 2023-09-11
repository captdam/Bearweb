<?php
	header('Content-Type: '.$BW->site->aux);
	if ($BW->site->template[1] == 'blob' || $BW->site->template[1] == 'externalimage') {
		echo $BW->site->content;
	} else if ($BW->site->template[1] == 'local') {
		$resource = Bearweb_Site_ResourceDir.$BW->site->content;
		if (!file_exists($resource)) throw new BW_WebServerError('Resource not found: '.$resource, 500);
		echo file_get_contents($resource);
	} else {
		$template = Bearweb_Site_TemplateDir.'object_'.$BW->site->template[1].'.php';
		if (!file_exists($template)) throw new BW_WebServerError('Secondary object template not found: '.$BW->site->template[1], 500);
		include $template;
	}
?>
