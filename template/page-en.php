<!DOCTYPE html>
<?php
	if ($BW->site->template[0] == 'page-zh')
		echo '<html lang="zh">';
	else
		echo '<html lang="en">';
?>
<head>
	<title><?= htmlspecialchars($BW->site->meta['title'] ?? '', ENT_COMPAT) ?> - Bearweb CMS</title>
	<meta property="og:title" content="<?= htmlspecialchars($BW->site->meta['title'] ?? '', ENT_COMPAT) ?>" />
	<meta property="og:site_name" content="Bearweb CMS" />
	<meta property="og:type" content="website" />
	<meta name="keywords" content="<?= htmlspecialchars($BW->site->meta['keywords'] ?? '', ENT_COMPAT) ?>" />
	<meta name="description" content="<?= htmlspecialchars($BW->site->meta['description'] ?? '', ENT_COMPAT) ?>" />
	<meta property="og:description" content="<?= htmlspecialchars($BW->site->meta['description'] ?? '', ENT_COMPAT) ?>" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta charset="utf-8" />
	<link href="/web/style.css" rel="stylesheet" type="text/css" />
	<script src="/web/bearapi.js"></script>
	<script src="/web/bearweb.js"></script>
	<link rel="canonical" href="'https://bearweb.captdam.com/'<?= $BW->site->url ?>" />
	<meta property="og:url" content="'https://bearweb.captdam.com/'<?= $BW->site->url ?>" />
	<?=  array_key_exists('robots', $BW->site->meta) ? ('<meta name="robots" content="'.htmlspecialchars($BW->site->meta['robots'], ENT_COMPAT).'" />') : '' ?>
	<?=  array_key_exists('img', $BW->site->meta) ? ('<meta property="__og:image" content="/'.htmlspecialchars($BW->site->meta['img'], ENT_COMPAT).'" />') : '' ?>
	<?=  $BW->site->owner ? ('<meta name="author" content="'.htmlspecialchars($BW->site->owner, ENT_COMPAT).'" />') : '' ?>
	<?=  array_key_exists('lang-en', $BW->site->aux) ? ('<link rel="alternate" hreflang="en" href="/'.htmlspecialchars($BW->site->aux['lang-en'], ENT_COMPAT).'" type="text/html" />') : '' ?>
	<?=  array_key_exists('lang-zh', $BW->site->aux) ? ('<link rel="alternate" hreflang="zh" href="/'.htmlspecialchars($BW->site->aux['lang-zh'], ENT_COMPAT).'" type="text/html" />') : '' ?>
