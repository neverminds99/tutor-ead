<?php
/**
 * Partial: Exibição da tela da aula (player e playlist).
 * VERSÃO COMPLETA E CORRIGIDA
 */
defined('ABSPATH') || exit;

// --- VERIFICAÇÃO DE TELEFONE ---
$user_phone_raw = $wpdb->get_var($wpdb->prepare(
    "SELECT phone_number FROM {$wpdb->prefix}tutoread_user_info WHERE user_id = %d", 
    $user_id
));

// Limpa o número de telefone, removendo tudo que não for dígito
$cleaned_phone = preg_replace('/\D/', '', $user_phone_raw ?? '');

// Valida se o número limpo tem 10 ou 11 dígitos (padrão brasileiro com ou sem o 9º dígito)
$is_phone_valid = (strlen($cleaned_phone) === 10 || strlen($cleaned_phone) === 11);

// O modal só deve aparecer se o telefone NÃO for válido
$show_phone_modal = !$is_phone_valid;

// Este partial espera que as seguintes variáveis já estejam definidas pelo template-curso.php:
// $lesson, $course, $user_id, $course_id, $modules, $dias_passados

global $wpdb;

// --- LÓGICA DE DADOS ESPECÍFICA PARA ESTA VISÃO ---

// Pega a "unidade" ou "módulo" da aula atual
$current_item = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}tutoread_modules WHERE id = %d", $lesson['module_id']), ARRAY_A );

$playlist_items = [];
$module_title = '';

if ($current_item) {
    if ($current_item['parent_id'] > 0) { // A aula está dentro de uma unidade
        // Pega o módulo pai para o título
        $parent_module = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}tutoread_modules WHERE id = %d", $current_item['parent_id']), ARRAY_A );
        $module_title = $parent_module ? $parent_module['title'] : '';
        // As aulas da playlist são as da unidade
        $playlist_items = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}tutoread_lessons WHERE module_id = %d ORDER BY lesson_order ASC", $current_item['id']), ARRAY_A );
    } else { // A aula está diretamente em um módulo
        $module_title = $current_item['title'];
        // As aulas da playlist são as do módulo (que não pertencem a nenhuma unidade)
        $playlist_items = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}tutoread_lessons WHERE module_id = %d ORDER BY lesson_order ASC", $current_item['id']), ARRAY_A );
    }
}

// Pega o status de TODAS as aulas do módulo de uma vez só para otimização
$lesson_ids_in_module = wp_list_pluck($playlist_items, 'id');
$statuses = [];
if (!empty($lesson_ids_in_module)) {
    // Usamos OBJECT_K para criar um array associativo com o ID da aula como chave
    $status_results = $wpdb->get_results( $wpdb->prepare(
        "SELECT aula_id, status FROM {$wpdb->prefix}progresso_aulas WHERE aluno_id = %d AND aula_id IN (" . implode(',', array_fill(0, count($lesson_ids_in_module), '%d')) . ")",
        array_merge([$user_id], $lesson_ids_in_module)
    ), OBJECT_K );
    if ($status_results) {
        foreach ($status_results as $aula_id => $data) {
            $statuses[$aula_id] = $data->status;
        }
    }
}

// Verifica se a aula ATUAL está concluída (para o botão principal)
$lesson_concluido = (isset($statuses[$lesson['id']]) && $statuses[$lesson['id']] === 'concluido');

// Cálculo do progresso do curso
$total_aulas = $wpdb->get_var( $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}tutoread_lessons l INNER JOIN {$wpdb->prefix}tutoread_modules m ON l.module_id = m.id WHERE m.course_id = %d", $course_id) );
$aulas_concluidas = count(array_filter($statuses, function($s) { return $s === 'concluido'; }));
$progresso_percent = ( $total_aulas > 0 ) ? ( $aulas_concluidas / $total_aulas ) * 100 : 0;

// Busca os comentários para a aula atual
$comments = $wpdb->get_results( $wpdb->prepare("SELECT c.*, u.display_name FROM {$wpdb->prefix}tutoread_comments c INNER JOIN {$wpdb->prefix}users u ON c.user_id = u.ID WHERE c.lesson_id = %d ORDER BY c.created_at DESC", $lesson['id'] ) );
$comments_mode = get_option('tutor_ead_comments_mode');
$comments_disabled = ($comments_mode === 'restricted');
$comments_placeholder = $comments_disabled ? __('Os comentários estão desativados no momento!', 'tutor-ead') : __('Adicione um comentário...', 'tutor-ead');


