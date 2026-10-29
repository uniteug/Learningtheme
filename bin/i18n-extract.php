<?php
// Extrait les chaînes traduisibles (PHP, JS, block.json, theme.json) d'un paquet, par domaine.
// Usage : php bin/i18n-extract.php <dossier> <domaine> > chaines.json
[$_, $dir, $domain] = $argv;
$fns = array( '__' => 's', '_e' => 's', 'esc_html__' => 's', 'esc_html_e' => 's', 'esc_attr__' => 's', 'esc_attr_e' => 's', '_x' => 'sc', '_ex' => 'sc', 'esc_html_x' => 'sc', 'esc_attr_x' => 'sc', '_n' => 'sp', '_nx' => 'spc' );
$out = array();
$add = function ( $msgid, $ctx = '', $plural = '', $ref = '' ) use ( &$out ) {
	$key = $ctx . "\x04" . $msgid;
	if ( ! isset( $out[ $key ] ) ) {
		$out[ $key ] = array( 'msgid' => $msgid, 'context' => $ctx, 'plural' => $plural, 'refs' => array() );
	}
	if ( count( $out[ $key ]['refs'] ) < 3 ) {
		$out[ $key ]['refs'][] = $ref;
	}
};
$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) );
foreach ( $it as $file ) {
	$path = $file->getPathname();
	$rel  = substr( $path, strlen( $dir ) + 1 );
	if ( preg_match( '#(\.min\.js|/vendor/|/node_modules/)#', $path ) ) {
		continue;
	}
	if ( str_ends_with( $path, '.php' ) ) {
		$tokens = token_get_all( file_get_contents( $path ) );
		$n      = count( $tokens );
		for ( $i = 0; $i < $n; $i++ ) {
			$t = $tokens[ $i ];
			if ( ! is_array( $t ) || T_STRING !== $t[0] || ! isset( $fns[ $t[1] ] ) ) {
				continue;
			}
			// Arguments littéraux.
			$j = $i + 1;
			while ( $j < $n && is_array( $tokens[ $j ] ) && T_WHITESPACE === $tokens[ $j ][0] ) $j++;
			if ( '(' !== $tokens[ $j ] ) continue;
			$args = array(); $depth = 0; $cur = null;
			for ( $k = $j + 1; $k < $n; $k++ ) {
				$tk = $tokens[ $k ];
				if ( '(' === $tk || '[' === $tk ) $depth++;
				if ( ')' === $tk || ']' === $tk ) { if ( 0 === $depth ) { $args[] = $cur; break; } $depth--; }
				if ( ',' === $tk && 0 === $depth ) { $args[] = $cur; $cur = null; continue; }
				if ( is_array( $tk ) && T_CONSTANT_ENCAPSED_STRING === $tk[0] && 0 === $depth && null === $cur ) {
					$cur = eval( 'return ' . $tk[1] . ';' );
				} elseif ( is_array( $tk ) && T_WHITESPACE === $tk[0] ) {
				} elseif ( 0 === $depth && null !== $cur && ! ( is_array( $tk ) && T_CONSTANT_ENCAPSED_STRING === $tk[0] ) ) {
					$cur = false; // expression non littérale
				} elseif ( 0 === $depth && null === $cur ) {
					$cur = false;
				}
			}
			$type = $fns[ $t[1] ];
			$dom  = end( $args );
			if ( $dom !== $domain || ! is_string( $args[0] ?? null ) ) continue;
			$line = $t[2];
			if ( 's' === $type ) $add( $args[0], '', '', "$rel:$line" );
			elseif ( 'sc' === $type ) $add( $args[0], (string) $args[1], '', "$rel:$line" );
			elseif ( 'sp' === $type ) $add( $args[0], '', (string) $args[1], "$rel:$line" );
			elseif ( 'spc' === $type ) $add( $args[0], (string) $args[3], (string) $args[1], "$rel:$line" );
		}
		// En-têtes de patterns.
		if ( preg_match_all( '/^\s*\*\s*(Title|Description):\s*(.+)$/m', file_get_contents( $path ), $m, PREG_SET_ORDER ) && str_contains( $rel, 'patterns/' ) ) {
			foreach ( $m as $mm ) $add( trim( $mm[2] ), 'Pattern ' . strtolower( $mm[1] ), '', $rel );
		}
	} elseif ( str_ends_with( $path, '.js' ) ) {
		$src = file_get_contents( $path );
		if ( preg_match_all( "/\b__\(\s*'((?:[^'\\\\]|\\\\.)*)'\s*,\s*'([^']+)'\s*\)/", $src, $m, PREG_SET_ORDER ) ) {
			foreach ( $m as $mm ) if ( $mm[2] === $domain ) $add( stripcslashes( $mm[1] ), '', '', $rel );
		}
	} elseif ( 'block.json' === basename( $path ) ) {
		$b = json_decode( file_get_contents( $path ), true );
		foreach ( array( 'title' => 'block title', 'description' => 'block description' ) as $f => $ctx ) {
			if ( ! empty( $b[ $f ] ) ) $add( $b[ $f ], $ctx, '', $rel );
		}
		foreach ( (array) ( $b['keywords'] ?? array() ) as $kw ) $add( $kw, 'block keyword', '', $rel );
	} elseif ( 'theme.json' === basename( $path ) || ( str_contains( $rel, 'styles/' ) && str_ends_with( $path, '.json' ) ) ) {
		$b = json_decode( file_get_contents( $path ), true );
		if ( ! empty( $b['title'] ) ) $add( $b['title'], 'Style variation name', '', $rel );
		array_walk_recursive( $b, function ( $v, $k ) use ( $add, $rel ) { /* noms de préréglages */ } );
		foreach ( array( 'color.palette', 'color.gradients', 'typography.fontSizes', 'typography.fontFamilies', 'spacing.spacingSizes', 'shadow.presets' ) as $p ) {
			[$a, $c] = explode( '.', $p );
			foreach ( (array) ( $b['settings'][ $a ][ $c ] ?? array() ) as $e ) {
				if ( ! empty( $e['name'] ) ) $add( $e['name'], ucfirst( $c ) === 'Palette' ? 'Color name' : ( 'gradients' === $c ? 'Gradient name' : ( 'fontSizes' === $c ? 'Font size name' : ( 'fontFamilies' === $c ? 'Font family name' : ( 'spacingSizes' === $c ? 'Space size name' : 'Shadow name' ) ) ) ), '', $rel );
			}
		}
		foreach ( (array) ( $b['customTemplates'] ?? array() ) as $e ) if ( ! empty( $e['title'] ) ) $add( $e['title'], 'Custom template name', '', $rel );
		foreach ( (array) ( $b['templateParts'] ?? array() ) as $e ) if ( ! empty( $e['title'] ) ) $add( $e['title'], 'Template part name', '', $rel );
	}
}
echo json_encode( array_values( $out ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
