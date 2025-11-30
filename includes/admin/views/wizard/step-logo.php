<?php defined('ABSPATH') || exit; ?>

<div class="wizard-step-content">
    <h2><?php _e('Envie seu Logotipo', 'tutor-ead'); ?></h2>
    <p class="lead"><?php _e('Sua marca é importante. O logotipo aparecerá em várias partes do site.', 'tutor-ead'); ?></p>

    <div class="form-group">
        <div class="media-preview" id="logo-preview">
            <?php if (!empty($data['course_logo'])) : ?>
                <img src="<?php echo esc_url($data['course_logo']); ?>">
            <?php else: ?>
                <span><?php _e('A pré-visualização aparecerá aqui.', 'tutor-ead'); ?></span>
            <?php endif; ?>
        </div>
        <input type="hidden" id="tutor_ead_course_logo" name="tutor_ead_course_logo" value="<?php echo esc_attr($data['course_logo']); ?>">
        <button type="button" class="button button-primary" id="upload_logo_button" style="width: 100%; margin-top: 15px; padding: 10px;"><?php _e('Escolher Imagem', 'tutor-ead'); ?></button>
    </div>

    <div class="wizard-buttons">
        <a href="<?php echo esc_url(add_query_arg('step', 'course_name', admin_url('index.php?page=tutor-ead-setup-wizard'))); ?>" class="button button-secondary"><span class="dashicons dashicons-arrow-left-alt"></span> <?php _e('Voltar', 'tutor-ead'); ?></a>
        <button type="submit" class="button button-primary"><?php _e('Continuar', 'tutor-ead'); ?> <span class="dashicons dashicons-arrow-right-alt"></span></button>
    </div>
</div>
