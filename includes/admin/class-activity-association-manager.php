<?php

namespace TutorEAD\Admin;

defined('ABSPATH') || exit;

class ActivityAssociationManager {

    public function __construct() {
        add_action('wp_ajax_tutoread_update_activity_position', [__CLASS__, 'handle_ajax_update_position']);
        add_action('wp_ajax_tutoread_unposition_activity', [__CLASS__, 'handle_ajax_unposition_activity']);
    }

    /**
     * Renderiza a página do editor de posição da atividade (Drag and Drop).
     */
    public static function associar_atividade_curso_page() {
        global $wpdb;

        if (!isset($_GET['course_id'])) {
            wp_die('Parâmetros inválidos. ID do curso não fornecido.');
        }

        $course_id = intval($_GET['course_id']);
        $course = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tutoread_courses WHERE id = %d", $course_id));

        if (!$course) {
            wp_die('Curso não encontrado.');
        }

        // Buscar todas as atividades associadas a este curso
        $all_associated_activities = $wpdb->get_results($wpdb->prepare(
            "SELECT a.id, a.titulo as title, ca.module_id, ca.lesson_id, ca.position
             FROM {$wpdb->prefix}atividades as a
             JOIN {$wpdb->prefix}tutoread_course_activities as ca ON a.id = ca.activity_id
             WHERE ca.course_id = %d",
            $course_id
        ));

        // Separar atividades em posicionadas e não posicionadas
        $positioned_activities = [];
        $unpositioned_activities = [];
        foreach ($all_associated_activities as $activity) {
            if ($activity->module_id === null) {
                $unpositioned_activities[] = $activity;
            } else {
                $positioned_activities[] = $activity;
            }
        }

        // Buscar a estrutura de módulos e aulas do curso
        $modules = $wpdb->get_results($wpdb->prepare(
            "SELECT id, title, module_order FROM {$wpdb->prefix}tutoread_modules WHERE course_id = %d ORDER BY module_order ASC",
            $course_id
        ), ARRAY_A);

        $modules_with_lessons = [];
        foreach ($modules as $module) {
            $module['lessons'] = $wpdb->get_results($wpdb->prepare(
                "SELECT id, title, module_id, lesson_order FROM {$wpdb->prefix}tutoread_lessons WHERE module_id = %d ORDER BY lesson_order ASC, id ASC",
                $module['id']
            ), ARRAY_A);
            $modules_with_lessons[] = $module;
        }

        // Renderizar a nova view, passando os novos dados
        $view_path = __DIR__ . '/views/view-activity-position-editor.php';
        if (file_exists($view_path)) {
            include $view_path;
        } else {
            wp_die('Arquivo de visualização não encontrado.');
        }
    }

    /**
     * Manipula a requisição AJAX para atualizar a posição da atividade.
     */
    public static function handle_ajax_update_position() {
        check_ajax_referer('tutoread_update_activity_position_nonce', 'nonce');

        global $wpdb;

        $activity_id = isset($_POST['activity_id']) ? intval($_POST['activity_id']) : 0;
        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;
        $module_id = isset($_POST['module_id']) ? intval($_POST['module_id']) : 0;
        $lesson_id = isset($_POST['lesson_id']) ? intval($_POST['lesson_id']) : 0;
        $position = isset($_POST['position']) ? sanitize_text_field($_POST['position']) : '';

        if (!$activity_id || !$course_id || !$module_id || !in_array($position, ['antes', 'depois'])) {
            wp_send_json_error(['message' => 'Dados inválidos.']);
        }

        $data_to_update = [
            'module_id' => $module_id,
            'lesson_id' => ($lesson_id === 0) ? null : $lesson_id,
            'position'  => $position
        ];

        $where = [
            'activity_id' => $activity_id,
            'course_id'   => $course_id
        ];

        $result = $wpdb->update("{$wpdb->prefix}tutoread_course_activities", $data_to_update, $where);

        if ($result === false) {
            wp_send_json_error(['message' => $wpdb->last_error]);
        } else {
            wp_send_json_success();
        }
    }

    /**
     * Manipula a requisição AJAX para desposicionar a atividade (remover posição).
     */
    public static function handle_ajax_unposition_activity() {
        if (false === check_ajax_referer('tutoread_unposition_activity_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => 'Falha na verificação de segurança (nonce).']);
            return;
        }

        try {
            global $wpdb;

            $activity_id = isset($_POST['activity_id']) ? intval($_POST['activity_id']) : 0;
            $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;

            if (!$activity_id || !$course_id) {
                wp_send_json_error(['message' => 'Dados inválidos (ID da atividade ou do curso ausente).']);
                return;
            }

            $data_to_update = [
                'module_id' => null,
                'lesson_id' => null,
                'position'  => null
            ];

            $where = [
                'activity_id' => $activity_id,
                'course_id'   => $course_id
            ];

            $result = $wpdb->update("{$wpdb->prefix}tutoread_course_activities", $data_to_update, $where);

            if ($result === false) {
                wp_send_json_error(['message' => 'Erro no banco de dados: ' . $wpdb->last_error]);
            } else {
                wp_send_json_success();
            }
        } catch (\Exception $e) {
            wp_send_json_error(['message' => 'Exceção capturada: ' . $e->getMessage()]);
        }
    }
}

// Inicializa a classe para registrar o hook AJAX
new ActivityAssociationManager();