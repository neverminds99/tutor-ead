<?php
/**
 * Partial: Exibição da visão geral do curso.
 * VERSÃO COM CARDS LARGOS E THUMBNAILS
 */
defined('ABSPATH') || exit;

// --- INÍCIO DA LÓGICA DE ATIVIDADES ---
global $wpdb;
$user_id = get_current_user_id();
$course_id = $course['id'];

// Busca TODAS as atividades associadas ao CURSO de uma vez para otimizar
$activities_assoc = $wpdb->get_results( $wpdb->prepare(
    "SELECT ca.*, a.titulo AS activity_title, a.is_externa, a.link_externo, a.dias_visualizacao FROM {$wpdb->prefix}tutoread_course_activities ca INNER JOIN {$wpdb->prefix}atividades a ON ca.activity_id = a.id WHERE ca.course_id = %d",
    $course_id
), ARRAY_A );

// Agrupa as atividades por MÓDULO, AULA e POSIÇÃO
$assoc_grouped = [];
if ($activities_assoc) {
    foreach ($activities_assoc as $assoc) {
        $module_id = intval($assoc['module_id']);
        $lesson_ref_id = empty($assoc['lesson_id']) ? 0 : intval($assoc['lesson_id']);
        $position = $assoc['position'] ?? 'depois';
        $assoc_grouped[$module_id][$lesson_ref_id][$position][] = $assoc;
    }
}

// Busca os IDs de atividades já respondidas pelo aluno
$answered_activity_ids = [];
if (!empty($activities_assoc)) {
    $activity_ids_in_course = array_unique(wp_list_pluck($activities_assoc, 'activity_id'));
    if (!empty($activity_ids_in_course)) {
        $answered_activity_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT DISTINCT atividade_id FROM {$wpdb->prefix}respostas WHERE aluno_id = %d AND atividade_id IN (" . implode(',', array_fill(0, count($activity_ids_in_course), '%d')) . ")",
            array_merge([$user_id], $activity_ids_in_course)
        ));
    }
}
// --- FIM DA LÓGICA DE ATIVIDADES ---


// As variáveis necessárias ($course, $modules, etc.) são fornecidas pelo template-curso.php
global $wpdb;
$user_id = get_current_user_id();
$course_id = $course['id'];

// Lógica de progresso (pode ser movida para o template principal se desejar)
$total_aulas_query = $wpdb->get_results( $wpdb->prepare("SELECT l.id FROM {$wpdb->prefix}tutoread_lessons l JOIN {$wpdb->prefix}tutoread_modules m ON l.module_id = m.id WHERE m.course_id = %d", $course_id) );
$total_aulas = count($total_aulas_query);
$aulas_concluidas = isset($statuses) ? count(array_filter($statuses, function($s) { return $s === 'concluido'; })) : 0;
$progresso_percent = ($total_aulas > 0) ? ($aulas_concluidas / $total_aulas) * 100 : 0;
?>

