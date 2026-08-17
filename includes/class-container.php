<?php

namespace ElementorColorChanger;

defined( 'ABSPATH' ) || exit;

/**
 * Minimal service container.
 *
 * Services are registered as factories and instantiated on first use, so an
 * add-on can replace an implementation before anything is built.
 *
 * Replace a service from an add-on:
 *
 *     add_action( 'eccw_register_services', function () {
 *         Container::replace( 'editor', function () {
 *             return new \My_Addon\Pro_Live_Editor();
 *         } );
 *     } );
 */
class Container {

	/**
	 * Registered factories, keyed by service id.
	 *
	 * @var array<string,callable>
	 */
	private static $factories = array();

	/**
	 * Resolved instances, keyed by service id.
	 *
	 * @var array<string,object>
	 */
	private static $instances = array();

	/**
	 * Register a factory. Does nothing if the id is already taken.
	 *
	 * @param string   $id      Service id.
	 * @param callable $factory Returns the service instance.
	 * @return bool Whether the factory was registered.
	 */
	public static function set( $id, $factory ) {
		$id = (string) $id;

		if ( isset( self::$factories[ $id ] ) || ! is_callable( $factory ) ) {
			return false;
		}

		self::$factories[ $id ] = $factory;

		return true;
	}

	/**
	 * Register or overwrite a factory, discarding any resolved instance.
	 *
	 * This is the entry point for add-ons swapping an implementation.
	 *
	 * @param string   $id      Service id.
	 * @param callable $factory Returns the service instance.
	 * @return bool Whether the factory was accepted.
	 */
	public static function replace( $id, $factory ) {
		$id = (string) $id;

		if ( ! is_callable( $factory ) ) {
			return false;
		}

		self::$factories[ $id ] = $factory;
		unset( self::$instances[ $id ] );

		return true;
	}

	/**
	 * Resolve a service, building it on first request.
	 *
	 * @param string $id Service id.
	 * @return object|null Null when nothing is registered under that id.
	 */
	public static function get( $id ) {
		$id = (string) $id;

		if ( isset( self::$instances[ $id ] ) ) {
			return self::$instances[ $id ];
		}

		if ( ! isset( self::$factories[ $id ] ) ) {
			return null;
		}

		$instance = call_user_func( self::$factories[ $id ] );

		if ( ! is_object( $instance ) ) {
			return null;
		}

		/**
		 * Filters a service instance as it is resolved.
		 *
		 * @param object $instance Resolved service.
		 * @param string $id       Service id.
		 */
		$instance = apply_filters( 'eccw_service', $instance, $id );

		self::$instances[ $id ] = $instance;

		return $instance;
	}

	/**
	 * Whether a factory is registered under this id.
	 *
	 * @param string $id Service id.
	 * @return bool
	 */
	public static function has( $id ) {
		return isset( self::$factories[ (string) $id ] );
	}

	/**
	 * Registered service ids.
	 *
	 * @return string[]
	 */
	public static function ids() {
		return array_keys( self::$factories );
	}

	/**
	 * Drop all factories and instances. Used by the test suite.
	 */
	public static function reset() {
		self::$factories = array();
		self::$instances = array();
	}
}
