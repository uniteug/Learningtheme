/**
 * Service worker Learn & Drive : révision hors ligne.
 *
 * - Pages : réseau d'abord (4 s), sinon la copie gardée, sinon la page « hors
 *   ligne ». Seules les pages marquées par le serveur (en-tête X-LD-Offline: 1 :
 *   leçons, questions, fiches, lexique, actualités…) sont gardées, jamais les
 *   pages personnelles, la recherche ou les sessions d'examen.
 * - CSS, JS, polices, images du site : copie gardée, rafraîchie en arrière-plan.
 * - API et administration : toujours le réseau.
 *
 * Servi par le plugin à la racine du site ; les valeurs entre crochets sont
 * remplacées à l'envoi.
 */
/* global self, caches, URL, Response, AbortController */
const VERSION = '[[VERSION]]';
const SCOPE = '[[SCOPE]]';
const OFFLINE_URL = '[[OFFLINE_URL]]';
const PAGES = 'ld-pages-' + VERSION;
const ASSETS = 'ld-assets-' + VERSION;
const MAX_PAGES = 80;
const MAX_ASSETS = 150;

self.addEventListener( 'install', ( event ) => {
	event.waitUntil(
		caches
			.open( PAGES )
			.then( ( cache ) => cache.add( new Request( OFFLINE_URL, { cache: 'reload' } ) ) )
			.then( () => self.skipWaiting() )
	);
} );

self.addEventListener( 'activate', ( event ) => {
	event.waitUntil(
		caches
			.keys()
			.then( ( keys ) =>
				Promise.all(
					keys
						.filter( ( key ) => key.startsWith( 'ld-' ) && key !== PAGES && key !== ASSETS )
						.map( ( key ) => caches.delete( key ) )
				)
			)
			.then( () => self.clients.claim() )
	);
} );

/** Garde au plus `max` entrées (les plus anciennes partent d'abord). */
async function trim( name, max ) {
	const cache = await caches.open( name );
	const keys = await cache.keys();
	await Promise.all( keys.slice( 0, Math.max( 0, keys.length - max ) ).map( ( key ) => cache.delete( key ) ) );
}

function isExcluded( url ) {
	const path = url.pathname;
	return (
		! path.startsWith( SCOPE ) ||
		path.includes( '/wp-admin' ) ||
		path.includes( '/wp-login' ) ||
		path.includes( '/wp-json/' ) ||
		path.endsWith( '/sw.js' ) ||
		url.searchParams.has( 'preview' )
	);
}

async function page( request ) {
	const cache = await caches.open( PAGES );
	const controller = new AbortController();
	const timer = setTimeout( () => controller.abort(), 4000 );
	try {
		const response = await fetch( request, { signal: controller.signal } );
		clearTimeout( timer );
		const url = new URL( request.url );
		if ( response.ok && response.headers.get( 'X-LD-Offline' ) === '1' && ! url.search ) {
			// Une seule copie par URL, rangée en dernier (la plus récente).
			await cache.delete( request );
			await cache.put( request, response.clone() );
			trim( PAGES, MAX_PAGES );
		}
		return response;
	} catch ( error ) {
		clearTimeout( timer );
		return ( await cache.match( request, { ignoreSearch: false } ) ) || ( await cache.match( OFFLINE_URL ) ) || Response.error();
	}
}

async function asset( request, event ) {
	const cache = await caches.open( ASSETS );
	const cached = await cache.match( request );
	const network = fetch( request )
		.then( async ( response ) => {
			if ( response.ok && response.type === 'basic' ) {
				await cache.put( request, response.clone() );
				trim( ASSETS, MAX_ASSETS );
			}
			return response;
		} )
		.catch( () => cached );
	if ( cached ) {
		event.waitUntil( network );
		return cached;
	}
	return network;
}

self.addEventListener( 'fetch', ( event ) => {
	const request = event.request;
	if ( request.method !== 'GET' ) {
		return;
	}
	const url = new URL( request.url );
	if ( url.origin !== self.location.origin || isExcluded( url ) ) {
		return;
	}
	if ( request.mode === 'navigate' ) {
		event.respondWith( page( request ) );
		return;
	}
	if ( /\/wp-(content|includes)\/.+\.(css|js|woff2?|svg|png|jpe?g|webp|avif|gif)$/.test( url.pathname ) ) {
		event.respondWith( asset( request, event ) );
	}
} );

// La page « hors ligne » demande la liste des pages disponibles.
self.addEventListener( 'message', async ( event ) => {
	if ( event.data !== 'ld-offline-pages' || ! event.source ) {
		return;
	}
	const cache = await caches.open( PAGES );
	const pages = [];
	for ( const request of await cache.keys() ) {
		if ( request.url.split( '#' )[ 0 ] === new URL( OFFLINE_URL, self.location ).href ) {
			continue;
		}
		const response = await cache.match( request );
		const html = response ? await response.text() : '';
		const title = ( html.match( /<title>([^<]*)<\/title>/i ) || [] )[ 1 ] || request.url;
		pages.push( { url: request.url, title } );
	}
	event.source.postMessage( { type: 'ld-offline-pages', pages: pages.reverse() } );
} );
