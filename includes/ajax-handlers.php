<?php



namespace TutorEAD;


defined('ABSPATH') || exit;


/**
 * Este arquivo contém handlers para requisições AJAX do Course Builder,
 * além de novos handlers para o sistema de boletins.
 *
 * Principais funcionalidades:


 * - get_modules_for_course_helper: Retorna módulos de um curso para seleção dinâmica.
 * - get_lessons_for_module_helper: Retorna aulas de um módulo para organização da playlist.
 * - create_aluno_negocio_page: Cria a página de negócio do aluno via AJAX.
 * - tutor_ead_get_courses_for_aluno: Retorna os cursos em que um aluno está matriculado (para o boletim).
 * - tutor_ead_get_atividades_for_course: Retorna as atividades associadas a um curso (para o boletim).
 * - tutor_ead_get_alunos_for_course: Retorna os alunos matriculados em um curso (para o boletim).
 *
 * Novos handlers AJAX adicionados:
 * - tutor_ead_previsualizar_notas / tutor_ead_inserir_notas (boletim).
 * - tutor_ead_alert_viewed: Registra quantas vezes um alerta/aviso já foi exibido
 *   para cada usuário, respeitando o campo limite_exibicoes.
 */



/* =========================================================================
 * Handler: get_modules_for_course_helper
 * ========================================================================= */

add_action('wp_ajax_get_modules_for_course_helper', function () {

    check_ajax_referer('get_modules_for_course_helper_nonce', 'nonce');

    global $wpdb;

    $course_id = intval($_POST['course_id']);



    $modules = \TutorEAD\obter_modulos_por_curso($course_id);

    echo '<option value="">' . __("Selecione um módulo", "tutor-ead") . '</option>';

    if ($modules) {

        foreach ($modules as $module) {

            echo '<option value="' . esc_attr($module['id']) . '">' . esc_html($module['title']) . '</option>';

        }

    } else {

        echo '<option value="">' . __("Nenhum módulo encontrado", "tutor-ead") . '</option>';

    }

    wp_die();

});



/* =========================================================================
 * Handler: get_lessons_for_module_helper
 * ========================================================================= */

add_action('wp_ajax_get_lessons_for_module_helper', function () {

    check_ajax_referer('get_lessons_for_module_helper_nonce', 'nonce');

    global $wpdb;

    $module_id = intval($_POST['module_id']);



    $lessons = $wpdb->get_results(

        $wpdb->prepare("SELECT id, title FROM {$wpdb->prefix}tutoread_lessons WHERE module_id = %d", $module_id),

        ARRAY_A

    );

    echo '<option value="">' . __("Selecione uma Aula", "tutor-ead") . '</option>';

    if ($lessons) {

        foreach ($lessons as $lesson) {

            echo '<option value="' . esc_attr($lesson['id']) . '">' . esc_html($lesson['title']) . '</option>';

        }

    }

    wp_die();

});



/* =========================================================================
 * Handler: create_aluno_negocio_page
 * ========================================================================= */

add_action('wp_ajax_create_aluno_negocio_page', function () {

    if (!is_user_logged_in()) {

        wp_send_json_error(['message' => __('Usuário não autenticado.', 'tutor-ead')]);

    }

    $user_id = get_current_user_id();

    $page_id = get_user_meta($user_id, 'aluno_negocio_page_id', true);



    if ($page_id && get_post($page_id)) {

        wp_send_json_success([

            'message'  => __('Você já possui uma página de negócio.', 'tutor-ead'),

            'page_url' => get_permalink($page_id)

        ]);

    }



    if (!function_exists('TutorEAD\create_aluno_negocio_page')) {

        $path = plugin_dir_path(__FILE__) . 'class-page-manager.php';

        if (file_exists($path)) {

            require_once $path;

        }

    }



    if (function_exists('TutorEAD\create_aluno_negocio_page')) {

        $new_page_id = \TutorEAD\create_aluno_negocio_page($user_id);

        if ($new_page_id && !is_wp_error($new_page_id)) {

            wp_send_json_success([

                'message'  => __('Página de negócio criada com sucesso!', 'tutor-ead'),

                'page_url' => get_permalink($new_page_id)

            ]);

        }

    }

    wp_send_json_error(['message' => __('Erro ao criar a página de negócio.', 'tutor-ead')]);

});



/* =========================================================================
 * Handlers para combos do boletim (atividades / alunos)
 * ========================================================================= */

add_action('wp_ajax_tutor_ead_get_atividades_for_course', function () {

    global $wpdb;

    $course_id = intval($_POST['course_id']);



    if (!$course_id) {

        echo '<option value="">' . esc_html__('Selecione uma Atividade', 'tutor-ead') . '</option>';

        wp_die();

    }



    $sql = $wpdb->prepare(

        "\n        SELECT DISTINCT a.id, a.titulo\n        FROM {$wpdb->prefix}atividades a\n        INNER JOIN {$wpdb->prefix}tutoread_course_activities ca ON a.id = ca.activity_id\n        WHERE ca.course_id = %d\n    ", $course_id);



    $atividades = $wpdb->get_results($sql);

    echo '<option value="">' . esc_html__('Selecione uma Atividade', 'tutor-ead') . '</option>';

    if ($atividades) {

        foreach ($atividades as $at) {

            echo '<option value="' . esc_attr($at->id) . '">' . esc_html($at->titulo) . '</option>';

        }

    } else {

        echo '<option value="">' . esc_html__('Nenhuma atividade encontrada', 'tutor-ead') . '</option>';

    }

    wp_die();

});



add_action('wp_ajax_tutor_ead_get_alunos_for_course', function () {

    global $wpdb;

    $course_id = intval($_POST['course_id']);



    echo '<option value="">' . esc_html__('Selecione um Aluno', 'tutor-ead') . '</option>';

    if (!$course_id) {

        wp_die();

    }



    $capabilities_key = $wpdb->prefix . 'capabilities';

    $cap_like_value   = '%"tutor_aluno"%';



    $sql = $wpdb->prepare(

        "\n        SELECT DISTINCT u.ID, u.display_name\n        FROM {$wpdb->prefix}matriculas m\n        INNER JOIN {$wpdb->prefix}users u ON u.ID = m.user_id\n        INNER JOIN {$wpdb->prefix}usermeta um ON um.user_id = u.ID\n        WHERE m.course_id = %d\n          AND um.meta_key = %s\n          AND um.meta_value LIKE %s\n    ", $course_id, $capabilities_key, $cap_like_value);



    $alunos = $wpdb->get_results($sql);

    if ($alunos) {

        foreach ($alunos as $al) {

            echo '<option value="' . esc_attr($al->ID) . '">' .

                 esc_html($al->display_name) .

                 ' (ID: ' . esc_html($al->ID) . ')</option>';

        }

    } else {

        echo '<option value="">' . esc_html__('Nenhum aluno matriculado', 'tutor-ead') . '</option>';

    }

    wp_die();

});



