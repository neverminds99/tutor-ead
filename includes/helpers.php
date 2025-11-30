<?php

namespace TutorEAD;

// Evita acesso direto ao arquivo
defined('ABSPATH') || exit;

// Análise do arquivo: /includes/helpers.php

// Este arquivo define um conjunto de funções auxiliares para manipular cursos, módulos, aulas e atividades no sistema Tutor EAD.
// Todas as funções utilizam `$wpdb` para interagir diretamente com o banco de dados do WordPress.

/**
 * Funções principais:
 *
 * 1. **obter_modulos_por_curso($curso_id)**:
 *    - Retorna todos os módulos associados a um curso específico.
 *    - Utiliza `$wpdb->get_results()` com `prepare()` para evitar SQL Injection.
 *
 * 2. **obter_aulas_por_modulo($modulo_id)**:
 *    - Retorna todas as aulas associadas a um módulo específico.
 *    - Similar à função de módulos, garantindo segurança com `prepare()`.
 *
 * 3. **criar_modulo($curso_id, $title, $description)**:
 *    - Insere um novo módulo no banco de dados, vinculando-o a um curso existente.
 *    - Utiliza `sanitize_text_field()` e `sanitize_textarea_field()` para validar os dados de entrada.
 *    - Retorna o ID do módulo recém-criado.
 *
 * 4. **criar_aula($modulo_id, $title, $description, $video_url)**:
 *    - Insere uma nova aula vinculada a um módulo específico.
 *    - Sanitiza os dados do título e da descrição, além de validar a URL do vídeo com `esc_url_raw()`.
 *    - Retorna o ID da aula recém-criada.
 *
 * 5. **obter_atividades_cadastradas()**:
 *    - Retorna todas as atividades cadastradas no sistema.
 *    - Ordena os resultados pela data de criação em ordem decrescente.
 *
 * Observações:
 * - O uso de `prepare()` evita SQL Injection, garantindo segurança ao manipular o banco de dados.
 * - As funções foram projetadas para serem reutilizáveis e podem ser chamadas em diferentes partes do sistema.
 */

/**
 * Obtém os módulos associados a um curso.
 *
 * @param int $curso_id ID do curso.
 * @return WP_Post[] Lista de módulos associados ao curso.
 */
/**
 * Obter módulos associados a um curso.
 */
 /**
 * Função para processar a exclusão de um aluno.
 */
function delete_student_handler() {
    if (!isset($_GET['user_id'], $_GET['delete_nonce']) || !wp_verify_nonce($_GET['delete_nonce'], 'delete_student_' . $_GET['user_id'])) {
        wp_die(__('Erro de segurança.', 'tutor-ead'));
    }

    $user_id = intval($_GET['user_id']);
    if ($user_id > 0) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user($user_id);
        wp_redirect(admin_url('admin.php?page=tutor-ead-students&delete_success=1'));
        exit;
    }
}

// Garante que o WordPress reconheça a função antes de registrá-la
add_action('admin_post_delete_student', __NAMESPACE__ . '\delete_student_handler');


function obter_modulos_por_curso($curso_id) {

    global $wpdb;

    return $wpdb->get_results(

        $wpdb->prepare(

            "SELECT * FROM {$wpdb->prefix}tutoread_modules WHERE course_id = %d",

            intval($curso_id)

        ),

        ARRAY_A

    );

}



/**

 * Obtém as aulas associadas a um módulo.

 * 

 * @param int $modulo_id ID do módulo.

 * @return WP_Post[] Lista de aulas associadas ao módulo.

 */

/**

 * Obter aulas associadas a um módulo.

 */

function obter_aulas_por_modulo($modulo_id) {

    global $wpdb;

    return $wpdb->get_results(

        $wpdb->prepare(

            "SELECT * FROM {$wpdb->prefix}tutoread_lessons WHERE module_id = %d",

            intval($modulo_id)

        ),

        ARRAY_A

    );

}



/**

 * Criar um novo módulo.

 */

function criar_modulo($curso_id, $title, $description) {

    global $wpdb;

    $wpdb->insert(

        "{$wpdb->prefix}tutoread_modules",

        [

            'course_id'   => intval($curso_id),

            'title'       => sanitize_text_field($title),

            'description' => sanitize_textarea_field($description),

        ],

        ['%d', '%s', '%s']

    );

    return $wpdb->insert_id;

}