<main>
    <div class="course-overview-container">
      <div class="course-info-block">
        <?php if ( ! empty( $course['capa_img'] ) ) : ?>
          <img src="<?php echo esc_url( $course['capa_img'] ); ?>" alt="Capa do Curso" class="course-cover">
        <?php else : ?>
          <img src="https://via.placeholder.com/800x400?text=Capa+do+Curso" alt="Capa do Curso" class="course-cover">
        <?php endif; ?>
        <div class="cover-progress-section">
          <div class="cover-progress"><div class="cover-progress-fill" style="width: <?php echo esc_attr( $progresso_percent ); ?>%;"></div></div>
          <p><?php echo round( $progresso_percent, 2 ); ?>% concluído</p>
        </div>
        <a href="<?php echo esc_url( $play_link ); ?>" class="play-button">
          <span class="icon">▶</span> Continuar Assistindo
        </a>
        <div class="course-description">
          <h1><?php echo esc_html( $course['title'] ); ?></h1>
          <?php echo wpautop(esc_html( $course['description'] )); ?>
        </div>
        <a href="<?php echo esc_url( home_url( '/dashboard-aluno' ) ); ?>" class="back-admin-btn">Voltar ao Painel do Aluno</a>
      </div>
      
      <div class="module-list">
        <h2>Módulos e Aulas</h2>
        <?php if ( $modules ) : ?>
          <?php 
          $global_prev_concluded = true;
          foreach ( $modules as $module ) : ?>
            <div class="module-overview">
              <h3><?php echo esc_html( $module['title'] ); ?></h3>
              
              <div class="lesson-cards-container">
                <?php
                // Busca por unidades dentro do módulo
                $units = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}tutoread_modules WHERE parent_id = %d ORDER BY module_order ASC", $module['id']), ARRAY_A );

                if ( $units ) { // Se existem unidades
                    foreach ( $units as $unit ) {
                        echo '<h4 class="unit-title">' . esc_html( $unit['title'] ) . '</h4>'; // Exibe o título da unidade
                        
                        $lessons = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}tutoread_lessons WHERE module_id = %d ORDER BY lesson_order ASC", $unit['id']), ARRAY_A );
                        
                        if ( $lessons ) {
                            foreach ( $lessons as $lesson_item ) {
                                // --- INÍCIO: LÓGICA COMPLETA DE RENDERIZAÇÃO DO CARD DA AULA (DENTRO DA UNIDADE) ---
                                $lesson_id_current = intval($lesson_item['id']);
                                $module_id_current = intval($unit['id']); // O "módulo" para atividades é a unidade

                                if (isset($assoc_grouped[$module_id_current][$lesson_id_current]['antes'])) {
                                    foreach ($assoc_grouped[$module_id_current][$lesson_id_current]['antes'] as $assoc) {
                                        $is_answered = in_array($assoc['activity_id'], $answered_activity_ids);
                                        $activity_card_classes = 'activity-card' . ($is_answered ? ' is-completed' : '');
                                        echo '<a href="?course_id=' . esc_attr($course_id) . '&activity_id=' . esc_attr($assoc['activity_id']) . '" target="_blank" rel="noopener noreferrer" class="' . esc_attr($activity_card_classes) . '"><div class="activity-card-icon-wrapper"><span class="dashicons dashicons-clipboard"></span></div><div class="card-content"><span class="card-title">' . esc_html($assoc['activity_title']) . '</span></div></a>';
                                    }
                                }

                                $status_info = $lessons_with_status[$lesson_id_current] ?? ['is_unlocked' => true, 'is_time_unlocked' => true, 'is_sequentially_unlocked' => true, 'is_concluded' => false, 'unlock_date' => null];
                                $is_unlocked = $status_info['is_unlocked'];
                                $is_concluded = $status_info['is_concluded'];

                                $card_classes = 'lesson-card';
                                if ($is_concluded) $card_classes .= ' is-completed';
                                if (!$is_unlocked) $card_classes .= ' is-locked';

                                $thumb_url = TUTOR_EAD_IMG_URL . 'default-thumbnail.png';
                                if (!empty($lesson_item['video_url'])) {
                                    $video_url = $lesson_item['video_url'];
                                    if (preg_match('/\.pdf$/i', $video_url)) {
                                        $thumb_url = TUTOR_EAD_IMG_URL . 'pdf.png';
                                    } elseif (preg_match('/[\?\&]v=([^\?\&]+)/', $video_url, $matches)) {
                                        $thumb_url = 'https://img.youtube.com/vi/' . $matches[1] . '/mqdefault.jpg';
                                    }
                                }
                                ?>
                                <a href="<?php echo $is_unlocked ? esc_url(add_query_arg(['course_id' => $course_id, 'lesson_id' => $lesson_id_current], get_permalink())) : '#'; ?>" class="<?php echo esc_attr($card_classes); ?>">
                                    <?php if (!$is_unlocked) : ?>
                                        <?php if (!$status_info['is_time_unlocked']) : 
                                            $days_remaining = 0;
                                            if ($status_info['unlock_date']) {
                                                $unlock_timestamp = DateTime::createFromFormat('d/m/Y', $status_info['unlock_date'])->setTime(0, 0, 0)->getTimestamp();
                                                $now_timestamp = current_time('timestamp');
                                                $diff_seconds = $unlock_timestamp - $now_timestamp;
                                                $days_remaining = ceil($diff_seconds / (60 * 60 * 24));
                                                if ($days_remaining < 0) $days_remaining = 0;
                                            }
                                        ?>
                                            <div class="content-drip-overlay">
                                                <div class="drip-icon-clock"></div>
                                                <div class="drip-release-text">Libera em <strong class="drip-days-number"><?php echo intval($days_remaining); ?></strong> dias</div>
                                            </div>
                                        <?php elseif (!$status_info['is_sequentially_unlocked']) : ?>
                                            <div class="sequential-lock-overlay">
                                                <div class="lock-icon-padlock"></div>
                                                <span class="lock-text">Marque como assistida a aula anterior para liberar esta aula</span>
                                            </div>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                    <div class="card-thumbnail" style="background-image: url('<?php echo esc_url($thumb_url); ?>');"></div>
                                    <div class="card-content"><span class="card-title"><?php echo esc_html( $lesson_item['title'] ); ?></span></div>
                                </a>
                                <?php
                                if (isset($assoc_grouped[$module_id_current][$lesson_id_current]['depois'])) {
                                    foreach ($assoc_grouped[$module_id_current][$lesson_id_current]['depois'] as $assoc) {
                                        $is_answered = in_array($assoc['activity_id'], $answered_activity_ids);
                                        $activity_card_classes = 'activity-card' . ($is_answered ? ' is-completed' : '');
                                        echo '<a href="?course_id=' . esc_attr($course_id) . '&activity_id=' . esc_attr($assoc['activity_id']) . '" target="_blank" rel="noopener noreferrer" class="' . esc_attr($activity_card_classes) . '"><div class="activity-card-icon-wrapper"><span class="dashicons dashicons-clipboard"></span></div><div class="card-content"><span class="card-title">' . esc_html($assoc['activity_title']) . '</span></div></a>';
                                    }
                                }
                                // --- FIM: LÓGICA COMPLETA DE RENDERIZAÇÃO DO CARD DA AULA ---
                            }
                        } else {
                            echo '<p>Nenhuma aula disponível nesta unidade.</p>';
                        }
                    }
                } else { // Se não existem unidades, busca aulas diretamente do módulo
                    $lessons = $wpdb->get_results( $wpdb->prepare("SELECT * FROM {$wpdb->prefix}tutoread_lessons WHERE module_id = %d ORDER BY lesson_order ASC", $module['id']), ARRAY_A );
                    
                    if ( $lessons ) {
                        foreach ( $lessons as $lesson_item ) {
                            // --- INÍCIO: LÓGICA COMPLETA DE RENDERIZAÇÃO DO CARD DA AULA (DIRETO NO MÓDULO) ---
                            $lesson_id_current = intval($lesson_item['id']);
                            $module_id_current = intval($module['id']);

                            if (isset($assoc_grouped[$module_id_current][$lesson_id_current]['antes'])) {
                                foreach ($assoc_grouped[$module_id_current][$lesson_id_current]['antes'] as $assoc) {
                                    $is_answered = in_array($assoc['activity_id'], $answered_activity_ids);
                                    $activity_card_classes = 'activity-card' . ($is_answered ? ' is-completed' : '');
                                    echo '<a href="?course_id=' . esc_attr($course_id) . '&activity_id=' . esc_attr($assoc['activity_id']) . '" target="_blank" rel="noopener noreferrer" class="' . esc_attr($activity_card_classes) . '"><div class="activity-card-icon-wrapper"><span class="dashicons dashicons-clipboard"></span></div><div class="card-content"><span class="card-title">' . esc_html($assoc['activity_title']) . '</span></div></a>';
                                }
                            }

                            $status_info = $lessons_with_status[$lesson_id_current] ?? ['is_unlocked' => true, 'is_time_unlocked' => true, 'is_sequentially_unlocked' => true, 'is_concluded' => false, 'unlock_date' => null];
                            $is_unlocked = $status_info['is_unlocked'];
                            $is_concluded = $status_info['is_concluded'];

                            $card_classes = 'lesson-card';
                            if ($is_concluded) $card_classes .= ' is-completed';
                            if (!$is_unlocked) $card_classes .= ' is-locked';

                            $thumb_url = TUTOR_EAD_IMG_URL . 'default-thumbnail.png';
                            if (!empty($lesson_item['video_url'])) {
                                $video_url = $lesson_item['video_url'];
                                if (preg_match('/\.pdf$/i', $video_url)) {
                                    $thumb_url = TUTOR_EAD_IMG_URL . 'pdf.png';
                                } elseif (preg_match('/[\?\&]v=([^\?\&]+)/', $video_url, $matches)) {
                                    $thumb_url = 'https://img.youtube.com/vi/' . $matches[1] . '/mqdefault.jpg';
                                }
                            }
                            ?>
                            <a href="<?php echo $is_unlocked ? esc_url(add_query_arg(['course_id' => $course_id, 'lesson_id' => $lesson_id_current], get_permalink())) : '#'; ?>" class="<?php echo esc_attr($card_classes); ?>">
                                <?php if (!$is_unlocked) : ?>
                                    <?php if (!$status_info['is_time_unlocked']) : 
                                        $days_remaining = 0;
                                        if ($status_info['unlock_date']) {
                                            $unlock_timestamp = DateTime::createFromFormat('d/m/Y', $status_info['unlock_date'])->setTime(0, 0, 0)->getTimestamp();
                                            $now_timestamp = current_time('timestamp');
                                            $diff_seconds = $unlock_timestamp - $now_timestamp;
                                            $days_remaining = ceil($diff_seconds / (60 * 60 * 24));
                                            if ($days_remaining < 0) $days_remaining = 0;
                                        }
                                    ?>
                                        <div class="content-drip-overlay">
                                            <div class="drip-icon-clock"></div>
                                            <div class="drip-release-text">Libera em <strong class="drip-days-number"><?php echo intval($days_remaining); ?></strong> dias</div>
                                        </div>
                                    <?php elseif (!$status_info['is_sequentially_unlocked']) : ?>
                                        <div class="sequential-lock-overlay">
                                            <div class="lock-icon-padlock"></div>
                                            <span class="lock-text">Marque como assistida a aula anterior para liberar esta aula</span>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <div class="card-thumbnail" style="background-image: url('<?php echo esc_url($thumb_url); ?>');"></div>
                                <div class="card-content"><span class="card-title"><?php echo esc_html( $lesson_item['title'] ); ?></span></div>
                            </a>
                            <?php
                            if (isset($assoc_grouped[$module_id_current][$lesson_id_current]['depois'])) {
                                foreach ($assoc_grouped[$module_id_current][$lesson_id_current]['depois'] as $assoc) {
                                    $is_answered = in_array($assoc['activity_id'], $answered_activity_ids);
                                    $activity_card_classes = 'activity-card' . ($is_answered ? ' is-completed' : '');
                                    echo '<a href="?course_id=' . esc_attr($course_id) . '&activity_id=' . esc_attr($assoc['activity_id']) . '" target="_blank" rel="noopener noreferrer" class="' . esc_attr($activity_card_classes) . '"><div class="activity-card-icon-wrapper"><span class="dashicons dashicons-clipboard"></span></div><div class="card-content"><span class="card-title">' . esc_html($assoc['activity_title']) . '</span></div></a>';
                                }
                            }
                            // --- FIM: LÓGICA COMPLETA DE RENDERIZAÇÃO DO CARD DA AULA ---
                        }
                    } else {
                        echo '<p>Nenhuma aula disponível neste módulo.</p>';
                    }
                }
                ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else :
          echo '<p>Nenhum módulo cadastrado para este curso.</p>';
        endif; ?>
      </div>
    </div>
</main>

<style>
.activity-card {
    display: flex;
    align-items: center;
    background-color: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 12px;
    margin-bottom: 10px;
    text-decoration: none;
    color: #1f2937;
    transition: box-shadow 0.2s ease;
    position: relative;
}
.activity-card:hover {
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
}
.activity-card.is-completed {
    background-color: #f9fafb;
}
.activity-card.is-completed .card-title {
    color: #6b7280;
    text-decoration: line-through;
}
.activity-card.is-completed .activity-card-icon-wrapper .dashicons {
    color: #10b981; /* green-500 */
}
.activity-card-icon-wrapper {
    background-color: #f3f4f6;
    border-radius: 6px;
    padding: 10px;
    margin-right: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}
.activity-card-icon-wrapper .dashicons {
    font-size: 24px;
    color: #4b5563;
}
</style>