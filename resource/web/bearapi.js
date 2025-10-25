'use strict';
class BearAPI {
	static async xhr(url, payload, progress) {
		return await new Promise(callback => {
			const xhr = new XMLHttpRequest();
			if (typeof progress == 'function') {
				xhr.upload.onprogress = event => { progress(event); };
				xhr.onprogress = event => { progress(event); };
			}
			xhr.onloadend = event => {
				callback({
					status: xhr.status,
					json: JSON.parse(xhr.responseText)
				});
			};
			if (typeof payload != 'undefined') {
				xhr.open('POST', url);
				//xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
				const form = new FormData();
				for (const [key, value] of Object.entries(payload)) { form.set(key, value); }
				xhr.send(form);
			} else {
				xhr.open('GET', url);
				xhr.send();
			}
		});
	}
	static throwError(code, message) {
		const e = new Error(message);
		e.name = code;
		throw e;
	}
}
class BearAPI_Resource extends BearAPI {
	static async get(url, encode, progress) { // Get (read) a resource from server, encode = b64 for base64 encoded data (binary)
		const res = await this.xhr('/api/resource/get?encode=' + encode, {url: url}, progress);
		if (res.status != 200) this.throwError(res.status, res.json.error);
		res.json.meta = res.json.meta == '[]' ? '{}' : res.json.meta;
		res.json.aux = res.json.aux == '[]' ? '{}' : res.json.aux;
		return res.json;
	}
	static async create(url) { // Create a new resource
		const res = await this.xhr('/api/resource/create', {url: url});
		if (res.status != 201) this.throwError(res.status, res.json.error);
		return res.json;
	}
	static async update(data, encode, progress) { // Update a resource, data = {url: "url", category: "category"...}, encode = b64 for base64 encoded data (binary)
		const res = await this.xhr('/api/resource/update?encode=' + encode, data, progress);
		if (res.status != 202) this.throwError(res.status, res.json.error);
		return res.json;
	}
	static async delete(url) { // Delete a resource
		const res = await this.xhr('/api/resource/delete', {url: url});
		if (res.status != 410) this.throwError(res.status, res.json.error);
		return res.json;
	}
	static async reindex() { // Request server to rebuild index
		const res = await this.xhr('/api/resource/reindex', {url: ''});
		if (res.status != 202) this.throwError(res.status, res.json.error);
		return res.json;
	}
}
class BearAPI_User extends BearAPI {
	static async get(id) {
		const res = await this.xhr('/api/user/get', {id: id});
		if (res.status != 200) this.throwError(res.status, res.json.error);
		return res.json;
	}
	static async logoff() {
		const res = await this.xhr('/api/user/logoff', {id: 'dummyid'});
		if (res.status != 401) this.throwError(res.status, res.json.error);
		return res.json;
	}
	static async login(id, password, passwordnew = null) {
		const r1 = await this.xhr('/api/user/get', {id: id});
		if (r1.status != 200) this.throwError(r1.status, 'Cannot get salt [P1]: ' + r1.json.error);
		const salt = r1.json.salt;
		const sessionKey = cookie.get('BW_SessionKey'); // Server has same key for the same session
		const p1 = await this.__hash(atob(salt) + password); // Old hash saved on server
		const p2 = await this.__hash(sessionKey + btoa(String.fromCharCode(...new Uint8Array(p1)))); // Use salt to verify with server
		const pn = await this.__hash(atob(sessionKey) + (passwordnew ?? password)); // Update hash on server with new salt
		const r2 = await this.xhr('/api/user/login', {
			id: id,
			password: btoa(String.fromCharCode(...new Uint8Array(p2))),
			passwordnew: btoa(String.fromCharCode(...new Uint8Array(pn)))
		});
		if (r2.status != 202) this.throwError(r2.status, 'Cannot bind session [P2]: ' + r2.json.error);
		return r2.json;
	}
	static async register(id, password) {
		const sessionKey = cookie.get('BW_SessionKey'); // Server has same key for the same session
		const pn = await this.__hash(atob(sessionKey) + password); // Update hash on server with new salt
		const r2 = await this.xhr('/api/user/register', {
			id: id,
			password: btoa(String.fromCharCode(...new Uint8Array(pn)))
		});
		if (r2.status != 201) this.throwError(r2.status, r2.json.error);
		return r2.json;
	}
	static async __hash(src) { return await window.crypto.subtle.digest('SHA-384', (new TextEncoder()).encode(src)); }
}