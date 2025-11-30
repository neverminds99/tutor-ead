<?php
defined('ABSPATH') || exit;
?>
<h2 style="color:#555;border-bottom:1px solid #ccc;padding-bottom:5px;">
	<?php esc_html_e( 'Atividades Presenciais', 'tutor-ead' ); ?>
</h2>

<a href="<?php echo esc_url( admin_url( 'admin.php?page=tutor-ead-add-atividade-sem-url' ) ); ?>"
   class="button"
   style="background:#555;color:#fff;padding:10px 15px;border:none;border-radius:4px;text-decoration:none;display:inline-block;margin-bottom:15px;">
	<?php esc_html_e( 'Criar Nova Atividade Sem URL', 'tutor-ead' ); ?>
</a>

<?php if ( $atividades_presenciais ) : ?>
	<table class="widefat fixed striped" style="width:100%;border-collapse:collapse;margin-bottom:20px;">
		<thead style="background:#ddd;">
			<tr>
				<th style="padding:8px;border:1px solid #ccc;"><?php esc_html_e( 'ID', 'tutor-ead' ); ?></th>
				<th style="padding:8px;border:1px solid #ccc;"><?php esc_html_e( 'Título', 'tutor-ead' ); ?></th>
				<th style="padding:8px;border:1px solid #ccc;"><?php esc_html_e( 'Criado em', 'tutor-ead' ); ?></th>
				<th style="padding:8px;border:1px solid #ccc;"><?php esc_html_e( 'Ações', 'tutor-ead' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $atividades_presenciais as $a ) : ?>
				<tr>
					<td style="padding:8px;border:1px solid #ccc;"><?php echo esc_html( $a->id ); ?></td>
					<td style="padding:8px;border:1px solid #ccc;"><?php echo esc_html( $a->titulo ); ?></td>
					<td style="padding:8px;border:1px solid #ccc;"><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $a->data_criacao ) ) ); ?></td>
					<td style="padding:8px;border:1px solid #ccc;">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=tutor-ead-associar-atividade&atividade_id=' . $a->id ) ); ?>"><?php esc_html_e( 'Associar', 'tutor-ead' ); ?></a> | 
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=tutor-ead-edit-atividade-padrao&atividade_id=' . $a->id ) ); ?>"><?php esc_html_e( 'Editar', 'tutor-ead' ); ?></a> | 
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=tutor-ead-relatorio-atividade&atividade_id=' . $a->id ) ); ?>"><?php esc_html_e( 'Relatório', 'tutor-ead' ); ?></a> | 
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=tutor-ead-delete-atividade&atividade_id=' . $a->id ) ); ?>"
						   style="color:#a00;"
						   onclick="return confirm('<?php echo esc_js( __( 'Tem certeza que deseja excluir esta atividade?', 'tutor-ead' ) ); ?>');">
							<?php esc_html_e( 'Excluir', 'tutor-ead' ); ?>
						</a>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php else : ?>
	<p style="color:#777;"><?php esc_html_e( 'Nenhuma atividade presencial encontrada.', 'tutor-ead' ); ?></p>
<?php endif; ?>
