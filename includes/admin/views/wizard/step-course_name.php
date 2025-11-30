<?php defined('ABSPATH') || exit; ?>

<div class="wizard-step-content">
    <h2><?php _e('Como devemos chamar sua plataforma?', 'tutor-ead'); ?></h2>
    <p class="lead"><?php _e('Este será o nome principal do seu ambiente de ensino.', 'tutor-ead'); ?></p>

    <div class="form-group">
        <input type="text" id="tutor_ead_course_name" name="tutor_ead_course_name" class="regular-text" value="<?php echo esc_attr($data['course_name']); ?>" placeholder="<?php _e('Ex: Minha Escola de Cursos', 'tutor-ead'); ?>" autofocus>
    </div>

    <div class="wizard-buttons">
        <a href="<?php echo esc_url(add_query_arg('step', 'welcome', admin_url('index.php?page=tutor-ead-setup-wizard'))); ?>" class="button button-secondary"><span class="dashicons dashicons-arrow-left-alt"></span> <?php _e('Voltar', 'tutor-ead'); ?></a>
        <button type="submit" class="button button-primary"><?php _e('Continuar', 'tutor-ead'); ?> <span class="dashicons dashicons-arrow-right-alt"></span></button>
    </div>
</div>
