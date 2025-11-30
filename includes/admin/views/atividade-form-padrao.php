<?php
defined('ABSPATH') || exit;
?>
<div class="wrap" style="background:#fff; padding:20px; border-radius:8px;">
    <h1><?php esc_html_e( 'Adicionar Nova Atividade Padrão', 'tutor-ead' ); ?></h1>

    <form method="POST" id="atividade-form">
        <?php include __DIR__ . '/partials/form-fields-padrao.php'; ?>
        <p><input type="submit" class="button button-primary"
            value="<?php esc_attr_e( 'Salvar Atividade', 'tutor-ead' ); ?>"></p>
    </form>
</div>
