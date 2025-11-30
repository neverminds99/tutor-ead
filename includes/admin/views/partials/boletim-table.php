<?php
/**
 * Partial: Tabela de boletins
 *
 * Variáveis esperadas (scope da view‑pai):
 * --------------------------------------------------------------
 * $rows         array  Lista de registros retornados do banco
 * $wpdb         objeto  De WordPress, para obter nomes de usuários (opcional)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<table class="wp-list-table widefat striped boletim-table">
    <thead>
        <tr>
            <th><?php _e( 'ID', 'tutor-ead' ); ?></th>
            <th><?php _e( 'Aluno', 'tutor-ead' ); ?></th>
            <th><?php _e( 'Curso', 'tutor-ead' ); ?></th>
            <th><?php _e( 'Atividade', 'tutor-ead' ); ?></th>
            <th><?php _e( 'Nota', 'tutor-ead' ); ?></th>
            <th><?php _e( 'Feedback', 'tutor-ead' ); ?></th>
            <th><?php _e( 'Data', 'tutor-ead' ); ?></th>
            <th><?php _e( 'Ações', 'tutor-ead' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php if ( $rows ) : foreach ( $rows as $r ) : ?>
            <tr>
                <td><?php echo esc_html( $r->id ); ?></td>
                <td>
                    <?php echo esc_html( get_userdata( $r->aluno_id )->display_name ?? '—' ); ?>
                    (ID <?php echo esc_html( $r->aluno_id ); ?>)
                </td>
                <td><?php echo esc_html( $r->course_title ); ?></td>
                <td><?php echo esc_html( $r->atividade_title ); ?></td>
                <td><?php echo esc_html( $r->nota ); ?></td>
                <td><?php echo esc_html( $r->feedback ); ?></td>
                <td><?php echo esc_html( $r->data_atualizacao ); ?></td>
                <td>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=tutor-ead-edit-boletim&id=' . $r->id ) ); ?>">
                        <?php _e( 'Editar', 'tutor-ead' ); ?>
                    </a>
                </td>
            </tr>
        <?php endforeach; else : ?>
            <tr><td colspan="8"><?php _e( 'Nenhum registro encontrado.', 'tutor-ead' ); ?></td></tr>
        <?php endif; ?>
    </tbody>
</table>
