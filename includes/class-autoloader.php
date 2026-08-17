<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

spl_autoload_register(
	function ( $class ) { // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames -- Parameter name is fixed by PHP's autoload contract.
		$prefix = 'ElementorColorChanger\\';
		$base   = ECCW_PATH . 'includes/';

		if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
				return;
		}

		$relative_class = substr( $class, strlen( $prefix ) );

		// Every class in this plugin sits directly under the one namespace, so
		// anything left holding a separator is either a sub-namespace that does
		// not exist or a crafted string handed to class_exists(). Refusing is
		// cheaper than reasoning about what path it would produce.
		if ( ! preg_match( '/^[A-Za-z0-9_]+$/', $relative_class ) ) {
			return;
		}

		$class_name = str_replace( '_', '-', $relative_class );
		$file       = $base . 'class-' . strtolower( $class_name ) . '.php';

		if ( file_exists( $file ) ) {
			require $file;
		}
	}
);
