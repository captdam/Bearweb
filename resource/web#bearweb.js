'use strict';

// Front-end JS.
// This JS is used to power my front-end templates.
// You can modify this file for your own needs. You may remove this file if you are using your own front-end templates. 

const _ = (s, p = document) => p.querySelector(s);
const __ = (s, p = document) => p.querySelectorAll(s);
//const ready = () => new Promise(res => window.addEventListener('load',res));
const ready = () => new Promise(res => document.readyState === "loading" ? window.addEventListener('DOMContentLoaded',res) : res);
const delay = timeout => new Promise(res => setTimeout(res, timeout));
const dom = j => {
	if (typeof j == 'string') {
		const x = (new DOMParser()).parseFromString(j, 'text/html').body;
		if (_('parsererror', x))
			throw new Error(_('parsererror', x).textContent);
		if (_('sourcetext', x))
			throw new Error(_('sourcetext', x).textContent);
		return x;
	} else {
		let tag = 'div', children = [];
		if ('_' in j) { tag = j._; delete j._; }
		if ('children' in j) { children = j.children; delete j.children; }
		const x =  Object.assign(document.createElement(tag), j);
		children.map(y => {if (y) x.appendChild(dom(y))});
		return x;
	}
};
const timestamp2str = (x = Date.now() / 1000) => { return (new Date(x * 1000)).toLocaleString('zh'); };

ready().then(() => {
	if (window.navigator.userAgent.includes('mobile')) _('meta[name=viewport]').content = 'width=1024'; // Request desktop version on mobile
	_('#header_logo').after(dom({_: 'span', id: 'header_button', textContent: '≡', onclick: event => {
		if (_('#header_button').textContent == '≡') { // Process page HTML header: Phone menu
			_('#header_button').textContent = '×';
			__('#header_topbar>*')[1].style.display = 'block';
		} else {
			_('#header_button').textContent = '≡';
			__('#header_topbar>*')[1].style.display = '';
		}
	}}));
	_('#header_logo').onclick = () => { window.scrollTo({top: 0, behavior: 'smooth'}); }; // Logo: go page top
});


// Comma-separated keywords to colorful bullets in <div><span>, prepended with #
const p2liKeywords = node => {
	let list = document.createElement('div');
	list.classList = 'keywords';
	node.textContent.split(',').map(x => {
		x = x.trim();
		if (!x.length) return;
		const e = document.createElement('span');
		e.textContent = x;
		//e.href = _('#header_search_container').getAttribute('action') + '?keywords=' + x;
		e.style.background = '#' + Math.floor(Math.random()*128+128).toString(16) + Math.floor(Math.random()*128+128).toString(16) + Math.floor(Math.random()*128+128).toString(16);
		list.appendChild(e);
	});
	node.replaceWith(list);
}
ready().then(() => { Array.prototype.slice.call(__('.content_keywords')).map(node => p2liKeywords(node)); });

// Hide elements not contain text content of any of the keywords separated by ' '
const filter = (elements, keywords) => {
	elements.forEach(x => {
		let ok = false;
		keywords.split(' ').map(s => {
			if (x.textContent.toLowerCase().includes(s.toLowerCase()))
				ok = true;
		});
		x.style.display = ok ? '' : 'none';
	});
}

// Filter on category list (and search) page
ready().then(() => {
	if (!_('#categoryfilter') || !_('#categorylist')) return;
	_('#categoryfilter').onkeyup = event => { filter(__('#categorylist>*'), event.target.value); };
	const q = new URLSearchParams(window.location.search);
	if (q.has('keywords')) {
		_('#categoryfilter').value = q.get('keywords');
		_('#categoryfilter').dispatchEvent(new Event('keyup'));
	}
});

