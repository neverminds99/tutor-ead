<?php
/**
 * Partial: Formulário de criação/edição de Boletim
 *
 * Espera:
 * $action   string  'create' | 'edit'
 * $courses  array   (lista de cursos)
 * $boletim  object|null  (registro existente – apenas para edição)
 */
defined( 'ABSPATH' ) || exit;

$is_edit = ( $action === 'edit' );
$values  = [
	'course_id'        => $is_edit ? $boletim->course_id        : 0,
	'course_title'     => $is_edit ? $boletim->course_title     : '',
	'atividade_id'     => $is_edit ? $boletim->atividade_id     : 0,
	'atividade_title'  => $is_edit ? $boletim->atividade_title  : '',
	'nota'             => $is_edit ? $boletim->nota             : '',
	'feedback'         => $is_edit ? $boletim->feedback         : '',
	'aluno_id'         => $is_edit ? $boletim->aluno_id         : 0,
];
?>
<form method="post"
	  action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
	  class="boletim-form">

	<?php
	if ( $is_edit ) {
		wp_nonce_field( 'edit_boletim_action', 'edit_boletim_nonce' );
		echo '<input type="hidden" name="boletim_id" value="' . esc_attr( $boletim->id ) . '">';
	} else {
		wp_nonce_field( 'create_boletim_action', 'create_boletim_nonce' );
	}
	?>
	<input type="hidden" name="action"
		   value="<?php echo $is_edit ? 'edit_boletim' : 'create_boletim'; ?>">

	<table class="form-table">
		<!-- Curso -->
		<tr>
			<th><label for="course_id"><?php _e( 'Curso', 'tutor-ead' ); ?></label></th>
			<td>
				<select name="course_id" id="course_select" required>
					<option value=""><?php _e( 'Selecione um Curso', 'tutor-ead' ); ?></option>
					<?php foreach ( $courses as $c ) : ?>
						<option value="<?php echo esc_attr( $c->id ); ?>"
							<?php selected( $values['course_id'], $c->id ); ?>>
							<?php echo esc_html( $c->title ); ?>
						</option>
					<?php endforeach; ?>
				</select>

				<input type="text" id="course_title" name="course_title"
					   value="<?php echo esc_attr( $values['course_title'] ); ?>" placeholder="<?php _e('Nome do Curso', 'tutor-ead'); ?>"
					   readonly required>
			</td>
		</tr>

		<!-- Atividade -->
		<tr>
			<th><label for="atividade_id"><?php _e( 'Atividade', 'tutor-ead' ); ?></label></th>
			<td>
				<select name="atividade_id" id="atividade_select" required
						data-selected="<?php echo esc_attr( $values['atividade_id'] ); ?>">
					<?php if ( $is_edit ) : ?>
						<option value="<?php echo esc_attr( $values['atividade_id'] ); ?>" selected>
							<?php echo esc_html( $values['atividade_title'] ); ?>
						</option>
					<?php else : ?>
						<option value=""><?php _e( 'Selecione uma Atividade', 'tutor-ead' ); ?></option>
					<?php endif; ?>
				</select>

				<input type="text" id="atividade_title" name="atividade_title"
					   value="<?php echo esc_attr( $values['atividade_title'] ); ?>" placeholder="<?php _e('Nome da Atividade', 'tutor-ead'); ?>"
					   readonly required>
			</td>
		</tr>

		<!-- Nota -->
		<tr>
			<th><label for="nota"><?php _e( 'Nota', 'tutor-ead' ); ?></label></th>
			<td>
				<input type="number" name="nota" step="0.1" required
					   value="<?php echo esc_attr( $values['nota'] ); ?>"
					   placeholder="0.0">
			</td>
		</tr>

		<!-- Feedback -->
		<tr>
			<th><label for="feedback"><?php _e( 'Feedback', 'tutor-ead' ); ?></label></th>
			<td>
				<textarea name="feedback" rows="4" placeholder="<?php _e('Digite seu feedback aqui...', 'tutor-ead'); ?>"><?php
					echo esc_textarea( $values['feedback'] ); ?></textarea>
			</td>
		</tr>

		<!-- Aluno -->
		<tr>
			<th><label for="aluno_id"><?php _e( 'Aluno (ID)', 'tutor-ead' ); ?></label></th>
			<td>
				<select name="aluno_id" id="aluno_select" required
						data-selected="<?php echo esc_attr( $values['aluno_id'] ); ?>">
					<?php if ( $is_edit ) : ?>
						<option value="<?php echo esc_attr( $values['aluno_id'] ); ?>" selected>
							ID <?php echo esc_html( $values['aluno_id'] ); ?>
						</option>
					<?php else : ?>
						<option value=""><?php _e( 'Selecione um Aluno', 'tutor-ead' ); ?></option>
					<?php endif; ?>
				</select>
			</td>
		</tr>
	</table>

	<?php
	submit_button(
		$is_edit ? __( 'Atualizar Boletim', 'tutor-ead' ) : __( 'Criar Boletim', 'tutor-ead' ),
		'primary',
		$is_edit ? 'edit_boletim' : 'create_boletim'
	);
	?>
</form>