/* =========================================================================
 * Boletim –	Pré‑visualizar e Inserir notas em massa
 * ========================================================================= */

add_action('wp_ajax_tutor_ead_previsualizar_notas', function () {

    if (!current_user_can('manage_options')) {

        wp_send_json_error(['message' => __('Permissão negada.', 'tutor-ead')]);

    }

    $json_data = wp_unslash($_POST['json_data'] ?? '');

    $data = json_decode($json_data, true);



    if (json_last_error() !== JSON_ERROR_NONE || empty($data['notas'])) {

        wp_send_json_error(['message' => __('JSON inválido ou vazio.', 'tutor-ead')]);

    }



    $result = [];

    foreach ($data['notas'] as $n) {

        $email = sanitize_email($n['email'] ?? '');

        $user  = get_user_by('email', $email);

        $result[] = [

            'email'           => $email,

            'user_id'         => $user ? $user->ID : null,

            'course_id'       => intval($n['course_id'] ?? 0),

            'course_title'    => sanitize_text_field($n['course_title'] ?? ''),

            'atividade_id'    => intval($n['atividade_id'] ?? 0),

            'atividade_title' => sanitize_text_field($n['atividade_title'] ?? ''),

            'nota'            => floatval($n['nota'] ?? 0),

            'feedback'        => sanitize_text_field($n['feedback'] ?? '')

        ];

    }

    wp_send_json_success($result);

});



add_action('wp_ajax_tutor_ead_inserir_notas', function () {

    global $wpdb;



    if (!current_user_can('manage_options')) {

        wp_send_json_error(['message' => __('Permissão negada.', 'tutor-ead')]);

    }

    $json_data = wp_unslash($_POST['json_data'] ?? '');

    $data = json_decode($json_data, true);



    if (json_last_error() !== JSON_ERROR_NONE || empty($data['notas'])) {

        wp_send_json_error(['message' => __('JSON inválido ou vazio.', 'tutor-ead')]);

    }



    $results = [];

    foreach ($data['notas'] as $n) {

        $email = sanitize_email($n['email'] ?? '');

        $user  = get_user_by('email', $email);

        if (!$user) {

            $results[] = [

                'status'   => 'erro',

                'mensagem' => __('Usuário não encontrado para: ', 'tutor-ead') . $email,

                'registro' => $n

            ];

            continue;

        }



        $course_id = intval($n['course_id'] ?? 0);

        $course_title = $course_id

            ? $wpdb->get_var($wpdb->prepare("SELECT title FROM {$wpdb->prefix}tutoread_courses WHERE id = %d", $course_id))

            : '';



        $dados = [

            'course_id'       => $course_id,

            'course_title'    => $course_title,

            'atividade_id'    => intval($n['atividade_id'] ?? 0),

            'atividade_title' => sanitize_text_field($n['atividade_title'] ?? ''),

            'nota'            => floatval($n['nota'] ?? 0),

            'feedback'        => sanitize_text_field($n['feedback'] ?? ''),

            'aluno_id'        => $user->ID,

        ];



        $ok = $wpdb->insert(

            $wpdb->prefix . 'boletins',

            $dados,

            ['%d','%s','%d','%s','%f','%s','%d']

        );



        $results[] = [

            'status'   => $ok ? 'sucesso' : 'erro',

            'mensagem' => ($ok

                ? __('Nota inserida para: ', 'tutor-ead')

                : __('Erro ao inserir para: ', 'tutor-ead')) . $email,

            'registro' => $n

        ];

    }

    wp_send_json_success($results);

});



/* =========================================================================
 * NOVO	HANDLER –	Registra visualização de alerta/aviso
 * ========================================================================= */

add_action('wp_ajax_tutor_ead_alert_viewed', function () {

    $user_id  = get_current_user_id();

    $alert_id = intval($_POST['alert_id'] ?? 0);



    if (!$user_id || !$alert_id) {

        wp_send_json_error(['message' => __('Parâmetros inválidos.', 'tutor-ead')]);

    }



    global $wpdb;

    $table = $wpdb->prefix . 'tutoread_alertas_views';



    $q = $wpdb->query($wpdb->prepare(

        "INSERT INTO {$table} (alert_id, user_id, views, last_view)

         VALUES (%d, %d, 1, NOW())

         ON DUPLICATE KEY UPDATE

           views = views + 1,

           last_view = NOW()",

        $alert_id, $user_id

    ));



    if ($q === false) {

        wp_send_json_error(['message' => __('Erro ao registrar visualização.', 'tutor-ead')]);

    }

    wp_send_json_success();

});

// =========================================================================
// HANDLERS PARA O DASHBOARD DE ADMIN FRONTEND
// =========================================================================

// Função de verificação de permissão reutilizável
function can_manage_tutor_ead() {
    if (!is_user_logged_in()) {
        return false;
    }
    $user = wp_get_current_user();
    $allowed_roles = ['administrator', 'tutor_admin', 'tutor_course_editor'];
    
    // Verifica se o usuário tem pelo menos uma das funções permitidas.
    if (array_intersect($allowed_roles, $user->roles)) {
        return true;
    }

    return false;
}

/**
 * AJAX Handler para buscar alunos.
 */
add_action('wp_ajax_tutoread_get_students', function () {
    check_ajax_referer('tutoread_dashboard_nonce', 'nonce');
    if (!can_manage_tutor_ead()) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }

    global $wpdb;
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

    $args = [
        'role'    => 'tutor_aluno',
        'orderby' => 'user_registered',
        'order'   => 'DESC',
        'search'  => "*{$search}*",
        'search_columns' => ['user_login', 'user_email', 'display_name'],
    ];

    $user_query = new \WP_User_Query($args);
    $students = [];
    $user_info_table = $wpdb->prefix . 'tutoread_user_info';

    foreach ($user_query->get_results() as $user) {
        $user_info = $wpdb->get_row($wpdb->prepare(
            "SELECT full_name, phone_number FROM $user_info_table WHERE user_id = %d",
            $user->ID
        ));

        $display_name = $user_info && !empty($user_info->full_name) ? $user_info->full_name : $user->display_name;
        $phone_number = $user_info ? $user_info->phone_number : '';

        $students[] = [
            'id' => $user->ID,
            'name' => $display_name,
            'email' => $user->user_email,
            'phone_number' => $phone_number,
            'registered_date' => date('d/m/Y', strtotime($user->user_registered)),
        ];
    }
    wp_send_json_success($students);
});

