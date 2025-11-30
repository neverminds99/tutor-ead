<?php
/**
 * Partial: Filtros da listagem de boletins
 *
 * Reaproveitável em todas as telas que precisarem filtrar boletins.
 *
 * Variáveis esperadas (via `compact()` no controller ou view-pai):
 * ----
 * $filters    array  — valores atuais (course_id, atividade_id, aluno_id, date_from, date_to)
 * $courses    array  — lista de cursos  (obj->id, obj->title)
 * $atividades  array  — lista de atividades (obj->id, obj->titulo)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<form method="get" class="boletim-filtros">
    <input type="hidden" name="page" value="tutor-ead-boletim" />

    <!-- Curso ---- -->
    <div class="filter-field filter-field-large">
        <label><?php _e( 'Curso', 'tutor-ead' ); ?></label>
        <select name="course_id">
            <option value="0">— <?php _e( 'Todos', 'tutor-ead' ); ?> —</option>
            <?php foreach ( $courses as $c ) : ?>
                <option value="<?php echo esc_attr( $c->id ); ?>" <?php selected( $filters['course_id'], $c->id ); ?>>
                    <?php echo esc_html( $c->title ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Atividade ---- -->
    <div class="filter-field filter-field-large">
        <label><?php _e( 'Atividade', 'tutor-ead' ); ?></label>
        <select name="atividade_id">
            <option value="0">— <?php _e( 'Todas', 'tutor-ead' ); ?> —</option>
            <?php foreach ( $atividades as $a ) : ?>
                <option value="<?php echo esc_attr( $a->id ); ?>" <?php selected( $filters['atividade_id'], $a->id ); ?>>
                    <?php echo esc_html( $a->titulo ); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Campos menores em uma linha ---- -->
    <div class="filter-row">
        <!-- Aluno ID ---- -->
        <div class="filter-field filter-field-small">
            <label><?php _e( 'Aluno (ID)', 'tutor-ead' ); ?></label>
            <input type="number" name="aluno_id" value="<?php echo esc_attr( $filters['aluno_id'] ); ?>" placeholder="<?php _e('ID do Aluno', 'tutor-ead'); ?>" />
        </div>

        <!-- Período ---- -->
        <div class="filter-field filter-field-small">
            <label><?php _e( 'Data Início', 'tutor-ead' ); ?></label>
            <input type="date" name="date_from" value="<?php echo esc_attr( $filters['date_from'] ); ?>" placeholder="<?php _e('AAAA-MM-DD', 'tutor-ead'); ?>" />
        </div>

        <div class="filter-field filter-field-small">
            <label><?php _e( 'Data Fim', 'tutor-ead' ); ?></label>
            <input type="date" name="date_to" value="<?php echo esc_attr( $filters['date_to'] ); ?>" placeholder="<?php _e('AAAA-MM-DD', 'tutor-ead'); ?>" />
        </div>
    </div>

    <div class="filter-field filter-field-button">
        <button class="button">
            <span class="dashicons dashicons-filter"></span>
            <?php _e( 'Filtrar', 'tutor-ead' ); ?>
        </button>
    </div>
</form>