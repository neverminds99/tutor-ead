<?php
defined('ABSPATH') || exit;

$highlight_color = get_option('tutor_ead_highlight_color', '#0073aa');
$perguntas_json = wp_json_encode($perguntas);
?>
<style>
    .quiz-builder-wrap { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; background: #f3f4f6; margin: -20px; padding: 32px; min-height: 100vh; }
    .quiz-builder-wrap * { box-sizing: border-box; }
    .quiz-builder-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
    .quiz-builder-title { font-size: 28px; font-weight: 600; color: #1f2937; margin: 0; }
    .btn { display: inline-flex; align-items: center; gap: 8px; text-decoration: none; font-size: 14px; font-weight: 600; padding: 10px 20px; border-radius: 8px; cursor: pointer; transition: all 0.2s ease; border: 1px solid transparent; }
    .btn-primary { background: <?php echo $highlight_color; ?>; color: #fff; }
    .btn-primary:hover { background: #005a87; }
    .btn-secondary { background: #fff; color: #334155; border-color: #cbd5e1; }
    .btn-secondary:hover { background: #f8fafc; }
    .btn-danger { background: #dc2626; color: #fff; }
    .btn-danger:hover { background: #b91c1c; }
    .quiz-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; margin-bottom: 24px; }
    .quiz-card-header { padding: 20px; border-bottom: 1px solid #e5e7eb; }
    .quiz-card-body { padding: 20px; }
    .form-group { margin-bottom: 20px; }
    .form-group label { display: block; font-weight: 500; margin-bottom: 8px; color: #374151; }
    .form-group input[type="text"], .form-group input[type="number"], .form-group textarea { width: 100%; padding: 10px; border: 1px solid #d1d5db; border-radius: 6px; }
    #questions-container .question-card { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px; margin-bottom: 20px; }
    .question-header { display: flex; justify-content: space-between; align-items: center; padding: 16px; border-bottom: 1px solid #e5e7eb; }
    .question-title { font-size: 16px; font-weight: 600; margin: 0; }
    .question-body { padding: 16px; }
    .alternatives-list { margin-top: 16px; }
    .alternative-item { display: flex; align-items: center; gap: 12px; margin-bottom: 12px; }
    .alternative-item input[type="text"] { flex-grow: 1; }
</style>

<div class="wrap quiz-builder-wrap">
    <form method="POST" id="quiz-builder-form">
        <div class="quiz-builder-header">
            <h1 class="quiz-builder-title"><?php esc_html_e('Editor de Quiz', 'tutor-ead'); ?></h1>
            <div>
                <input type="submit" class="btn btn-primary" value="<?php esc_attr_e('Salvar Alterações', 'tutor-ead'); ?>">
            </div>
        </div>

        <!-- Campos Principais da Atividade -->
        <div class="quiz-card">
            <div class="quiz-card-header">
                <h2><?php esc_html_e('Detalhes da Atividade', 'tutor-ead'); ?></h2>
            </div>
            <div class="quiz-card-body">
                <input type="hidden" name="activity_id" value="<?php echo esc_attr($atividade->id); ?>">
                <div class="form-group">
                    <label for="titulo"><?php esc_html_e('Título do Quiz', 'tutor-ead'); ?></label>
                    <input type="text" id="titulo" name="titulo" value="<?php echo esc_attr($atividade->titulo); ?>" required>
                </div>
                <div class="form-group">
                    <label for="descricao"><?php esc_html_e('Descrição', 'tutor-ead'); ?></label>
                    <textarea id="descricao" name="descricao" rows="4"><?php echo esc_textarea($atividade->descricao); ?></textarea>
                </div>
                 <div class="form-group">
                    <label for="nota_maxima"><?php esc_html_e('Nota Máxima', 'tutor-ead'); ?></label>
                    <input type="number" id="nota_maxima" name="nota_maxima" value="<?php echo esc_attr($atividade->nota_maxima); ?>" step="1" min="0">
                </div>
            </div>
        </div>

        <!-- Associação de Curso -->
        <div class="postbox quiz-card">
            <h2 class="hndle quiz-card-header"><span><?php esc_html_e('Associação de Curso', 'tutor-ead'); ?></span></h2>
            <div class="inside quiz-card-body">
                <p><?php esc_html_e('Selecione abaixo os locais onde esta atividade deve aparecer. Você poderá definir a posição exata (módulo, aula de referência) após associar ao curso.', 'tutor-ead'); ?></p>
                <table class="form-table">
                    <tbody>
                        <?php if ( ! empty( $all_courses ) ) : ?>
                            <?php foreach ( $all_courses as $course ) : ?>
                                <?php
                                // Verifica se a associação já existe para este curso
                                $is_associated = isset( $current_associations[ $course->id ] );
                                ?>
                                <tr>
                                    <th scope="row">
                                        <label for="course_assoc_<?php echo esc_attr( $course->id ); ?>">
                                            <?php echo esc_html( $course->title ); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <input type="checkbox" name="associated_courses[]" id="course_assoc_<?php echo esc_attr( $course->id ); ?>" value="<?php echo esc_attr( $course->id ); ?>" <?php checked( $is_associated ); ?>>
                                        <?php if ($is_associated): ?>
                                            <span style="color: #2271b1; font-style: italic; margin-left: 10px;">
                                                <?php esc_html_e('Já associado.', 'tutor-ead'); ?>
                                                <a href="<?php echo esc_url(admin_url('admin.php?page=tutor-ead-associar-atividade&activity_id=' . $atividade->id . '&course_id=' . $course->id)); ?>" style="margin-left: 15px;">
                                                    <?php esc_html_e('Editar Posição', 'tutor-ead'); ?>
                                                </a>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else : ?>
                            <tr>
                                <td colspan="2"><?php esc_html_e('Nenhum curso encontrado para associação.', 'tutor-ead'); ?></td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Container das Perguntas -->
        <div class="quiz-card">
            <div class="quiz-card-header">
                <h2><?php esc_html_e('Perguntas do Quiz', 'tutor-ead'); ?></h2>
            </div>
            <div class="quiz-card-body">
                <div id="questions-container">
                    <!-- Perguntas existentes serão carregadas aqui -->
                </div>
                <button type="button" id="add-question" class="btn btn-secondary">
                    <span class="dashicons dashicons-plus-alt"></span>
                    <?php esc_html_e('Adicionar Pergunta', 'tutor-ead'); ?>
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Template para uma nova pergunta (escondido) -->
<template id="question-template">
    <div class="question-card">
        <div class="question-header">
            <h3 class="question-title"><?php esc_html_e('Nova Pergunta', 'tutor-ead'); ?></h3>
            <button type="button" class="btn-danger remove-question">&times;</button>
        </div>
        <div class="question-body">
            <div class="form-group">
                <label for="question_title__{{q_index}}"><?php esc_html_e('Enunciado da Pergunta', 'tutor-ead'); ?></label>
                <input type="text" name="perguntas[{{q_index}}][titulo]" id="question_title__{{q_index}}" required>
            </div>
            <div class="alternatives-list">
                <!-- Alternativas serão adicionadas aqui -->
            </div>
            <button type="button" class="btn btn-secondary add-alternative">
                <span class="dashicons dashicons-plus-alt"></span>
                <?php esc_html_e('Adicionar Alternativa', 'tutor-ead'); ?>
            </button>
        </div>
    </div>
</template>

<!-- Template para uma nova alternativa (escondido) -->
<template id="alternative-template">
    <div class="alternative-item">
        <input type="radio" name="perguntas[{{q_index}}][correta]" value="{{a_index}}" required>
        <input type="text" name="perguntas[{{q_index}}][alternativas][{{a_index}}][texto]" placeholder="<?php esc_attr_e('Texto da alternativa', 'tutor-ead'); ?>" required>
        <button type="button" class="btn-danger remove-alternative">&times;</button>
    </div>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const questionsContainer = document.getElementById('questions-container');
    const addQuestionBtn = document.getElementById('add-question');
    const questionTemplate = document.getElementById('question-template');
    const alternativeTemplate = document.getElementById('alternative-template');
    
    let questionCounter = 0;
    const existingPerguntas = <?php echo $perguntas_json; ?>;

    function addAlternative(alternativesList, qIndex, alternative = null) {
        const aIndex = alternativesList.children.length;
        const newAlternativeHtml = alternativeTemplate.innerHTML
            .replace(/{{q_index}}/g, qIndex)
            .replace(/{{a_index}}/g, aIndex);
        
        const alternativeNode = document.createElement('div');
        alternativeNode.innerHTML = newAlternativeHtml;

        if (alternative) {
            alternativeNode.querySelector('input[type="text"]').value = alternative.texto;
            if (alternative.correta == 1) {
                alternativeNode.querySelector('input[type="radio"]').checked = true;
            }
        }

        alternativesList.appendChild(alternativeNode.firstElementChild);
    }

    function addQuestion(question = null) {
        const qIndex = questionCounter++;
        const newQuestionHtml = questionTemplate.innerHTML.replace(/{{q_index}}/g, qIndex);
        const questionNode = document.createElement('div');
        questionNode.innerHTML = newQuestionHtml;

        const alternativesList = questionNode.querySelector('.alternatives-list');

        if (question) {
            questionNode.querySelector('input[name*="[titulo]"]').value = question.titulo;
            if (question.alternativas && question.alternativas.length) {
                question.alternativas.forEach(alt => addAlternative(alternativesList, qIndex, alt));
            }
        } else {
            // Adiciona 2 alternativas por padrão para novas perguntas
            addAlternative(alternativesList, qIndex);
            addAlternative(alternativesList, qIndex);
        }

        questionsContainer.appendChild(questionNode.firstElementChild);
    }

    // Carregar perguntas existentes
    if (existingPerguntas && existingPerguntas.length > 0) {
        existingPerguntas.forEach(p => addQuestion(p));
    } else {
        // Inicializar com uma pergunta vazia se não houver nenhuma
        addQuestion();
    }

    // Event Listeners
    addQuestionBtn.addEventListener('click', () => addQuestion());

    questionsContainer.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-alternative')) {
            const alternativesList = e.target.previousElementSibling;
            const qIndex = e.target.closest('.question-card').querySelector('input[name*="[titulo]"]').name.match(/\[(\d+)\]/)[1];
            addAlternative(alternativesList, qIndex);
        }
        if (e.target.classList.contains('remove-question')) {
            e.target.closest('.question-card').remove();
        }
        if (e.target.classList.contains('remove-alternative')) {
            e.target.closest('.alternative-item').remove();
        }
    });
});
</script>