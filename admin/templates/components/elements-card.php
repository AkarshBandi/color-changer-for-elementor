<?php
/**
 * Store Design — the store elements, one card each.
 *
 * This replaced a `wp-list-table` with a Status column and up to four
 * `<select>` elements per row — thirty-seven controls in one grid, all equally
 * loud, none of them showing what they affected.
 *
 * A card per element instead, each showing its main colour and nothing else
 * until asked. The other states are real and still editable; they are just not
 * the first thing anyone has to read.
 *
 * @package WooCommerce_Elementor_Colors
 */

defined( 'ABSPATH' ) || exit;

/** @var array $mappings All widget mappings. */
/** @var array $kit_colors Elementor kit colors keyed by token id. */
/** @var array $kit_labels Token id => human label. */
/** @var array $descriptions Widget key => plain description. */
/** @var array $defaults Per-slot default color tokens. */
?>
<div class="eccw-grid">
	<?php foreach ( $mappings as $eccw_widget_key => $eccw_widget_data ) : ?>
		<?php require ECCW_PATH . 'admin/templates/components/element-row.php'; ?>
	<?php endforeach; ?>
</div>