// Busca as atividades associadas ao módulo da aula atual
$activities_assoc = $wpdb->get_results( $wpdb->prepare("SELECT ca.*, a.titulo AS activity_title, a.is_externa, a.link_externo, a.dias_visualizacao FROM {$wpdb->prefix}tutoread_course_activities ca INNER JOIN {$wpdb->prefix}atividades a ON ca.activity_id = a.id WHERE ca.course_id = %d AND ca.module_id = %d", $course_id, $lesson['module_id']), ARRAY_A );
$assoc_grouped = [];
if ($activities_assoc) {
    foreach ($activities_assoc as $assoc) {
        $lesson_ref_id = empty($assoc['lesson_id']) ? 0 : intval($assoc['lesson_id']);
        $position = $assoc['position'] ?? 'depois';
        $assoc_grouped[$lesson_ref_id][$position][] = $assoc;
    }
}

// --- LÓGICA PARA IDENTIFICAR ATIVIDADES RESPONDIDAS ---
$answered_activity_ids = [];
if (!empty($activities_assoc)) {
    // 1. Coleta todos os IDs de atividades únicos deste módulo
    $activity_ids_in_module = array_unique(wp_list_pluck($activities_assoc, 'activity_id'));

    // 2. Busca na tabela de respostas quais desses IDs o aluno já respondeu
    if (!empty($activity_ids_in_module)) {
        // A tabela de respostas no seu sistema é {$wpdb->prefix}respostas
        $answered_activity_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT atividade_id FROM {$wpdb->prefix}respostas WHERE aluno_id = %d AND atividade_id IN (" . implode(',', array_fill(0, count($activity_ids_in_module), '%d')) . ")",
            array_merge([$user_id], $activity_ids_in_module)
        ));
    }
}
?>

