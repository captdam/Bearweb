<!DOCTYPE html>
<?php header('Content-Type: text/html'); ?>
<html
	<?= array_key_exists('lang', $BW->site->aux) ? 'lang="'.$BW->site->aux['lang'].'"' : '' ?>
	data-suser="<?=$BW->session->sUser?>"
	data-pagestate="<?=substr($BW->site->state,0,1)?>"
><head>
	<title><?=$BW->site->meta[0]?> - Captdam's Blog</title> <meta property="og:title" content="<?=$BW->site->meta[0]?>" /><meta property="og:site_name" content="Captdam's Blog" />
	<meta name="keywords" content="<?=$BW->site->meta[1]??''?>" />
	<meta name="description" content="<?=$BW->site->meta[2]??''?>" /> <meta property="og:description" content="<?=$BW->site->meta[2]??''?>" />
	<meta name="robots" content="<?= $BW->site->state == 'S' ? 'no' : '' ?>index" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta charset="utf-8" />
	<link href="/web/favorite.png" rel="icon" type="image/png" />
	<link href="/web/style.css" rel="stylesheet" type="text/css" />
	<link rel="canonical" href="https://captdam.com/<?=$BW->site->url?>" /> <meta property="og:url" content="https://captdam.com/<?=$BW->site->url?>" />
	<script src="/web/bearweb.js"></script>
	<meta property="__og:image" content="<?= $BW->site->aux['bgimg']??'' ?>" />
	<?= $BW->site->owner ? '<meta name="author" content="'.$BW->site->owner.'" />' : '' ?>
	<?= array_key_exists('lang-en', $BW->site->aux) ? '<link rel="alternate" hreflang="en" href="'.$BW->site->aux['lang-en'].'" type="text/html" />' : '' ?>
	<?= array_key_exists('lang-zh', $BW->site->aux) ? '<link rel="alternate" hreflang="zh" href="'.$BW->site->aux['lang-zh'].'" type="text/html" />' : '' ?>
</head><body>
	<header>
		<div id="header_topbar">
			<div><a id="header_logo">Captdam</a><span id="header_button">≡</span></div>
			<div>
			<form id="header_search_container" action="/blog" method="get" target="search"><input name="s" id="header_search" placeholder="Search by keywords"></form>
				<nav id="header_navCat"><ul>
					<li><a href="/en">Home</a></li>
					<li><a href="/embedded/en">Embedded</a></li>
					<li><a href="/computer/en">Computer</a></li>
				</ul></nav>
				<nav id="header_navLang"><ul>
					<?= array_key_exists('lang-en', $BW->site->aux) ? '<li><a hreflang="en" href="'.$BW->site->aux['lang-en'].'">EN</a></li>' : '' ?>
					<?= array_key_exists('lang-zh', $BW->site->aux) ? '<li><a hreflang="zh" href="'.$BW->site->aux['lang-zh'].'">中</a></li>' : '' ?>
				</ul></nav>
			</div>
		</div>
	</header>
	<nav id="header_searchResult_container" style="height:0">
		<div>
			<p>Loading index...</p>
		</div>
	</nav>
	<main>
<?php if ($BW->site->template[1] == 'error'): ?>
		<div class="sidebyside" style="grid-template-columns: 25% 1fr;">
			<img src="/web/heihua.jpg" alt="Rua~~" />
			<div>
				<h2>The server is DOOMED</h2>
				<p>This is weird.</p>
				<div class="main_note" style="--color: red;">
					<h2 id="ERROR_TITLE"><?=$BW->site->meta[0] ?? 'Error unknown'?></h2>
					<p id="ERROR_DESCRIPTION"><?=$BW->site->meta[2] ?? 'No detail description'?></p>
					<p>
						Request ID: <span id="ERROR_ID"><?=$BW->session->tID?></span><br />
						<span class="info">This random thing could be used to fix this, waaaaaaa~~</span>
					</p>
				</div>
				<h2>What to do:</h2>
				<p>There are some <del>helpful</del> solutions:</p>
				<ul>
					<li>Coffee time. Give it a break, come back later.</li>
					<li>Verify the URL.</li>
					<li>Just assume you are able to see this page.</li>
					<li>Use page inspector to find backdoors on this page.</li>
					<li>Make a cast: <code>Administrator commandou sudo unlockpage -url=this -f</code></li>
				</ul>
			</div>
		</div>
<?php elseif ($BW->site->template[1] == 'direct'): ?>
		<?= $BW->site->content ?>
<?php elseif ($BW->site->template[1] == 'local'): ?>
		<?php
			$resource = Bearweb_Site::Dir_Resource.$BW->site->content;
			if (!file_exists($resource)) throw new BW_WebServerError('Resource not found: '.$resource, 500);
			echo file_get_contents($resource);
		?>
<?php elseif ($BW->site->template[1] == 'content'): ?>
		<div class="tintimg" style="--bgcolor:rgba(255,255,255,0.7);--bgimg:<?= $BW->site->aux['bgimg']??'' ?>">
			<h1><?=$BW->site->meta[0]?></h1>
			<p><?=$BW->site->meta[2]??''?></p>
			<p class="content_keywords"><?=$BW->site->meta[1]??''?></p>
			<i>--by <?=$BW->site->owner?> @ <?=date('M j, Y',$BW->site->modify)?></i>
			<?= array_key_exists('lang-en', $BW->site->aux) ? '<p><a hreflang="en" href="'.$BW->site->aux['lang-en'].'">[en] Here is the English version of this article</a></p>' : '' ?>
			<?= array_key_exists('lang-zh', $BW->site->aux) ? '<p><a hreflang="zh" href="'.$BW->site->aux['lang-zh'].'">【中】 这里是这篇文章的中文版</a></p>' : '' ?>
			<?= array_key_exists('github', $BW->site->aux) ? '<p class="github_logo">Also on GitHub: <a href="'.$BW->site->aux['github'].'" target="_blank">'.$BW->site->aux['github'].'</a></p>' : '' ?>
			<?= count($BW->site->meta) > 3 ? '<nav id="content_index"><h2>Index</h2>'.$BW->site->meta[3].'</nav>' : ''?>
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
	<?= $BW->site->owner && $BW->site->access($BW->user) == Bearweb_Site::ACCESS_RW ? '<script>ready().then(Interface_Resource.content.__init(\''.$BW->site->url.'\'))</script>' : '' ?>
	<footer>
		<div class="sidebyside" style="grid-template-columns: 30ch 1fr;">
			<div>
				<h2>Captdam's blog</h2>
				<h3>My Links</h3>
				<p><a href="mailto:admin@beardle.com">✉ Admin E-mail</a></p>
				<p><a class="github_logo"> My Github</a></p>
				<h3>Friend Sites</h3>
				<p><a href="https://r12f.com" target="_blank">r12f</a></p>
			</div>
			<div>
				<p>All resource (include text and media, unless otherwise stated) are shared using <a target="_blank" href="https://creativecommons.org/licenses/by-sa/4.0/deed.en">CC BY-SA</a>.</p>
				<p>Some resources from external sources are the property of their respective owners and are for identification purpose.</p>
				<p><a href="/sitemap.xml">XML Sitemap</a> <a href="/rss.xml">RSS</a></p>
			</div>
		</div>
	</footer>
	<div id="viewer_container"></div>
	<div id="dialog_container"></div>
	<div id="modal_container" onclick="modal()"><div id="modal">
		<div id="modal_close">╳</div>
		<div id="modal_content" onclick="event.stopPropagation()"></div>
	</div></div>
</body></html>