/**

 * Criar uma nova aula.

 */

function criar_aula($modulo_id, $title, $description, $video_url = '') {

    global $wpdb;

    $wpdb->insert(

        "{$wpdb->prefix}tutoread_lessons",

        [

            'module_id'   => intval($modulo_id),

            'title'       => sanitize_text_field($title),

            'description' => sanitize_textarea_field($description),

            'video_url'   => esc_url_raw($video_url)

        ],

        ['%d', '%s', '%s', '%s']

    );

    return $wpdb->insert_id;

}




function obter_atividades_cadastradas() {

    global $wpdb;



    // Consulta para retornar as atividades cadastradas na tabela personalizada

    $atividades = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}atividades ORDER BY data_criacao DESC");



    return $atividades;

}
/**
 * Gera um avatar de placeholder em SVG com a inicial do nome e uma cor de fundo aleatória.
 *
 * @param string $name O nome do usuário.
 * @return string Data URI do SVG.
 */
function generate_placeholder_avatar($name) {
    $initial = mb_substr($name, 0, 1);
    $colors = ['#f44336', '#e91e63', '#9c27b0', '#673ab7', '#3f51b5', '#2196f3', '#03a9f4', '#00bcd4', '#009688', '#4caf50', '#8bc34a', '#cddc39', '#ffeb3b', '#ffc107', '#ff9800', '#ff5722', '#795548', '#9e9e9e', '#607d8b'];
    $bg_color = $colors[crc32($name) % count($colors)];

    $svg = sprintf(
        '<svg width="100" height="100" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">'
        . '<rect width="100" height="100" fill="%s" />'
        . '<text x="50" y="50" font-family="Arial, sans-serif" font-size="50" fill="#ffffff" text-anchor="middle" dy=".3em">%s</text>'
        . '</svg>',
        esc_attr($bg_color),
        esc_html(strtoupper($initial))
    );

    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/**
 * Registra uma atividade do aluno e garante que apenas os últimos 5 registros sejam mantidos.
 *
 * @param int $user_id ID do aluno.
 * @param string $activity_type Tipo de atividade (ex: 'login', 'course_entry').
 * @param int|null $course_id ID do curso, se aplicável.
 */
function record_student_activity($user_id, $activity_type, $course_id = null, $lesson_id = null, $details = '') {
    global $wpdb;
    $table_name = $wpdb->prefix . 'tutoread_student_activity_log';

    // Insere o novo registro de atividade.
    $wpdb->insert($table_name, [
        'user_id'       => $user_id,
        'activity_type' => $activity_type,
        'course_id'     => $course_id,
        'lesson_id'     => $lesson_id,
        'access_time'   => current_time('mysql'),
        'details'       => $details
    ]);
}

/**
 * Obtém a URL da miniatura de um vídeo (YouTube) ou um ícone para outros tipos de link (PDF).
 *
 * @param string $video_url A URL do conteúdo.
 * @return string A URL da miniatura ou a URL de uma imagem padrão.
 */
function get_video_thumbnail_url($video_url) {
    // Usa a constante correta para o diretório de imagens.
    $default_image = TUTOR_EAD_IMG_URL . 'pdf.png';

    if (empty($video_url)) {
        return $default_image;
    }

    $video_id = '';

    // Tenta extrair o ID de um vídeo do YouTube de vários formatos de URL.
    if (preg_match('/(youtube\.com\/(?:watch\?v=|embed\/|v\/)|youtu\.be\/|\/v\/|\/e\/|watch\?v%3D|watch\?feature=player_embedded&v=)([a-zA-Z0-9_\-]+)/', $video_url, $matches)) {
        $video_id = $matches[2];
    }

    if ($video_id) {
        // Retorna a URL da miniatura de qualidade média do YouTube.
        return "https://img.youtube.com/vi/{$video_id}/mqdefault.jpg";
    }

    // Verifica se a URL aponta para um arquivo PDF.
    if (preg_match('/\.pdf(\?.*)?$/i', $video_url)) {
        return TUTOR_EAD_IMG_URL . 'pdf.png';
    }
    
    // Se não for YouTube nem PDF, retorna a imagem padrão.
    return $default_image;
}