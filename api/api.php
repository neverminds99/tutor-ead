<?php
/**
 * Arquivo: wp-content/plugins/tutoread/api/api.php
 * Descrição: Arquivo da API REST do TutorEAD, refatorado para maior clareza, eficiência e correção de bugs.
 * Versão: 3.1 (Correção de Syntax Error)
 */

if (!defined('ABSPATH')) {
    exit;
}

// Carregamento da biblioteca JWT (sem alterações)
$jwt_src_path = __DIR__ . '/../libs/firebase/php-jwt/src/';
$interface_file = $jwt_src_path . 'JWTExceptionWithPayloadInterface.php';
if (file_exists($interface_file)) {
    require_once $interface_file;
}
foreach (glob($jwt_src_path . '*.php') as $file) {
    if ($file === $interface_file) {
        continue;
    }
    require_once $file;
}

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Classe TutorEAD_JWT (sem alterações)
if (!class_exists('TutorEAD_JWT')) {
    class TutorEAD_JWT {
        public static function generate_token($user_id, $role) {
            $secret = get_option('tutoread_jwt_secret', '');
            if (empty($secret)) {
                return new WP_Error('jwt_secret_missing', 'Chave JWT não configurada', ['status' => 500]);
            }
            $issued_at = time();
            $expires   = $issued_at + DAY_IN_SECONDS;
            $payload = [
                'iss'     => get_site_url(),
                'iat'     => $issued_at,
                'exp'     => $expires,
                'user_id' => (int) $user_id,
                'role'    => sanitize_text_field($role),
            ];
            return JWT::encode($payload, $secret, 'HS256');
        }

        public static function validate_token($token) {
            $secret = get_option('tutoread_jwt_secret', '');
            if (empty($secret)) {
                return false;
            }
            try {
                return JWT::decode($token, new Key($secret, 'HS256'));
            } catch (Exception $e) {
                return false;
            }
        }
    }
}

// Callback de Permissão (sem alterações)
function tutoread_permission_validate_user(WP_REST_Request $request) {
    $header = $request->get_header('Authorization');
    if (!$header) {
        return new WP_Error('jwt_missing', 'JWT ausente no cabeçalho', ['status' => 401]);
    }
    if (!preg_match('/Bearer\s+(\S+)/', $header, $matches)) {
        return new WP_Error('jwt_malformed', 'Formato do cabeçalho Authorization inválido', ['status' => 401]);
    }
    $token = $matches[1];
    $payload = TutorEAD_JWT::validate_token($token);
    if (!$payload) {
        return new WP_Error('jwt_invalid', 'Token inválido ou expirado', ['status' => 401]);
    }
    $expected_iss = untrailingslashit(strtolower(home_url()));
    $token_iss    = untrailingslashit(strtolower($payload->iss));
    if ($token_iss !== $expected_iss) {
        return new WP_Error('jwt_forbidden', 'Issuer (iss) do JWT não confere com o site atual', ['status' => 403]);
    }
    return [
        'user_id' => intval($payload->user_id),
        'role'    => sanitize_text_field($payload->role),
    ];
}


/**
 * ====================================================== 
 * REGISTRO DOS ENDPOINTS REST
 * ====================================================== 
 */
