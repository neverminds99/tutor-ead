<?php
/**
 * View: Listagem de Boletins (layout moderno com cards)
 */
defined( 'ABSPATH' ) || exit;
use function __ as _;

$create_url = admin_url( 'admin.php?page=tutor-ead-create-boletim' );
$highlight_color = get_option('tutor_ead_highlight_color', '#0073aa');
?>

<!-- Estilos Modernos -->
<style>
    .tutor-students-wrap {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        background: #f3f4f6;
        margin: -20px;
        padding: 32px;
        min-height: 100vh;
    }
    .tutor-students-wrap * { box-sizing: border-box; }
    .students-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 32px;
    }
    .students-title {
        font-size: 32px;
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 8px 0;
    }
    .students-subtitle {
        color: #6b7280;
        font-size: 16px;
        margin: 0;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 24px;
        margin-bottom: 32px;
    }
    .stat-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        text-align: center;
    }
    .stat-icon {
        width: 56px;
        height: 56px;
        background: #f3f4f6;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 16px;
    }
    .stat-icon .dashicons {
        font-size: 32px;
        color: <?php echo $highlight_color; ?>;
    }
    .stat-value {
        font-size: 32px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 4px;
    }
    .stat-label {
        font-size: 14px;
        color: #6b7280;
    }
    .stat-description {
        font-size: 12px;
        color: #9ca3af;
        margin-top: 4px;
    }
    .tutor-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        margin-bottom: 24px;
    }
    .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .card-title {
        font-size: 20px;
        font-weight: 600;
        color: #1f2937;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .card-title .dashicons {
        font-size: 24px;
        color: <?php echo $highlight_color; ?>;
    }
    .btn-primary {
        background: <?php echo $highlight_color; ?>;
        color: #ffffff;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        text-decoration: none;
    }
    .btn-primary:hover {
        background: <?php echo $highlight_color; ?>e6;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    /* Estilos para a Grade de Cards de Notas */
    .boletim-cards-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 24px;
    }
    .boletim-card-item {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 20px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        transition: all 0.2s ease;
    }
    .boletim-card-item:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }
    .boletim-card-header {
        margin-bottom: 16px;
    }
    .boletim-card-aluno {
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
    }
    .boletim-card-id {
        font-size: 12px;
        color: #9ca3af;
    }
    .boletim-card-body {
        margin-bottom: 16px;
    }
    .boletim-card-body p {
        margin: 0 0 8px;
        font-size: 14px;
        color: #6b7280;
    }
    .boletim-card-body strong {
        color: #374151;
    }
    .boletim-card-nota {
        font-size: 28px;
        font-weight: 700;
        color: <?php echo $highlight_color; ?>;
        text-align: center;
        margin-bottom: 16px;
    }
    .boletim-card-footer {
        border-top: 1px solid #f3f4f6;
        padding-top: 16px;
        text-align: right;
        display: flex;
        gap: 8px;
        justify-content: flex-end;
    }
    .btn-secondary {
        background: #f3f4f6;
        color: #374151;
        border: 1px solid #e5e7eb;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 500;
        font-size: 13px;
        cursor: pointer;
        text-decoration: none;
        transition: all 0.2s ease;
    }
    .btn-secondary:hover {
        background: #e5e7eb;
    }
    .btn-danger {
        background: #fee2e2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }
    .btn-danger:hover {
        background: #fecaca;
    }
</style>

<div class="tutor-students-wrap">

	<!-- ===== Cabeçalho ===== -->
	<header class="students-header">
		<div>
			<h1 class="students-title">
				<span class="dashicons dashicons-welcome-learn-more"></span>
				<?php _e( 'Boletim', 'tutor-ead' ); ?>
			</h1>
			<p class="students-subtitle"><?php _e( 'Gerencie as notas e feedbacks dos alunos', 'tutor-ead' ); ?></p>
		</div>
		<a href="<?php echo esc_url( $create_url ); ?>" class="btn-primary">
			<span class="dashicons dashicons-plus-alt"></span>
			<?php _e( 'Novo Boletim', 'tutor-ead' ); ?>
		</a>
	</header>

    <!-- ===== Estatísticas ===== -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon">
                <span class="dashicons dashicons-feedback"></span>
            </div>
            <div class="stat-value"><?php echo intval($notas_lancadas); ?> de <?php echo intval($notas_esperadas); ?></div>
            <div class="stat-label"><?php _e('Cobertura de Notas', 'tutor-ead'); ?></div>
            <div class="stat-description"><?php _e('Total de notas lançadas no boletim', 'tutor-ead'); ?></div>
        </div>
    </div>

	<!-- ===== Filtros ===== -->
	<div class="tutor-card">
		<div class="card-header">
			<h2 class="card-title">
				<span class="dashicons dashicons-filter"></span>
				<?php _e( 'Filtros', 'tutor-ead' ); ?>
			</h2>
		</div>
		<?php include __DIR__ . '/partials/boletim-filters.php'; ?>
	</div>

	<!-- ===== Grade de Cards de Notas ===== -->
	<div class="tutor-card">
        <div class="card-header">
            <h2 class="card-title">
                <span class="dashicons dashicons-list-view"></span>
                <?php _e( 'Últimas Notas Lançadas', 'tutor-ead' ); ?>
            </h2>
        </div>
        
        <div class="boletim-cards-grid">
            <?php if ( !empty($rows) ) : foreach ( $rows as $r ) : 
                $delete_nonce = wp_create_nonce( 'delete_boletim_nonce' );
                $delete_url = admin_url( 'admin-post.php?action=delete_boletim&id=' . $r->id . '&nonce=' . $delete_nonce );
            ?>
                <div class="boletim-card-item">
                    <div>
                        <div class="boletim-card-header">
                            <div class="boletim-card-aluno"><?php echo esc_html( get_userdata( $r->aluno_id )->display_name ?? '—' ); ?></div>
                            <div class="boletim-card-id">ID: <?php echo esc_html( $r->aluno_id ); ?></div>
                        </div>
                        <div class="boletim-card-nota"><?php echo esc_html( $r->nota ); ?></div>
                        <div class="boletim-card-body">
                            <p><strong><?php _e( 'Curso:', 'tutor-ead' ); ?></strong> <?php echo esc_html( $r->course_title ); ?></p>
                            <p><strong><?php _e( 'Atividade:', 'tutor-ead' ); ?></strong> <?php echo esc_html( $r->atividade_title ); ?></p>
                            <p><strong><?php _e( 'Data:', 'tutor-ead' ); ?></strong> <?php echo esc_html( $r->data_atualizacao ); ?></p>
                        </div>
                    </div>
                    <div class="boletim-card-footer">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=tutor-ead-edit-boletim&id=' . $r->id ) ); ?>" class="btn-secondary">
                            <?php _e( 'Editar', 'tutor-ead' ); ?>
                        </a>
                        <a href="<?php echo esc_url( $delete_url ); ?>" class="btn-secondary btn-danger" onclick="return confirm('<?php _e( 'Tem certeza que deseja excluir esta nota?', 'tutor-ead' ); ?>');">
                            <?php _e( 'Excluir', 'tutor-ead' ); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; else : ?>
                <p><?php _e( 'Nenhum registro encontrado.', 'tutor-ead' ); ?></p>
            <?php endif; ?>
        </div>
        
        <?php include __DIR__ . '/partials/boletim-pagination.php'; ?>
	</div>

</div>