/**
 * AJAX Handler para adicionar um novo aluno.
 */
add_action('wp_ajax_tutoread_add_student', function () {
    check_ajax_referer('tutoread_dashboard_nonce', 'nonce');
    if (!can_manage_tutor_ead()) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }

    $email = sanitize_email($_POST['email']);
    $name = sanitize_text_field($_POST['name']);
    $password = $_POST['password']; // Obter a senha do POST

    if (!is_email($email)) {
        wp_send_json_error(['message' => 'E-mail inválido.']);
    }
    if (email_exists($email)) {
        wp_send_json_error(['message' => 'Este e-mail já está em uso.']);
    }
    if (empty($password)) {
        wp_send_json_error(['message' => 'A senha é obrigatória para novos alunos.']);
    }

    $user_id = wp_create_user($name, $password, $email);

    if (is_wp_error($user_id)) {
        wp_send_json_error(['message' => $user_id->get_error_message()]);
    }

    $user = new \WP_User($user_id);
    $user->set_role('tutor_aluno');

    // Enviar e-mail de boas-vindas (opcional, mas recomendado)
    wp_new_user_notification($user_id, null, 'admin');

    wp_send_json_success(['message' => 'Aluno adicionado com sucesso! A senha foi enviada para o e-mail do usuário.']);
});

/**
 * AJAX Handler para deletar um aluno.
 */
add_action('wp_ajax_tutoread_delete_student', function () {
    check_ajax_referer('tutoread_dashboard_nonce', 'nonce');
    if (!can_manage_tutor_ead()) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }

    $user_id = intval($_POST['user_id']);
    if ($user_id === get_current_user_id()) {
        wp_send_json_error(['message' => 'Você não pode excluir a si mesmo.']);
    }

    require_once(ABSPATH.'wp-admin/includes/user.php');
    if (wp_delete_user($user_id)) {
        wp_send_json_success(['message' => 'Aluno excluído com sucesso.']);
    } else {
        wp_send_json_error(['message' => 'Erro ao excluir o aluno.']);
    }
});

/**
 * AJAX Handler para buscar cursos.
 */
add_action('wp_ajax_tutoread_get_courses', function () {
    check_ajax_referer('tutoread_dashboard_nonce', 'nonce');
    if (!can_manage_tutor_ead()) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }

    global $wpdb;
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
    $table_name = $wpdb->prefix . 'tutoread_courses';

    $query = "SELECT * FROM $table_name";
    if (!empty($search)) {
        $query .= $wpdb->prepare(" WHERE title LIKE %s", '%' . $wpdb->esc_like($search) . '%');
    }
    $query .= " ORDER BY title ASC";

    $courses = $wpdb->get_results($query, ARRAY_A);

    // Para cada curso, buscar o nome do professor e a contagem de alunos
    foreach ($courses as $key => $course) {
        if ($course['professor_id']) {
            $professor = get_userdata($course['professor_id']);
            $courses[$key]['professor_name'] = $professor ? $professor->display_name : 'N/A';
        } else {
            $courses[$key]['professor_name'] = 'N/A';
        }
        // Contar alunos matriculados
        $matriculas_table = $wpdb->prefix . 'matriculas';
        $student_count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(id) FROM $matriculas_table WHERE course_id = %d AND role = 'aluno'", $course['id']));
        $courses[$key]['student_count'] = $student_count;
    }


    wp_send_json_success($courses);
});

/**
 * AJAX Handler para adicionar um novo curso.
 */
add_action('wp_ajax_tutoread_add_course', function () {
    check_ajax_referer('tutoread_dashboard_nonce', 'nonce');
    if (!can_manage_tutor_ead()) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }

    global $wpdb;
    $title = sanitize_text_field($_POST['title']);
    $description = sanitize_textarea_field($_POST['description']);
    $table_name = $wpdb->prefix . 'tutoread_courses';

    $result = $wpdb->insert(
        $table_name,
        ['title' => $title, 'description' => $description],
        ['%s', '%s']
    );

    if ($result) {
        wp_send_json_success(['message' => 'Curso adicionado com sucesso.']);
    } else {
        wp_send_json_error(['message' => 'Erro ao adicionar o curso.']);
    }
});

/**
 * AJAX Handler para deletar um curso.
 */
add_action('wp_ajax_tutoread_delete_course', function () {
    check_ajax_referer('tutoread_dashboard_nonce', 'nonce');
    if (!can_manage_tutor_ead()) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }

    global $wpdb;
    $course_id = intval($_POST['course_id']);
    $table_name = $wpdb->prefix . 'tutoread_courses';

    $result = $wpdb->delete($table_name, ['id' => $course_id], ['%d']);

    if ($result) {
        wp_send_json_success(['message' => 'Curso excluído com sucesso.']);
    } else {
        wp_send_json_error(['message' => 'Erro ao excluir o curso.']);
    }
});

// =========================================================================
// HANDLERS PARA O DASHBOARD DE PROFESSOR FRONTEND
// =========================================================================

/**
 * AJAX Handler para buscar os cursos de um professor.
 */
add_action('wp_ajax_tutoread_get_professor_courses', function () {
    check_ajax_referer('tutoread_dashboard_nonce', 'nonce');
    if (!current_user_can('tutor_professor')) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }

    global $wpdb;
    $professor_id = get_current_user_id();
    
    $courses_table = $wpdb->prefix . 'tutoread_courses';
    $matriculas_table = $wpdb->prefix . 'matriculas';

    $courses = $wpdb->get_results($wpdb->prepare(
        "SELECT id, title FROM $courses_table WHERE professor_id = %d ORDER BY title ASC",
        $professor_id
    ), ARRAY_A);

    foreach ($courses as $key => $course) {
        $student_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(id) FROM $matriculas_table WHERE course_id = %d AND role = 'aluno'",
            $course['id']
        ));
        $courses[$key]['student_count'] = $student_count;
    }

    wp_send_json_success($courses);
});

/**
 * AJAX Handler para buscar os alunos de um professor.
 */
