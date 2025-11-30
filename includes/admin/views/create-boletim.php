<?php
/**
 * View: Criar Novo Boletim (layout dashboard)
 *
 * Espera:
 * $courses (lista de cursos) já carregada pelo controller
 */
defined( 'ABSPATH' ) || exit;

// variáveis para o formulário
$action  = 'create';
$boletim = null; // criação

// Exibe aviso de nota duplicada, se aplicável
if ( isset( $_GET['exists'] ) ) {
    $boletim_page_url = admin_url( 'admin.php?page=tutor-ead-boletim' );
    echo '<div class="notice notice-warning is-dismissible"><p>'
       . sprintf(
           wp_kses(
               /* translators: %s: URL to the bulletin page */
               __( 'Já existe uma nota para este aluno nesta atividade. Você pode <a href="%s">editá-la na página de boletim</a>.', 'tutor-ead' ),
               [ 'a' => [ 'href' => [] ] ]
           ),
           esc_url( $boletim_page_url )
       )
       . '</p></div>';
}
?>
<div class="wrap boletim-wrap">

	<header class="boletim-header">
		<div class="header-left">
			<h1 class="boletim-title">
				<span class="dashicons dashicons-welcome-write-blog"></span>
				<?php _e( 'Criar Novo Boletim', 'tutor-ead' ); ?>
			</h1>
			<p class="boletim-subtitle"><?php _e( 'Adicione uma nova avaliação ao boletim do aluno', 'tutor-ead' ); ?></p>
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