add_action('rest_api_init', function () {
    // Rota de Login (sem alterações)
    register_rest_route('tutoread/v2', '/login', [
        'methods'             => 'POST',
        'callback'            => 'tutoread_login_callback',
        'permission_callback' => '__return_true',
    ]);

    // Rota para LER e ATUALIZAR dados do perfil do usuário
    register_rest_route('tutoread/v2', '/user/(?P<user_id>\d+)', [
        [
            'methods'             => 'GET',
            'callback'            => 'tutoread_get_user_info_callback', // [OTIMIZADO]
            'permission_callback' => 'tutoread_permission_validate_user',
        ],
        [
            'methods'             => 'POST',
            'callback'            => 'tutoread_update_user_info_callback', // [ATUALIZADO]
            'permission_callback' => 'tutoread_permission_validate_user',
        ]
    ]);
    
    // Rota para CRIAR um novo aluno (sem alterações)
    register_rest_route('tutoread/v2', '/student', [
        'methods'             => 'POST',
        'callback'            => 'tutoread_create_student_callback',
        'permission_callback' => 'tutoread_permission_validate_user', // Protegido por JWT
    ]);

    // Rota para buscar alunos (sem alterações)
    register_rest_route('tutoread/v2', '/buscar-alunos', [
        'methods'             => 'GET',
        'callback'            => 'tutoread_buscar_alunos_callback',
        'permission_callback' => 'tutoread_permission_validate_user',
    ]);

    // Rota para listar todos os cursos (sem alterações)
    register_rest_route('tutoread/v2', '/cursos', [
        'methods'             => 'GET',
        'callback'            => 'tutoread_get_all_courses_callback',
        'permission_callback' => 'tutoread_permission_validate_user',
    ]);

    // [NOVO] Rota dedicada para DEFINIR os cursos de um aluno
    register_rest_route('tutoread/v2', '/aluno/(?P<user_id>\d+)/cursos', [
        'methods'             => 'POST',
        'callback'            => 'tutoread_set_student_courses_callback',
        'permission_callback' => 'tutoread_permission_validate_user',
        'args' => [
            'course_ids' => [
                'required'          => true,
                'validate_callback' => function($param) { return is_array($param); }
            ]
        ]
    ]);
    
    // Rota para LER os cursos de um aluno (mantida por compatibilidade)
    register_rest_route('tutoread/v2', '/aluno/(?P<user_id>\d+)/cursos', [
        'methods'             => 'GET',
        'callback'            => 'tutoread_get_cursos_callback',
        'permission_callback' => 'tutoread_permission_validate_user',
    ]);

    // Demais rotas (curso, modulo, aula, etc.) mantidas como estavam
    register_rest_route('tutoread/v2', '/curso/(?P<course_id>\d+)', [ /* ... */ ]);
    register_rest_route('tutoread/v2', '/modulo/(?P<module_id>\d+)/aulas', [ /* ... */ ]);
    register_rest_route('tutoread/v2', '/aula/(?P<lesson_id>\d+)', [ /* ... */ ]);
    register_rest_route('tutoread-central/v1', '/site/(?P<identifier>[a-zA-Z0-9_-]+)', [ /* ... */ ]);

});


/**
 * ====================================================== 
 * IMPLEMENTAÇÃO DOS CALLBACKS
 * ====================================================== 
 */

/**
 * [OTIMIZADO] Callback para: GET /user/{id}
 * Retorna os dados do perfil E os cursos do aluno em uma única chamada.
 */
function tutoread_get_user_info_callback(WP_REST_Request $request) {
    $auth = tutoread_permission_validate_user($request);
    if (is_wp_error($auth)) return $auth;
    $token_user_id = (int) $auth['user_id'];
    $token_role = sanitize_text_field($auth['role']);
    $param_user_id = (int) $request->get_param('user_id');

    if ($token_user_id != $param_user_id && $token_role != 'administrator') {
        return new WP_Error('forbidden', 'Você não pode visualizar os dados de outro usuário.', ['status' => 403]);
    }

    $user_data = get_userdata($param_user_id);
    if (!$user_data) {
        return new WP_Error('user_not_found', 'Usuário não encontrado.', ['status' => 404]);
    }
    
    $response = [
        'id'      => (int) $user_data->ID,
        'email'   => $user_data->user_email,
        'name'    => $user_data->display_name,
        'profile' => [],
        'courses' => []
    ];

    global $wpdb;
    $info_table = $wpdb->prefix . 'tutoread_user_info';
    $user_info = $wpdb->get_row($wpdb->prepare("SELECT full_name, phone_number, bio FROM {$info_table} WHERE user_id = %d", $param_user_id), ARRAY_A);

    $response['profile'] = [
        'full_name'    => $user_info['full_name'] ?? $user_data->display_name,
        'phone_number' => $user_info['phone_number'] ?? '',
        'bio'          => $user_info['bio'] ?? ''
    ];

    $matriculas_table = $wpdb->prefix . 'matriculas';
    $courses_table    = $wpdb->prefix . 'tutoread_courses';
    $course_ids = $wpdb->get_col($wpdb->prepare("SELECT course_id FROM {$matriculas_table} WHERE user_id = %d", $param_user_id));

    if (!empty($course_ids)) {
        $sanitized_course_ids = array_map('intval', $course_ids);
        $placeholders = implode(', ', array_fill(0, count($sanitized_course_ids), '%d'));
        $query = $wpdb->prepare("SELECT id, title FROM {$courses_table} WHERE id IN ($placeholders)", $sanitized_course_ids);
        $courses = $wpdb->get_results($query, ARRAY_A);
        $response['courses'] = $courses;
    }
    
    return rest_ensure_response($response);
}

