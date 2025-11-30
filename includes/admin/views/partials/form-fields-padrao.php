<?php
defined('ABSPATH') || exit;
/* variáveis predefinidas: $atividade (opcional) */
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
		<td style="padding:8px;"><textarea name="descricao" rows="5" required
			   style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;"><?php
			   echo isset( $atividade ) ? esc_textarea( $atividade->descricao ) : ''; ?></textarea></td>
	</tr>

	<tr>
		<th style="text-align:left;padding:8px;"><?php esc_html_e( 'Nota Máxima', 'tutor-ead' ); ?></th>
		<td style="padding:8px;"><input type="number" step="0.1" name="nota_maxima" required
			   value="<?php echo isset( $atividade ) ? esc_attr( $atividade->nota_maxima ) : ''; ?>"
			   style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;"></td>
	</tr>

	<tr>
		<th style="text-align:left;padding:8px;"><?php esc_html_e( 'Número de Tentativas', 'tutor-ead' ); ?></th>
		<td style="padding:8px;"><input type="number" name="num_tentativas" required
			   value="<?php echo isset( $atividade ) ? esc_attr( $atividade->num_tentativas ) : ''; ?>"
			   style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;"></td>
	</tr>

	<tr>
		<th style="text-align:left;padding:8px;"><?php esc_html_e( 'Usar Valores Individuais por Pergunta', 'tutor-ead' ); ?></th>
		<td style="padding:8px;"><input type="checkbox" name="usar_valores_individuais" value="1"
			   <?php echo isset( $atividade ) && $atividade->usar_valores_individuais ? 'checked' : ''; ?>
			   style="transform:scale(1.2);"></td>
	</tr>

	<tr>
		<th style="text-align:left;padding:8px;"><?php esc_html_e( 'Dias para Visualização', 'tutor-ead' ); ?></th>
		<td style="padding:8px;"><input type="number" name="dias_visualizacao" required
			   value="<?php echo isset( $atividade ) ? esc_attr( $atividade->dias_visualizacao ) : '0'; ?>"
			   style="width:100%;padding:10px;border:1px solid #ccc;border-radius:4px;"></td>
	</tr>
</table>
