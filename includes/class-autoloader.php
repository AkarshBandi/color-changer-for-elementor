<?php

namespace WooElementorColors;

defined( 'ABSPATH' ) || exit;

spl_autoload_register( function ( $class ) {
	$prefix = 'WooElementorColors\\';
	$base   = WOOEC_PATH . 'includes/';

	if ( strncmp( $prefix, $class, strlen( $prefix ) ) !== 0 ) {
		return;
	}

	$relative_class = substr( $class, strlen( $prefix ) );
	$class_name     = str_replace( '_', '-', $relative_class );
	$file           = $base . 'class-' . strtolower( $class_name ) . '.php';

	if ( file_exists( $file ) ) {
		require $file;
	}
} );
