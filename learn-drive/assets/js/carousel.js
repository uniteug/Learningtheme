/**
 * Carrousels : défilement natif (scroll-snap, tactile, clavier) enrichi de
 * boutons précédent / suivant. Sans JavaScript, la rangée reste défilable.
 */
( function () {
	var SELECTOR = '.ld-carousel, .ld-tracks__list, :where(.ld-rail, .is-style-carousel) :where(.wp-block-post-template, .wp-block-term-template)';
	var labels = window.ldCarousel || { prev: 'Précédent', next: 'Suivant' };
	var reduce = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	var arrow = '<svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" focusable="false"><path d="M9 6l6 6-6 6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>';

	function setup( track ) {
		if ( track.dataset.ldCarousel ) {
			return;
		}
		track.dataset.ldCarousel = '1';
		track.classList.add( 'is-carousel-ready' );

		var nav = document.createElement( 'div' );
		nav.className = 'ld-carousel-nav';
		var rtl = 'rtl' === window.getComputedStyle( track ).direction ? -1 : 1;

		function button( dir ) {
			var b = document.createElement( 'button' );
			b.type = 'button';
			b.className = 'ld-carousel-btn is-' + dir;
			b.setAttribute( 'aria-label', labels[ dir ] );
			b.innerHTML = arrow;
			b.addEventListener( 'click', function () {
				var step = Math.max( 240, track.clientWidth * 0.85 ) * ( 'prev' === dir ? -1 : 1 ) * rtl;
				track.scrollBy( { left: step, behavior: reduce.matches ? 'auto' : 'smooth' } );
			} );
			return b;
		}

		var prev = button( 'prev' );
		var next = button( 'next' );
		nav.appendChild( prev );
		nav.appendChild( next );
		track.parentNode.insertBefore( nav, track.nextSibling );

		function update() {
			var max = track.scrollWidth - track.clientWidth;
			var pos = Math.abs( track.scrollLeft );
			nav.hidden = max < 8;
			prev.disabled = pos < 4;
			next.disabled = pos > max - 4;
		}

		track.addEventListener( 'scroll', update, { passive: true } );
		if ( 'ResizeObserver' in window ) {
			new window.ResizeObserver( update ).observe( track );
		}
		update();
	}

	function init() {
		document.querySelectorAll( SELECTOR ).forEach( setup );
	}

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}
}() );
