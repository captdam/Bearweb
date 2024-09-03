'use strict';

const _ = (s, p = document) => p.querySelector(s);
const __ = (s, p = document) => p.querySelectorAll(s);
const ready = () => new Promise(res => window.addEventListener('load',res));
const dom = j => {
	let tag = 'div', children = [];
	if ('_' in j) { tag = j._; delete j._; }
	if ('children' in j) { children = j.children; delete j.children; }
	const x =  Object.assign(document.createElement(tag), j);
	children.map(y => x.appendChild(dom(y)));
	return x;
};
const timestamp2str = (x = Date.now() / 1000) => { return (new Date(x * 1000)).toLocaleString('zh'); };

const onload_frame = () => { // Front-end framework
	(() => { if (window.navigator.userAgent.includes('mobile')) _('meta[name=viewport]').content = 'width=1024'; })(); // Request desktop version on mobile
	(() => { _('#header_button').onclick = () => { // Process page HTML head: Phone menu
		_('#header_nav').style.display = _('#header_button').textContent == '≡' ? 'block' : 'none';
		_('#header_button').textContent = _('#header_button').textContent == '≡' ? '×' : '≡';
	}; })();
};
const onload_content = () => { // Content section only
	(() => { // Key words style
		Array.prototype.slice.call(__('.content_keywords')).map( (x) => {
			var k = x.textContent.split(',');
			x.textContent = '';
			k.map( (y) => {
				var w = document.createElement('a');
				w.textContent = y.trim();
				var c = '#';
				c += Math.floor(Math.random()*128+128).toString(16);
				c += Math.floor(Math.random()*128+128).toString(16);
				c += Math.floor(Math.random()*128+128).toString(16);
				w.style.backgroundColor = c;
	//			w.href = '/search?s=' + y.trim();
	//			w.target = 'search';
				x.appendChild(w);
			} );
		} );
	})();
};
ready().then(()=>{onload_frame();onload_content();});

// Animation
const typebox = (dom, list, speed = 200, delay = 10) => {
	let text = '', _text = '', _speed = 0, _delay = 0;
	setInterval(() => {
		if (_text != text) {
			_text = text.substring(0, _text.length + 1);
			dom.textContent = _text;
		} else if (_delay) {
			_delay--;
		} else {
			_text = '';
			_delay = delay;
			text = list[Math.floor(Math.random() * list.length)];
			dom.textContent = _text;
		}
	}, speed);
};

const dialog = (d, delay = 5000) => {
	if (typeof d == 'string')
		d = dom({textContent: timestamp2str() + ' - ' + d});
	d.style.transition = 'opacity linear 1s, background linear ' + delay / 1000 + 's';
	d.style.backgroundSize = '0% 2px, 100% 100%';
	_('#dialog_container').prepend(d);
	setTimeout(() => {d.style.backgroundSize = '100% 2px, 100% 100%'}, 50);
	setTimeout(() => {d.style.opacity = 0}, delay + 50);
	setTimeout(() => {d.remove()}, delay + 1100);
}

// Cookie util
const cookie = {
	get: key => decodeURIComponent(('; '+document.cookie).split('; '+key+'=').pop().split(';')[0]),
	set: (key, value) => document.cookie = key + '=' + encodeURIComponent(value),
	remove: key => document.cookie = key + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT'
};

//Modal
function modal(dom) {
	const container = _('#modal_container');
	const modal = _('#modal');
	const content = _('#modal_content');
	if (typeof dom == 'undefined') {
		modal.style.top = '-100%';
		setTimeout( () => container.style.background = 'transparent', 400);
		setTimeout( () => content.replaceChildren(), 1000);
		setTimeout( () => container.style.display = 'none', 1400);
	} else {
		content.replaceChildren(dom);
		setTimeout( () => container.style.background = 'rgba(0,0,0,0.7)', 50);
		container.style.display = 'block';
		setTimeout( () => modal.style.top = '100px', 400);
	}
	
}
function modalFormat(contents) {
	var display = [];
	contents.forEach(
		(x,i) => display.push({
			"tag" : (i ? "p" : "h2"),  //First element: i = 0 => Title
			"textContent" : x
		})
	);
	modal(domStructure({"tag":"div","child":display}));
}

const API_Resource = {
	get: (url, base64 = false) => { return new Promise( async (ok, fail) => { // Detail of a resource
		const res = await fetch('/api/resource/get', {method: 'POST', body: new URLSearchParams('URL=' + encodeURIComponent(url)), headers: {'X-Content-Encoding': base64 ? 'base64' : 'raw'}});
		const body = await res.json();
		if (res.status == 200) { ok({
			category: body.category,
			title: body.meta[0],
			keywords: body.meta[1],
			state: body.state,
			description: body.meta[2],
			content: body.content,
			aux: JSON.stringify(body.aux)
		}); } else { fail({status: res.status, error: body.Error}); }
	} ); },
	my: () => { return new Promise( async (ok, fail) => { // List of my resource (Owner)
		const res = await fetch('/api/resource/my', {method: 'POST', body: new URLSearchParams('URL=dummy')});
		const body = await res.json();
		if (res.status == 200) { ok(
			body.sort((a,b) => a.URL.localeCompare(b.URL))
		); } else { fail({status: res.status, error: body.Error}); }
	} ); },
	create: url => { return new Promise( async (ok, fail) => { // Create a resource by URL
		const res = await fetch('/api/resource/create', {method: 'POST', body: new URLSearchParams('URL=' + encodeURIComponent(url))});
		const body = await res.json();
		if (res.status == 201) { ok(); } else { fail({status: res.status, error: body.Error}); }
	} ); },
	update: (form, base64 = false) => { return new Promise( async (ok, fail) => { // Update a resource
		const res = await fetch('/api/resource/update', {method: 'POST', body: form, headers: {'X-Content-Encoding': base64 ? 'base64' : 'raw'}});
		const body = await res.json();
		if (res.status == 202) { ok(); } else { fail({status: res.status, error: body.Error}); }
	} ); },
	upload: form => {

	}
}