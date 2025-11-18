
<!DOCTYPE html><html lang="en" data-suser="<?= htmlspecialchars($BW->session->sUser, ENT_COMPAT) ?>"><head>
	<?= $BW->site->util_html_head('https://example.com/', 'Example Site') ?>
</head><body>


<?php if ($BW->site->owner && $BW->site->access($BW->user) == Bearweb_Site::ACCESS_RW): ?>
	<div>
		<p>Example content for resource owner</p>
	</div>
<?php endif; ?>

<main>
<?php if ($BW->site->template[1] == 'error'): ?>
	<div>
		<h2 id="ERROR_TITLE"><?=$BW->site->meta['title'] ?? 'Error unknown'?></h2>
		<p id="ERROR_DESCRIPTION"><?=$BW->site->meta['description'] ?? 'No detail description'?></p>
	</div>
<?php elseif ($BW->site->template[1] == 'direct'): ?>
	<?= $BW->site->content ?>
<?php elseif ($BW->site->template[1] == 'article'): ?>
	<div>
		<h1><?=$BW->site->meta['title']??''?></h1>
		<p><?=$BW->site->meta['description']??''?></p>
		<p><?=$BW->site->meta['keywords']??''?></p>
	</div>
	<?=$BW->site->content?>
<?php else: ?>
	<?php
		$template = Bearweb_Site::Dir_Template.'page_'.$BW->site->template[1].'.php';
		if (!file_exists($template)) throw new BW_WebServerError('Secondary page template not found: '.$BW->site->template[1], 500);
		include $template;
	?>
<?php endif; ?>
</main>

</body></html>