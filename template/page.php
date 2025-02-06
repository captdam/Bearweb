<!DOCTYPE html>
<?php header('Content-Type: text/html'); ?>
<html <?= array_key_exists('lang', $BW->site->aux) ? 'lang="'.$BW->site->aux['lang'].'"' : '' ?> data-suser="<?=$BW->session->sUser?>" ><head>
	<title><?=$BW->site->meta[0]?> - Captdam's Blog</title>
	<meta name="keywords" content="<?=$BW->site->meta[1]??''?>" />
	<meta name="description" content="<?=$BW->site->meta[2]??''?>" />
	<meta name="robots" content="<?= $BW->site->state == 'S' ? 'no' : '' ?>index" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta charset="utf-8" />
	<link href="/web/favorite.png" rel="icon" type="image/png" />
	<link href="/web/style.css" rel="stylesheet" type="text/css" />
	<link rel="canonical" href="https://captdam.com/<?=$BW->site->url?>" />
	<script src="/web/bearweb.js"></script>
	<script src="/web/md5.js"></script>
	<?= $BW->site->owner ? '<meta name="author" content="'.$BW->site->owner.'" />' : '' ?>
	<?= array_key_exists('lang-en', $BW->site->aux) ? '<link rel="alternate" hreflang="en" href="'.$BW->site->aux['lang-en'].'" type="text/html" />' : '' ?>
	<?= array_key_exists('lang-zh', $BW->site->aux) ? '<link rel="alternate" hreflang="zh" href="'.$BW->site->aux['lang-zh'].'" type="text/html" />' : '' ?>
</head><body>
	<header>
		<div id="header_topbar">
			<div><a id="header_logo">Captdam</a><span id="header_button">≡</span></div>
			<div>
				<form id="header_search_container" action="/search" method="get" target="searchtab" style="display:none !important"><input name="search" id="header_search" placeholder="Search..." disabled></form>
				<nav id="header_navCat"><ul>
					<li><a href="/">Home</a></li>
					<li><a href="/embedded">Embedded</a></li>
					<li><a href="/computer">Computer</a></li>
				</ul></nav>
				<nav id="header_navLang"><ul>
					<?= array_key_exists('lang-en', $BW->site->aux) ? '<li><a hreflang="en" href="'.$BW->site->aux['lang-en'].'">EN</a></li>' : '' ?>
					<?= array_key_exists('lang-zh', $BW->site->aux) ? '<li><a hreflang="zh" href="'.$BW->site->aux['lang-zh'].'">中</a></li>' : '' ?>
				</ul></nav>
			</div>
		</div>
	</header>
	<main>
<?php if ($BW->site->template[1] == 'error'): ?>
		<div class="sidebyside" style="grid-template-columns: 25% 1fr;">
			<img src="/web/heihua.jpg" alt="Rua~~" />
			<div>
				<h2>服务器娘进入了傲娇模式。。。。。。</h2>
				<p>总之，由于某些不可抗因素，服务器娘现在进入了傲娇模式。因此，你将无法看到这个页面。</p>
				<div class="main_note" style="--color: red;">
					<h2 id="ERROR_TITLE"><?=$BW->site->meta[0] ?? 'Error unknown'?></h2>
					<p id="ERROR_DESCRIPTION"><?=$BW->site->meta[2] ?? 'No detail description'?></p>
					<p>
						Request ID: <span id="ERROR_ID"><?=$BW->session->tID?></span><br />
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
					<li>在电脑前的地面上画上魔法阵，并咏唱：Administrator commandou sudo unlockpage -url=this -f</li>
				</ul>
			</div>
		</div>
<?php elseif ($BW->site->template[1] == 'direct'): ?>
		<?= $BW->site->content ?>
<?php elseif ($BW->site->template[1] == 'local'): ?>
		<?php
			$resource = Bearweb_Config::Site_ResourceDir.$BW->site->content;
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
			<?= array_key_exists('github', $BW->site->aux) ? '<p>Also on GitHub: <a href="'.$BW->site->aux['github'].'" target="_blank">'.$BW->site->aux['github'].'</a></p>' : '' ?>
		</div><?=$BW->site->content?>
<?php else: ?>
		<?php
			$template = Bearweb_Config::Site_TemplateDir.'page_'.$BW->site->template[1].'.php';
			if (!file_exists($template)) throw new BW_WebServerError('Secondary page template not found: '.$BW->site->template[1], 500);
			include $template;
		?>
