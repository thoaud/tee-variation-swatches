<?php
/**
 * Button Swatch Template
 * Expected variables in scope: $term (WP_Term), $selected (bool), $disabled (bool), $unavailable (bool)
 */

$classes = array(
    'tee-swatch-base',
    'group',
    'inline-flex items-center justify-center uppercase text-sm font-medium rounded-md border px-3 py-2',
    'has-checked:border-indigo-600 has-checked:bg-indigo-600 group-has-checked:text-white',
);
if ( ! empty( $disabled ) ) {
    $classes[] = 'tee-swatch-disabled';
}
if ( ! empty( $unavailable ) ) {
    $classes[] = 'tee-swatch-unavailable';
}

$label = esc_html( $term->name ?? '' );
$input_id = 'tee-vs-' . esc_attr( $term->slug ?? uniqid() );
?>
<label class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>" aria-label="<?php echo $label; ?>">
    <input id="<?php echo esc_attr( $input_id ); ?>" class="absolute inset-0 appearance-none focus:outline-none" type="radio" name="attribute_<?php echo esc_attr( $term->taxonomy ); ?>" value="<?php echo esc_attr( $term->slug ); ?>" <?php checked( ! empty( $selected ) ); ?> <?php disabled( ! empty( $disabled ) ); ?> />
    <span><?php echo $label; ?></span>
</label>