add_action('wp_ajax_tutoread_get_professor_students', function () {
    check_ajax_referer('tutoread_dashboard_nonce', 'nonce');
    if (!current_user_can('tutor_professor')) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }

    global $wpdb;
    $professor_id = get_current_user_id();

    $courses_table = $wpdb->prefix . 'tutoread_courses';
    $matriculas_table = $wpdb->prefix . 'matriculas';
    $users_table = $wpdb->prefix . 'users';

    // 1. Encontrar os IDs dos cursos do professor
    $course_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT id FROM $courses_table WHERE professor_id = %d",
        $professor_id
    ));

    if (empty($course_ids)) {
        wp_send_json_success([]); // Envia array vazio se o professor não tem cursos
        return;
    }

    // 2. Buscar alunos matriculados nesses cursos
    $course_ids_placeholder = implode(',', array_fill(0, count($course_ids), '%d'));
    
    $students_data = $wpdb->get_results($wpdb->prepare(
        "SELECT u.ID, u.display_name, u.user_email, m.course_id, c.title as course_title
         FROM $matriculas_table m
         JOIN $users_table u ON m.user_id = u.ID
         JOIN $courses_table c ON m.course_id = c.id
         WHERE m.course_id IN ($course_ids_placeholder) AND m.role = 'aluno'
         ORDER BY u.display_name ASC",
        $course_ids
    ), ARRAY_A);

    // 3. Agrupar cursos por aluno
    $students = [];
    foreach ($students_data as $student) {
        $student_id = $student['ID'];
        if (!isset($students[$student_id])) {
            $students[$student_id] = [
                'id' => $student_id,
                'name' => $student['display_name'],
                'email' => $student['user_email'],
                'courses' => []
            ];
        }
        $students[$student_id]['courses'][] = $student['course_title'];
    }

    // Formatar a lista de cursos para string
    foreach ($students as $key => $student) {
        $students[$key]['courses_list'] = implode(', ', $student['courses']);
    }

    wp_send_json_success(array_values($students));
});

// =========================================================================
// HANDLERS PARA EDIÇÃO NO DASHBOARD DE ADMIN
// =========================================================================

/**
 * AJAX Handler para buscar os detalhes de um aluno.
 */
add_action('wp_ajax_tutoread_get_student_details', function() {
    check_ajax_referer('tutoread_dashboard_nonce', 'nonce');
    if (!can_manage_tutor_ead()) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }

    global $wpdb;
    $user_id = intval($_POST['user_id']);
    $user = get_userdata($user_id);

    if (!$user) {
        wp_send_json_error(['message' => 'Aluno não encontrado.']);
    }

    // Buscar telefone da tabela user_info
    $user_info_table = $wpdb->prefix . 'tutoread_user_info';
    $phone_number = $wpdb->get_var($wpdb->prepare(
        "SELECT phone_number FROM $user_info_table WHERE user_id = %d",
        $user_id
    ));

    // Buscar todos os cursos e os cursos do aluno
    $courses_table = $wpdb->prefix . 'tutoread_courses';
    $matriculas_table = $wpdb->prefix . 'matriculas';
    
    // Garante que todos os IDs de cursos sejam inteiros
    $all_courses = array_map(function($course) {
        $course['id'] = intval($course['id']);
        return $course;
    }, $wpdb->get_results("SELECT id, title FROM $courses_table ORDER BY title ASC", ARRAY_A));

    $enrolled_courses = $wpdb->get_col($wpdb->prepare(
        "SELECT course_id FROM $matriculas_table WHERE user_id = %d AND role = 'aluno'",
        $user_id
    ));

    wp_send_json_success([
        'id' => $user->ID,
        'name' => $user->display_name,
        'email' => $user->user_email,
        'phone_number' => $phone_number,
        'all_courses' => $all_courses,
        'enrolled_course_ids' => array_map('intval', $enrolled_courses),
    ]);
});

/**
 * AJAX Handler para buscar os detalhes de um curso.
 */
add_action('wp_ajax_tutoread_get_course_details', function() {
    check_ajax_referer('tutoread_dashboard_nonce', 'nonce');
    if (!can_manage_tutor_ead()) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }

    global $wpdb;
    $course_id = intval($_POST['course_id']);
    $course = $wpdb->get_row(
        $wpdb->prepare("SELECT id, title, description FROM {$wpdb->prefix}tutoread_courses WHERE id = %d", $course_id),
        ARRAY_A
    );

    if (!$course) {
        wp_send_json_error(['message' => 'Curso não encontrado.']);
    }

    wp_send_json_success($course);
});

/**
 * AJAX Handler para atualizar um aluno.
 */
add_action('wp_ajax_tutoread_update_student', function() {
    check_ajax_referer('tutoread_dashboard_nonce', 'nonce');
    if (!can_manage_tutor_ead()) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }

    global $wpdb;
    $user_id = intval($_POST['user_id']);
    $email = sanitize_email($_POST['email']);
    $name = sanitize_text_field($_POST['name']);
    $phone_number = sanitize_text_field($_POST['phone_number']);
    $course_ids = isset($_POST['course_ids']) ? array_map('intval', $_POST['course_ids']) : [];

    if (!is_email($email)) {
        wp_send_json_error(['message' => 'E-mail inválido.']);
    }
    
    $existing_user = get_user_by('email', $email);
    if ($existing_user && $existing_user->ID != $user_id) {
        wp_send_json_error(['message' => 'Este e-mail já está em uso por outro usuário.']);
    }

    // Atualiza dados básicos do usuário
    $user_data = [
        'ID' => $user_id,
        'display_name' => $name,
        'user_email' => $email,
    ];

    $password = isset($_POST['password']) ? $_POST['password'] : '';
    if (!empty($password)) {
        $user_data['user_pass'] = $password; // wp_update_user will hash this
    }

    $result = wp_update_user($user_data);

    if (is_wp_error($result)) {
        wp_send_json_error(['message' => $result->get_error_message()]);
    }

    // Atualiza/Insere o telefone na tabela user_info
    $user_info_table = $wpdb->prefix . 'tutoread_user_info';
    $wpdb->replace(
        $user_info_table,
        ['user_id' => $user_id, 'phone_number' => $phone_number],
        ['%d', '%s']
    );

    // Atualiza as matrículas do aluno
    $matriculas_table = $wpdb->prefix . 'matriculas';
    // 1. Remove todas as matrículas existentes do aluno
    $wpdb->delete($matriculas_table, ['user_id' => $user_id, 'role' => 'aluno'], ['%d', '%s']);
    // 2. Insere as novas matrículas
    if (!empty($course_ids)) {
        foreach ($course_ids as $course_id) {
            $wpdb->insert(
                $matriculas_table,
                ['user_id' => $user_id, 'course_id' => $course_id, 'role' => 'aluno'],
                ['%d', '%d', '%s']
            );
        }
    }

    wp_send_json_success(['message' => 'Aluno atualizado com sucesso.']);
});

/**
 * AJAX Handler para atualizar um curso.
 */
