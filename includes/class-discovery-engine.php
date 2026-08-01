<?php

namespace WooElementorColors;

defined( 'ABSPATH' ) || exit;

class Discovery_Engine {

	public function scan_all_pages() {
		$post_types = apply_filters(
			'wooce_scan_post_types',
			array( 'page', 'product' )
		);

		$posts = get_posts( array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'meta_key'       => '_elementor_data',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		) );

		$widgets = array();

		foreach ( $posts as $post_id ) {
			$elementor_data = get_post_meta( $post_id, '_elementor_data', true );

			if ( empty( $elementor_data ) ) {
				continue;
			}

			$data = json_decode( $elementor_data, true );

			if ( ! is_array( $data ) ) {
				continue;
			}

			$this->walk_elements( $data, $widgets );
		}

		return array_unique( $widgets );
	}

	private function walk_elements( $elements, &$widgets ) {
		Mapping_Service::walk_elements( $elements, $widgets );
	}
}
