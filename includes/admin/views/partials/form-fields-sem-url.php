<?php
defined('ABSPATH') || exit;
/* $atividade (opcional) */
?>
<h2 style="color:#555;margin-bottom:10px;"><?php esc_html_e( 'Informações da Atividade', 'tutor-ead' ); ?></h2>

<table class="form-table" style="width:100%;">
	<tr>
		<th style="text-align:left;padding:8px;"><?php esc_html_e( 'Título', 'tutor-ead' ); ?></th>
		<td style="padding:8px;"><input type="text" name="titulo" required
			   value="<?php echo isset( $atividade ) ? esc_attr( $atividade->titulo ) : ''; ?>"
			   style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;"></td>
	</tr>

	<tr>
		<th style="text-align:left;padding:8px;"><?php esc_html_e( 'Descrição', 'tutor-ead' ); ?></th>
		<td style="padding:8px;"><textarea name="descricao" rows="5"
			   style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;"><?php
			   echo isset( $atividade ) ? esc_textarea( $atividade->descricao ) : ''; ?></textarea></td>
	</tr>

	<tr>
		<th style="text-align:left;padding:8px;"><?php esc_html_e( 'Dias para Visualização', 'tutor-ead' ); ?></th>
		<td style="padding:8px;"><input type="number" name="dias_visualizacao" required
			   value="<?php echo isset( $atividade ) ? esc_attr( $atividade->dias_visualizacao ) : '0'; ?>"
			   style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;"></td>
	</tr>
</table>
