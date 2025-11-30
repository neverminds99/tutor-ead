<?php
defined('ABSPATH') || exit;
/* $associations (array) já fornecido */
?>
<h2 style="color:#555;margin-top:20px;">
	<?php esc_html_e( 'Associações desta Atividade', 'tutor-ead' ); ?>
</h2>

<table class="widefat fixed striped" style="width:100%;border-collapse:collapse;margin-bottom:20px;">
	<thead style="background:#ddd;">
		<tr>
			<th style="padding:8px;border:1px solid #ccc;"><?php esc_html_e( 'Curso', 'tutor-ead' ); ?></th>
			<th style="padding:8px;border:1px solid #ccc;"><?php esc_html_e( 'Módulo', 'tutor-ead' ); ?></th>
			<th style="padding:8px;border:1px solid #ccc;"><?php esc_html_e( 'Ação', 'tutor-ead' ); ?></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $associations as $assoc ) : ?>
			<tr>
				<td style="padding:8px;border:1px solid #ccc;"><?php echo esc_html( $assoc['course_title'] ); ?></td>
				<td style="padding:8px;border:1px solid #ccc;"><?php echo esc_html( $assoc['module_title'] ); ?></td>
				<td style="padding:8px;border:1px solid #ccc;">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=tutor-ead-delete-associacao&assoc_id=' . intval( $assoc['id'] ) ) ); ?>"
					   style="color:#a00;"
					   onclick="return confirm('<?php echo esc_js( __( 'Tem certeza que deseja excluir esta associação?', 'tutor-ead' ) ); ?>');">
						<?php esc_html_e( 'Excluir Associação', 'tutor-ead' ); ?>
					</a>
				</td>
			</tr>
		<?php endforeach; ?>
	</tbody>
</table>
