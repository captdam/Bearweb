<?php
	header('Content-Type: application/json');
	$template = Bearweb_Site_TemplateDir.'api_'.$BW->site->template[1].'.php';
	if (!file_exists($template)) throw new BW_WebServerError('Secondary object template not found: '.$BW->site->template[1], 500);
	$data = include $template;
	echo json_encode($data);
?>