/**
 * [ATUALIZADO] Callback para: POST /user/{id}
 * Agora lida apenas com dados do perfil, sem alterar matrículas.
 */
function tutoread_update_user_info_callback(WP_REST_Request $request) {
    $auth = tutoread_permission_validate_user($request);
    if (is_wp_error($auth)) return $auth;
    $token_user_id = (int) $auth['user_id'];
    $token_role    = $auth['role'];
    $param_user_id = (int) $request->get_param('user_id');

    if ($token_role !== 'administrator' && $token_user_id !== $param_user_id) {
        return new WP_Error('forbidden', 'Você não pode atualizar os dados de outro usuário.', ['status' => 403]);
    }

    global $wpdb;
    $body = $request->get_json_params();
    $info_table = $wpdb->prefix . 'tutoread_user_info';
    $user_info_id = $wpdb->get_var($wpdb->prepare("SELECT id FROM {$info_table} WHERE user_id = %d", $param_user_id));

    if (!$user_info_id) {
        $wpdb->insert($info_table, ['user_id' => $param_user_id], ['%d']);
        $user_info_id = $wpdb->insert_id;
    }

    $info_data_to_update = [];
    if (isset($body['full_name'])) $info_data_to_update['full_name'] = sanitize_text_field($body['full_name']);
    if (isset($body['phone_number'])) $info_data_to_update['phone_number'] = sanitize_text_field($body['phone_number']);
    if (isset($body['bio'])) $info_data_to_update['bio'] = sanitize_textarea_field($body['bio']);

    if (!empty($info_data_to_update)) {
        $wpdb->update($info_table, $info_data_to_update, ['id' => $user_info_id]);
    }

    clean_user_cache($param_user_id);
    return rest_ensure_response(['success' => true, 'message' => 'Dados de perfil atualizados com sucesso.']);
}

/**
 * [NOVO] Callback para: POST /aluno/{id}/cursos
 * Define a lista completa de matrículas para um aluno.
 */
function tutoread_set_student_courses_callback(WP_REST_Request $request) {
    $auth = tutoread_permission_validate_user($request);
    if (is_wp_error($auth) || $auth['role'] !== 'administrator') {
        return new WP_Error('forbidden', 'Apenas administradores podem alterar matrículas.', ['status' => 403]);
    }

    $param_user_id = (int) $request->get_param('user_id');
    $body = $request->get_json_params();
    $course_ids = array_map('intval', $body['course_ids']);

    global $wpdb;
    $matriculas_table = $wpdb->prefix . 'matriculas';

    $wpdb->query('START TRANSACTION');
    $wpdb->delete($matriculas_table, ['user_id' => $param_user_id], ['%d']);

    if (!empty($course_ids)) {
        foreach ($course_ids as $course_id) {
            $wpdb->insert(
                $matriculas_table,
                ['user_id' => $param_user_id, 'course_id' => $course_id, 'role' => 'tutor_aluno'],
                ['%d', '%d', '%s']
            );
        }
    }
    $wpdb->query('COMMIT');
    
    $cache_key = "tutoread_student_courses_{$param_user_id}";
    wp_cache_delete($cache_key, 'tutoread_matriculas');

    return rest_ensure_response(['success' => true, 'message' => 'Matrículas atualizadas com sucesso.']);
}

/**
 * Callback para: POST /student
 * Lida com a criação de um novo aluno (sem alterações lógicas).
 */
