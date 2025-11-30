<?php
/**
 * View: Editar Boletim (layout dashboard)
 *
 * Espera:
 * $courses (lista de cursos)
 * $boletim (objeto existente) – fornecido pelo controller
 */
defined( 'ABSPATH' ) || exit;

$action = 'edit';
?>
<div class="wrap boletim-wrap">

	<header class="boletim-header">
		<div class="header-left">
			<h1 class="boletim-title">
				<span class="dashicons dashicons-welcome-write-blog"></span>
				<?php _e( 'Editar Boletim', 'tutor-ead' ); ?>
			</h1>
			<p class="boletim-subtitle">
				<?php printf( __( 'Editando avaliação #%s', 'tutor-ead' ), $boletim->id ); ?>
			</p>
		</div>
	</header>

	<div class="boletim-card">
		<div class="card-header">
			<h2 class="card-title">
				<span class="dashicons dashicons-edit"></span>
				<?php _e( 'Formulário de Avaliação', 'tutor-ead' ); ?>
			</h2>
		</div>
		<div class="card-divider"></div>
		<?php include __DIR__ . '/partials/boletim-form.php'; ?>
	</div>

</div>