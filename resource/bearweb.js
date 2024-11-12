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
	(() => { _('#viewer_container').onclick = () => { _('#viewer_container').style.display = 'none'; }; })();// Viewer for figures
	(() => { // Special page
		if (['A', 'P'].includes(_('html').dataset.pagestate)) {
			_('html').style.setProperty('--highlight-bgcolor', '#622');
			_('html').style.setProperty('--content-bgcolor0', '#EDD');
			_('html').style.setProperty('--content-bgcolor1', '#F6D0D0');
		}
	})();
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
	(() => { // Viewer for figures
		__('.main_content figure>img').forEach(x => {
			x.onclick = () => {
				_('#viewer_container').style.background = 'center/contain no-repeat url(' + x.src + ')';
				_('#viewer_container').style.display = 'block';
			};
		});
	})();
	(() => { // Special page
		if (['A', 'P'].includes(_('html').dataset.pagestate)) {
			_('html').style.setProperty('--highlight-bgcolor', '#622');
			_('html').style.setProperty('--content-bgcolor0', '#EDD');
			_('html').style.setProperty('--content-bgcolor1', '#F6D0D0');
		}
	})();
	(() => { // Index

	})();
};
ready().then(()=>{onload_frame();onload_content();});

const cookie = {
	get: key => decodeURIComponent(('; '+document.cookie).split('; '+key+'=').pop().split(';')[0]),
	set: (key, value) => document.cookie = key + '=' + encodeURIComponent(value),
	remove: key => document.cookie = key + '=; expires=Thu, 01 Jan 1970 00:00:00 GMT'
};

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

// APIs

const API_Resource = {
	get: (url, base64 = false) => { return new Promise( async (ok, fail) => { // Detail of a resource
		const res = await fetch('/api/resource/get', {method: 'POST', body: new URLSearchParams({URL: url}), headers: {'X-Content-Encoding': base64 ? 'base64' : 'raw'}});
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
		const res = await fetch('/api/resource/my', {method: 'POST', body: new URLSearchParams({URL: 'dummy'})});
		const body = await res.json();
		if (res.status == 200) { ok(
			body.sort((a,b) => a.URL.localeCompare(b.URL))
		); } else { fail({status: res.status, error: body.Error}); }
	} ); },
	create: url => { return new Promise( async (ok, fail) => { // Create a resource by URL
		const res = await fetch('/api/resource/create', {method: 'POST', body: new URLSearchParams({URL: url})});
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

const API_User = {
	__hash: async src => await window.crypto.subtle.digest('SHA-384', (new TextEncoder()).encode(src)),
	login: (id, pass, passnew = null) => { return new Promise( async (ok, fail) => { // Login and change password (everytime login will change password hash on server-side)
		const response1 = await (await fetch('/api/user/loginkey', {method: 'POST', body: new URLSearchParams({ID: id})})).json();
		if ('Error' in response1) {
			fail({status: response1.status, error: 'Login failed (1. Get key): ' + response1.Error});
			return;
		}
		const salt = response1.User.Salt;
		const sessionKey = cookie.get('BW_SessionKey'); // Server has same key for the same session
		const p1 = await API_User.__hash(atob(salt) + pass); // Old hash saved on server
		const p2 = await API_User.__hash(sessionKey + btoa(String.fromCharCode(...new Uint8Array(p1)))); // Use salt to verify with server
		const pn = await API_User.__hash(atob(sessionKey) + passnew ?? pass); // Update hash on server with new salt
		const response2 = await (await fetch('/api/user/login', {method: 'POST', body: new URLSearchParams({ID: id, Password: pass, PasswordNew: passnew})})).json();
		if ('Error' in response2) {
			fail({status: response2.status, error: 'Login failed (2. Submit credentials): ' + response2.Error});
			return;
		}
		console.log('XXX');
		ok();
	} ); }
}