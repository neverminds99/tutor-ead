<?php defined('ABSPATH') || exit; ?>

<div class="lesson-item" data-item-id="<?php echo esc_attr($aula['id']); ?>" data-item-type="lesson">
    <div class="item-header">
        <span class="drag-handle" title="<?php _e('Arrastar', 'tutor-ead'); ?>">☰</span>
        <span class="dashicons dashicons-media-default item-icon"></span>
        <span class="item-title-display"><?php echo esc_html($aula['title']); ?></span>
        <div class="item-actions">
            <button type="button" class="button-icon edit-item-btn" title="<?php _e('Editar', 'tutor-ead'); ?>"><span class="dashicons dashicons-edit"></span></button>
            <button type="button" class="button-icon delete-item-btn" title="<?php _e('Excluir', 'tutor-ead'); ?>"><span class="dashicons dashicons-trash"></span></button>
        </div>
    </div>
    <div class="item-data-inputs hidden">
        <input type="text" class="item-title-input" value="<?php echo esc_html($aula['title']); ?>">
        <textarea class="item-content-input"><?php echo esc_textarea($aula['content']); ?></textarea>
        <input type="url" class="item-video-url-input" value="<?php echo esc_url($aula['video_url']); ?>">
    </div>
</div>