<?php defined('ABSPATH') || exit; ?>

<template id="inspector-module-form-template">
    <div class="inspector-form" data-item-type="module">
        <div class="inspector-header"><h3><?php _e('Editar Módulo', 'tutor-ead'); ?></h3><button type="button" class="button-icon close-inspector-btn" title="<?php _e('Fechar', 'tutor-ead'); ?>"><span class="dashicons dashicons-no-alt"></span></button></div>
        <div class="inspector-content">
            <div class="form-field"><label>Título do Módulo</label><input type="text" id="inspector-title"></div>
            <div class="form-field"><label>Descrição</label><textarea id="inspector-description" rows="5"></textarea></div>
            <div class="form-field"><label>Imagem de Capa</label><div class="image-uploader"><img src="" class="image-preview"><div class="image-buttons"><button type="button" class="button select-image-btn">Selecionar</button><button type="button" class="button-link-delete remove-image-btn">Remover</button></div></div></div>
        </div>
    </div>
</template>

<template id="inspector-lesson-form-template">
    <div class="inspector-form" data-item-type="lesson">
        <div class="inspector-header"><h3><?php _e('Editar Aula', 'tutor-ead'); ?></h3><button type="button" class="button-icon close-inspector-btn" title="<?php _e('Fechar', 'tutor-ead'); ?>"><span class="dashicons dashicons-no-alt"></span></button></div>
        <div class="inspector-content">
            <div class="form-field"><label>Título da Aula</label><input type="text" id="inspector-title"></div>
            <div class="form-field"><label>Conteúdo</label><textarea id="inspector-content" rows="8"></textarea></div>
            <div class="form-field"><label>URL do Vídeo</label><input type="url" id="inspector-video-url"></div>
        </div>
    </div>
</template>

<template id="module-item-template">
    <div class="module-item" data-item-type="module">
        <div class="item-header"><span class="drag-handle" title="Arrastar">☰</span><img src="<?php echo $placeholder_img; ?>" class="item-thumbnail"><strong class="item-title-display">Novo Módulo</strong><div class="item-actions"><button type="button" class="button-icon edit-item-btn" title="Editar"><span class="dashicons dashicons-edit"></span></button><button type="button" class="button-icon delete-item-btn" title="Excluir"><span class="dashicons dashicons-trash"></span></button></div></div>
        <div class="lessons-container"></div>
        <div class="item-data-inputs hidden"><input type="text" class="item-title-input" value="Novo Módulo"><textarea class="item-description-input"></textarea><input type="hidden" class="item-capa-img-input" value=""></div>
    </div>
</template>

<template id="lesson-item-template">
    <div class="lesson-item" data-item-type="lesson">
        <div class="item-header"><span class="drag-handle" title="Arrastar">☰</span><span class="dashicons dashicons-media-default item-icon"></span><span class="item-title-display">Nova Aula</span><div class="item-actions"><button type="button" class="button-icon edit-item-btn" title="Editar"><span class="dashicons dashicons-edit"></span></button><button type="button" class="button-icon delete-item-btn" title="Excluir"><span class="dashicons dashicons-trash"></span></button></div></div>
        <div class="item-data-inputs hidden"><input type="text" class="item-title-input" value="Nova Aula"><textarea class="item-content-input"></textarea><input type="url" class="item-video-url-input" value=""></div>
    </div>
</template>

<template id="inspector-unit-form-template">
    <div class="inspector-form" data-item-type="unit">
        <div class="inspector-header"><h3><?php _e('Editar Unidade', 'tutor-ead'); ?></h3><button type="button" class="button-icon close-inspector-btn" title="<?php _e('Fechar', 'tutor-ead'); ?>"><span class="dashicons dashicons-no-alt"></span></button></div>
        <div class="inspector-content">
            <div class="form-field"><label>Título da Unidade</label><input type="text" id="inspector-title"></div>
        </div>
    </div>
</template>

<template id="unit-item-template">
    <div class="unit-item" data-item-type="unit">
        <div class="item-header"><span class="drag-handle" title="Arrastar">☰</span><span class="dashicons dashicons-folder item-icon"></span><strong class="item-title-display">Nova Unidade</strong><div class="item-actions"><button type="button" class="button-icon edit-item-btn" title="Editar"><span class="dashicons dashicons-edit"></span></button><button type="button" class="button-icon delete-item-btn" title="Excluir"><span class="dashicons dashicons-trash"></span></button></div></div>
        <div class="lessons-container"></div>
        <div class="item-data-inputs hidden"><input type="text" class="item-title-input" value="Nova Unidade"></div>
    </div>
</template>