<?php
/**
 * The status area's four checks.
 *
 * The product spec asks the screen to answer five questions. "Which Elementor
 * kit is being used?" was the one it could not, because the kit id was known but
 * never turned into a name. These cover the two facts that answer it, including
 * the states where the answer is honestly "no".
 *
 * @package Color_Changer_For_Elementor
 */

use PHPUnit\Framework\TestCase;
use ElementorColorChanger\Admin_Interface;

final class StatusFactsTest extends TestCase {

	protected function setUp(): void {
		ECCW_Test_Store::reset();
	}

	public function test_reports_the_kit_name() {
		ECCW_Test_Store::$options['elementor_active_kit'] = 6;
		ECCW_Test_Store::$meta[6]['__title']              = 'Bariatric Chic Kit';

		$this->assertSame( 'Bariatric Chic Kit', Admin_Interface::kit_name() );
	}

	public function test_reports_no_kit_name_when_there_is_no_kit() {
		$this->assertSame( '', Admin_Interface::kit_name() );
	}

	public function test_trims_a_padded_kit_title() {
		ECCW_Test_Store::$options['elementor_active_kit'] = 6;
		ECCW_Test_Store::$meta[6]['__title']              = "  Default Kit \n";

		$this->assertSame( 'Default Kit', Admin_Interface::kit_name() );
	}

	public function test_detects_kit_typography() {
		ECCW_Test_Store::$options['elementor_active_kit']     = 6;
		ECCW_Test_Store::$meta[6]['_elementor_page_settings'] = array(
			'h1_typography_font_family' => 'Playfair Display',
		);

		$this->assertTrue( Admin_Interface::has_kit_typography() );
	}

	/**
	 * A kit with colours but no fonts must say so rather than claim fonts were
	 * found — the checklist is only worth showing if it can report a "no".
	 */
	public function test_reports_absent_typography_honestly() {
		ECCW_Test_Store::$options['elementor_active_kit']     = 6;
		ECCW_Test_Store::$meta[6]['_elementor_page_settings'] = array(
			'system_colors' => array(
				array(
					'_id'   => 'primary',
					'title' => 'Primary',
					'color' => '#111111',
				),
			),
		);

		$this->assertFalse( Admin_Interface::has_kit_typography() );
	}

	/**
	 * Custom colour titles are the whole reason the palette reads in English
	 * rather than in Elementor's internal ids.
	 */
	public function test_kit_colour_labels_use_the_titles_the_owner_chose() {
		ECCW_Test_Store::$options['elementor_active_kit']     = 6;
		ECCW_Test_Store::$meta[6]['_elementor_page_settings'] = array(
			'system_colors' => array(
				array(
					'_id'   => 'primary',
					'title' => 'Primary',
					'color' => '#111111',
				),
			),
			'custom_colors' => array(
				array(
					'_id'   => '2666acb8',
					'title' => 'Light Grey',
					'color' => '#FAFAFA',
				),
			),
		);

		$labels = Admin_Interface::kit_color_labels();

		$this->assertSame( 'Light Grey', $labels['2666acb8'], 'a custom colour must read as its own title, not its id' );
		$this->assertSame( 'Primary', $labels['primary'] );
		$this->assertSame( 'Button colour', $labels['button_normal'], 'derived tokens keep their plain-language names' );
	}

	public function test_kit_colour_labels_survive_a_kit_with_no_titles() {
		ECCW_Test_Store::$options['elementor_active_kit']     = 6;
		ECCW_Test_Store::$meta[6]['_elementor_page_settings'] = array(
			'custom_colors' => array(
				array(
					'_id'   => 'abc123',
					'color' => '#fff',
				),
			),
		);

		$labels = Admin_Interface::kit_color_labels();

		$this->assertArrayNotHasKey( 'abc123', $labels, 'a colour with no title must not produce an empty label' );
	}
}