<?php endif; ?>
	</main>
<?php if ($BW->site->owner && $BW->site->access($BW->user) == -1): ?>
	<div style="background:#DA8"><form id="editor" onchange="_('#editor_render').style.background=_('#editor_submit').style.background='red'" onsubmit="event.preventDefault(); API_Resource.update(new FormData(_('#editor'))).then(x => {
			dialog('Success');
		}, x => {
			dialog('Error: ' + x.status + ' - ' + x.error);
		});"><h1>Editor</h1>
		<div><label for="editor_url">URL</label>			<input type="text" name="URL" id="editor_url" value="<?=$BW->site->url?>" readonly /></div>
		<div><label for="editor_category">Category</label>		<select name="Category" id="editor_category">
			<option id="editor_type_embedded" value="Embedded">Embedded</option>
			<option id="editor_type_computer" value="Computer">Computer</option>
		</select></div>
		<div><label for="editor_title">Title</label>			<input type="text" name="Title" id="editor_title" /></div>
		<div><label for="editor_keywords">Keywords</label>		<input type="text" name="Keywords" id="editor_keywords" /></div>
		<div><label for="editor_state">State</label>			<input type="text" name="State" id="editor_state" /></div>
		<div><label for="editor_description">Description</label>	<textarea type="text" name="Description" id="editor_description"></textarea></div>
		<div><label for="editor_content">Content</label>		<textarea type="text" name="Content" id="editor_content"></textarea></div>
		<div><label for="editor_aux">Aux </label>			<textarea type="text" name="Aux" id="editor_aux"></textarea></div>
		<div style="display:flex;justify-content:center;margin-top:1em;">
			<button id="editor_reload" type="button" style="padding:0 2em;background:red" onclick="API_Resource.get(_('#editor_url').value).then(x => {
				['category', 'title', 'keywords', 'state', 'description', 'content', 'aux'].forEach(y => _('#editor_'+y).value = x[y]);
				dialog('Loaded');
				_('#editor_reload').style.background = 'green';
				_('#editor_render').style.background = 'red';
			}, x => {
				dialog('Error: ' + x.status + ' - ' + x.error);
			})">Reload</button>
			<button id="editor_render" type="button" style="padding:0 2em;background:grey" onclick="(() => {
				const aux = JSON.parse(_('#editor_aux').value);
				const pre = '<div class=\'tintimg\' style=\'--bgcolor:rgba(255,255,255,0.7);--bgimg:' + (aux.bgimg ?? '') + '\'><h1>' + _('#editor_title').value + '</h1><p>' + _('#editor_description').value + '</p><p class=\'content_keywords\'>' + _('#editor_keywords').value + '</p><i>--by ' + _('meta[name=author]').content + ' @ [Now]</i></div>';
				_('main').innerHTML = pre + _('#editor_content').value;
				onload_content();
				dialog('Rendered');
				_('#editor_render').style.background = 'green';
				_('#editor_submit').style.background = 'red';
			})()">Render</button>
			<button id="editor_submit" type="submit" style="padding:0 2em;background:grey" onclick="_('#editor_submit').style.background='green'">Update</button>
		</div>
	</form></div>
<?php endif; ?>
	<footer>
		<div class="sidebyside" style="grid-template-columns: 30ch 1fr;">
			<div>
				<h2>Captdam's blog</h2>
				<h3>My Links</h3>
				<p><a href="mailto:admin@beardle.com">✉ Admin E-mail</a></p>
				<p><a href="https://github.com/captdam" target="_blank"><img alt="Github logo" src="https://github.githubassets.com/images/modules/logos_page/GitHub-Mark.png" width="12px" height="12px" margin-right="3px"> My Github</a></p>
				<h3>Friend Sites</h3>
				<p><a href="https://r12f.com" target="_blank">r12f</a></p>
			</div>
			<div>
				<p>本站所有内容(包括文本和媒体资源,除非另有说明)根据CC BY-SA协议共享。</p>
				<p>All resource (include text and media, unless otherwise stated) are shared using CC BY-SA.</p>
				<p>站内所涉及部分内容为外部资源且为其各自所有者的资产，本站仅作识别所用。</p>
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