add_action('wp_ajax_tutoread_update_course', function() {
    check_ajax_referer('tutoread_dashboard_nonce', 'nonce');
    if (!can_manage_tutor_ead()) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }

    global $wpdb;
    $course_id = intval($_POST['course_id']);
    $title = sanitize_text_field($_POST['title']);
    $description = sanitize_textarea_field($_POST['description']);

    $result = $wpdb->update(
        $wpdb->prefix . 'tutoread_courses',
        ['title' => $title, 'description' => $description],
        ['id' => $course_id],
        ['%s', '%s'],
        ['%d']
    );

    if ($result === false) {
        wp_send_json_error(['message' => 'Erro ao atualizar o curso ou nenhum dado foi alterado.']);
    }

    wp_send_json_success(['message' => 'Curso atualizado com sucesso.']);
});

// =========================================================================
// HANDLERS PARA O BOLETIM DO PROFESSOR
// =========================================================================

/**
 * Busca alunos de um curso específico para o professor logado.
 */
add_action('wp_ajax_tutoread_get_students_for_course_boletim', function() {
    check_ajax_referer('tutoread_dashboard_nonce', 'nonce');
    if (!current_user_can('tutor_professor')) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }

    global $wpdb;
    $course_id = intval($_POST['course_id']);
    $professor_id = get_current_user_id();

    // Validação extra: o curso pertence a este professor?
    $is_his_course = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->prefix}tutoread_courses WHERE id = %d AND professor_id = %d",
        $course_id, $professor_id
    ));

    if (!$is_his_course) {
        wp_send_json_error(['message' => 'Curso inválido.']);
    }

    $matriculas_table = $wpdb->prefix . 'matriculas';
    $users_table = $wpdb->prefix . 'users';

    $students = $wpdb->get_results($wpdb->prepare(
        "SELECT u.ID, u.display_name FROM $matriculas_table m
         JOIN $users_table u ON m.user_id = u.ID
         WHERE m.course_id = %d AND m.role = 'aluno'
         ORDER BY u.display_name ASC",
        $course_id
    ), ARRAY_A);

    wp_send_json_success($students);
});

/**
 * Busca atividades de um curso específico.
 */
add_action('wp_ajax_tutoread_get_activities_for_course_boletim', function() {
    check_ajax_referer('tutoread_dashboard_nonce', 'nonce');
    if (!current_user_can('tutor_professor')) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }
    
    global $wpdb;
    $course_id = intval($_POST['course_id']);
    
    $activities_table = $wpdb->prefix . 'atividades';
    $assoc_table = $wpdb->prefix . 'tutoread_course_activities';

    $activities = $wpdb->get_results($wpdb->prepare(
        "SELECT a.id, a.titulo FROM $activities_table a
         JOIN $assoc_table ca ON a.id = ca.activity_id
         WHERE ca.course_id = %d
         ORDER BY a.titulo ASC",
        $course_id
    ), ARRAY_A);

    wp_send_json_success($activities);
});

/**
 * Salva uma nova entrada no boletim.
 */
add_action('wp_ajax_tutoread_save_boletim_entry', function() {
    check_ajax_referer('tutoread_dashboard_nonce', 'nonce');
    if (!current_user_can('tutor_professor')) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }

    global $wpdb;
    $professor_id = get_current_user_id();
    
    $course_id = intval($_POST['course_id']);
    $aluno_id = intval($_POST['aluno_id']);
    $atividade_id = intval($_POST['atividade_id']);
    $nota = floatval($_POST['nota']);
    $feedback = sanitize_textarea_field($_POST['feedback']);

    // Validações de segurança
    $is_his_course = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}tutoread_courses WHERE id = %d AND professor_id = %d", $course_id, $professor_id));
    if (!$is_his_course) {
        wp_send_json_error(['message' => 'Você não tem permissão para lançar notas neste curso.']);
    }

    $is_student_in_course = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}matriculas WHERE user_id = %d AND course_id = %d", $aluno_id, $course_id));
    if (!$is_student_in_course) {
        wp_send_json_error(['message' => 'Este aluno não está matriculado no curso selecionado.']);
    }

    $course = $wpdb->get_row($wpdb->prepare("SELECT title FROM {$wpdb->prefix}tutoread_courses WHERE id = %d", $course_id));
    $activity = $wpdb->get_row($wpdb->prepare("SELECT titulo FROM {$wpdb->prefix}atividades WHERE id = %d", $atividade_id));

    $result = $wpdb->insert(
        $wpdb->prefix . 'boletins',
        [
            'aluno_id' => $aluno_id,
            'course_id' => $course_id,
            'course_title' => $course->title,
            'atividade_id' => $atividade_id,
            'atividade_title' => $activity->titulo,
            'nota' => $nota,
            'feedback' => $feedback,
        ],
        ['%d', '%d', '%s', '%d', '%s', '%f', '%s']
    );

    if ($result) {
        wp_send_json_success(['message' => 'Nota lançada com sucesso!']);
    } else {
        wp_send_json_error(['message' => 'Erro ao salvar a nota no banco de dados.']);
    }
});

/**
 * Busca o histórico de boletins de um professor.
 */
add_action('wp_ajax_tutoread_get_boletim_history', function() {
    check_ajax_referer('tutoread_dashboard_nonce', 'nonce');
    if (!current_user_can('tutor_professor')) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }

    global $wpdb;
    $professor_id = get_current_user_id();

    $course_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}tutoread_courses WHERE professor_id = %d",
        $professor_id
    ));

    if (empty($course_ids)) {
        wp_send_json_success([]);
        return;
    }
    
    $course_ids_placeholder = implode(',', array_fill(0, count($course_ids), '%d'));

    $history = $wpdb->get_results($wpdb->prepare(
        "SELECT b.course_title, b.atividade_title, b.nota, b.data_atualizacao, u.display_name as student_name
         FROM {$wpdb->prefix}boletins b
         JOIN {$wpdb->prefix}users u ON b.aluno_id = u.ID
         WHERE b.course_id IN ($course_ids_placeholder)
         ORDER BY b.data_atualizacao DESC",
       $course_ids
    ), ARRAY_A);

    // Formatar data
    foreach ($history as $key => $item) {
        $history[$key]['data_formatada'] = date('d/m/Y H:i', strtotime($item['data_atualizacao']));
    }

    wp_send_json_success($history);
});

// =========================================================================
// HANDLERS PARA GERENCIAMENTO DE PROFESSORES (ADMIN)
// =========================================================================

/**
 * AJAX Handler para buscar professores.
 */
