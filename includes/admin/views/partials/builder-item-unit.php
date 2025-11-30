<?php defined('ABSPATH') || exit; ?>

<div class="unit-item" data-item-id="<?php echo esc_attr($unit['id']); ?>" data-item-type="unit" data-parent-id="<?php echo esc_attr($unit['parent_id']); ?>">
    <div class="item-header">
        <span class="drag-handle" title="<?php _e('Arrastar', 'tutor-ead'); ?>">☰</span>
        <span class="dashicons dashicons-folder item-icon"></span>
        <strong class="item-title-display"><?php echo esc_html($unit['title']); ?></strong>
        <div class="item-actions">
            <button type="button" class="button-icon edit-item-btn" title="<?php _e('Editar', 'tutor-ead'); ?>"><span class="dashicons dashicons-edit"></span></button>
            <button type="button" class="button-icon delete-item-btn" title="<?php _e('Excluir', 'tutor-ead'); ?>"><span class="dashicons dashicons-trash"></span></button>
        </div>
    </div>
    <div class="lessons-container">
        <?php
        if (!empty($unit['lessons'])) {
            foreach ($unit['lessons'] as $aula) {
                require('builder-item-lesson.php');
            }
        }
        ?>
    </div>
    <div class="item-data-inputs hidden">
        <input type="text" class="item-title-input" value="<?php echo esc_attr($unit['title']); ?>">
    </div>
</div>