<main>
    <div class="video-container">
        <div class="video-player" id="player">
            <?php
            if ( !empty( $lesson['video_url'] ) ) {
                $url = esc_url( $lesson['video_url'] );
                if ( preg_match( '/\.pdf$/i', $url ) ) {
                    echo '<iframe src="' . $url . '" width="100%" height="500px" style="border:none;"></iframe>';
                } else {
                    echo wp_oembed_get( $url ) ?: '<p><a href="' . $url . '" target="_blank">Ver Conteúdo</a></p>';
                }
            } else {
                echo '<div style="display:flex; justify-content:center; align-items:center; height:100%; background:#eee; color:#888;">[Sem conteúdo visual para esta aula]</div>';
            }
            ?>
        </div>

        <h1><?php echo esc_html( $lesson['title'] ); ?></h1>
        
        <div class="channel-info">
            <div class="channel-name">
                Curso: <?php echo esc_html($course['title']); ?>
            </div>
            <div class="video-actions">
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="lesson_id" value="<?php echo esc_attr( $lesson['id'] ); ?>">
                    <?php if ( $lesson_concluido ) : ?>
                        <button type="submit" name="desmarcar_aula">Desmarcar como concluída</button>
                    <?php else : ?>
                        <button type="submit" name="concluir_aula">Marcar como concluída</button>
                    <?php endif; ?>
                </form>
                <a href="<?php echo esc_url( add_query_arg( 'course_id', $course_id, get_permalink() ) ); ?>" class="back-to-course-btn" title="Voltar ao Curso">Visão Geral do Curso</a>
            </div>
        </div>
        
        <div class="progress-section">
            <p><?php echo round( $progresso_percent, 2 ); ?>% concluído</p>
            <div class="progress-bar"><div class="progress-bar-fill" style="width: <?php echo esc_attr( $progresso_percent ); ?>%;"></div></div>
        </div>
        
        <div class="lesson-detail">
            <?php echo wpautop( $lesson['content'] ); ?>
        </div>
        
        <div class="comments-section">
             <h3>Comentários</h3>
            <form method="POST">
                <input type="hidden" name="lesson_id" value="<?php echo esc_attr( $lesson['id'] ); ?>">
                <div class="comment-input">
                    <input type="text" name="new_comment" placeholder="<?php echo $comments_placeholder; ?>" <?php echo $comments_disabled ? 'disabled' : ''; ?> required>
                    <button type="submit" <?php echo $comments_disabled ? 'disabled' : ''; ?>>Enviar</button>
                </div>
            </form>
            <div id="comments-list">
              <?php if ( ! empty( $comments ) ) : ?>
                <?php foreach ( $comments as $comment ) : ?>
                  <div class="comment">
                      <div class="comment-avatar">
                          <img src="<?php echo esc_url( get_avatar_url( $comment->user_id ) ); ?>" alt="Avatar de <?php echo esc_attr($comment->display_name); ?>">
                      </div>
                      
                      <div class="comment-main">
                          <div class="comment-header">
                              <strong class="comment-author"><?php echo esc_html( $comment->display_name ); ?></strong>
                              <span class="comment-date"><?php echo esc_html( date_i18n( 'd/m/Y H:i', strtotime( $comment->created_at ) ) ); ?></span>
                          </div>
                          <div class="comment-body">
                              <?php echo esc_html( $comment->comment ); ?>
                          </div>
                      </div>
                  </div>
                <?php endforeach; ?>
              <?php else : ?>
                  <p class="no-comments">Nenhum comentário ainda. Seja o primeiro a comentar!</p>
              <?php endif; ?>
            </div>
        </div>
        
    </div>

    <!-- <div class="playlist-section">
        <div class="module-header">
            <div class="module-name"><?php echo esc_html($module_title); ?></div>
            </div>
        
        <ul class="lesson-list">
        <?php if ($playlist_items) :
            foreach ($playlist_items as $playlist_lesson) :
                $playlist_lesson_id = intval($playlist_lesson['id']);
                $is_current_lesson = ($playlist_lesson_id === $lesson['id']);

                if (isset($assoc_grouped[$playlist_lesson_id]['antes'])) {
                    foreach ($assoc_grouped[$playlist_lesson_id]['antes'] as $assoc) {
                        if (($assoc['dias_visualizacao'] == 0 || $dias_passados >= $assoc['dias_visualizacao'])) {
                            // Verifica se a atividade atual foi respondida
                            $is_answered = in_array($assoc['activity_id'], $answered_activity_ids);
                            // Define a classe CSS e o ícone com base no status
                            $li_class = 'associated-activity' . ($is_answered ? ' answered-activity' : '');
                            $icon = $is_answered ? '✓' : '⚡';
                            echo '<li class="' . esc_attr($li_class) . '"><a href="?course_id=' . esc_attr($course_id) . '&activity_id=' . esc_attr($assoc['activity_id']) . '" target="_blank" rel="noopener noreferrer"><span class="activity-icon">' . $icon . '</span>' . esc_html($assoc['activity_title']) . '</a></li>';
                        }
                    }
                }

                $status_info = $lessons_with_status[$playlist_lesson_id] ?? ['is_unlocked' => true, 'is_time_unlocked' => true, 'is_sequentially_unlocked' => true, 'is_concluded' => false, 'unlock_date' => null];
                $is_unlocked = $status_info['is_unlocked'];
                $is_concluded = $status_info['is_concluded'];
                
                $thumb_url = TUTOR_EAD_IMG_URL . 'default-thumbnail.png';
                if (!empty($playlist_lesson['video_url'])) {
                    if (preg_match('/\.pdf$/i', $playlist_lesson['video_url'])) $thumb_url = TUTOR_EAD_IMG_URL . 'pdf.png';
                    elseif (preg_match('/[\?&]v=([^\?&]+)/', $playlist_lesson['video_url'], $matches)) $thumb_url = 'https://img.youtube.com/vi/' . $matches[1] . '/hqdefault.jpg';
                }
                
                $li_classes = $is_current_lesson ? 'current-lesson' : '';
                if (!$is_unlocked) {
                    $li_classes .= ' is-locked';
                    if (!$status_info['is_time_unlocked']) {
                        $li_classes .= ' is-time-locked';
                    } elseif (!$status_info['is_sequentially_unlocked']) {
                        $li_classes .= ' is-sequentially-locked';
                    }
                }
                ?>
                <li class="<?php echo trim($li_classes); ?>">
                    <a href="<?php echo $is_unlocked ? esc_url(add_query_arg(['course_id' => $course_id, 'lesson_id' => $playlist_lesson_id], get_permalink())) : '#'; ?>" class="lesson-locked-link">
                        <div class="thumbnail" style="background-image: url('<?php echo esc_url($thumb_url); ?>');">
                            <?php if ($is_concluded) : ?>
                                <span class="lesson-completed-icon">✓</span>
                            <?php elseif (!$is_unlocked && !$status_info['is_time_unlocked']) :
                                $days_remaining = 0;
                                if ($status_info['unlock_date']) {
                                    $unlock_timestamp = DateTime::createFromFormat('d/m/Y', $status_info['unlock_date'])->setTime(0, 0, 0)->getTimestamp();
                                    $now_timestamp = current_time('timestamp');
                                    $diff_seconds = $unlock_timestamp - $now_timestamp;
                                    $days_remaining = ceil($diff_seconds / (60 * 60 * 24));
                                    if ($days_remaining < 0) $days_remaining = 0;
                                }
                                ?>
                                <div class="drip-overlay-playlist">
                                    <div class="drip-icon-clock-playlist"></div>
                                    <div class="drip-release-text-playlist">
                                        <strong class="drip-days-number-playlist"><?php echo intval($days_remaining); ?></strong> dias
                                    </div>
                                </div>
                            <?php elseif (!$is_unlocked && !$status_info['is_sequentially_unlocked']) : ?>
                                <div class="lock-overlay-playlist">
                                    <div class="lock-icon-padlock-playlist"></div>
                                    <span class="lock-text-playlist">Conclua a anterior</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <span class="lesson-title"><?php echo esc_html($playlist_lesson['title']); ?></span>
                    </a>
                    <?php
                    if (strpos($li_classes, 'is_sequentially_locked') !== false) {
                        echo '<span class="tooltip-bubble">Clique em "Marcar como concluída" na aula anterior para liberar este conteúdo.</span>';
                    }
                    if (strpos($li_classes, 'is-time-locked') !== false && !empty($status_info['unlock_date'])) {
                        echo '<span class="tooltip-bubble">Essa aula estará disponível no dia ' . esc_html($status_info['unlock_date']) . '</span>';
                    }
                    ?>
                </li>
                <?php
                 if (isset($assoc_grouped[$playlist_lesson_id]['depois'])) {
                    foreach ($assoc_grouped[$playlist_lesson_id]['depois'] as $assoc) {
                        if (($assoc['dias_visualizacao'] == 0 || $dias_passados >= $assoc['dias_visualizacao'])) {
                            // Verifica se a atividade atual foi respondida
                            $is_answered = in_array($assoc['activity_id'], $answered_activity_ids);
                            // Define a classe CSS e o ícone com base no status
                            $li_class = 'associated-activity' . ($is_answered ? ' answered-activity' : '');
                            $icon = $is_answered ? '✓' : '⚡';
                            echo '<li class="' . esc_attr($li_class) . '"><a href="?course_id=' . esc_attr($course_id) . '&activity_id=' . esc_attr($assoc['activity_id']) . '" target="_blank" rel="noopener noreferrer"><span class="activity-icon">' . $icon . '</span>' . esc_html($assoc['activity_title']) . '</a></li>';
                        }
                    }
                }
            endforeach;
        endif;
        ?>
        </ul>
    </div> -->
