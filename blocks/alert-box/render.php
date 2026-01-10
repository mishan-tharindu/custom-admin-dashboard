<?php
// Retrieve attributes
$message = isset($attributes['message']) ? $attributes['message'] : '';
$type    = isset($attributes['alertType']) ? $attributes['alertType'] : 'info';

// Output HTML
?>
<div class="cad-alert-box <?php echo esc_attr($type); ?>">
    <strong>Alert:</strong> <?php echo wp_kses_post($message); ?>
</div>