add_action('wp_ajax_tutoread_get_professores', function() {
    check_ajax_referer('tutoread_dashboard_nonce', 'nonce');
    if (!can_manage_tutor_ead()) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }

    global $wpdb;
    $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

    $args = [
        'role'    => 'tutor_professor',
        'orderby' => 'display_name',
        'order'   => 'ASC',
        'search'  => "*{$search}*",
        'search_columns' => ['user_login', 'user_email', 'display_name'],
    ];

    $user_query = new \WP_User_Query($args);
    $professores = [];
    $courses_table = $wpdb->prefix . 'tutoread_courses';

    foreach ($user_query->get_results() as $user) {
        $courses = $wpdb->get_col($wpdb->prepare(
            "SELECT title FROM $courses_table WHERE professor_id = %d",
            $user->ID
        ));
        
        $professores[] = [
            'id' => $user->ID,
            'name' => $user->display_name,
            'email' => $user->user_email,
            'courses_list' => !empty($courses) ? implode(', ', $courses) : 'Nenhum',
        ];
    }
    wp_send_json_success($professores);
});

/**
 * AJAX Handler para adicionar um novo professor.
 */
add_action('wp_ajax_tutoread_add_professor', function() {
    check_ajax_referer('tutoread_dashboard_nonce', 'nonce');
    if (!can_manage_tutor_ead()) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }

    $email = sanitize_email($_POST['email']);
    $name = sanitize_text_field($_POST['name']);
    $password = sanitize_text_field($_POST['password']);

    if (empty($password)) {
        wp_send_json_error(['message' => 'A senha é obrigatória.']);
    }
    if (!is_email($email) || email_exists($email)) {
        wp_send_json_error(['message' => 'E-mail inválido ou já existente.']);
    }

    $user_id = wp_create_user($name, $password, $email);
    if (is_wp_error($user_id)) {
        wp_send_json_error(['message' => $user_id->get_error_message()]);
    }

    $user = new \WP_User($user_id);
    $user->set_role('tutor_professor');
    wp_new_user_notification($user_id, null, 'admin');

    wp_send_json_success(['message' => 'Professor adicionado com sucesso!']);
});

/**
 * AJAX Handler para buscar as associações de cursos de um professor.
 */
add_action('wp_ajax_tutoread_get_professor_course_assignments', function() {
    check_ajax_referer('tutoread_dashboard_nonce', 'nonce');
    if (!can_manage_tutor_ead()) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }

    global $wpdb;
    $professor_id = intval($_POST['professor_id']);
    $courses_table = $wpdb->prefix . 'tutoread_courses';
    $matriculas_table = $wpdb->prefix . 'matriculas';

    // Garante que todos os IDs de cursos sejam inteiros
    $all_courses = array_map(function($course) {
        $course['id'] = intval($course['id']);
        return $course;
    }, $wpdb->get_results("SELECT id, title FROM $courses_table ORDER BY title ASC", ARRAY_A));

    $assigned_courses_as_professor = $wpdb->get_col($wpdb->prepare(
        "SELECT id FROM $courses_table WHERE professor_id = %d",
        $professor_id
    ));

    $assigned_courses_as_student = $wpdb->get_col($wpdb->prepare(
        "SELECT course_id FROM $matriculas_table WHERE user_id = %d",
        $professor_id
    ));

    $assigned_courses = array_unique(array_merge($assigned_courses_as_professor, $assigned_courses_as_student));

    wp_send_json_success([
        'all_courses' => $all_courses,
        'assigned_ids' => array_map('intval', array_values($assigned_courses)),
    ]);
});

/**
 * AJAX Handler para atualizar as associações de cursos de um professor.
 */
add_action('wp_ajax_tutoread_update_professor_assignments', function() {
    check_ajax_referer('tutoread_dashboard_nonce', 'nonce');
    if (!can_manage_tutor_ead()) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }

    global $wpdb;
    $professor_id = intval($_POST['professor_id']);
    $course_ids = isset($_POST['course_ids']) ? array_map('intval', $_POST['course_ids']) : [];
    $courses_table = $wpdb->prefix . 'tutoread_courses';

    // 1. Desassociar o professor de TODOS os cursos
    $wpdb->update(
        $courses_table,
        ['professor_id' => null],
        ['professor_id' => $professor_id],
        ['%d'],
        ['%d']
    );

    // 2. Reassociar o professor aos cursos selecionados
    if (!empty($course_ids)) {
        $ids_placeholder = implode(',', array_fill(0, count($course_ids), '%d'));
        $wpdb->query($wpdb->prepare(
            "UPDATE $courses_table SET professor_id = %d WHERE id IN ($ids_placeholder)",
            array_merge([$professor_id], $course_ids)
        ));
    }

    wp_send_json_success(['message' => 'Cursos do professor atualizados com sucesso.']);
});

/**
 * AJAX Handler para migrar dados da usermeta para a tabela user_info.
 */
add_action('wp_ajax_tutoread_migrate_phone_numbers', function() {
    check_ajax_referer('tutoread_dashboard_nonce', 'nonce');
    if (!can_manage_tutor_ead()) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }

    global $wpdb;
    $user_query = new \WP_User_Query(['role' => 'tutor_aluno']);
    $students = $user_query->get_results();
    $migrated_count = 0;
    $migration_log = []; // Log detalhado

    foreach ($students as $student) {
        $phone_number = get_user_meta($student->ID, 'celular', true);
        $full_name = $student->display_name;

        if (empty($phone_number) && empty($full_name)) {
            continue; // Pula para o próximo aluno se não há dados para migrar
        }

        $user_info_table = $wpdb->prefix . 'tutoread_user_info';
        $existing_data = $wpdb->get_row($wpdb->prepare("SELECT * FROM $user_info_table WHERE user_id = %d", $student->ID));
        
        $data_to_migrate = [];
        $migrated_fields = [];

        // Verifica se o telefone precisa ser migrado
        if (empty($existing_data->phone_number) && !empty($phone_number)) {
            $data_to_migrate['phone_number'] = $phone_number;
            $migrated_fields[] = 'Telefone';
        }

        // Verifica se o nome completo precisa ser migrado
        if (empty($existing_data->full_name) && !empty($full_name)) {
            $data_to_migrate['full_name'] = $full_name;
            $migrated_fields[] = 'Nome Completo';
        }

        if (!empty($data_to_migrate)) {
            if (empty($existing_data)) {
                // INSERE um novo registro se não existir
                $data_to_migrate['user_id'] = $student->ID;
                $wpdb->insert($user_info_table, $data_to_migrate);
            } else {
                // ATUALIZA o registro existente apenas com os campos novos
                $wpdb->update(
                    $user_info_table,
                    $data_to_migrate,
                    ['user_id' => $student->ID] // WHERE
                );
            }

            // Limpa o meta antigo apenas se o telefone foi migrado
            if (in_array('Telefone', $migrated_fields)) {
                delete_user_meta($student->ID, 'celular');
            }
            
            $migrated_count++;
            $log_message = sprintf(
                "Aluno '%s' (ID: %d): %s migrado(s).",
                $student->display_name,
                $student->ID,
                implode(' e ', $migrated_fields)
            );
            $migration_log[] = $log_message;
        }
    }

    $summary_message = sprintf('%d registros de alunos foram migrados ou atualizados com sucesso.', $migrated_count);
    
    wp_send_json_success([
        'message' => $summary_message,
        'log' => $migration_log
    ]);
});

