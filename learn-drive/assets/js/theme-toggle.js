/**
 * Bascule clair / sombre. Les boutons portent la classe « ld-theme-toggle ».
 */
( function () {
	var root = document.documentElement;
	var media = window.matchMedia( '(prefers-color-scheme: dark)' );

	function isDark() {
		var forced = root.getAttribute( 'data-theme' );
		return forced ? forced === 'dark' : media.matches;
	}

	function sync() {
		var dark = isDark();
		document.querySelectorAll( '.ld-theme-toggle' ).forEach( function ( button ) {
			button.setAttribute( 'aria-pressed', dark ? 'true' : 'false' );
		} );
	}

	document.addEventListener( 'click', function ( event ) {
		var button = event.target.closest( '.ld-theme-toggle' );
		if ( ! button ) {
			return;
		}
		var next = isDark() ? 'light' : 'dark';
		root.setAttribute( 'data-theme', next );
		try {
			localStorage.setItem( 'ld-theme', next );
		} catch ( e ) {}
		sync();
	} );

	media.addEventListener( 'change', sync );
	sync();
} )();