</main>

<style>
/* Estilização para atividades respondidas na playlist */
.associated-activity.answered-activity a {
    /* Torna o texto mais suave para indicar que foi concluído */
    color: #888;
    font-style: italic;
}
.associated-activity.answered-activity .activity-icon {
    /* Muda a cor do ícone de 'check' para verde */
    color: #28a745;
    font-weight: bold;
}
/* Garante que o hover não mude a cor de uma atividade concluída */
.associated-activity.answered-activity a:hover {
    color: #888;
    background-color: transparent; /* Impede mudança de fundo no hover */
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const lockedItems = document.querySelectorAll('.playlist-section .is-time-locked, .playlist-section .is-sequentially-locked');
    // Detecta se é um dispositivo com tela de toque para diferenciar mobile de desktop
    const isTouchDevice = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);

    lockedItems.forEach(function(item) {
        const tooltip = item.querySelector('.tooltip-bubble');
        if (!tooltip) return; // Pula se não houver tooltip

        if (isTouchDevice) {
            // LÓGICA PARA MOBILE (CLIQUE/TOQUE)
            item.addEventListener('click', function(event) {
                // Impede a navegação se o link estiver bloqueado
                if (this.classList.contains('is-locked')) {
                    event.preventDefault();
                }

                const isCurrentlyVisible = this.classList.contains('tooltip-visible');

                // Primeiro, fecha todos os outros tooltips que possam estar abertos
                document.querySelectorAll('.tooltip-visible').forEach(function(openItem) {
                    if (openItem !== item) {
                        openItem.classList.remove('tooltip-visible');
                    }
                });

                // Em seguida, alterna a visibilidade do tooltip do item clicado
                if (isCurrentlyVisible) {
                    this.classList.remove('tooltip-visible');
                } else {
                    this.classList.add('tooltip-visible');
                }
            });
        } else {
            // LÓGICA PARA DESKTOP (HOVER)
            item.addEventListener('mouseenter', function() {
                this.classList.add('tooltip-visible');
            });
            item.addEventListener('mouseleave', function() {
                this.classList.remove('tooltip-visible');
            });
        }
    });

    // Adiciona um listener no documento para fechar tooltips se o usuário clicar fora deles (útil em mobile)
    document.addEventListener('click', function(event) {
        // Se o clique não foi dentro de um item de playlist que pode ter um tooltip, fecha todos
        if (!event.target.closest('.is-time-locked, .is-sequentially-locked')) {
            document.querySelectorAll('.tooltip-visible').forEach(function(openItem) {
                openItem.classList.remove('tooltip-visible');
            });
        }
    });
});
</script>

