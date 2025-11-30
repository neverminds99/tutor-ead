<?php
defined('ABSPATH') || exit;
?>
<div class="wrap" style="background:#fff; padding:20px; border-radius:8px; box-shadow:0 2px 4px rgba(0,0,0,.1); margin:20px 0;">
    <h1><?php esc_html_e('Editar Atividade Externa', 'tutor-ead'); ?></h1>

    <?php if (isset($_GET['success'])) : ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e('Atividade atualizada com sucesso!', 'tutor-ead'); ?></p>
        </div>
    <?php endif; ?>

    <form method="POST">
        <?php
        // O objeto $atividade é passado pelo render_view no ActivityManager
        if (isset($atividade)) {
            include __DIR__ . '/partials/form-fields-externa.php';
        }
        ?>
        <p><input type="submit" name="save_external_activity" class="button button-primary" value="<?php esc_attr_e('Salvar Alterações', 'tutor-ead'); ?>"></p>
    </form>
</div>