/**
 * AJAX Handler para verificar se há dados de alunos a serem migrados.
 */
add_action('wp_ajax_tutoread_check_unmigrated_data', function() {
    check_ajax_referer('tutoread_dashboard_nonce', 'nonce');
    if (!can_manage_tutor_ead()) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }

    global $wpdb;
    $has_unmigrated_data = false;

    $user_query = new \WP_User_Query(['role' => 'tutor_aluno']);
    $students = $user_query->get_results();

    foreach ($students as $student) {
        $usermeta_celular = get_user_meta($student->ID, 'celular', true);
        $display_name = $student->display_name;

        $user_info_data = $wpdb->get_row($wpdb->prepare(
            "SELECT phone_number, full_name FROM {$wpdb->prefix}tutoread_user_info WHERE user_id = %d",
            $student->ID
        ));

        // Verifica se o celular precisa ser migrado
        if (!empty($usermeta_celular)) {
            if (!$user_info_data || empty($user_info_data->phone_number)) {
                $has_unmigrated_data = true;
                break; 
            }
        }

        // Verifica se o nome completo precisa ser migrado
        // Consideramos que precisa migrar se display_name não for vazio e full_name na nova tabela for vazio
        if (!empty($display_name)) {
            if (!$user_info_data || empty($user_info_data->full_name)) {
                $has_unmigrated_data = true;
                break; 
            }
        }
    }

    wp_send_json_success(['has_unmigrated_data' => $has_unmigrated_data]);
});


add_action('wp_ajax_tutoread_get_student_activity_logs', function() {
    check_ajax_referer('tutoread_dashboard_nonce', 'nonce');
    if (!can_manage_tutor_ead()) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }

    global $wpdb;
    $student_id = intval($_POST['student_id']);
    $log_table = $wpdb->prefix . 'tutoread_student_activity_log';

    $logs = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$log_table} WHERE user_id = %d ORDER BY access_time DESC LIMIT 20",
        $student_id
    ), ARRAY_A);

    wp_send_json_success($logs);
});

/* =========================================================================
 * Handler: tutoread_update_user_phone
 * ========================================================================= */
add_action('wp_ajax_tutoread_update_user_phone', function () {
    check_ajax_referer('tutoread_update_phone_nonce', 'nonce');

    global $wpdb;
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    $phone_number = isset($_POST['phone_number']) ? sanitize_text_field($_POST['phone_number']) : '';

    if (empty($user_id) || empty($phone_number)) {
        wp_send_json_error(['message' => 'Dados inválidos.']);
    }

    // Opcional: Verifique se o usuário que está fazendo a requisição é o próprio usuário ou um admin
    if ($user_id !== get_current_user_id() && !current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Permissão negada.']);
    }

    $table_name = $wpdb->prefix . 'tutoread_user_info';

    // Use REPLACE para inserir ou atualizar
    $result = $wpdb->replace(
        $table_name,
        [
            'user_id' => $user_id,
            'phone_number' => $phone_number
        ],
        ['%d', '%s']
    );

    if ($result === false) {
        wp_send_json_error(['message' => 'Erro ao salvar o telefone no banco de dados.']);
    } else {
        wp_send_json_success(['message' => 'Telefone atualizado com sucesso!']);
    }
});

add_action('wp_ajax_tutoread_update_profile', function () {
    check_ajax_referer('tutoread_update_profile_nonce', 'nonce');

    if ( !is_user_logged_in() ) {
        wp_send_json_error(['message' => 'Usuário não autenticado.']);
    }

    global $wpdb;
    $user_id = get_current_user_id();

    $full_name = sanitize_text_field($_POST['full_name']);
    $phone_number = sanitize_text_field($_POST['phone_number']);
    $bio = sanitize_textarea_field($_POST['bio']);

    $data_to_update = [
        'full_name' => $full_name,
        'phone_number' => $phone_number,
        'bio' => $bio
    ];

    if ( !empty($_FILES['avatar']) ) {
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');

        $attachment_id = media_handle_upload('avatar', 0);

        if ( is_wp_error($attachment_id) ) {
            wp_send_json_error(['message' => 'Erro ao fazer upload da imagem: ' . $attachment_id->get_error_message()]);
        } else {
            $data_to_update['profile_photo_url'] = wp_get_attachment_url($attachment_id);
        }
    }

    $table_name = $wpdb->prefix . 'tutoread_user_info';

    $result = $wpdb->replace(
        $table_name,
        array_merge(['user_id' => $user_id], $data_to_update),
        array_merge(['%d'], array_fill(0, count($data_to_update), '%s'))
    );

    if ($result === false) {
        wp_send_json_error(['message' => 'Erro ao atualizar o perfil.']);
    } else {
        wp_send_json_success(['message' => 'Perfil atualizado com sucesso!']);
    }
});

add_action('wp_ajax_tutoread_remove_avatar', function () {
    check_ajax_referer('tutoread_remove_avatar_nonce', 'nonce');

    if ( !is_user_logged_in() ) {
        wp_send_json_error(['message' => 'Usuário não autenticado.']);
    }

    global $wpdb;
    $user_id = get_current_user_id();
    $table_name = $wpdb->prefix . 'tutoread_user_info';

    $result = $wpdb->update(
        $table_name,
        ['profile_photo_url' => ''],
        ['user_id' => $user_id],
        ['%s'],
        ['%d']
    );

    if ($result === false) {
        wp_send_json_error(['message' => 'Erro ao remover a foto de perfil.']);
    } else {
        wp_send_json_success([
            'message' => 'Foto de perfil removida com sucesso!',
            'default_avatar_url' => get_avatar_url($user_id, array('size' => 120))
        ]);
    }
});

