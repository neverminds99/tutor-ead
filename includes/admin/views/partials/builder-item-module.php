<?php defined('ABSPATH') || exit; ?>

<?php
// Define qual URL de imagem usar: a capa salva ou o placeholder
$capa_img_url = !empty($modulo['capa_img']) ? esc_url($modulo['capa_img']) : $placeholder_img;
?>

<div class="module-item" data-item-id="<?php echo esc_attr($modulo['id']); ?>" data-item-type="module">
    <div class="item-header">
        <span class="drag-handle" title="<?php _e('Arrastar', 'tutor-ead'); ?>">☰</span>
        <img src="<?php echo $capa_img_url; ?>" class="item-thumbnail">
        <strong class="item-title-display"><?php echo esc_html($modulo['title']); ?></strong>
        <div class="item-actions">
            <button type="button" class="button-icon edit-json-btn" title="Editar como JSON"><span class="dashicons dashicons-edit"></span><span class="json-badge">J</span></button>
            <button type="button" class="button-icon edit-item-btn" title="<?php _e('Editar', 'tutor-ead'); ?>"><span class="dashicons dashicons-edit"></span></button>
            <button type="button" class="button-icon delete-item-btn" title="<?php _e('Excluir', 'tutor-ead'); ?>"><span class="dashicons dashicons-trash"></span></button>
        </div>
    </div>
    <div class="lessons-container">
        <?php
        // Renderiza as unidades aninhadas
        if (!empty($modulo['units'])) {
            foreach ($modulo['units'] as $unit) {
                require('builder-item-unit.php');
            }
        }
        // Renderiza as aulas diretas do módulo
        if (!empty($modulo['lessons'])) {
            foreach ($modulo['lessons'] as $aula) {
                require('builder-item-lesson.php');
            }
        }
        ?>
    </div>
    <div class="item-data-inputs hidden">
        <input type="text" class="item-title-input" value="<?php echo esc_html($modulo['title']); ?>">
        <textarea class="item-description-input"><?php echo esc_textarea($modulo['description']); ?></textarea>
        <input type="hidden" class="item-capa-img-input" value="<?php echo esc_url($modulo['capa_img']); ?>">
    </div>
</div>