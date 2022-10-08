<?php
	header('Content-Type: '.$BW->site->getData('Aux'));
	header('Cache-Control: private, max-age=3600');

	if ($BW->site->getTemplate()[1] == 'blob') {
		echo $BW->site->getData('Content');
	} else if ($BW->site->getTemplate()[1] == 'local') {
		$resource = Bearweb_Site_ResourceDir.$BW->site->getData('Content');
		if (!file_exists($resource)) throw new BW_WebServerError('Resource not found: '.$resource, 500);
		echo file_get_contents($resource);
	} else {
		$template = Bearweb_Site_TemplateDir.'object_'.$BW->site->getTemplate()[1].'.php';
		if (!file_exists($template)) throw new BW_WebServerError('Secondary object template not found: '.$BW->site->getTemplate()[1], 500);
		include $template;
	}
?>