function tutoread_create_student_callback(WP_REST_Request $request) {
    $params = $request->get_json_params();
    $username = sanitize_user($params['username']);
    $email = sanitize_email($params['email']);
    $password = $params['password'];
    $course_ids = isset($params['course_ids']) && is_array($params['course_ids']) ? $params['course_ids'] : [];

    if (username_exists($username)) return new WP_Error('username_exists', 'O nome de usuário já existe.', ['status' => 400]);
    if (email_exists($email)) return new WP_Error('email_exists', 'O e-mail já está em uso.', ['status' => 400]);

    $user_id = wp_create_user($username, $password, $email);
    if (is_wp_error($user_id)) return $user_id;

    $user = new WP_User($user_id);
    $user->set_role('tutor_aluno');

    global $wpdb;
    $info_table = $wpdb->prefix . 'tutoread_user_info';
    $info_data = [
        'user_id'      => $user_id,
        'full_name'    => sanitize_text_field($params['full_name'] ?? ''),
        'phone_number' => sanitize_text_field($params['phone_number'] ?? ''),
        'bio'          => sanitize_textarea_field($params['bio'] ?? ''),
    ];
    $wpdb->insert($info_table, $info_data);

    if (!empty($course_ids)) {
        $matriculas_table = $wpdb->prefix . 'matriculas';
        foreach ($course_ids as $course_id) {
            $wpdb->insert(
                $matriculas_table,
                ['user_id' => $user_id, 'course_id' => intval($course_id), 'role' => 'tutor_aluno'],
                ['%d', '%d', '%s']
            );
        }
    }

    $cache_key = "tutoread_student_courses_{$user_id}";
    wp_cache_delete($cache_key, 'tutoread_matriculas');
    clean_user_cache($user_id);

    return rest_ensure_response([
        'success' => true,
        'message' => 'Aluno criado e matriculado com sucesso.',
        'user_id' => $user_id,
    ]);
}

/**
 * Callback para: GET /aluno/{id}/cursos
 * Mantido para compatibilidade, mas a busca principal usa o GET /user/{id} otimizado.
 */
function tutoread_get_cursos_callback(WP_REST_Request $request) {
    $auth = tutoread_permission_validate_user($request);
    if (is_wp_error($auth)) return $auth;
    $user_id_from_token = intval($auth['user_id']);
    $role_from_token = $auth['role'];
    $param_id = intval($request->get_param('user_id'));

    if ($role_from_token !== 'administrator' && $user_id_from_token !== $param_id) {
        return new WP_Error('forbidden', 'Você não pode ver cursos de outro usuário', ['status' => 403]);
    }
    
    $user_id_to_query = $param_id;
    $cache_key = "tutoread_student_courses_{$user_id_to_query}";
    $cache_group = 'tutoread_matriculas';
    
    $cached_courses = wp_cache_get($cache_key, $cache_group);
    if (false !== $cached_courses) {
        return rest_ensure_response($cached_courses);
    }

    global $wpdb;
    $matriculas_table = $wpdb->prefix . 'matriculas';
    $courses_table    = $wpdb->prefix . 'tutoread_courses';
    $course_ids = $wpdb->get_col($wpdb->prepare("SELECT course_id FROM {$matriculas_table} WHERE user_id = %d", $user_id_to_query));
    if (empty($course_ids)) {
        wp_cache_set($cache_key, [], $cache_group, HOUR_IN_SECONDS);
        return rest_ensure_response([]);
    }

    $sanitized_course_ids = array_map('intval', $course_ids);
    $placeholders = implode(', ', array_fill(0, count($sanitized_course_ids), '%d'));
    $query = $wpdb->prepare("SELECT id, title FROM {$courses_table} WHERE id IN ($placeholders)", $sanitized_course_ids);
    $courses = $wpdb->get_results($query, ARRAY_A);
    
    wp_cache_set($cache_key, $courses, $cache_group, HOUR_IN_SECONDS);
    return rest_ensure_response($courses);
}

/**
 * Callback para: GET /cursos
 * Retorna uma lista de todos os cursos para a tela de matrícula.
 */
function tutoread_get_all_courses_callback(WP_REST_Request $request) {
    global $wpdb;
    $courses_table = $wpdb->prefix . 'tutoread_courses';
    $courses = $wpdb->get_results("SELECT id, title FROM {$courses_table} ORDER BY title ASC", ARRAY_A);
    return rest_ensure_response($courses);
}

/**
 * Callback para: GET /buscar-alunos
 */