// Cookie
const cookie = {
	get: key => decodeURIComponent(('; '+document.cookie).split('; '+key+'=').pop().split(';')[0]),
	set: (key, value, attribute) => document.cookie = key + '=' + encodeURIComponent(value) + attribute,
	remove: key => document.cookie = key + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT'
};
ready().then(async () => {
	const lang =  _('html').lang ?? 'en';
	let title = 'Cookie Consent';
	let message = '🍪 This website uses cookies for essential functionalities. By continuing accessing the site, you accept the cookies.';
	let button = 'I Accept';
	if (lang.substr(0, 2) == 'zh') {
		title = '先吃点小饼干';
		message = '🍪 本站需要使用cookie来完成一些必要功能。继续浏览本站则代表你同意本站使用cookie。';
		button = '没问题';
	}
	if (!cookie.get('BW_CookieConsent')) {
		for (;;) {
			try {
				modal(dom({children: [
					{_: 'h4', textContent: title},
					{_: 'p', textContent: message},
					{_: 'button', textContent: button, style: 'width:100%;margin:0;', onclick: () => {
						cookie.set('BW_CookieConsent', '1', ';expires=max-age;max-age=9999999999;path=/;SameSite=Strict;');
						modal();
					}},
				]}));
				break;
			} catch (e) { await delay(1); /* Wait until modal ready (modal is loaded in ready()) */ }
		}

	}
});

// Animation
const typebox = (dom, list, speed = 200, delay = 10) => {
	let text = '', _text = '', _speed = 0, _delay = 0;
	setInterval(() => {
		if (_text != text) {
			_text = text.substring(0, _text.length + 1);
			dom.innerText = _text;
		} else if (_delay) {
			_delay--;
		} else {
			_text = '';
			_delay = delay;
			text = list[Math.floor(Math.random() * list.length)];
			dom.innerText = _text;
		}
	}, speed);
};
ready().then(() => { __('typebox').forEach(node => typebox(node, node.dataset.list.split(/, */g))); });

const jumpbox = (dom, target = null, auxChars = [], speed = 50, chance = 2) => {
	const apply = () => { dom.innerText = buf.join(''); };
	let buf = [];
	let ref = [];
	for (const c of (target ? target : dom.innerText)) {
		auxChars.push(c);
		buf.push(c.charCodeAt(0) < 0x20 ? c : ' ');
		ref.push(c);
		apply();
	}
	console.log(buf, ref, auxChars);
	setInterval(() => {
		for (let i = 0; i < buf.length; i++) {
			let retry = chance;
			while (buf[i] != ref[i] && retry > 0) {
				buf[i] = auxChars[Math.floor(Math.random() * auxChars.length)];
				if (buf[i].charCodeAt(0) < 0x20)
					continue;
				retry--;
			}
		}
		apply();
	}, speed);
};

// Generate index from target DOM's <hx> tags
ready().then(() => {
	__('.hxIndex_nav').forEach(node => {
		const list = __('h1,h2,h3,h4,h5,h6,h7', _(node.dataset.index_target));
		for (let i = 0; i < list.length; i++) {
			const x = document.createElement('a');
			x.classList.add('hxIndex_list');
			x.onclick = event => {
				event.preventDefault();
				window.scrollBy({top: list[i].getBoundingClientRect().top - 80, behavior: 'smooth'});
				list[i].style.background = 'orange';
				setTimeout(() => { list[i].style.background = 'white'; }, 1500);
				setTimeout(() => { list[i].style.background = ''; }, 2000);
			};
			x.textContent = list[i].textContent;
			x.style.marginLeft = list[i].tagName.substring(1) * 4 + 'ch';
			node.appendChild(x);
			list[i].classList.add('hxIndex_target');
			list[i].onclick = event => {
				event.preventDefault();
				window.scrollBy({top: x.getBoundingClientRect().top - 80, behavior: 'smooth'});
				x.style.background = 'orange';
				setTimeout(() => { x.style.background = 'white'; }, 1500);
				setTimeout(() => { x.style.background = ''; }, 2000);
			};
		}
	});
});

// Dialog overlays
const dialog = (d, delay = 5000, color = 'lightgrey') => {
	if (typeof d == 'string')
		d = dom({textContent: timestamp2str() + ' - ' + d});
	d.style.transition = 'opacity linear 1s, background linear ' + delay / 1000 + 's';
	d.style.backgroundSize = '0% 2px, 100% 100%';
	d.style.backgroundColor = color;
	_('#dialog_container').prepend(d);
	if (delay) {
		setTimeout(() => {d.style.backgroundSize = '100% 2px, 100% 100%'}, 50);
		setTimeout(() => {d.style.opacity = 0}, delay + 50);
		setTimeout(() => {d.remove()}, delay + 1100);
	} else {
		d.style.cursor = 'crosshair';
		d.onclick = event => d.remove();
	}
}
const dialog_success = (code, message, delay = 5000) => { dialog('Success: ' + code + ' - ' + message, delay, 'green'); };
const dialog_warning = (code, message, delay = 5000) => { dialog('Warning: ' + code + ' - ' + message, delay, 'orange'); };
const dialog_error = (code, message, delay = 5000) => { dialog('Error: ' + code + ' - ' + message, delay, 'red'); };
const modal = dom => {
	if (dom == null)
		_('#modal').close();
	else {
		_('#modal').replaceChildren(dom);
		_('#modal').showModal();
	}
};
ready().then(() => { _('body').appendChild(dom({_: 'dialog', id: 'modal'})); });

