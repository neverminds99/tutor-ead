<?php defined('ABSPATH') || exit; ?>

<div class="wizard-step-content text-center">
    <span class="dashicons dashicons-yes-alt success-icon"></span>
    <h2><?php _e('Configuração Concluída!', 'tutor-ead'); ?></h2>
    <p class="lead"><?php _e('Seu ambiente Tutor EAD está pronto para ser usado.', 'tutor-ead'); ?></p>
    <p><?php _e('Você pode gerenciar todas as configurações a qualquer momento na página de Configurações do Tutor EAD.', 'tutor-ead'); ?></p>

    <div class="wizard-buttons">
        <a href="<?php echo esc_url(admin_url('admin.php?page=tutor-ead-settings')); ?>" class="button button-secondary"><?php _e('Configurações Avançadas', 'tutor-ead'); ?></a>
        <button type="submit" class="button button-primary"><?php _e('Ir para o Painel', 'tutor-ead'); ?> <span class="dashicons dashicons-dashboard"></span></button>
    </div>
</div>
