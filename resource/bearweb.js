'use strict';

const _ = (s, p = document) => p.querySelector(s);
const __ = (s, p = document) => p.querySelectorAll(s);
const ready = () => new Promise(res => window.addEventListener('load',res));
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
		children.map(y => x.appendChild(dom(y)));
		return x;
	}
};
const timestamp2str = (x = Date.now() / 1000) => { return (new Date(x * 1000)).toLocaleString('zh'); };

const onload_frame = () => { // Front-end framework
	(() => { if (window.navigator.userAgent.includes('mobile')) _('meta[name=viewport]').content = 'width=1024'; })(); // Request desktop version on mobile
	(() => { _('#header_button').onclick = () => { // Process page HTML header: Phone menu
		__('#header_topbar>*')[1].style.display = _('#header_button').textContent == '≡' ? 'block' : 'none';
		_('#header_button').textContent = _('#header_button').textContent == '≡' ? '×' : '≡';
	}; })();
	(() => { _('#header_logo').onclick = () => { window.scrollTo({top: 0, behavior: 'smooth'}); }; })(); // Logo: go page top
	(() => { _('#header_search').onfocus = async () => {
		const index = (new DOMParser()).parseFromString(await (await fetch('/sitemap.xml')).text(), 'text/xml').firstChild.children;
	}; })();
	(() => { _('#viewer_container').onclick = () => { _('#viewer_container').style.display = 'none'; }; })();// Viewer for figures
};
const onload_content = () => { // Content section only (for content reload, such as in-place modify)
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
				w.href = '/blog?s=' + y.trim();
				w.target = 'search';
				x.appendChild(w);
			} );
		} );
	})();
	(() => { // Viewer for figures
		__('.main_content figure>img').forEach(x => {
			x.onclick = () => {
				const link = x.getAttribute('src');
				_('#viewer_container').style.background = 'center/contain no-repeat url(' + link + ')';
				_('#viewer_container').style.display = 'block';
				_('#viewer_container').replaceChildren(dom({_: 'a', id: 'viewer_container_link', href: link, textContent: 'View figure - URL: ' + link}));
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
	(() => { // Index generation for blog content page
		Array.prototype.slice.call(__('.content_index_nav')).map(nav => {
			const target = _(nav.getAttribute('href'));
			nav.style.marginLeft = target.tagName.substring(1) * 4 + 'ch';
			nav.onclick = event => {
				event.preventDefault();
				window.scrollBy({top: target.getBoundingClientRect().top - 80, behavior: 'smooth'});
				target.style.background = 'orange';
				setTimeout(() => { target.style.background = 'white'; }, 1500);
				setTimeout(() => { target.style.background = ''; }, 2000);
			};
		});
	})();
};
ready().then(()=>{onload_frame();onload_content();});

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

// APIs

const API_Resource = {
	get: (url, base64 = false) => { return new Promise( async (ok, fail) => { // Detail of a resource
		const res = await fetch('/api/resource/get', {method: 'POST', body: new URLSearchParams({URL: url}), headers: {'X-Content-Encoding': base64 ? 'base64' : 'raw'}});
		if (res.status == 200) {
			let resource = await res.json();
			ok(resource);
		} else { fail({status: res.status, error: body.Error}); }
	} ); },
	my: () => { return new Promise( async (ok, fail) => { // List of my resource (Owner)
		const res = await fetch('/api/resource/my', {method: 'POST', body: new URLSearchParams({URL: 'dummy'})});
		const body = await res.json();
		if (res.status == 200) {
			ok(body.sort((a,b) => a.url.localeCompare(b.URL)));
		} else { fail({status: res.status, error: body.Error}); }
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
};

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
};

// Front-side to use API

const Interface_Resource = {
	__parseXML: src => { // Use XML instead of HTML because output is more clean. First child of XML is the inner XML, then get all children
		const x = (new DOMParser()).parseFromString('<xml>' + src + '</xml>', 'text/xml').children[0];
		if (_('parsererror', x))
			throw new Error(_('parsererror', x).textContent);
		if (_('sourcetext', x))
			throw new Error(_('sourcetext', x).textContent);
		return x;
	},
	__parseHTML: src => { // Allows insert into DOM for live view
		const x = (new DOMParser()).parseFromString(src, 'text/html').body;
		if (_('parsererror', x))
			throw new Error(_('parsererror', x).textContent);
		if (_('sourcetext', x))
			throw new Error(_('sourcetext', x).textContent);
		return x;
	},
	
	content: { // Content page
		__init: url => {
			_('main').after(...dom(`
				<div style="background:#DA8"><form id="editor" onchange="_('#editor_render').style.background=_('#editor_submit').style.background='red'" onsubmit="Interface_Resource.content.update(event)"><h1>Editor</h1>
					<div><label for="editor_url">URL</label>			<input type="text" name="URL" id="editor_url" value="` + url + `" readonly /></div>
					<div><label for="editor_category">Category</label>		<select name="Category" id="editor_category">
						<option id="editor_type_embedded-en" value="Embedded-en">Embedded-en</option>
						<option id="editor_type_embedded-zh" value="Embedded-zh">Embedded-zh</option>
						<option id="editor_type_computer-en" value="Computer-en">Computer-en</option>
						<option id="editor_type_computer-zh" value="Computer-zh">Computer-zh</option>
					</select></div>
					<div><label for="editor_title">Title</label>			<input type="text" name="Title" id="editor_title" /></div>
					<div><label for="editor_keywords">Keywords</label>		<input type="text" name="Keywords" id="editor_keywords" /></div>
					<div><label for="editor_state">State</label>			<input type="text" name="State" id="editor_state" /></div>
					<div><label for="editor_description">Description</label>	<textarea type="text" name="Description" id="editor_description"></textarea></div>
					<div><label for="editor_content">Content</label>		<textarea type="text" name="Content" id="editor_content"></textarea></div>
					<div><label for="editor_aux">Aux </label>			<textarea type="text" name="Aux" id="editor_aux"></textarea></div>
					<div style="display:flex;justify-content:center;margin-top:1em;">
						<button id="editor_reload" type="button" style="padding:0 2em;background:red" onclick="Interface_Resource.content.get(event)">Reload</button>
						<button id="editor_render" type="button" style="padding:0 2em;background:grey" onclick="Interface_Resource.content.render(event)">Render</button>
						<button id="editor_submit" type="submit" style="padding:0 2em;background:grey">Update</button>
					</div>
				</form></div>
			`).children);
		},
		get: event => {
			API_Resource.get(_('#editor_url').value).then(x => {
				x.title = x.meta[0];
				x.keywords = x.meta[1];
				x.description = x.meta[2];
				x.index = x.meta[3]; // Not used, will be regenerated based on new content
				x.aux = JSON.stringify(x.aux);
				['title', 'category', 'keywords', 'state', 'description', 'content', 'aux'].forEach(y => _('#editor_'+y).value = x[y]);
				dialog('Loaded');
				_('#editor_reload').style.background = 'green';
				_('#editor_render').style.background = 'red';
			}, x => {
				dialog('Error: ' + x.status + ' - ' + x.error, 5000, 'red');
			})
		},
		render: event => {
			const aux = JSON.parse(_('#editor_aux').value);
			const pre = '<div class="tintimg" style="--bgcolor:rgba(255,255,255,0.7);--bgimg:' + (aux.bgimg ?? '') + '"><h1>' + _('#editor_title').value + '</h1><p>' + _('#editor_description').value + '</p><p class="content_keywords">' + _('#editor_keywords').value + '</p><i>--by ' + _('meta[name=author]').content + ' @ [Now]</i></div>';
			_('main').replaceChildren(...Interface_Resource.__parseHTML(_('#editor_content').value).children);
			dialog('Rendered');
			_('#editor_render').style.background = 'green';
			_('#editor_submit').style.background = 'red';
		},
		update: event => {
			event.preventDefault();
			const resource = new FormData(_('#editor'));
			const serializer = new XMLSerializer();

			resource.set('Title', resource.get('Title').replace(/\n/g, ''));
			resource.set('Keywords', resource.get('Keywords').replace(/\n/g, ''));
			resource.set('Description', resource.get('Description').replace(/\n/g, ''));

			try { // Valid content HTML, add id to titles, create index to titles
				const content = Interface_Resource.__parseXML(resource.get('Content'));
				let index = '';
				let hxDom = __('h1,h2,h3,h4,h5,h6,h7', content);
				for (let i = 0; i < hxDom.length; i++) {
					hxDom[i].id = hxDom[i].id != '' ? hxDom[i].id : 'content_index-gen' + i;
					hxDom[i].classList.add('content_index_target');
					index += '<a href="#' + hxDom[i].id + '" class="content_index_nav">' + hxDom[i].textContent + '</a>';
				}

				resource.set('Meta', [resource.get('Title'), resource.get('Keywords'), resource.get('Description'), index].join('\n'));
				resource.delete('Title');
				resource.delete('Keywords');
				resource.delete('Description');

				//let str = '';
				//Array.prototype.slice.call(content.children).map(x => str += serializer.serializeToString(x));
				//resource.set('Content', str);
				resource.set('Content', content.innerHTML);
			} catch (error) { console.error('Content error:' + error);
				dialog('Error in Content: ' + error.message);
				return;
			}

			try {
				let aux = JSON.parse(resource.get('Aux'));
			} catch (error) { console.error(error);
				dialog('Error in Aux: ' + error.message, 5000, 'red');
				return;
			}

			API_Resource.update(resource).then(x => {
				dialog('Success');
				_('#editor_submit').style.background='green'
			}, x => {
				dialog('Error: ' + x.status + ' - ' + x.error, 5000, 'red');
				_('#editor_submit').style.background = 'red';
			});
		}
	},

	list: { // List page
		/*__init: () => {
			
		},*/
		getall: event => {
			API_Resource.my().then(list => {
				let d = [{_: 'div', classList: 'beartable_tr', children: []}];
				['URL (Open in new tab)', 'Title (Bring to Modify)', 'Category', 'Create', 'Modify', 'State'].forEach(x => d[0].children.push({_: 'span', classList: 'beartable_th', textContent: x}));
				list.map(x => {
					let c = {_: 'div', classList: 'beartable_tr', children: [
						{_: 'span', classList: 'beartable_td', children: [{_: 'a', href: x.url, target: '_blank', textContent: x.url}]},
						{_: 'span', classList: 'beartable_td', children: [{_: 'a', onclick: () => Interface_Resource.list.get(x.url), textContent: x.meta[0]}]},
						{_: 'span', classList: 'beartable_td', textContent: x.category, style: 'background:' + (c => {
							if (c.substring(0, 8) == 'Embedded') return 'aqua';
							if (c.substring(0, 8) == 'Computer') return 'salmon';
							if (c == 'Content') return 'lightgray';
							return 'none';
						})(x.category)},
						{_: 'span', classList: 'beartable_td', textContent: timestamp2str(x.create)},
						{_: 'span', classList: 'beartable_td', textContent: timestamp2str(x.modify)},
						{_: 'span', classList: 'beartable_td', textContent: x.state, style: 'background:' + (c => {
							if (c == 'O') return 'lightgreen';
							if (c == 'A' || c == 'P') return 'pink';
							if (c == 'S') return 'lightgray';
							return 'none';
						})(x.state)}
					]};
					d.push(c);
				});
				_('#list_table').replaceChildren(dom({_: 'div', classList: 'beartable', children: d}));
			}, x => {
				dialog('Error: ' + x.status + ' - ' + x.error, 5000, 'red');
			});
		},
		get: url => {
			API_Resource.get(url, true).then(d => {
				d.aux = JSON.stringify(d.aux);
				['url', 'title', 'state', 'content', 'aux'].forEach(x => {
					d.title = d.meta[0];
					_('#modify_'+x).value = d[x];
				});
				dialog('Loaded!');
			}, x => {
				dialog('Loading failed: ' + x.status + ' - ' + x.error, 5000, 'red');
			});
		},
		create: event => {
			event.preventDefault();
			API_Resource.create(_('#create_url').value).then( x => {
				dialog('Creating resource success!');
				_('#list button').click();
			}, x => {
				dialog('Creating resource failed: ' + x.status + ' - ' + x.error, 5000, 'red');
			});
		},
		modify: event => {
			event.preventDefault();
			API_Resource.update(new FormData(_('#form_modify')), true).then(() => {
				dialog('Modifying resource success!')
			}, x => {
				dialog('Modifying resource failed: ' + x.status + ' - ' + x.error, 5000, 'red')
			});
		},
		drop: event =>  {
			event.preventDefault();
			const f = event.dataTransfer.items[0].getAsFile();
			const reader = new FileReader();
			reader.onloadend = x => {
				_('#modify_content').value = x.target.result.split(',')[1];
			};
			reader.readAsDataURL(f);
		}
	}
};