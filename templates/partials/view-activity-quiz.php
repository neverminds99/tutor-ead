<?php
/**
 * Partial: Exibição de Atividade/Prova Interna (Layout Moderno com Revisão de Prova).
 */
defined('ABSPATH') || exit;

global $wpdb;

// Todas as variáveis necessárias já foram carregadas e preparadas no template-curso.php:
// $activity, $resume_lesson_id, $user_submission, $can_retake, $correct_answers

$questions = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}perguntas WHERE atividade_id = %d ORDER BY id ASC", $activity['id']), ARRAY_A );
$highlight_color = get_option('tutor_ead_highlight_color', '#0073aa');
$resume_url = $resume_lesson_id ? add_query_arg(['course_id' => $course_id, 'lesson_id' => $resume_lesson_id], get_permalink()) : get_permalink();

$is_review_mode = ($user_submission && !$can_retake);
$user_answers = $user_submission ? json_decode($user_submission['respostas'], true) : [];

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html( $activity['titulo'] ); ?> - <?php echo $is_review_mode ? 'Revisão' : 'Prova'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --main-color: <?php echo esc_attr( $highlight_color ); ?>;
            --border-color: #e5e7eb;
            --text-color: #374151;
            --light-text-color: #6b7280;
            --bg-color: #f9fafb;
            --white-color: #ffffff;
            --success-color: #22c55e; --success-bg: #f0fdf4; --success-border: #bbf7d0;
            --error-color: #ef4444; --error-bg: #fef2f2; --error-border: #fecaca;
        }
        body { font-family: 'Roboto', sans-serif; margin: 0; padding: 20px; background-color: var(--bg-color); color: var(--text-color); }
        .quiz-container { max-width: 800px; margin: 20px auto; background: var(--white-color); border: 1px solid var(--border-color); border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .quiz-header { padding: 24px 32px; border-bottom: 1px solid var(--border-color); }
        .quiz-header h1 { font-size: 28px; font-weight: 700; color: var(--main-color); margin: 0 0 8px 0; }
        .quiz-description { font-size: 16px; color: var(--light-text-color); margin: 0; }
        
        .activity-details { display: flex; gap: 20px; padding: 20px 32px; background: #fdfdff; border-bottom: 1px solid var(--border-color); }
        .detail-item { display: flex; flex-direction: column; gap: 4px; }
        .detail-item .label { font-size: 12px; font-weight: 500; color: var(--light-text-color); text-transform: uppercase; }
        .detail-item .value { font-size: 16px; font-weight: 500; color: var(--text-color); }

        .quiz-body { padding: 16px 32px; }
        .question-block { margin-bottom: 32px; padding-bottom: 32px; border-bottom: 1px solid var(--border-color); }
        .question-block:last-child { margin-bottom: 0; padding-bottom: 0; border-bottom: none; }
        .question-title { font-size: 18px; font-weight: 700; margin: 0 0 16px 0; }
        
        .alternatives-grid { display: flex; flex-direction: column; gap: 12px; }
        .alternative-label { position: relative; display: block; border: 2px solid var(--border-color); border-radius: 8px; padding: 16px; transition: all 0.2s ease-in-out; }
        .alternative-input { display: none; }
        .alternative-label:not(.review-mode) { cursor: pointer; }
        .alternative-label:not(.review-mode):hover { border-color: var(--main-color); background: #f5f8ff; }
        .alternative-input:checked + .alternative-label { border-color: var(--main-color); background-color: #eef4ff; color: var(--main-color); font-weight: 700; }

        /* Estilos de Revisão */
        .review-mode.is-correct { border-color: var(--success-color); background-color: var(--success-bg); }
        .review-mode.is-incorrect { border-color: var(--error-color); background-color: var(--error-bg); }
        .review-mode.is-correct.user-selected { box-shadow: 0 0 15px rgba(34, 197, 94, 0.3); }
        .review-mode.is-incorrect.user-selected { box-shadow: 0 0 15px rgba(239, 68, 68, 0.3); }
        .review-mode::after { content: ''; position: absolute; right: 16px; top: 50%; transform: translateY(-50%); font-size: 24px; font-weight: bold; }
        .review-mode.is-correct::after { content: '✓'; color: var(--success-color); }
        .review-mode.is-incorrect.user-selected::after { content: '✗'; color: var(--error-color); }

        .quiz-footer { padding: 24px 32px; border-top: 1px solid var(--border-color); display: flex; justify-content: space-between; align-items: center; background: #f9fafb; }
        .submit-btn { padding: 12px 24px; background: var(--main-color); color: var(--white-color); border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 500; }
        .submit-btn:disabled { background-color: #9ca3af; cursor: not-allowed; }
        .back-btn { color: var(--light-text-color); text-decoration: none; font-weight: 500; }

        .success-modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0, 0, 0, 0.6); display: none; align-items: center; justify-content: center; z-index: 1000; }
        .success-modal-box { background: var(--white-color); padding: 40px; border-radius: 12px; text-align: center; }
    </style>
</head>
<body>
  <div class="quiz-container">
    <header class="quiz-header">
      <h1><?php echo esc_html( $activity['titulo'] ); ?></h1>
      <?php if ( ! empty( $activity['descricao'] ) ) : ?>
        <p class="quiz-description"><?php echo nl2br(esc_html( $activity['descricao'] )); ?></p>
      <?php endif; ?>
    </header>

    <section class="activity-details">
        <div class="detail-item"><span class="label">Nota Máxima</span><span class="value"><?php echo esc_html( $activity['nota_maxima'] ?? 'N/A' ); ?></span></div>
        <div class="detail-item"><span class="label">Tentativas</span><span class="value"><?php echo esc_html( $activity['num_tentativas'] ?? 'Ilimitadas' ); ?></span></div>
        <div class="detail-item"><span class="label">Disponível por</span><span class="value"><?php echo esc_html( $activity['dias_visualizacao'] ?? 'Sempre' ); ?> dias</span></div>
    </section>

    <main class="quiz-body">
        <form id="quiz-form">
            <?php foreach ( $questions as $q ) : 
                $alternatives = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}alternativas WHERE pergunta_id = %d ORDER BY id ASC", $q['id']), ARRAY_A );
            ?>
            <div class="question-block">
                <h3 class="question-title"><?php echo esc_html( $q['titulo'] ); ?></h3>
                <div class="alternatives-grid">
                    <?php foreach ( $alternatives as $alt ) : 
                        $is_checked = (isset($user_answers[$q['id']]) && $user_answers[$q['id']] == $alt['id']);
                        $label_classes = [];
                        if ($is_review_mode) {
                            $label_classes[] = 'review-mode';
                            if (isset($correct_answers[$q['id']]) && $correct_answers[$q['id']] == $alt['id']) {
                                $label_classes[] = 'is-correct';
                            }
                            if ($is_checked) {
                                $label_classes[] = 'user-selected';
                                if (!isset($correct_answers[$q['id']]) || $correct_answers[$q['id']] != $alt['id']) {
                                    $label_classes[] = 'is-incorrect';
                                }
                            }
                        }
                    ?>
                    <div>
                        <input class="alternative-input" type="radio" name="answers[<?php echo esc_attr( $q['id'] ); ?>]" value="<?php echo esc_attr( $alt['id'] ); ?>" id="alt_<?php echo esc_attr( $alt['id'] ); ?>" <?php checked($is_checked); ?> <?php if($is_review_mode) echo 'disabled'; ?> required>
                        <label class="alternative-label <?php echo implode(' ', $label_classes); ?>" for="alt_<?php echo esc_attr( $alt['id'] ); ?>">
                            <?php echo esc_html( $alt['texto'] ); ?>
                        </label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            
            <footer class="quiz-footer">
                <a class="back-btn" href="<?php echo esc_url($resume_url); ?>">Voltar ao Curso</a>
                <?php if (!$is_review_mode) : ?>
                    <input type="hidden" name="activity_id" value="<?php echo esc_attr($activity['id']); ?>">
                    <button class="submit-btn" type="button" id="submit-quiz-btn">Enviar Respostas</button>
                <?php endif; ?>
            </footer>
        </form>
    </main>
  </div>

  <div id="success-modal" class="success-modal-overlay">
    <div class="success-modal-box"><h2>Enviado com Sucesso!</h2><p>Suas respostas foram salvas. Redirecionando...</p></div>
  </div>

  <?php if (!$is_review_mode) : ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('quiz-form');
        const submitBtn = document.getElementById('submit-quiz-btn');
        const successModal = document.getElementById('success-modal');

        if (!submitBtn) return;

        submitBtn.addEventListener('click', function() {
            const totalQuestions = <?php echo count($questions); ?>;
            const formData = new FormData(form);
            let answeredCount = 0;
            for (let pair of formData.entries()) {
                if (pair[0].startsWith('answers[')) { answeredCount++; }
            }

            if (answeredCount < totalQuestions) {
                alert('Por favor, responda todas as perguntas antes de enviar.');
                return;
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Enviando...';

            formData.append('action', 'tutoread_submit_quiz');
            formData.append('nonce', '<?php echo wp_create_nonce("quiz_submission_nonce"); ?>');

            fetch('<?php echo esc_url(admin_url("admin-ajax.php")); ?>', {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    successModal.style.display = 'flex';
                    setTimeout(() => { window.location.href = '<?php echo esc_url_raw($resume_url); ?>'; }, 2000);
                } else {
                    alert('Erro: ' + data.data.message);
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Enviar Respostas';
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Ocorreu um erro de comunicação.');
                submitBtn.disabled = false;
                submitBtn.textContent = 'Enviar Respostas';
            });
        });
    });
  </script>
  <?php endif; ?>
</body>
</html>