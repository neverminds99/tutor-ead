<?php
/**
 * Template Name: Visualização do Curso - Modo Expandido
 * VERSÃO FINAL COMPLETA - Layout Customizado e Lógica Integrada
 */

// --- Bloco de Definição de Modo de Preview (VERSÃO CORRIGIDA) ---
$is_impersonation_mode = (isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating'] === true);

// Define a variável $original_admin_id SOMENTE se estivermos no modo de preview.
// Isso evita o erro "Undefined variable".
$original_admin_id = null; // Inicia como nulo por padrão
if ($is_impersonation_mode) {
    $original_admin_id = isset($_SESSION['original_admin_id']) ? (int)$_SESSION['original_admin_id'] : null;
}
// --- Fim do Bloco de Definição ---

// O resto do seu código do template continua a partir daqui...
// Ex: if (  is_user_logged_in() ) { ... }

// --- ETAPA 1: PROCESSAMENTO DE FORMULÁRIOS (POSTs) ---
// Esta lógica deve sempre vir primeiro para processar ações antes de qualquer saída de HTML.

global $wpdb;
$user_id   = get_current_user_id();
$course_id = isset( $_GET['course_id'] ) ? intval( $_GET['course_id'] ) : 0;
$lesson_id_get = isset( $_GET['lesson_id'] ) ? intval( $_GET['lesson_id'] ) : 0;
$lesson_id_post = isset( $_POST['lesson_id'] ) ? intval( $_POST['lesson_id'] ) : 0;

// --- Lógica de Registro de Atividade ---
if (!$is_impersonation_mode) {
    // Pega o último log do usuário para evitar duplicatas
    $last_log = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tutoread_student_activity_log WHERE user_id = %d ORDER BY access_time DESC LIMIT 1",
        $user_id
    ));

    // Registra a visualização da aula (se aplicável)
    if ($lesson_id_get > 0) {
        if (!$last_log || $last_log->activity_type !== 'lesson_view' || (int)$last_log->lesson_id !== $lesson_id_get) {
            $lesson_title = $wpdb->get_var($wpdb->prepare("SELECT title FROM {$wpdb->prefix}tutoread_lessons WHERE id = %d", $lesson_id_get));
            if ($lesson_title) {
                \TutorEAD\record_student_activity($user_id, 'lesson_view', $course_id, $lesson_id_get, 'Visualizou a aula: ' . $lesson_title);
            }
        }
    }
    // Registra a entrada no curso (se não houver um log de aula)
    elseif ($course_id > 0) {
        if (!$last_log || $last_log->activity_type !== 'course_entry' || (int)$last_log->course_id !== $course_id) {
            $course_title = $wpdb->get_var($wpdb->prepare("SELECT title FROM {$wpdb->prefix}tutoread_courses WHERE id = %d", $course_id));
            if ($course_title) {
                \TutorEAD\record_student_activity($user_id, 'course_entry', $course_id, null, 'Acessou o curso: ' . $course_title);
            }
        }
    }
}

// Processa o POST para marcar/desmarcar a aula como assistida.
if ( ( isset( $_POST['concluir_aula'] ) || isset( $_POST['desmarcar_aula'] ) ) && $lesson_id_post > 0 ) {
    $new_status = isset( $_POST['concluir_aula'] ) ? 'concluido' : 'nao_iniciado';
    
    $existe = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}progresso_aulas WHERE aluno_id = %d AND aula_id = %d", $user_id, $lesson_id_post ) );

    if ( $existe ) {
        $wpdb->update( "{$wpdb->prefix}progresso_aulas", [ 'status' => $new_status ], [ 'aluno_id' => $user_id, 'aula_id' => $lesson_id_post ] );
    } else {
        $wpdb->insert( "{$wpdb->prefix}progresso_aulas", [ 'aluno_id' => $user_id, 'aula_id'  => $lesson_id_post, 'status'   => $new_status ] );
    }

    // Registra a atividade de conclusão de aula
    if ($new_status === 'concluido') {
        $lesson_title = $wpdb->get_var($wpdb->prepare("SELECT title FROM {$wpdb->prefix}tutoread_lessons WHERE id = %d", $lesson_id_post));
        \TutorEAD\record_student_activity($user_id, 'lesson_completion', $course_id, $lesson_id_post, 'Concluiu a aula: ' . $lesson_title);
    }

    wp_redirect( add_query_arg( [ 'course_id' => $course_id, 'lesson_id' => $lesson_id_post ], get_permalink() ) );
    exit;
}

