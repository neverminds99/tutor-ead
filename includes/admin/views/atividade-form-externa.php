<?php
defined('ABSPATH') || exit;
?>
<div class="wrap" style="background:#fff; padding:20px; border‑radius:8px; box‑shadow:0 2px 4px rgba(0,0,0,.1); margin:20px;">
	<h1><?php esc_html_e( 'Adicionar Nova Atividade Padrão', 'tutor-ead' ); ?></h1>

	<form method="POST" id="atividade-form">
		<?php include __DIR__ . '/partials/form-fields-externa.php'; ?>
		<p><input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Salvar Atividade', 'tutor-ead' ); ?>"></p>
	</form>
</div>
