<?php
/**
 * Color Swatch Template
 * Expected variables in scope: $term (WP_Term), $color (string hex), $selected (bool), $disabled (bool), $unavailable (bool)
 */

$classes = array(
    'tee-swatch-base',
    'group',
    'relative inline-flex items-center justify-center rounded-full outline outline-1 -outline-offset-1 w-10 h-10',
    'checked:outline-2 checked:outline-offset-2 forced-color-adjust-none',
);
if ( ! empty( $disabled ) ) {
    $classes[] = 'tee-swatch-disabled';
}
if ( ! empty( $unavailable ) ) {
    $classes[] = 'tee-swatch-unavailable';
}
$color = isset( $color ) && $color ? $color : ( get_term_meta( $term->term_id, 'tee_vs_swatch_color', true ) ?: '#cccccc' );
$input_id = 'tee-vs-' . esc_attr( $term->slug ?? uniqid() );
?>
<label class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" aria-label="<?php echo esc_html( $term->name ?? '' ); ?>">
    <input id="<?php echo esc_attr( $input_id ); ?>" class="absolute inset-0 appearance-none focus:outline-none" type="radio" name="attribute_<?php echo esc_attr( $term->taxonomy ); ?>" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( ! empty( $selected ) ); ?> <?php disabled( ! empty( $disabled ) ); ?> />
    <span class="block rounded-full w-8 h-8" style="background-color: <?php echo esc_attr( $color ); ?>;"></span>
</label>