<?php if ($show_phone_modal) : ?>
<div id="phone-modal-overlay" class="phone-modal-overlay">
    <div id="phone-modal" class="phone-modal">
        <h2>Antes de continuar...</h2>
        <p>Precisamos do seu número de telefone para mantermos você atualizado sobre o curso.</p>
        <form id="phone-modal-form">
            <label for="phone_number">Telefone</label>
            <input type="text" id="phone_number" name="phone_number" placeholder="(99) 99999-9999" required>
            <button type="submit">Salvar e continuar</button>
            <p id="phone-modal-error" class="phone-modal-error"></p>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const showModal = <?php echo json_encode($show_phone_modal); ?>;
    if (showModal) {
        const modalOverlay = document.getElementById('phone-modal-overlay');
        const form = document.getElementById('phone-modal-form');
        const phoneInput = document.getElementById('phone_number');
        const errorP = document.getElementById('phone-modal-error');

        modalOverlay.style.display = 'flex';

        // Máscara de telefone
        phoneInput.addEventListener('input', function (e) {
            let value = e.target.value.replace(/\D/g, '');
            value = value.replace(/^(\d{2})(\d)/g, '($1) $2');
            value = value.replace(/(\d)(\d{4})$/, '$1-$2');
            e.target.value = value;
        });

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            errorP.textContent = '';
            const phoneNumber = phoneInput.value;

            // Validação simples
            if (phoneNumber.length < 14) {
                errorP.textContent = 'Por favor, insira um número de telefone válido.';
                return;
            }

            const formData = new FormData();
            formData.append('action', 'tutoread_update_user_phone');
            formData.append('phone_number', phoneNumber);
            formData.append('user_id', <?php echo json_encode($user_id); ?>);
            formData.append('nonce', <?php echo json_encode(wp_create_nonce('tutoread_update_phone_nonce')); ?>);

            fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    modalOverlay.style.display = 'none';
                } else {
                    errorP.textContent = data.data.message || 'Ocorreu um erro. Tente novamente.';
                }
            })
            .catch(error => {
                errorP.textContent = 'Ocorreu um erro de conexão. Tente novamente.';
            });
        });
    }
});
</script>
<?php endif; ?>