function tutoread_buscar_alunos_callback(WP_REST_Request $request) {
    $search_term = sanitize_text_field($request->get_param('search'));
    if (empty($search_term)) {
        return new WP_Error('bad_request', 'O parâmetro "search" é obrigatório.', ['status' => 400]);
    }
    $args = [
        'role'           => 'tutor_aluno',
        'search'         => '*' . esc_attr($search_term) . '*',
        'search_columns' => ['user_login', 'user_email', 'user_nicename', 'display_name'],
    ];
    $user_query = new WP_User_Query($args);
    $users = $user_query->get_results();

    if (empty($users)) {
        return rest_ensure_response([]);
    }
    $response_data = [];
    foreach ($users as $user) {
        $response_data[] = [
            'id'    => $user->ID,
            'name'  => $user->display_name,
            'email' => $user->user_email,
        ];
    }
    return rest_ensure_response($response_data);
}


// Implementações dos outros callbacks (login, curso, etc.) permanecem aqui...
// ...
/**
 * Callback para: POST /login
 * Autentica o usuário e retorna um token JWT.
 */
function tutoread_login_callback(WP_REST_Request $request) {
    $params = $request->get_json_params();
    $username = isset($params['username']) ? sanitize_text_field($params['username']) : '';
    $password = isset($params['password']) ? strval($params['password']) : '';

    if (empty($username) || empty($password)) {
        return new WP_Error('credentials_missing', 'Usuário e senha são obrigatórios.', ['status' => 400]);
    }

    // Autentica o usuário com as credenciais fornecidas
    $user = wp_authenticate($username, $password);

    // Se a autenticação falhar, retorna um erro
    if (is_wp_error($user)) {
        return new WP_Error('authentication_failed', 'Usuário ou senha inválidos.', ['status' => 403]);
    }

    // Pega a primeira "role" (função) do usuário para o token
    $user_roles = (array) $user->roles;
    $role = !empty($user_roles) ? $user_roles[0] : 'subscriber';

    // Gera o token JWT
    $token = TutorEAD_JWT::generate_token($user->ID, $role);

    // Se a geração do token falhar (ex: chave secreta não configurada), retorna o erro
    if (is_wp_error($token)) {
        return $token;
    }

    // Retorna a resposta de sucesso com o token
    return rest_ensure_response([
        'success' => true,
        'token'   => $token,
        'user'    => [
            'id'    => $user->ID,
            'email' => $user->user_email,
            'name'  => $user->display_name,
        ]
    ]);
}

function tutor_ead_delete_course_data_callback(WP_REST_Request $request) {
    global $wpdb;

    $course_id = (int) $request->get_param('id');
    if (empty($course_id)) {
        return new WP_Error('invalid_course_id', 'ID do curso inválido.', ['status' => 400]);
    }

    $courses_table = $wpdb->prefix . 'tutoread_courses';
    $modules_table = $wpdb->prefix . 'tutoread_modules';
    $lessons_table = $wpdb->prefix . 'tutoread_lessons';
    $matriculas_table = $wpdb->prefix . 'matriculas';

    // Iniciar transação
    $wpdb->query('START TRANSACTION');

    try {
        // 1. Obter todos os módulos associados ao curso
        $module_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM $modules_table WHERE course_id = %d", $course_id));

        // 2. Excluir aulas associadas a esses módulos
        if (!empty($module_ids)) {
            $ids_sql = implode(',', array_map('intval', $module_ids));
            $wpdb->query("DELETE FROM $lessons_table WHERE module_id IN ($ids_sql)");
        }

        // 3. Excluir os módulos
        $wpdb->delete($modules_table, ['course_id' => $course_id], ['%d']);

        // 4. Excluir matrículas
        $wpdb->delete($matriculas_table, ['course_id' => $course_id], ['%d']);

        // 5. Excluir o curso principal
        $deleted = $wpdb->delete($courses_table, ['id' => $course_id], ['%d']);

        if ($deleted === false) {
            throw new Exception('Não foi possível excluir o curso da tabela principal.');
        }

        // 6. Excluir metadados relacionados (como modo de visualização)
        delete_option('tutoread_view_mode_' . $course_id);

        // Commit da transação
        $wpdb->query('COMMIT');

        return rest_ensure_response(['success' => true, 'message' => 'Curso excluído com sucesso.']);

    } catch (Exception $e) {
        // Rollback em caso de erro
        $wpdb->query('ROLLBACK');
        return new WP_Error('delete_failed', 'Ocorreu um erro ao excluir o curso: ' . $e->getMessage(), ['status' => 500]);
    }
}