// Image viewer
const imgViewer = event => {
	_('#imgViewer_img').style.background = 'center / contain no-repeat url("' + event.target.src + '")';
	_('#imgViewer_img').replaceChildren();
	if (event.target.dataset.hd) {
		const url = event.target.dataset.hd;
		const xhr = new XMLHttpRequest();
		xhr.onprogress = event => { if (event.lengthComputable) {
			_('#imgViewer_text').style.backgroundSize = Math.floor(event.loaded/event.total*100) + '% 5px, 100% 100%';
		} };
		xhr.onloadend = event => { if (xhr.status == 200) {
			const url = URL.createObjectURL(xhr.response);
			_('#imgViewer_img').appendChild(Object.assign(document.createElement('img'), {src: url}));
		} };
		xhr.open('GET', url);
		xhr.responseType = 'blob';
		xhr.send();
	}
	const text = dom({_: 'div', id: 'imgViewer_text', style: 'display:block', children: [
		{_: 'div', classList: 'layflat', children: [
			{_: 'a', id: 'imgViewer_close', textContent: 'Close'},
			{_: 'a', id: 'imgViewer_linkThumb', href: event.target.getAttribute('src'), target: '_blank', textContent: 'Thumbnail', onclick: event => event.stopImmediatePropagation()},
			event.target.dataset.hd ? {_: 'a', id: 'imgViewer_linkHd', href: event.target.dataset.hd, target: '_blank', textContent: 'HD Image', onclick: event => event.stopImmediatePropagation()} : null
		]},
		{_: 'h1', id: 'imgViewer_title', textContent: event.target.getAttribute('title') ?? event.target.dataset.title ?? event.target.getAttribute('src')},
		{_: 'p', id: 'imgViewer_description', textContent: event.target.dataset.description ?? ''},
		{_: 'p', id: 'imgViewer_caption', textContent: _('figcaption', event.target.parentNode) ? _('figcaption', event.target.parentNode).textContent : ''},
		{_: 'p', id: 'imgViewer_keywords', textContent: event.target.dataset.keywords ?? ''}
	]});
	p2liKeywords(_('#imgViewer_keywords', text));
	_('#imgViewer_text').replaceWith(text);
	_('#imgViewer').style.display = 'block';
	_('#imgViewer').scrollTo(0,0);
};
ready().then(() => {
	_('header').after(dom({_: 'div', id: 'imgViewer', children: [{_: 'div', id: 'imgViewer_img'}, {_: 'div', id: 'imgViewer_text'}], onclick: event => _('#imgViewer').style.display = 'none'}));
	__('figure>img').forEach(node => { node.onclick = event => imgViewer(event); });
});

// Homepage only: image show
ready().then(() => {
	if (!_('#home_imageshow')) return;
	let i = 0;
	const show = () => {
		_('#home_imageshow').style.background = '#000'; //Remove placeholder bgimg
		__('#home_imageshow>*:nth-child(n+2)').forEach(node => {
			node.style.opacity = 0;
			node.style.zIndex = 0;
			_('#home_imageshow>*:nth-child(n+2)>*:last-child', node).style.backgroundSize = '0 2px, 100% 100%';
		});
		const img = __('#home_imageshow>*:nth-child(n+2)')[i];
		img.style.opacity = 1;
		img.style.zIndex = 1;
		_('#home_imageshow>*:nth-child(n+2)>*:last-child', img).style.backgroundSize = '100% 2px, 100% 100%';
		i = ++i % __('#home_imageshow>*:nth-child(n+2)').length;
	};
	show();
	setInterval(show, 5000);
});