// Processa o POST para inserir comentário.
if ( isset( $_POST['new_comment'] ) && !empty( $_POST['new_comment'] ) && $lesson_id_post > 0 ) {
    $new_comment = sanitize_textarea_field( $_POST['new_comment'] );
    $wpdb->insert( "{$wpdb->prefix}tutoread_comments", [ 'lesson_id' => $lesson_id_post, 'user_id' => $user_id, 'comment' => $new_comment ] );
    
    wp_redirect( add_query_arg( [ 'course_id' => $course_id, 'lesson_id' => $lesson_id_post ], get_permalink() ) );
    exit;
}


// --- ETAPA 2: BUSCA DE DADOS E VERIFICAÇÕES (VERSÃO COM DRIP CONTENT) ---

if ( ! is_user_logged_in() ) {
    if ( isset( $_GET['impersonate_token'] ) ) {
        auth_redirect();
    }
    wp_redirect( home_url( '/login' ) );
    exit;
}

// Pega os parâmetros da URL para o contexto da página atual.
$lesson_id   = isset( $_GET['lesson_id'] ) ? intval( $_GET['lesson_id'] ) : 0;
$activity_id = isset( $_GET['activity_id'] ) ? intval( $_GET['activity_id'] ) : 0;

// --- INÍCIO DA LÓGICA DE ACESSO SIMPLIFICADA ---
// Verifica se o aluno está matriculado no curso.
$matricula_info = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}matriculas WHERE user_id = %d AND course_id = %d", $user_id, $course_id ) );

// Acesso negado se não estiver matriculado E não for admin (ou admin personificando outro usuário)
if ( ! $matricula_info && $course_id > 0 && !current_user_can('manage_options') && !$is_impersonation_mode ) {
    wp_die( '<h2>Acesso Negado</h2><p>Você não está matriculado neste curso.</p>', 'Acesso Negado', ['response' => 403] );
}
// --- FIM DA LÓGICA DE ACESSO ---

// Obtenção dos dados gerais
$course         = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}tutoread_courses WHERE id = %d", $course_id ), ARRAY_A );
$modules        = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}tutoread_modules WHERE course_id = %d AND parent_id = 0 ORDER BY module_order ASC", $course_id ), ARRAY_A );
$matricula_data = $matricula_info ? $matricula_info->data_matricula : null;

// =========================================================================
// INÍCIO DA LÓGICA DE VERIFICAÇÃO DO DRIP CONTENT
// =========================================================================

// 1. Coletar as regras de Drip aplicáveis
$release_scope   = get_option('tutor_ead_lesson_release_scope', 'global');
$drip_rules      = [];
$is_drip_enabled = false;

if ($release_scope === 'global') {
    $release_type = get_option('tutor_ead_global_release_type', 'full');
    if ($release_type === 'drip') {
        $is_drip_enabled = true;
        $drip_rules = [
            'quantity'  => (int) get_option('tutor_ead_global_drip_quantity', 1),
            'frequency' => (int) get_option('tutor_ead_global_drip_frequency', 1),
            'unit'      => get_option('tutor_ead_global_drip_unit', 'days'),
        ];
    }
} else { // 'per_course'
    $course_settings = get_option('tutor_ead_drip_settings_' . $course_id, []);
    $release_type = $course_settings['release_type'] ?? 'unlocked';
    if ($release_type === 'drip') {
        $is_drip_enabled = true;
        $drip_rules = [
            'quantity'  => (int) ($course_settings['drip_quantity'] ?? 1),
            'frequency' => (int) ($course_settings['drip_frequency'] ?? 1),
            'unit'      => $course_settings['drip_unit'] ?? 'days',
        ];
    }
}
// Garante que a quantidade não seja zero para evitar divisão por zero.
if (isset($drip_rules['quantity']) && $drip_rules['quantity'] === 0) {
    $drip_rules['quantity'] = 1;
}

// 2. Calcular o tempo decorrido desde a matrícula na unidade correta
$time_passed = 0;
if ($is_drip_enabled && $matricula_data) {
    $enrollment_timestamp = strtotime($matricula_data);
    $seconds_passed = time() - $enrollment_timestamp;

    switch ($drip_rules['unit']) {
        case 'hours':
            $time_passed = floor($seconds_passed / (60 * 60));
            break;
        case 'weeks':
            $time_passed = floor($seconds_passed / (60 * 60 * 24 * 7));
            break;
        case 'months': // Supondo 30 dias por mês para simplificar
            $time_passed = floor($seconds_passed / (60 * 60 * 24 * 30));
            break;
        case 'days':
        default:
            $time_passed = floor($seconds_passed / (60 * 60 * 24));
            break;
    }
}

// 3. Obter todas as aulas e determinar o status de CADA UMA (Bloqueada/Liberada)
$all_course_lessons = $wpdb->get_results( $wpdb->prepare(
    "SELECT l.id, l.video_url, l.content FROM {$wpdb->prefix}tutoread_lessons l
     JOIN {$wpdb->prefix}tutoread_modules m ON l.module_id = m.id
     WHERE m.course_id = %d
     ORDER BY m.module_order ASC, l.lesson_order ASC",
    $course_id
) );

