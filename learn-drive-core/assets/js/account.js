/**
 * Espace apprenant : code d'accès, objectif, export et suppression des données.
 */
import { store, getContext, getConfig } from '@wordpress/interactivity';

const NS = 'learn-drive/account';

async function api( path, { method = 'POST', body } = {} ) {
	const { restUrl, nonce } = getConfig( NS );
	const response = await fetch( restUrl + path, {
		method,
		credentials: 'same-origin',
		headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': nonce },
		body: body ? JSON.stringify( body ) : undefined,
	} );
	const data = await response.json().catch( () => ( {} ) );
	if ( ! response.ok ) {
		throw new Error( data.message || response.statusText );
	}
	return data;
}

const t = ( key ) => ( getConfig( NS ).i18n || {} )[ key ] || '';

store( NS, {
	actions: {
		*redeem( event ) {
			event.preventDefault();
			const ctx = getContext();
			const code = event.target.elements.code.value.trim();
			ctx.busy = true;
			try {
				const data = yield api( 'access/redeem', { body: { code } } );
				ctx.message = data.message;
				window.setTimeout( () => window.location.reload(), 1200 );
			} catch ( e ) {
				ctx.message = e.message;
			} finally {
				ctx.busy = false;
			}
		},
		*saveGoal( event ) {
			event.preventDefault();
			const ctx = getContext();
			try {
				yield api( 'me/goal', {
					body: { date: event.target.elements.date.value },
				} );
				ctx.message = t( 'saved' );
			} catch ( e ) {
				ctx.message = e.message || t( 'error' );
			}
		},
		*exportData() {
			const ctx = getContext();
			try {
				const data = yield api( 'me/progress', { method: 'GET' } );
				const blob = new Blob( [ JSON.stringify( data, null, 2 ) ], {
					type: 'application/json',
				} );
				const link = document.createElement( 'a' );
				link.href = URL.createObjectURL( blob );
				link.download = 'ma-progression.json';
				link.click();
				URL.revokeObjectURL( link.href );
			} catch ( e ) {
				ctx.message = t( 'error' );
			}
		},
		*deleteData() {
			const ctx = getContext();
			// eslint-disable-next-line no-alert
			if ( ! window.confirm( t( 'confirmDelete' ) ) ) {
				return;
			}
			try {
				yield api( 'me/progress', { method: 'DELETE' } );
				ctx.message = t( 'deleted' );
				window.setTimeout( () => window.location.reload(), 1200 );
			} catch ( e ) {
				ctx.message = t( 'error' );
			}
		},
	},
} );
