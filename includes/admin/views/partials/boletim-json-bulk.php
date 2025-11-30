<?php
/**
 * Partial: Inserção em massa via JSON
 *
 * ▸ Exibe botão de exemplo, lista de IDs (cursos/atividades) e textarea
 *   para colar o JSON.
 * ▸ O JavaScript que faz a pré‑visualização e a inserção definitiva foi
 *   movido para `assets/js/boletim.js` para evitar script inline.
 *
 * Variáveis opcionais (para futura expansão):
 * $courses_ids   — lista de cursos (id, title)
 * $atividades_ids — lista de atividades (id, titulo)
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }
?>
<hr />
<h2>
    <?php _e( 'Inserir Notas em Massa via JSON', 'tutor-ead' ); ?>
    <a href="#" id="json_example_btn" class="dashicons dashicons-editor-help" title="<?php esc_attr_e( 'Exibir exemplo de JSON', 'tutor-ead' ); ?>"></a>
</h2>

<!-- Exemplo oculto --------------------------------------------------------- -->
<div id="json_example" style="display:none;" class="notice-info">
    <p><strong><?php _e( 'Exemplo de JSON:', 'tutor-ead' ); ?></strong></p>
    <pre><code class="language-json">{
  "notas": [
    {
      "email": "aluno1@exemplo.com",
      "course_id": 1,
      "atividade_id": 10,
      "atividade_title": "Exercício 1",
      "nota": 8.5,
      "feedback": "Bom desempenho"
    }
  ]
}</code></pre>
    <p><?php _e( 'Cole um array de notas com e‑mail, IDs e nota. Depois clique em “Pré‑visualizar”.', 'tutor-ead' ); ?></p>
</div>

<!-- IDs úteis ------------------------------------------------------------- -->
<a href="#" id="toggle_ids"><?php _e( 'Ver IDs de Cursos e Atividades', 'tutor-ead' ); ?></a>
<div id="ids_list" style="display:none;">
    <?php if ( ! empty( $courses_ids ) ) : ?>
        <h3><?php _e( 'Cursos', 'tutor-ead' ); ?></h3>
        <table class="widefat striped">
            <?php foreach ( $courses_ids as $c ) : ?>
                <tr><td><?php echo esc_html( $c->id ); ?></td><td><?php echo esc_html( $c->title ); ?></td></tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>

    <?php if ( ! empty( $atividades_ids ) ) : ?>
        <h3><?php _e( 'Atividades', 'tutor-ead' ); ?></h3>
        <table class="widefat striped">
            <?php foreach ( $atividades_ids as $a ) : ?>
                <tr><td><?php echo esc_html( $a->id ); ?></td><td><?php echo esc_html( $a->titulo ); ?></td></tr>
            <?php endforeach; ?>
        </table>
    <?php endif; ?>
</div>

<!-- Área de colagem -------------------------------------------------------- -->
<div id="json_bulk_section" class="card">
    <label for="json_input"><strong><?php _e( 'Cole o JSON aqui:', 'tutor-ead' ); ?></strong></label>
    <textarea id="json_input" rows="8" style="width:100%;"></textarea>

    <p class="actions">
        <button id="preview_json" class="button"><?php _e( 'Pré‑visualizar', 'tutor-ead' ); ?></button>
        <button id="continue_insertion" class="button button-primary" disabled><?php _e( 'Continuar com a inserção', 'tutor-ead' ); ?></button>
        <button id="try_again" class="button" style="display:none;"><?php _e( 'Tentar novamente', 'tutor-ead' ); ?></button>
    </p>

    <div id="json_preview"></div>
</div>
