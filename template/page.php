<!DOCTYPE html>
<?php header('Content-Type: text/html'); ?>
<html
	data-sid="<?=$BW->session->sID?>"
	data-tid="<?=$BW->session->tID?>"
	data-suser="<?=$BW->session->sUser?>"
	data-pagestate="<?=substr($BW->site->state,0,1)?>"
	data-httpstate="<?=http_response_code()?>"
><head>
	<title><?=$BW->site->title?> - Captdam's blog</title>
	<meta name="keywords" content="<?=$BW->site->keywords?>" />
	<meta name="description" content="<?=$BW->site->description?>" />
	<?php if ($BW->site->owner) echo '<meta name="author" content="',$BW->site->owner,'" />'; ?>
	<meta name="robots" content="<?= $BW->site->state == 'S' ? 'no' : '' ?>index" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta charset="utf-8" />
	<link href="/web/favorite.png" rel="icon" type="image/png" />
	<link href="/web/style.css" rel="stylesheet" type="text/css" />
	<script src="/web/bearweb.js"></script>
	<script src="/web/md5.js"></script>
</head><body>
	<header>
		<h1 id="header_logo">Captdam</h1>
		<span id="header_button">≡</span>
		<form id="header_search_container" action="/search" method="get" target="searchtab" style="opacity:0%"><input name="search" id="header_search" placeholder="..." /></form>
		<nav id="header_nav">
			<a href="/">Home</a>
			<a href="/about">About</a>
			<a href="/blog">Blogs</a>
		</nav>
	</header>
	<main>
		<?php switch ($BW->site->template[1]) {
			case 'error': # Error template 
				echo '<div class="sidebyside">
					<div style="width:25%">
						<img src="/web/heihua.jpg" alt="Rua~~" />
					</div>
					<div style="width:70%">
						<h2>服务器娘进入了傲娇模式。。。。。。</h2>
						<p>总之，由于某些不可抗因素，服务器娘现在进入了傲娇模式。因此，你将无法看到这个页面。</p>
						<div class="main_note" style="--color: red;">
							<h2 id="ERROR_TITLE">',$BW->site->title ?? 'Error unknown','</h2>
							<p id="ERROR_DESCRIPTION">',$BW->site->description ?? 'No detail description','</p>
							<p>
								Request ID: <span id="ERROR_ID">',$BW->session->tID,'</span><br />
								<span class="info">据说这个神秘代码能够拿来修bug</span>
							</p>
						</div>
						<h2>解决方案：</h2>
						<p>这里有一些<del>不太靠谱的</del>解决方案：</p>
						<ul>
							<li>服务器娘这种蹭得累，放置一会兴许就好了。</li>
							<li>检查URL，也许不小心多打或者少打或者打错了一个字符。</li>
							<li>假设你已经看见了这个页面，因为只有聪明的人才能看见这个页面。</li>
							<li>这个页面有服务器娘的小秘密，或许你可以通过使用网页开发者工具/HTML检视器来偷窥。</li>
							<li>在电脑前的地面上画上魔法阵，并使用A姐的语气大声喊出：Administrator commandou sudo unlockpage -url=this -f</li>
						</ul>
					</div>
				</div>';
				break;
			case 'direct': # Direct output: echo whatever saved in database bw_site directly
				echo $BW->site->content;
				break;
			case 'local': # Local file: echo the file specifid by bw_site.content
				$resource = Bearweb_Site_ResourceDir.$BW->site->content;
				if (!file_exists($resource)) throw new BW_WebServerError('Resource not found: '.$resource, 500);
				echo file_get_contents($resource);
				break;
			case 'content': # Content template: echo whatever saved in database bw_site, but also include meta data
				$aux = json_decode($BW->site->aux, true);
				echo '<div style="',
					is_array($aux) && array_key_exists('bgimg', $aux) ? 'background-size:cover;background-position:center;background-image:linear-gradient(180deg,rgba(255,255,255,0.9),rgba(255,255,255,0.7)),'.$aux['bgimg'].'; ' : '',
					'"><h1>',$BW->site->title,'</h1>
					<p>',$BW->site->description,'</p>
					<p class="content_keywords">',$BW->site->keywords,'</p>
					<i>--by ',$BW->site->owner,' @ ',date('Y-m-d H:i',$BW->site->modify),' GMT',
						$BW->site->modify == $BW->site->create ? '' : (' <span class="info" title="orginal post @ '.date('Y-m-d H:i', $BW->site->create).' GMT'.'">edited</span>'),
					'</i>',
					is_array($aux) && array_key_exists('lang-en', $aux) ? '<p><a href="'.$aux['lang-en'].'">[en] Here is the English version of this article</a></p>' : '',
					is_array($aux) && array_key_exists('lang-zh', $aux) ? '<p><a href="'.$aux['lang-zh'].'">【中】 这里是这篇文章的中文版</a></p>' : '',
				'</div>', $BW->site->content;
				break;
			default: # Custom template
				$template = Bearweb_Site_TemplateDir.'page_'.$BW->site->template[1].'.php';
				if (!file_exists($template)) throw new BW_WebServerError('Secondary page template not found: '.$BW->site->template[1], 500);
				include $template;
		} ?>
	</main>
	<footer>
		<div class="sidebyside">
			<div style="width:25%;">
				<h2>Captdam's blog</h2>
				<h3>My Links</h3>
				<p><a href="mailto:admina@beardle.com">✉ Admin E-mail</a></p>
				<p><a href="https://github.com/captdam" target="_blank"><img alt="Github logo" src="https://github.githubassets.com/images/modules/logos_page/GitHub-Mark.png" width="12px" height="12px" margin-right="3px"> My Github</a></p>
				<h3>Friend Sites</h3>
				<p><a href="https://r12f.com" target="_blank">r12f</a></p>
			</div>
			<div style="width:70%;">
				<p>本站所有内容(包括文本和媒体资源,除非另有说明)根据CC BY-SA协议共享。</p>
				<p>All resource (include text and media, unless otherwise stated) are shared using CC BY-SA.</p>
				<p>站内所涉及部分内容为外部资源且为其各自所有者的资产，本站仅作识别所用。</p>
				<p>Some resources from external sources are the property of their respective owners and are for identification purpose.</p>
			</div>
		</div>
	</footer>
	<div id="side" style="display:none;">
		<img src="/web/top.png" alt="Top of page" title="To page top" />
	</div>
	<div id="side_panel_left" class="side_panel" style="left: -320px"></div>
	<div id="side_panel_right" class="side_panel" style="right: -320px"></div>
	<div id="side_panel_ctrlleft" class="side_panel_control" style="left: 0"></div>
	<div id="side_panel_ctrlright" class="side_panel_control" style="right: 0"></div>
	<div id="modal_container" onclick="modal()"><div id="modal">
		<div id="modal_close">╳</div>
		<div id="modal_content" onclick="event.stopPropagation()"></div>
	</div></div>
</body></html>