</head><body>
	<header>
		<div id="header_topbar">
			<div><a id="header_logo">Bearweb</a></div>
			<div>
				<?php if ($BW->site->template[0] == 'page-zh'): ?>
					<form id="header_search_container" action="/search/zh" method="get" target="search"><input name="keywords" id="header_search" placeholder="使用关键字搜索 ⏎"></form>
					<nav id="header_navCat"><ul>
						<li><a href="/zh">主页</a></li>
						<li><a href="/framework/zh">架构</a></li>
						<li><a href="/template/zh">模板</a></li>
					</ul></nav>
				<?php else: ?>
					<form id="header_search_container" action="/search/en" method="get" target="search"><input name="keywords" id="header_search" placeholder="Search by keywords ⏎"></form>
					<nav id="header_navCat"><ul>
						<li><a href="/">Home</a></li>
						<li><a href="/framework/en">Framework</a></li>
						<li><a href="/template/en">Templates</a></li>
					</ul></nav>
				<?php endif; ?>
				<nav id="header_navLang"><ul>
					<?= array_key_exists('lang-en', $BW->site->aux) ? '<li><a hreflang="en" href="/'.htmlspecialchars($BW->site->aux['lang-en'], ENT_COMPAT).'">EN</a></li>' : '' ?>
					<?= array_key_exists('lang-zh', $BW->site->aux) ? '<li><a hreflang="zh" href="/'.htmlspecialchars($BW->site->aux['lang-zh'], ENT_COMPAT).'">中</a></li>' : '' ?>
				</ul></nav>
			</div>
		</div>
	</header>
	<?php if ($BW->site->owner && $BW->site->access($BW->user) == Bearweb_Site::ACCESS_RW) $BW->site->util_html_inplaceEditor(); ?>
	<main>
		<?php if ( array_key_exists('access', $BW->site->meta) || array_key_exists('robots', $BW->site->meta) ): ?>
			<div class="sidebyside" style="grid-template-columns: 100px 1fr; background: url('https://bearweb.captdam.com/web/strip.svg');">
				<span style="font-size: 100px; line-height: 100px;">⚠</span>
				<div><?php if ($BW->site->template[0] == 'page-zh'): ?>
					<b style="font-size:xx-large">注意！</b>
					<?= array_key_exists('access', $BW->site->meta) ? '<p>访问限制资源。</p>' : '' ?>
					<?= array_key_exists('robots', $BW->site->meta) ? '<p>禁用搜索索引资源。</p>' : '' ?>
				<?php else: ?>
					<b style="font-size:xx-large">CAUTION!</b>
					<?= array_key_exists('access', $BW->site->meta) ? '<p>Access controlled resource.</p>' : '' ?>
					<?= array_key_exists('robots', $BW->site->meta) ? '<p>No search index resource.</p>' : '' ?>
				<?php endif; ?></div>
			</div>
		<?php endif; ?>
		<?php if ($BW->site->template[1] == 'error'): ?>
			<div class="sidebyside" style="grid-template-columns: 25% 1fr;">
				<svg viewBox="0 0 10 10"><text x="1" y="9" style="font-size:9px">⚠</text></svg>
				<div><?php if ($BW->site->template[0] == 'page-zh'): ?>
					<h2>⛒服务器娘进入了傲娇模式。。。。。。</h2>
					<p>总之，由于某些不可抗因素，服务器娘现在进入了傲娇模式。因此，你将无法看到这个页面。</p>
					<div class="main_note_error">
						<h2 id="ERROR_TITLE"><?= htmlspecialchars($BW->site->meta['title']??'不可描述错误', ENT_COMPAT) ?></h2>
						<p id="ERROR_DESCRIPTION"><?= htmlspecialchars($BW->site->meta['description']??'当你凝视深渊，深渊也在凝视你。', ENT_COMPAT) ?></p>
						<p>
							Request ID: <span id="ERROR_ID"><?= htmlspecialchars($BW->session->tID, ENT_COMPAT) ?></span><br />
							<span class="info">据说这个神秘代码能够拿来修bug</span>
						</p>
					</div>
					<h2>🛠解决方案：</h2>
					<p>这里有一些<del>不太靠谱的</del>解决方案：</p>
					<ul>
						<li>服务器娘这种蹭得累，放置一会兴许就好了。</li>
						<li>检查URL，也许不小心多打或者少打或者打错了一个字符。</li>
						<li>假设你已经看见了这个页面，因为只有聪明的人才能看见这个页面。</li>
						<li>这个页面有服务器娘的小秘密，或许你可以通过使用网页开发者工具/HTML检视器来偷窥。</li>
						<li>在电脑前的地面上画上魔法阵，并咏唱：Administrator commandou sudo unlockpage -url=this -f</li>
					</ul>
					<?php else: ?>
					<h2>⛒The server is DOOMED</h2>
					<p>This is weird.</p>
					<div class="main_note_error">
						<h2 id="ERROR_TITLE"><?= htmlspecialchars($BW->site->meta['title']??'Error unknown', ENT_COMPAT) ?></h2>
						<p id="ERROR_DESCRIPTION"><?= htmlspecialchars($BW->site->meta['description']??'No detail description', ENT_COMPAT) ?></p>
						<p>
							Request ID: <span id="ERROR_ID"><?= htmlspecialchars($BW->session->tID, ENT_COMPAT) ?></span><br />
							<span class="info">This random thing could be used to fix this, waaaaaaa~~</span>
						</p>
					</div>
					<h2>🛠What to do:</h2>
					<p>There are some <del>helpful</del> solutions:</p>
					<ul>
						<li>Coffee time. Give it a break, come back later.</li>
						<li>Verify the URL.</li>
						<li>Just assume you are able to see this page.</li>
						<li>Use page inspector to find backdoors on this page.</li>
						<li>Make a cast: <code>Administrator commandou sudo unlockpage -url=this -f</code></li>
					</ul>
				<?php endif; ?></div>
			</div>
		<?php elseif ($BW->site->template[1] == 'direct'): ?>
			<?= $BW->site->content ?>
		<?php elseif ($BW->site->template[1] == 'langsel'): ?>
			<div>
				<?php if ($BW->site->template[0] == 'page-zh'): ?>
					<p>多语言着陆页</p>
					<h1>跳转中……</h1>
				<?php else: ?>
					<p>Multilingual landing page</p>
					<h1>Redirecting...</h1>
				<?php endif; ?>
				<ul><?php
					foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? 'x', 65) as $lang) {
						$lang = explode(';', trim($lang), 2)[0];
						if (array_key_exists('lang-'.$lang, $BW->site->aux)) {
							http_response_code(302);
							header('Location: /'.$BW->site->aux['lang-'.$lang]);
							echo '<script>window.location=\'/',htmlspecialchars($BW->site->aux['lang-'.$lang], ENT_COMPAT),'\';</script>';
							break;
						}
					}
					if (array_key_exists('lang-en', $BW->site->aux))
						echo '<li style="list-style: \'en \'"><a hreflang="en" href="/', htmlspecialchars($BW->site->aux['lang-en'], ENT_COMPAT), '">Here is the English version of this article</a></li>';
					if (array_key_exists('lang-zh', $BW->site->aux))
						echo '<li style="list-style: \'中 \'"><a hreflang="zh" href="/', htmlspecialchars($BW->site->aux['lang-zh'], ENT_COMPAT), '">这里是这篇文章的中文版</a></li>';
					
				?></ul>
			</div>
			<script></script>
		<?php elseif ($BW->site->template[1] == 'bulletin' || $BW->site->template[1] == 'catalog'): ?>
			<div class="tintimg home_categoryOverview sidebyside" style="--bgcolor: rgba(0,0,0,0.75); --bgimg:url(/<?= $BW->site->meta['img']??'web/banner.jpeg' ?>); color: #FFF; grid-template-columns: 40ch 1fr;"><div>
				<h1><?= htmlspecialchars($BW->site->meta['title']??'', ENT_COMPAT) ?></h1>
			</div><div>
				<p><?= htmlspecialchars($BW->site->meta['description']??'', ENT_COMPAT) ?></p>
				<?php
					if ($BW->site->template[1] == 'catalog') {
						if ($BW->site->template[0] == 'page-zh')
							echo '<input id="categoryfilter" placeholder="输入关键字过滤" />';
						else
							echo '<input id="categoryfilter" placeholder="Type keywords to filter result" />';
					}	
				?>
			</div></div>
			<?php
				if ($BW->site->template[1] == 'bulletin')
					echo '<div class="list_vertical" id="bulletinlist">', $BW->site->content, '</div>';
				else
					echo '<div class="list_horizontal" id="categorylist">', $BW->site->content, '</div>';
			?>
		<?php elseif ($BW->site->template[1] == 'article'): ?>
			<div class="tintimg" style="--bgcolor: rgba(255,255,255,0.75); --bgimg: url(/<?= htmlspecialchars($BW->site->meta['img']??'web/banner.jpeg', ENT_COMPAT) ?>); color: #000;">
				<h1><?= htmlspecialchars($BW->site->meta['title']??'', ENT_COMPAT) ?></h1>
				<p><?= htmlspecialchars($BW->site->meta['description']??'', ENT_COMPAT) ?></p>
				<p class="content_keywords"><?= htmlspecialchars($BW->site->meta['keywords']??'', ENT_COMPAT) ?></p>
				<i>--by <?=$BW->site->owner?> @ <?=date('M j, Y',$BW->site->modify)?><?php if ($BW->site->modify != $BW->site->create) echo ' <del>',date('M j, Y',$BW->site->create),'</del>' ?></i>
				<?php
					echo '<ul>';
						if (array_key_exists('github', $BW->site->aux))
							echo '<li style="list-style: \'💻 \'">Also on GitHub: <a href="', htmlspecialchars($BW->site->aux['github'], ENT_COMPAT), '" target="_blank">', htmlspecialchars($BW->site->aux['github'], ENT_COMPAT), '</a></li>';
						if (array_key_exists('lang-en', $BW->site->aux))
							echo '<li style="list-style: \'en \'"><a hreflang="en" href="/', htmlspecialchars($BW->site->aux['lang-en'], ENT_COMPAT), '">Here is the English version of this article</a></li>';
						if (array_key_exists('lang-zh', $BW->site->aux))
							echo '<li style="list-style: \'中 \'"><a hreflang="zh" href="/', htmlspecialchars($BW->site->aux['lang-zh'], ENT_COMPAT), '">这里是这篇文章的中文版</a></li>';
					echo '</ul><h2>Index</h2><nav class="hxIndex_nav" data-index_target="main"></nav>';
				?>
			</div>
			<?= $BW->site->content ?>
		<?php elseif ($BW->site->template[1] == 'image'): ?>
			<div class="main_wide" style="padding: 0; text-align: center; background: center/contain no-repeat url(/<?= htmlspecialchars($BW->site->meta['img']??'', ENT_COMPAT) ?>), #BBB; height: 100vh;">
				<img src="/<?= htmlspecialchars($BW->site->meta['hd']??'', ENT_COMPAT) ?>" style="width: 100%; height: 100%; object-fit: contain; opacity: 0.25;" onload="this.style.opacity=1" />
			</div>
			<div class="tintimg" style="--bgcolor: rgba(255,255,255,0.75); --bgimg: url(/web/banner.jpeg); color: #000;">
				<h1><?= htmlspecialchars($BW->site->meta['title']??'', ENT_COMPAT) ?></h1>
				<p><?= htmlspecialchars($BW->site->meta['description']??'', ENT_COMPAT) ?></p>
				<p class="content_keywords"><?= htmlspecialchars($BW->site->meta['keywords']??'', ENT_COMPAT) ?></p>
				<i>--by <?=$BW->site->owner?> @ <?=date('M j, Y',$BW->site->modify)?><?php if ($BW->site->modify != $BW->site->create) echo ' <del>',date('M j, Y',$BW->site->create),'</del>' ?></i>
				<?php
					echo '<ul>';
						if (array_key_exists('lang-en', $BW->site->aux))
							echo '<li style="list-style: \'en \'"><a hreflang="en" href="/', htmlspecialchars($BW->site->aux['lang-en'], ENT_COMPAT), '">Here is the English version of this article</a></li>';
						if (array_key_exists('lang-zh', $BW->site->aux))
							echo '<li style="list-style: \'中 \'"><a hreflang="zh" href="/', htmlspecialchars($BW->site->aux['lang-zh'], ENT_COMPAT), '">这里是这篇文章的中文版</a></li>';
					echo '</ul><nav class="layflat">';
					if (array_key_exists('img', $BW->site->meta))
						echo '<a href="/', htmlspecialchars($BW->site->meta['img']??'', ENT_COMPAT), '">Thumb</a>';
					if (array_key_exists('hd', $BW->site->meta))
						echo '<a href="/', htmlspecialchars($BW->site->meta['hd']??'', ENT_COMPAT), '">HD</a>';
					echo '</nav>';
				?>
			</div>
			<?= $BW->site->content ?>
		<?php else: throw new BW_WebServerError('Secondary page template not found: '.$BW->site->template[1], 500); endif; ?>
	</main>
	<footer class="tintimg" style="--bgcolor: rgba(0,0,0,0.25); --bgimg: url(/web/banner.jpeg); color:#FFF;">
		<?php if ($BW->site->template[0] == 'page-zh'): ?>
			<div>
				<h2>Bearweb CMS</h2>
				<p>轻量化，可移植的数据库驱动内容管理系统。专为个人博客与小型组织网站设计。</p>
			</div>
		<?php else: ?>
			<div>
				<h2>Bearweb CMS</h2>
				<p>A light-weight, portable database-driven content management system designed for personal blog and small-size organization website.</p>
			</div>
		<?php endif; ?>
	</footer>
	<div id="dialog_container"></div>
</body></html>