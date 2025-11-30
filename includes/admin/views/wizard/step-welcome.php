<?php defined('ABSPATH') || exit; ?>

<div class="wizard-step-content text-center">
    <h2><?php _e('Bem-vindo ao Tutor EAD!', 'tutor-ead'); ?></h2>
    <p class="lead"><?php _e('Este guia rápido ajudará você a configurar as opções essenciais do seu novo ambiente de ensino online.', 'tutor-ead'); ?></p>
    <p><?php _e('Se preferir, você pode pular e configurar tudo manualmente mais tarde na página de Configurações.', 'tutor-ead'); ?></p>

    <div class="wizard-buttons">
        <a href="<?php echo esc_url(admin_url('admin.php?page=tutor-ead-dashboard')); ?>" class="button button-secondary"><?php _e('Pular Configuração', 'tutor-ead'); ?></a>
        <button type="submit" class="button button-primary"><?php _e('Vamos Começar!', 'tutor-ead'); ?> <span class="dashicons dashicons-arrow-right-alt"></span></button>
    </div>
</div>
