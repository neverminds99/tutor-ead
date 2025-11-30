<?php defined('ABSPATH') || exit; ?>

<div class="wizard-step-content">
    <h2><?php _e('Escolha sua Cor Principal', 'tutor-ead'); ?></h2>
    <p class="lead"><?php _e('Esta cor será usada em botões, links e outros elementos para combinar com sua marca.', 'tutor-ead'); ?></p>

    <div class="form-group">
        <input type="text" id="tutor_ead_highlight_color" name="tutor_ead_highlight_color" class="wp-color-picker" value="<?php echo esc_attr($data['highlight_color']); ?>">
    </div>

    <div class="wizard-buttons">
        <a href="<?php echo esc_url(add_query_arg('step', 'logo', admin_url('index.php?page=tutor-ead-setup-wizard'))); ?>" class="button button-secondary"><span class="dashicons dashicons-arrow-left-alt"></span> <?php _e('Voltar', 'tutor-ead'); ?></a>
        <button type="submit" class="button button-primary"><?php _e('Finalizar', 'tutor-ead'); ?> <span class="dashicons dashicons-arrow-right-alt"></span></button>
    </div>
</div>
