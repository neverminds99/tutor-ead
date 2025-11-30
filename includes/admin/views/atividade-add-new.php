<?php
defined('ABSPATH') || exit;
$highlight_color = get_option('tutor_ead_highlight_color', '#0073aa');
?>
<style>
    .tutor-activities-wrap {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        background: #f3f4f6;
        margin: -20px;
        padding: 32px;
        min-height: 100vh;
    }
    .activities-header { margin-bottom: 32px; }
    .activities-title {
        font-size: 32px;
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 8px 0;
    }
    .activities-subtitle {
        color: #6b7280;
        font-size: 16px;
        margin: 0;
    }
    .activity-type-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 24px;
    }
    .activity-type-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 24px;
        text-align: center;
        text-decoration: none;
        color: inherit;
        transition: all 0.2s ease;
    }
    .activity-type-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 16px rgba(0,0,0,0.08);
        border-color: <?php echo $highlight_color; ?>;
    }
    .activity-type-card .dashicons {
        font-size: 48px;
        width: 80px;
        height: 80px;
        line-height: 80px;
        background: #f3f4f6;
        color: <?php echo $highlight_color; ?>;
        border-radius: 50%;
        margin-bottom: 16px;
    }
    .activity-type-title {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 8px;
    }
    .activity-type-description {
        font-size: 14px;
        color: #6b7280;
        line-height: 1.5;
    }
</style>

<div class="wrap tutor-activities-wrap">
    <div class="activities-header">
        <h1 class="activities-title"><?php esc_html_e('Adicionar Nova Atividade', 'tutor-ead'); ?></h1>
        <p class="activities-subtitle"><?php esc_html_e('Selecione o tipo de atividade que você deseja criar.', 'tutor-ead'); ?></p>
    </div>

    <div class="activity-type-grid">
        <a href="<?php echo admin_url('admin.php?page=tutor-ead-unified-form&type=padrao'); ?>" class="activity-type-card">
            <span class="dashicons dashicons-clipboard"></span>
            <h2 class="activity-type-title"><?php _e('Atividade Padrão (Quiz)', 'tutor-ead'); ?></h2>
            <p class="activity-type-description"><?php _e('Crie um quiz com perguntas e alternativas diretamente no WordPress.', 'tutor-ead'); ?></p>
        </a>

        <a href="<?php echo admin_url('admin.php?page=tutor-ead-unified-form&type=externa'); ?>" class="activity-type-card">
            <span class="dashicons dashicons-admin-site-alt3"></span>
            <h2 class="activity-type-title"><?php _e('Atividade Externa (Link)', 'tutor-ead'); ?></h2>
            <p class="activity-type-description"><?php _e('Adicione um link para uma atividade hospedada em outra plataforma.', 'tutor-ead'); ?></p>
        </a>

        <a href="<?php echo admin_url('admin.php?page=tutor-ead-unified-form&type=presencial'); ?>" class="activity-type-card">
            <span class="dashicons dashicons-groups"></span>
            <h2 class="activity-type-title"><?php _e('Atividade Presencial', 'tutor-ead'); ?></h2>
            <p class="activity-type-description"><?php _e('Registre uma atividade offline para lançar a nota manualmente.', 'tutor-ead'); ?></p>
        </a>
    </div>
</div>