// Pega o progresso do aluno de uma só vez para otimizar
$statuses = [];
if (!empty($all_course_lessons)) {
    $lesson_ids_in_course = wp_list_pluck($all_course_lessons, 'id');
    $status_results = $wpdb->get_results( $wpdb->prepare(
        "SELECT aula_id, status FROM {$wpdb->prefix}progresso_aulas WHERE aluno_id = %d AND aula_id IN (" . implode(',', array_fill(0, count($lesson_ids_in_course), '%d')) . ")",
        array_merge([$user_id], $lesson_ids_in_course)
    ), OBJECT_K );
    if ($status_results) {
        foreach ($status_results as $s_aula_id => $s_data) {
            $statuses[$s_aula_id] = $s_data->status;
        }
    }
}

// 4. Montar o array final com o status de liberação de cada aula
$lessons_with_status = [];
$resume_lesson_id = null;
$lesson_position = 0;
$prev_lesson_concluded = true; // A primeira aula está sempre sequencialmente liberada

if ($all_course_lessons) {
    foreach ($all_course_lessons as $course_lesson) {
        $lesson_position++;
        
        // Verificação de liberação por TEMPO
        $is_time_unlocked = true; // Assume liberada por padrão
        $unlock_date = null;

        if ($is_drip_enabled) {
            // A "rodada" de liberação (ex: 0 para as primeiras aulas, 1 para as próximas, etc.)
            $release_milestone = floor(($lesson_position - 1) / $drip_rules['quantity']);
            // O tempo necessário para essa rodada ser liberada
            $time_required = $release_milestone * $drip_rules['frequency'];
            
            $is_time_unlocked = ($time_passed >= $time_required);

            if (!$is_time_unlocked && $matricula_data) {
                // Calcula a data exata de liberação para exibir na interface
                $unlock_timestamp = strtotime("+$time_required {$drip_rules['unit']}", strtotime($matricula_data));
                $unlock_date = date_i18n('d/m/Y', $unlock_timestamp);
            }
        }
        
        // Verificação de liberação SEQUENCIAL
        $is_sequentially_unlocked = $prev_lesson_concluded;
        
        // Status final de liberação
        $is_unlocked = $is_time_unlocked && $is_sequentially_unlocked;

        // ADMIN PREVIEW OVERRIDE: Libera todo o conteúdo para o administrador.
        if (current_user_can('manage_options') || $is_impersonation_mode) {
            $is_unlocked = true;
            $is_time_unlocked = true;
            $is_sequentially_unlocked = true;
        }
        
        // Status de conclusão
        $is_concluded = isset($statuses[$course_lesson->id]) && $statuses[$course_lesson->id] === 'concluido';

        // Encontra a próxima aula para o botão "Continuar Assistindo"
        if ($is_unlocked && !$is_concluded && $resume_lesson_id === null) {
            $resume_lesson_id = $course_lesson->id;
        }

        // Armazena o status completo da aula
        $lessons_with_status[$course_lesson->id] = [
            'is_unlocked'              => $is_unlocked,
            'is_time_unlocked'         => $is_time_unlocked,
            'is_sequentially_unlocked' => $is_sequentially_unlocked,
            'is_concluded'             => $is_concluded,
            'unlock_date'              => $unlock_date, // Ex: '25/12/2025' ou null
        ];

        // Prepara para a próxima iteração
        $is_completable = !empty($course_lesson->video_url) || !empty($course_lesson->content);
        $prev_lesson_concluded = $is_concluded || !$is_completable;
    }
    
    // Fallback para o botão "Continuar": se todas as aulas foram concluídas, aponta para a última.
    if ($resume_lesson_id === null) {
        $last_lesson_obj = end($all_course_lessons);
        $resume_lesson_id = $last_lesson_obj ? $last_lesson_obj->id : null;
    }
}

$play_link = add_query_arg( [ 'course_id' => $course_id, 'lesson_id' => $resume_lesson_id ], get_permalink() );

// =========================================================================
// FIM DA LÓGICA DE VERIFICAÇÃO DO DRIP CONTENT
// =========================================================================

// --- ETAPA 3: ROTEAMENTO DA VISUALIZAÇÃO ---

$activity = null;
if ( $activity_id ) {
    $activity = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}atividades WHERE id = %d", $activity_id ), ARRAY_A );
}

