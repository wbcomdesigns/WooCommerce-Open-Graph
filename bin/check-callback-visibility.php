<?php
/**
 * Fail the build when a hook callback points at a non-public method.
 *
 * WordPress invokes callbacks from global scope via call_user_func_array(), which
 * cannot reach a private or protected method. The class still works internally, so
 * this passes code review and every unit test, then throws an uncaught TypeError the
 * first time the hook fires - taking the whole site down.
 *
 * That is exactly what shipped in 2.0.1: WOG_Sitemap::schedule_sitemap_update() was
 * private but registered on woocommerce_update_product, so saving any product fataled.
 *
 * Usage: php bin/check-callback-visibility.php [dir]
 * Exit 0 = clean, 1 = at least one non-public callback found.
 *
 * @package Woo_Open_Graph
 */

$root = $argv[1] ?? dirname( __DIR__ );

$rii = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $root ) );
$files = array();
foreach ( $rii as $file ) {
	if ( $file->isDir() ) {
		continue;
	}
	$path = $file->getPathname();
	if ( substr( $path, -4 ) !== '.php' ) {
		continue;
	}
	if ( strpos( $path, '/vendor/' ) !== false || strpos( $path, '/node_modules/' ) !== false ) {
		continue;
	}
	$files[] = $path;
}

// Pass 1: record every method and its visibility, per class.
$methods = array();
foreach ( $files as $path ) {
	$src   = file_get_contents( $path );
	$class = null;
	if ( preg_match_all(
		'/^\s*(?:final\s+|abstract\s+)?class\s+(\w+)|^\s*(public|private|protected)?\s*(?:static\s+)?function\s+(\w+)\s*\(/m',
		$src,
		$matches,
		PREG_SET_ORDER
	) ) {
		foreach ( $matches as $m ) {
			if ( ! empty( $m[1] ) ) {
				$class = $m[1];
			} elseif ( $class && ! empty( $m[3] ) ) {
				$visibility = ! empty( $m[2] ) ? $m[2] : 'public';
				$methods[ $class ][ $m[3] ] = $visibility;
			}
		}
	}
}

// Pass 2: every callback registered as array( $this, 'method' ) must resolve to a public method.
$registrars = 'add_action|add_filter|add_shortcode|register_activation_hook|register_deactivation_hook';
$problems   = array();

foreach ( $files as $path ) {
	$src = file_get_contents( $path );
	if ( ! preg_match( '/^\s*(?:final\s+|abstract\s+)?class\s+(\w+)/m', $src, $cm ) ) {
		continue;
	}
	$class = $cm[1];

	$pattern = '/(' . $registrars . ')\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,\s*array\(\s*(?:\$this|__CLASS__|self::class)\s*,\s*[\'"](\w+)[\'"]/';
	if ( preg_match_all( $pattern, $src, $hits, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
		foreach ( $hits as $hit ) {
			$method     = $hit[3][0];
			$hook       = $hit[2][0];
			$visibility = isset( $methods[ $class ][ $method ] ) ? $methods[ $class ][ $method ] : null;
			if ( 'private' === $visibility || 'protected' === $visibility ) {
				$line       = substr_count( substr( $src, 0, $hit[0][1] ), "\n" ) + 1;
				$problems[] = sprintf(
					'%s:%d  %s::%s() is %s but is hooked to "%s"',
					str_replace( $root . '/', '', $path ),
					$line,
					$class,
					$method,
					strtoupper( $visibility ),
					$hook
				);
			}
		}
	}
}

if ( empty( $problems ) ) {
	echo "Callback visibility: OK - every hook callback resolves to a public method.\n";
	exit( 0 );
}

echo "Callback visibility: " . count( $problems ) . " problem(s) found.\n\n";
foreach ( $problems as $problem ) {
	echo '  ' . $problem . "\n";
}
echo "\nWordPress calls hook callbacks from global scope; a private or protected method\n";
echo "throws an uncaught TypeError when the hook fires. Make these public.\n";
exit( 1 );
