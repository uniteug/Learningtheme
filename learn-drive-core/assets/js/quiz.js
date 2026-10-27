/**
 * Moteur de quiz Learn & Drive — module Interactivity API.
 *
 * Le HTML (questions, propositions, règles) est rendu par le serveur et reste
 * lisible sans JavaScript. Ce module ajoute : une question par écran, la
 * correction (toujours calculée par le serveur), le chronomètre, la reprise
 * d'un quiz interrompu et les préférences d'affichage (texte agrandi, police dys).
 */
import {
	store,
	getContext,
	getElement,
	getConfig,
	withScope,
} from '@wordpress/interactivity';

const NS = 'learn-drive/quiz';
const timers = new Map();

const cfg = () => getConfig( NS );
const t = ( key ) => ( cfg().i18n || {} )[ key ] || '';

const esc = ( value ) =>
	String( value ?? '' ).replace(
		/[&<>"']/g,
		( c ) =>
			( {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				'"': '&quot;',
				"'": '&#039;',
			} )[ c ]
	);

const storage = {
	get( key ) {
		try {
			return JSON.parse( window.localStorage.getItem( key ) || 'null' );
		} catch ( e ) {
			return null;
		}
	},
	set( key, value ) {
		try {
			window.localStorage.setItem( key, JSON.stringify( value ) );
		} catch ( e ) {}
	},
	remove( key ) {
		try {
			window.localStorage.removeItem( key );
		} catch ( e ) {}
	},
};

async function api( path, body, method = 'POST' ) {
	const { restUrl, nonce } = cfg();
	const headers = { 'Content-Type': 'application/json' };
	if ( nonce ) {
		headers[ 'X-WP-Nonce' ] = nonce;
	}
	const response = await fetch( restUrl + path, {
		method,
		headers,
		credentials: 'same-origin',
		body: body ? JSON.stringify( body ) : undefined,
	} );
	const data = await response.json().catch( () => ( {} ) );
	if ( ! response.ok ) {
		const error = new Error( data.message || response.statusText );
		error.data = data;
		throw error;
	}
	return data;
}

const hasValue = ( answer ) =>
	answer !== undefined &&
	answer !== null &&
	answer !== '' &&
	! ( Array.isArray( answer ) && answer.length === 0 ) &&
	! ( typeof answer === 'number' && Number.isNaN( answer ) );

const formatTime = ( seconds ) => {
	const s = Math.max( 0, seconds | 0 );
	return `${ Math.floor( s / 60 ) }:${ String( s % 60 ).padStart( 2, '0' ) }`;
};

const rootOf = ( element ) => element?.closest?.( '.ld-quiz' );

/** Donne le focus à la question courante (après mise à jour du DOM). */
const focusCurrent = ( root ) => {
	window.requestAnimationFrame( () => {
		const legend = root?.querySelector( '.ld-q.is-current .ld-q__legend' );
		if ( legend ) {
			legend.setAttribute( 'tabindex', '-1' );
			legend.focus( { preventScroll: true } );
			const top = root.getBoundingClientRect().top;
			if ( top < 0 && ! root.classList.contains( 'is-playing' ) ) {
				root.scrollIntoView( { block: 'start' } );
			}
		}
		if ( root?.classList.contains( 'is-playing' ) ) {
			root.scrollTop = 0;
		}
	} );
};

const stopTimer = ( uid ) => {
	if ( timers.has( uid ) ) {
		window.clearInterval( timers.get( uid ) );
		timers.delete( uid );
	}
};

const factor = ( ctx ) => ( ctx.extraTime ? 1.5 : 1 );

const { state, actions } = store( NS, {
	state: {
		get isCurrent() {
			const ctx = getContext();
			return ctx.layout === 'stack' || ctx.i === ctx.current;
		},
		get feedback() {
			const ctx = getContext();
			return ctx.fb[ ctx.qid ];
		},
		get isChecked() {
			const fb = state.feedback;
			return !! fb && fb.status !== 'pending' && fb.status !== 'error';
		},
		get isLocked() {
			const ctx = getContext();
			if ( ctx.finished || ctx.reviewing ) {
				return true;
			}
			return ctx.mode !== 'exam' && state.isChecked;
		},
		get isRight() {
			return state.feedback?.status === 'right';
		},
		get isWrong() {
			return state.feedback?.status === 'wrong';
		},
		get isChosen() {
			const ctx = getContext();
			const answer = ctx.answers[ ctx.qid ];
			if ( ! hasValue( answer ) ) {
				return false;
			}
			return Array.isArray( answer )
				? answer.includes( ctx.opt )
				: answer === ctx.opt;
		},
		get isSolution() {
			const ctx = getContext();
			const solution = state.feedback?.solution;
			return Array.isArray( solution ) && solution.includes( ctx.opt );
		},
		get isChosenWrong() {
			return state.isChecked && state.isChosen && ! state.isSolution;
		},
		get canCheck() {
			const ctx = getContext();
			return hasValue( ctx.answers[ ctx.qid ] ) && ! state.isLocked;
		},
		get isFlipped() {
			const ctx = getContext();
			return !! ctx.flipped[ ctx.qid ];
		},
		get showBar() {
			const ctx = getContext();
			return ctx.playing || ctx.reviewing;
		},
		get position() {
			return getContext().current + 1;
		},
		get progressText() {
			const ctx = getContext();
			return `${ ctx.current + 1 } / ${ ctx.total }`;
		},
		get progressWidth() {
			const ctx = getContext();
			return `${ Math.round( ( 100 * ( ctx.current + 1 ) ) / ctx.total ) }%`;
		},
		get timerText() {
			const ctx = getContext();
			return formatTime( ctx.duration ? ctx.timeLeft : ctx.qTimeLeft );
		},
		get isUrgent() {
			const ctx = getContext();
			return ctx.duration ? ctx.timeLeft <= 60 : ctx.qTimeLeft <= 5;
		},
		get canPrev() {
			const ctx = getContext();
			return (
				ctx.current > 0 &&
				( ctx.reviewing || ( ctx.playing && ctx.allowBack ) )
			);
		},
		get canNext() {
			const ctx = getContext();
			return (
				ctx.current < ctx.total - 1 && ( ctx.playing || ctx.reviewing )
			);
		},
		get canFinish() {
			const ctx = getContext();
			return (
				ctx.playing &&
				! ctx.reviewing &&
				( ctx.mode === 'exam' || ctx.current === ctx.total - 1 )
			);
		},
		get showResult() {
			const ctx = getContext();
			return ctx.finished && ! ctx.reviewing;
		},
		get scoreText() {
			const { summary } = getContext();
			return `${ summary.correct } / ${ summary.total }`;
		},
		get verdict() {
			return getContext().summary.passed ? t( 'passed' ) : t( 'failed' );
		},
		get hasTopics() {
			return getContext().summary.topics.length > 0;
		},
		get topicCount() {
			const { topic } = getContext();
			return `${ topic.errors } ${ t( 'errorsOf' ) } ${ topic.total }`;
		},
		get challengeText() {
			return `${ t( 'challenge' ) } ${ getContext().challenge }`;
		},
	},

	actions: {
		start() {
			const ctx = getContext();
			const root = rootOf( getElement().ref );
			ctx.intro = false;
			ctx.playing = true;
			ctx.finished = false;
			ctx.reviewing = false;
			ctx.resume = false;
			if ( ! ctx.restored ) {
				ctx.current = 0;
				ctx.timeLeft = Math.round( ctx.duration * factor( ctx ) );
			}
			ctx.restored = false;
			ctx.startedAt = Date.now();
			actions.startTimer();
			focusCurrent( root );
		},

		resume() {
			const ctx = getContext();
			const saved = storage.get( ctx.storageKey );
			if ( saved ) {
				ctx.answers = saved.answers || {};
				ctx.fb = saved.fb || {};
				ctx.current = Math.min( saved.current || 0, ctx.total - 1 );
				if ( saved.timeLeft ) {
					ctx.timeLeft = saved.timeLeft;
				}
				ctx.restored = true;
			}
			actions.start();
		},

		startTimer() {
			const ctx = getContext();
			stopTimer( ctx.uid );
			if ( ! ctx.duration && ! ctx.perQuestion ) {
				return;
			}
			ctx.qTimeLeft = Math.round( ctx.perQuestion * factor( ctx ) );
			timers.set(
				ctx.uid,
				window.setInterval(
					withScope( () => actions.tick() ),
					1000
				)
			);
		},

		tick() {
			const ctx = getContext();
			if ( ! ctx.playing ) {
				stopTimer( ctx.uid );
				return;
			}
			if ( ctx.duration ) {
				ctx.timeLeft = Math.max( 0, ctx.timeLeft - 1 );
				if ( ctx.timeLeft === 0 ) {
					ctx.message = t( 'timeUp' );
					actions.finish();
					return;
				}
			}
			if ( ctx.perQuestion ) {
				ctx.qTimeLeft -= 1;
				if ( ctx.qTimeLeft <= 0 ) {
					if ( ctx.current < ctx.total - 1 ) {
						ctx.current += 1;
						ctx.qTimeLeft = Math.round(
							ctx.perQuestion * factor( ctx )
						);
					} else {
						actions.finish();
					}
				}
			}
		},

		select( event ) {
			const ctx = getContext();
			const input = event.target;
			if ( state.isLocked ) {
				return;
			}
			if ( input.type === 'checkbox' ) {
				const value = parseInt( input.value, 10 );
				const current = Array.isArray( ctx.answers[ ctx.qid ] )
					? [ ...ctx.answers[ ctx.qid ] ]
					: [];
				const index = current.indexOf( value );
				if ( input.checked && index < 0 ) {
					current.push( value );
				} else if ( ! input.checked && index >= 0 ) {
					current.splice( index, 1 );
				}
				ctx.answers[ ctx.qid ] = current.sort();
			} else if ( input.type === 'radio' ) {
				ctx.answers[ ctx.qid ] = parseInt( input.value, 10 );
				if ( ctx.mode !== 'exam' ) {
					// Vrai/faux et choix unique : correction dès le choix (navigation au pouce).
					return actions.check();
				}
			} else {
				const raw = input.value.trim().replace( ',', '.' );
				ctx.answers[ ctx.qid ] = raw === '' ? null : parseFloat( raw );
			}
		},

		*check() {
			const ctx = getContext();
			const qid = ctx.qid;
			const answer = ctx.answers[ qid ];
			if ( ! hasValue( answer ) || state.isChecked ) {
				return;
			}
			ctx.fb[ qid ] = { status: 'pending' };
			try {
				const data = yield api( 'check', {
					question: qid,
					answer,
					quiz: ctx.quizId || 0,
				} );
				ctx.fb[ qid ] = {
					status: data.locked
						? 'locked'
						: data.correct
						? 'right'
						: 'wrong',
					solution: data.solution ?? null,
					text: data.solution_text || '',
					html: data.explanation || '',
					lesson: data.lesson || null,
					unlock: data.unlock_url || '',
				};
			} catch ( e ) {
				ctx.fb[ qid ] = { status: 'error' };
			}
		},

		*flipCard() {
			const ctx = getContext();
			const qid = ctx.qid;
			ctx.flipped[ qid ] = ! ctx.flipped[ qid ];
			if ( ctx.flipped[ qid ] && ! ctx.fb[ qid ] ) {
				try {
					const data = yield api( 'check', {
						question: qid,
						answer: 'reveal',
					} );
					ctx.fb[ qid ] = {
						status: data.locked ? 'locked' : 'reveal',
						html: data.explanation || '',
						unlock: data.unlock_url || '',
					};
				} catch ( e ) {
					ctx.fb[ qid ] = { status: 'error' };
				}
			}
		},

		knew() {
			actions.selfAssess( 'known' );
		},

		didNotKnow() {
			actions.selfAssess( 'unknown' );
		},

		*selfAssess( value ) {
			const ctx = getContext();
			const qid = ctx.qid;
			ctx.answers[ qid ] = value;
			const previous = ctx.fb[ qid ] || {};
			ctx.fb[ qid ] = {
				...previous,
				status: value === 'known' ? 'right' : 'wrong',
			};
			try {
				yield api( 'check', { question: qid, answer: value } );
			} catch ( e ) {}
		},

		next() {
			const ctx = getContext();
			if ( ctx.current < ctx.total - 1 ) {
				ctx.current += 1;
				if ( ctx.perQuestion && ctx.playing ) {
					ctx.qTimeLeft = Math.round( ctx.perQuestion * factor( ctx ) );
				}
				focusCurrent( rootOf( getElement().ref ) );
			}
		},

		prev() {
			const ctx = getContext();
			if ( state.canPrev ) {
				ctx.current -= 1;
				focusCurrent( rootOf( getElement().ref ) );
			}
		},

		*finish() {
			const ctx = getContext();
			if ( ctx.busy || ! ctx.quizId ) {
				return;
			}
			const root = rootOf( getElement()?.ref );
			ctx.busy = true;
			stopTimer( ctx.uid );
			try {
				const data = yield api( `quiz/${ ctx.quizId }/submit`, {
					answers: ctx.answers,
					session: ctx.session,
					elapsed: ctx.startedAt
						? Math.round( ( Date.now() - ctx.startedAt ) / 1000 )
						: 0,
				} );
				ctx.summary = {
					correct: data.correct,
					total: data.total,
					percent: data.percent,
					passed: data.passed,
					threshold: data.threshold,
					topics: data.topics || [],
				};
				Object.entries( data.details || {} ).forEach(
					( [ qid, detail ] ) => {
						const previous = ctx.fb[ qid ];
						if (
							ctx.mode !== 'exam' &&
							previous &&
							previous.status !== 'error'
						) {
							return;
						}
						ctx.fb[ qid ] = {
							status: detail.locked
								? 'locked'
								: detail.correct
								? 'right'
								: 'wrong',
							solution: detail.solution ?? null,
							text: detail.text || '',
							html: detail.explanation || '',
							lesson: null,
							unlock: detail.unlock_url || '',
						};
					}
				);
				ctx.playing = false;
				ctx.finished = true;
				ctx.reviewing = false;
				storage.remove( ctx.storageKey );
				window.requestAnimationFrame( () =>
					root?.querySelector( '.ld-quiz__result' )?.focus()
				);
			} catch ( e ) {
				ctx.message = e?.data?.message || t( 'error' );
				if ( ctx.playing ) {
					actions.startTimer();
				}
			} finally {
				ctx.busy = false;
			}
		},

		review() {
			const ctx = getContext();
			ctx.reviewing = true;
			ctx.current = 0;
			focusCurrent( rootOf( getElement().ref ) );
		},

		closeReview() {
			const ctx = getContext();
			const root = rootOf( getElement().ref );
			ctx.reviewing = false;
			window.requestAnimationFrame( () =>
				root?.querySelector( '.ld-quiz__result' )?.focus()
			);
		},

		restart() {
			const ctx = getContext();
			ctx.answers = {};
			ctx.fb = {};
			ctx.flipped = {};
			ctx.message = '';
			storage.remove( ctx.storageKey );
			actions.start();
		},

		quit() {
			const ctx = getContext();
			stopTimer( ctx.uid );
			ctx.playing = false;
			ctx.reviewing = false;
			ctx.intro = ! ctx.finished;
			ctx.resume = ! ctx.finished && Object.keys( ctx.answers ).length > 0;
		},

		toggleExtraTime( event ) {
			getContext().extraTime = !! event.target.checked;
		},

		toggleBig() {
			const ctx = getContext();
			ctx.big = ! ctx.big;
			storage.set( 'ld-quiz-prefs', { big: ctx.big, dys: ctx.dys } );
		},

		toggleDys() {
			const ctx = getContext();
			ctx.dys = ! ctx.dys;
			storage.set( 'ld-quiz-prefs', { big: ctx.big, dys: ctx.dys } );
		},

		*report( event ) {
			const ctx = getContext();
			const details = event.target.closest( 'details' );
			const field = details?.querySelector( 'textarea' );
			const message = field?.value.trim();
			if ( ! message ) {
				field?.focus();
				return;
			}
			try {
				yield api( 'report', { question: ctx.qid, message } );
				details.innerHTML = `<p class="ld-q__reported">${ esc(
					t( 'reportSent' )
				) }</p>`;
			} catch ( e ) {
				details.insertAdjacentHTML(
					'beforeend',
					`<p class="ld-fb ld-fb--error">${ esc( t( 'error' ) ) }</p>`
				);
			}
		},

		*share() {
			const ctx = getContext();
			const score = `${ ctx.summary.correct }/${ ctx.summary.total }`;
			const url = new URL( ctx.shareUrl || window.location.href );
			url.search = '';
			url.searchParams.set( 'defi', score );
			const text = t( 'shareText' ).replace( '%s', score );
			try {
				if ( navigator.share ) {
					yield navigator.share( { text, url: url.toString() } );
				} else {
					yield navigator.clipboard.writeText(
						`${ text } ${ url.toString() }`
					);
					ctx.message = t( 'copied' );
				}
			} catch ( e ) {}
		},
	},

	callbacks: {
		init() {
			const ctx = getContext();
			const prefs = storage.get( 'ld-quiz-prefs' );
			if ( prefs ) {
				ctx.big = !! prefs.big;
				ctx.dys = !! prefs.dys;
			}
			if ( ctx.layout === 'player' ) {
				const saved = storage.get( ctx.storageKey );
				if (
					saved &&
					( saved.session || '' ) === ( ctx.session || '' ) &&
					Object.keys( saved.answers || {} ).length > 0
				) {
					ctx.resume = true;
				}
			}
			const challenge = new URLSearchParams( window.location.search ).get(
				'defi'
			);
			if ( challenge && /^\d{1,3}\/\d{1,3}$/.test( challenge ) ) {
				ctx.challenge = challenge;
			}
		},

		persist() {
			const ctx = getContext();
			if ( ctx.layout !== 'player' || ! ctx.playing ) {
				return;
			}
			storage.set( ctx.storageKey, {
				answers: ctx.answers,
				fb: ctx.mode === 'exam' ? {} : ctx.fb,
				current: ctx.current,
				timeLeft: ctx.timeLeft,
				session: ctx.session,
				savedAt: Date.now(),
			} );
		},

		renderFeedback() {
			const ctx = getContext();
			const { ref } = getElement();
			const fb = ctx.fb[ ctx.qid ];

			if ( ! fb || fb.status === 'pending' ) {
				ref.innerHTML = '';
				return;
			}

			let html = '';
			if ( fb.status === 'locked' ) {
				html = `<p class="ld-fb ld-fb--locked">${ esc( t( 'locked' ) ) } ${
					fb.unlock
						? `<a href="${ esc( fb.unlock ) }" rel="nofollow">${ esc(
								t( 'unlock' )
						  ) }</a>`
						: ''
				}</p>`;
			} else if ( fb.status === 'error' ) {
				html = `<p class="ld-fb ld-fb--error">${ esc( t( 'error' ) ) }</p>`;
			} else if ( fb.status === 'reveal' ) {
				html = fb.html
					? `<div class="ld-fb__explanation">${ fb.html }</div>`
					: '';
			} else {
				const right = fb.status === 'right';
				html = `<p class="ld-fb ld-fb--${ right ? 'right' : 'wrong' }"><span class="ld-fb__icon" aria-hidden="true">${
					right ? '✓' : '✗'
				}</span> <strong>${ esc( right ? t( 'correct' ) : t( 'wrong' ) ) }</strong>${
					! right && fb.text
						? ` — ${ esc( t( 'answer' ) ) } ${ esc( fb.text ) }`
						: ''
				}</p>`;
				// Explication : HTML produit et filtré par le serveur (contenu de la question).
				if ( fb.html ) {
					html += `<div class="ld-fb__explanation">${ fb.html }</div>`;
				}
				if ( fb.lesson?.url ) {
					html += `<p class="ld-fb__lesson"><a href="${ esc(
						fb.lesson.url
					) }">${ esc( t( 'reviewLink' ) ) } : ${ esc(
						fb.lesson.title
					) }</a></p>`;
				}
			}
			ref.innerHTML = html;
		},
	},
} );
