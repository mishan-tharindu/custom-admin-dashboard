<?php
// Retrieve caption text
$caption = isset($attributes['caption']) ? $attributes['caption'] : '';

// get_block_wrapper_attributes() automatically handles:
// 1. Theme Fonts (has-font-family-x)
// 2. Custom Colors (has-text-color, etc.)
// 3. Font Sizes
// 4. Bold/Italic/Decoration (via style attribute)
// 5. Custom Classes
$wrapper_attributes = get_block_wrapper_attributes( array( 
    'class' => 'cad-photo-caption-block' // Add our base class
) );
?>
<div <?php echo $wrapper_attributes; ?>>
    <?php echo wp_kses_post($caption); ?>
</div>