if ( $activity && $activity['is_externa'] == 0 ) {
    // LÓGICA DE REVISÃO DE QUIZ
    $user_submission = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}respostas WHERE atividade_id = %d AND aluno_id = %d ORDER BY tentativa DESC LIMIT 1",
        $activity_id, $user_id
    ), ARRAY_A);

    $attempts_count = $user_submission ? (int)$user_submission['tentativa'] : 0;
    $max_attempts = !empty($activity['num_tentativas']) ? (int)$activity['num_tentativas'] : 0;

    // CORREÇÃO LÓGICA: A verificação deve ser estrita.
    $can_retake = ($max_attempts === 0) || ($attempts_count < $max_attempts);

    $correct_answers = [];
    if ($user_submission && !$can_retake) {
        $questions_in_activity = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}perguntas WHERE atividade_id = %d", $activity_id));
        if ($questions_in_activity) {
            $correct_answers_results = $wpdb->get_results(
                "SELECT pergunta_id, id FROM {$wpdb->prefix}alternativas WHERE correta = 1 AND pergunta_id IN (" . implode(',', $questions_in_activity) . ")",
                OBJECT_K
            );
            foreach ($correct_answers_results as $qid => $adata) {
                $correct_answers[$qid] = $adata->id;
            }
        }
    }

    require_once( __DIR__ . '/partials/view-activity-quiz.php' );
} 
else {
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo esc_html( $course['title'] ?? 'Curso' ); ?> - Tutor EAD</title>
        <?php wp_head(); ?>
    </head>
    <body <?php body_class('tutor-ead-page-body'); ?>>
        <?php
// Exibe o banner SOMENTE se a sessão de personificação estiver ativa.
// A variável $is_impersonation_mode foi definida no topo do arquivo.
if ( $is_impersonation_mode ) :
    // Gera a URL correta para a ação de SAÍDA que criamos no plugin principal.
    $exit_impersonation_url = admin_url('admin.php?action=exit_impersonation');
    $impersonated_user = wp_get_current_user();
?>
<style>
    .impersonation-banner { position: fixed; top: 0; left: 0; width: 100%;
        background-color: #d9534f; /* Cor de alerta */
        color: #fff; padding: 10px 20px;
        display: flex; justify-content: center; align-items: center;
        font-size: 14px; z-index: 99999; box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    .impersonation-banner p { margin: 0; padding: 0; }
    .impersonation-banner a {
        color: #fff; background-color: rgba(255, 255, 255, 0.2);
        border: 1px solid #fff; border-radius: 4px;
        padding: 5px 15px; text-decoration: none; margin-left: 20px;
        font-weight: bold; transition: background-color 0.2s ease;
    }
    .impersonation-banner a:hover { background-color: rgba(255, 255, 255, 0.4); }
    body { padding-top: 45px; /* Empurra o conteúdo para baixo para não sobrepor */ }
</style>
<div class="impersonation-banner">
    <p>
        <span class="dashicons dashicons-admin-users"></span>
        Você está navegando como o aluno: <strong><?php echo esc_html($impersonated_user->display_name); ?></strong>.
    </p>
    <a href="<?php echo esc_url($exit_impersonation_url); ?>" target="_self">Sair do Modo Preview</a>
</div>
<?php endif; ?>

        <header class="tutor-ead-header">
            <div class="logo">
                <?php
                $plugin_logo = get_option('tutor_ead_course_logo');
                if ( $plugin_logo ) { echo '<img src="' . esc_url( $plugin_logo ) . '" alt="Logo">'; } else { echo 'Tutor EAD'; }
                ?>
            </div>
            <input type="text" placeholder="Pesquisar" class="search-bar">
            <div class="user-menu">
                <div class="user-icon">👤</div>
                <div class="user-dropdown">
                    <?php
                    $dashboard_url = home_url('/dashboard-aluno');
                    ?>
                    <a href="<?php echo esc_url( $dashboard_url ); ?>">Dashboard</a>
                    <a href="<?php echo esc_url( wp_logout_url( home_url('/login-tutor-ead') ) ); ?>">Logout</a>
                </div>
            </div>
        </header>

        <?php
        $lesson = null;
        if ( $lesson_id ) {
            $lesson = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}tutoread_lessons WHERE id = %d", $lesson_id ), ARRAY_A );
        }

        if ( $lesson ) {
            require_once( __DIR__ . '/partials/view-lesson-player.php' );
        } else {
            require_once( __DIR__ . '/partials/view-course-overview.php' );
        }
        ?>

        <script>
          document.addEventListener('DOMContentLoaded', function(){
            var userIcon = document.querySelector('.user-icon');
            var userDropdown = document.querySelector('.user-dropdown');
            if (userIcon && userDropdown) {
                userIcon.addEventListener('click', function(e){
                    e.stopPropagation();
                    userDropdown.style.display = (userDropdown.style.display === 'block') ? 'none' : 'block';
                });
            }
            document.addEventListener('click', function(){
                if (userDropdown) {
                    userDropdown.style.display = 'none';
                }
            });
          });
        </script>
        
        <?php wp_footer(); ?>
    </body>
    </html>
    <?php
}