add_action('wp_ajax_tutoread_upload_avatar', function () {
    check_ajax_referer('tutoread_upload_avatar_nonce', 'nonce');

    if ( !is_user_logged_in() ) {
        wp_send_json_error(['message' => 'Usuário não autenticado.']);
    }

    if ( empty($_FILES['avatar']) ) {
        wp_send_json_error(['message' => 'Nenhuma imagem enviada.']);
    }

    require_once(ABSPATH . 'wp-admin/includes/image.php');
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/media.php');

    $attachment_id = media_handle_upload('avatar', 0);

    if ( is_wp_error($attachment_id) ) {
        wp_send_json_error(['message' => 'Erro ao fazer upload da imagem: ' . $attachment_id->get_error_message()]);
    }

    global $wpdb;
    $user_id = get_current_user_id();
    $table_name = $wpdb->prefix . 'tutoread_user_info';
    $profile_photo_url = wp_get_attachment_url($attachment_id);

    $result = $wpdb->replace(
        $table_name,
        [
            'user_id' => $user_id,
            'profile_photo_url' => $profile_photo_url
        ],
        ['%d', '%s']
    );

    if ($result === false) {
        wp_send_json_error(['message' => 'Erro ao salvar a URL da foto de perfil no banco de dados.']);
    } else {
        wp_send_json_success([
            'message' => 'Foto de perfil atualizada com sucesso!',
            'avatar_url' => $profile_photo_url
        ]);
    }
});

/* =========================================================================
 * Handler: tutoread_submit_quiz
 * Recebe as respostas do quiz via AJAX para processamento em segundo plano.
 * ========================================================================= */
add_action('wp_ajax_tutoread_submit_quiz', function () {
    // Validação de segurança
    check_ajax_referer('quiz_submission_nonce', 'nonce');
    if (!is_user_logged_in()) {
        wp_send_json_error(['message' => 'Usuário não autenticado.']);
        return;
    }

    global $wpdb;
    $user_id = get_current_user_id();
    $activity_id = isset($_POST['activity_id']) ? intval($_POST['activity_id']) : 0;
    // As respostas vêm como um array associativo [pergunta_id => alternativa_id]
    $answers = isset($_POST['answers']) ? (array) $_POST['answers'] : [];

    if (empty($activity_id) || empty($answers)) {
        wp_send_json_error(['message' => 'Dados incompletos.']);
        return;
    }

    // LÓGICA DE AVALIAÇÃO E SALVAMENTO
    $respostas_table = $wpdb->prefix . 'respostas';

    // 1. Calcular o número da tentativa
    $last_attempt = $wpdb->get_var($wpdb->prepare(
        "SELECT MAX(tentativa) FROM {$respostas_table} WHERE atividade_id = %d AND aluno_id = %d",
        $activity_id, $user_id
    ));
    $new_attempt = $last_attempt ? $last_attempt + 1 : 1;

    // @TODO: A lógica de cálculo de nota pode ser inserida aqui.
    // Por enquanto, a nota será 0.
    $nota_calculada = 0;

    // 2. Inserir no banco de dados
    $result = $wpdb->insert(
        $respostas_table,
        [
            'atividade_id' => $activity_id,
            'aluno_id'     => $user_id,
            'tentativa'    => $new_attempt,
            'respostas'    => json_encode($answers), // Salva as respostas como JSON
            'nota_obtida'  => $nota_calculada,
            'data_resposta'=> current_time('mysql')
        ],
        ['%d', '%d', '%d', '%s', '%f', '%s']
    );

    // 3. Verificar o resultado e enviar a resposta JSON
    if ($result === false) {
        wp_send_json_error(['message' => 'Ocorreu um erro ao salvar suas respostas no banco de dados.']);
    } else {
        wp_send_json_success(['message' => 'Respostas salvas com sucesso!']);
    }
});



/* ========================================================================== 
 * HANDLER PARA A VISUALIZAÇÃO POR NÍVEIS
 * ========================================================================== */

add_action('wp_ajax_tutor_ead_get_level_content', __NAMESPACE__ . '\tutor_ead_get_level_content_callback');
add_action('wp_ajax_nopriv_tutor_ead_get_level_content', __NAMESPACE__ . '\tutor_ead_get_level_content_callback');

function tutor_ead_get_level_content_callback() {
    check_ajax_referer('tutor_ead_levels_nonce', 'nonce');

    global $wpdb;
    $module_id = isset($_POST['module_id']) ? intval($_POST['module_id']) : 0;
    $unit_id = isset($_POST['unit_id']) ? intval($_POST['unit_id']) : 0;

    if (empty($module_id) && empty($unit_id)) {
        wp_send_json_error(['message' => 'ID do item não fornecido.']);
    }

    $parent_id = $unit_id > 0 ? $unit_id : $module_id;

    // Primeiro, verifique se há unidades filhas
    $units = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}tutoread_modules WHERE parent_id = %d ORDER BY module_order ASC",
        $parent_id
    ), ARRAY_A);

    $html = '';

    if (!empty($units)) {
        // Se houver unidades, liste-as
        foreach ($units as $unit) {
            $html .= '<div class="level-unit-item">';
            $html .= '<h4 class="level-item-title unit-title-level" data-unit-id="' . esc_attr($unit['id']) . '">';
            $html .= '<span class="level-item-icon dashicons dashicons-arrow-right-alt2"></span>';
            $html .= esc_html($unit['title']);
            $html .= '</h4>';
            $html .= '<div class="level-content-container" style="display: none;"></div>';
            $html .= '</div>';
        }
    } else {
        // Se não houver unidades, liste as aulas do módulo/unidade pai
        $lessons = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}tutoread_lessons WHERE module_id = %d ORDER BY lesson_order ASC",
            $parent_id
        ), ARRAY_A);

        if (!empty($lessons)) {
            $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0; // Correctly get course_id from POST
            foreach ($lessons as $lesson) {
                $lesson_url = add_query_arg(['course_id' => $course_id, 'lesson_id' => $lesson['id']], get_permalink(get_page_by_path('visualizar-curso')));
                
                $thumb_url = TUTOR_EAD_IMG_URL . 'default-thumbnail.png';
                if (!empty($lesson['video_url'])) {
                    $video_url = $lesson['video_url'];
                    if (preg_match('/\.pdf$/i', $video_url)) {
                        $thumb_url = TUTOR_EAD_IMG_URL . 'pdf.png';
                    } elseif (preg_match('/[\?\&]v=([^\?\&]+)/', $video_url, $matches)) {
                        $thumb_url = 'https://img.youtube.com/vi/' . $matches[1] . '/mqdefault.jpg';
                    }
                }

                $html .= '<a href="' . esc_url($lesson_url) . '" class="lesson-card">';
                $html .= '    <div class="card-thumbnail" style="background-image: url(\'' . esc_url($thumb_url) . '\');"></div>';
                $html .= '    <div class="card-content"><span class="card-title">' . esc_html($lesson['title']) . '</span></div>';
                $html .= '</a>';
            }
        } else {
            $html = '<p>Nenhum conteúdo disponível neste item.</p>';
        }
    }

    if (empty($html)) {
        $html = '<p>Nenhum conteúdo encontrado.</p>';
    }

    wp_send_json_success(['html' => $html]);
}
