import app from 'flarum/forum/app';
import {override} from 'flarum/common/extend';
import Modal from 'flarum/common/components/Modal';

import {data, queryAdd} from '../shared/util';

// Modal classes by name, some are lazy-loaded so capture them once loaded.
const modals = {};
for (const name of [
	'LogInModal',
	'SignUpModal',
	'ChangeEmailModal',
	'ChangePasswordModal'
]) {
	flarum.reg.onLoad('core', `forum/components/${name}`, module => {
		modals[name] = module;
	});
}

function redirectThrough(url) {
	return url ? queryAdd(url, 'redirect_to', location.href) : url;
}

function bypass() {
	// Add #localuser to URL to bypass the hijacking.
	return /#localuser$/i.test(location.href);
}

function shouldRedirect(componentClass) {
	switch (componentClass) {
		case undefined: {
			break;
		}
		case modals.LogInModal: {
			return redirectThrough(data().loginUrl);
		}
		case modals.SignUpModal: {
			return redirectThrough(data().registerUrl);
		}
		case modals.ChangeEmailModal: {
			return data().allowedChanges?.email ?
				null :
				data().profileUrl;
		}
		case modals.ChangePasswordModal: {
			return data().allowedChanges?.password ?
				null :
				data().profileUrl;
		}
	}
	return null;
}

function redirect(url) {
	location.href = url;

	// Never resolve, leaving the modal loading until the page changes.
	return new Promise(() => {});
}

export function intercept() {
	// Hijack the method that shows the different modals to redirect.
	override(app.modal, 'show', function(show, componentClass, ...args) {
		if (bypass()) {
			return show(componentClass, ...args);
		}

		// Modals can be lazy-loaded, check once the module has loaded.
		if (!(componentClass.prototype instanceof Modal)) {
			const load = componentClass;
			return show(async () => {
				const module = await load();
				const url = shouldRedirect(module.default);
				return url ? redirect(url) : module;
			}, ...args);
		}

		const url = shouldRedirect(componentClass);
		return url ? redirect(url) : show(componentClass, ...args);
	});
}
