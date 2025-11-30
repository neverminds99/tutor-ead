<?php defined('ABSPATH') || exit; ?>

<div id="inspector-default-view">
    <div class="block-library-header">
        <h3><?php _e('Blocos', 'tutor-ead'); ?></h3>
        <div class="view-toggle-buttons">
            <button type="button" id="view-toggle-list" class="button-icon active" title="Lista"><span class="dashicons dashicons-list-view"></span></button>
            <button type="button" id="view-toggle-grid" class="button-icon" title="Grade"><span class="dashicons dashicons-grid-view"></span></button>
        </div>
    </div>
    <p><?php _e('Clique ou arraste um bloco para a estrutura do curso.', 'tutor-ead'); ?></p>
    
    <div id="block-library-skeleton">
        <div class="skeleton-block-item"></div>
        <div class="skeleton-block-item"></div>
        <div class="skeleton-block-item"></div>
    </div>

    <div id="block-library" style="display: none;">
        <div class="block-item" data-block-type="module">
            <span class="dashicons dashicons-category"></span>
            <?php _e('Módulo', 'tutor-ead'); ?>
        </div>
        <div class="block-item" data-block-type="unit">
            <span class="dashicons dashicons-category"></span>
            <?php _e('Unidade', 'tutor-ead'); ?>
        </div>
        <div class="block-item" data-block-type="lesson">
            <span class="dashicons dashicons-media-default"></span>
            <?php _e('Aula', 'tutor-ead'); ?>
        </div>
    </div>
</div>

<div id="inspector-edit-view" class="hidden">
    </div>