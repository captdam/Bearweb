<?php
	header('Content-Type: '.$BW->site->get(Bearweb_Site::FIELD_AUX));
	if ($BW->site->get(Bearweb_Site::FIELD_TEMPLATE)[1] == 'blob' || $BW->site->get(Bearweb_Site::FIELD_TEMPLATE)[1] == 'externalimage') {
		echo $BW->site->get(Bearweb_Site::FIELD_CONTENT);
	} else if ($BW->site->get(Bearweb_Site::FIELD_TEMPLATE)[1] == 'local') {
		$resource = Bearweb_Site_ResourceDir.$BW->site->get(Bearweb_Site::FIELD_CONTENT);
		if (!file_exists($resource)) throw new BW_WebServerError('Resource not found: '.$resource, 500);
		echo file_get_contents($resource);
	} else {
		$template = Bearweb_Site_TemplateDir.'object_'.$BW->site->get(Bearweb_Site::FIELD_TEMPLATE)[1].'.php';
		if (!file_exists($template)) throw new BW_WebServerError('Secondary object template not found: '.$BW->site->get(Bearweb_Site::FIELD_TEMPLATE)[1], 500);
		include $template;
	}
?>
