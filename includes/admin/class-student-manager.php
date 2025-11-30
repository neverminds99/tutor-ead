<?php

namespace TutorEAD\Admin;

defined('ABSPATH') || exit;

/**
 * Análise do arquivo: /includes/admin/class-student-manager.php
 *
 * Objetivo:
 * Gerenciar alunos dentro do sistema Tutor EAD, permitindo criação, edição, listagem e exclusão.
 *
 * Funcionalidades:
 * - Cadastro de alunos individualmente e em massa (via JSON).
 * - Reset de senha para alunos individualmente ou em lote.
 * - Gerenciamento das matrículas dos alunos nos cursos.
 * - Exibição do progresso do aluno nos cursos e aulas.
 * - Edição dos dados do aluno, incluindo email, celular e permissões.
 *
 * Resumo:
 * A classe StudentManager permite que administradores e professores gerenciem os alunos dentro do sistema, oferecendo controle sobre contas, matrículas e progresso acadêmico.
 * Agora, ao adicionar um novo aluno, o formulário inclui uma seção com checkboxes listando todos os cursos disponíveis, facilitando a matrícula do aluno.
 */
class StudentManager {

    /**
     * Enfileira scripts e estilos específicos para a página de alunos.
     */
    public static function enqueue_scripts($hook) {
        if ($hook !== 'toplevel_page_tutor-ead-dashboard' && $hook !== 'tutor-ead_page_tutor-ead-students') {
            return;
        }
        // Enfileirar jQuery e o script para preview em JSON
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'tutor-ead-students-bulk-preview',
            plugin_dir_url(dirname(__FILE__, 2)) . 'assets/js/bulk-preview.js',
            ['jquery'],
            '1.0',
            true
        );
        wp_enqueue_script(
            'tutor-ead-admin-global',
            plugin_dir_url(dirname(__FILE__, 2)) . 'assets/js/admin-global.js',
            ['jquery'],
            '1.0',
            true
        );
        wp_localize_script('tutor-ead-admin-global', 'TutorEAD_Ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('tutoread_dashboard_nonce'),
        ]);
    }

    /**
     * Método AJAX para pré-visualização dos alunos a serem importados via JSON.
     */
    public static function ajax_preview_bulk_students() {
        // Permissão: apenas administradores
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Sem permissão', 403);
        }

        $json_text = wp_unslash($_POST['bulk_students_json'] ?? '');
        $data = json_decode(trim($json_text), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error([
                'message' => __('JSON inválido: ', 'tutor-ead') . json_last_error_msg(),
            ], 400);
        }

        if (!isset($data['users']) || !is_array($data['users'])) {
            wp_send_json_error([
                'message' => __('Formato inválido: chave "users" não encontrada ou não é array.', 'tutor-ead'),
            ], 400);
        }

        global $wpdb;
        $preview = [];

        foreach ($data['users'] as $idx => $user_data) {
            $item = [
                'index'   => $idx,
                'errors'  => [],
                'warnings'=> [],
                'status'  => 'new', // Possíveis valores: new | update | error
                'current' => null,
                'new'     => [],
            ];

            // Obtenção e sanitização dos campos
            $username = isset($user_data['username']) ? sanitize_user($user_data['username']) : '';
            $email    = isset($user_data['email'])    ? sanitize_email($user_data['email']) : '';
            $nome     = isset($user_data['nome'])     ? sanitize_text_field($user_data['nome']) : '';
            $celular  = isset($user_data['celular'])  ? sanitize_text_field($user_data['celular']) : '';
            $password = (isset($user_data['password']) && $user_data['password'] !== '')
                        ? sanitize_text_field($user_data['password'])
                        : '';

            // Validações básicas
            if (empty($username)) {
                $item['errors'][] = __('Username ausente', 'tutor-ead');
            }
            if (empty($email) || !is_email($email)) {
                $item['errors'][] = __('E‑mail inválido ou ausente', 'tutor-ead');
            }
            if (empty($nome)) {
                $item['warnings'][] = __('Nome de exibição ausente', 'tutor-ead');
            }
            if (empty($password)) {
                $item['warnings'][] = __('Senha não fornecida (será gerada automaticamente)', 'tutor-ead');
            }

            // Verificar se já existe usuário com este e‑mail
            if ($email && email_exists($email)) {
                $item['status'] = 'update';
                $existing_id = email_exists($email);
                $user_obj = get_userdata($existing_id);
                $item['current'] = [
                    'ID'       => $user_obj->ID,
                    'username' => $user_obj->user_login,
                    'email'    => $user_obj->user_email,
                    'nome'     => $user_obj->display_name,
                    'celular'  => get_user_meta($existing_id, 'celular', true),
                ];
            }

            $item['new'] = [
                'username' => $username,
                'email'    => $email,
                'nome'     => $nome,
                'celular'  => $celular,
                'password' => $password ? $password : __('(gerada automaticamente)', 'tutor-ead'),
            ];

            // Se houver erros críticos, define status error
            if (!empty($item['errors'])) {
                $item['status'] = 'error';
            }

            $preview[] = $item;
        }
        wp_send_json_success($preview);
    }

    /**
     * Renderiza a página de gerenciamento de alunos.
     */
    public static function students_page() {
        global $wpdb;
        $errors = [];
        
        // Pega a cor de destaque das opções
        $highlight_color = get_option('tutor_ead_highlight_color', '#0073aa');

        // Processar formulário de adição individual de aluno
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_student'])) {
            $student_username = sanitize_text_field($_POST['student_username']);
            $student_email    = sanitize_email($_POST['student_email']);
            $student_password = sanitize_text_field($_POST['student_password']);
            $student_celular  = !empty($_POST['student_celular']) ? sanitize_text_field($_POST['student_celular']) : '';
            $student_full_name = !empty($_POST['student_full_name']) ? sanitize_text_field($_POST['student_full_name']) : '';

            $user_id = wp_create_user($student_username, $student_password, $student_email);
            if (!is_wp_error($user_id)) {
                $user = new \WP_User($user_id);
                $user->set_role('tutor_aluno');

                // Prepara os dados para inserção na tabela personalizada
                $user_info_data = [
                    'user_id' => $user_id,
                    'full_name' => $student_full_name,
                    'phone_number' => $student_celular,
                ];

                // Adiciona os campos personalizados se eles foram enviados
                $possible_fields = ['cpf', 'rg', 'endereco', 'cep', 'cidade', 'estado', 'valor_contrato'];
                foreach ($possible_fields as $field_key) {
                    if (isset($_POST['student_' . $field_key])) {
                        $user_info_data[$field_key] = sanitize_text_field($_POST['student_' . $field_key]);
                    }
                }
                
                // Salva os dados na tabela tutoread_user_info
                $user_info_table = $wpdb->prefix . 'tutoread_user_info';
                $wpdb->insert($user_info_table, $user_info_data);

                // Matrículas do novo aluno
                if (isset($_POST['new_student_courses']) && is_array($_POST['new_student_courses'])) {
                    foreach ($_POST['new_student_courses'] as $course_id) {
                        $course_id = intval($course_id);
                        $wpdb->insert("{$wpdb->prefix}matriculas", [
                            'user_id'   => $user_id,
                            'course_id' => $course_id,
                            'role' => 'aluno'
                        ]);
                    }
                }

                echo '<div class="tutor-notification-success">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <p>' . __('Aluno registrado com sucesso!', 'tutor-ead') . '</p>
                      </div>';
            } else {
                echo '<div class="tutor-notification-error">
                        <span class="dashicons dashicons-no"></span>
                        <p>' . esc_html($user_id->get_error_message()) . '</p>
                      </div>';
            }
        }

        // Processar formulário de adição em massa via JSON
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_add_students'])) {
            $json_text = trim(stripslashes($_POST['bulk_students_json']));
            $data = json_decode($json_text, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                echo '<div class="tutor-notification-error">
                        <span class="dashicons dashicons-no"></span>
                        <p>' . __('JSON inválido: ', 'tutor-ead') . json_last_error_msg() . '</p>
                      </div>';
            } elseif (isset($data['users']) && is_array($data['users'])) {
                $created = 0;
                foreach ($data['users'] as $user_data) {
                    $student_username = isset($user_data['username']) ? sanitize_user($user_data['username']) : '';
                    $student_email    = isset($user_data['email']) ? sanitize_email($user_data['email']) : '';
                    $student_nome     = isset($user_data['nome']) ? sanitize_text_field($user_data['nome']) : '';
                    $student_celular  = isset($user_data['celular']) ? sanitize_text_field($user_data['celular']) : '';
                    $student_password = (isset($user_data['password']) && !empty($user_data['password']))
                                          ? sanitize_text_field($user_data['password'])
                                          : wp_generate_password(8, false);

                    if (!empty($student_username) && !empty($student_email)) {
                        $user_id = wp_create_user($student_username, $student_password, $student_email);
                        if (!is_wp_error($user_id)) {
                            wp_update_user(['ID' => $user_id, 'display_name' => $student_nome]);
                            update_user_meta($user_id, 'celular', $student_celular);
                            $user = new \WP_User($user_id);
                            $user->set_role('tutor_aluno');
                            $created++;
                        }
                    }
                }
                if ($created > 0) {
                    echo '<div class="tutor-notification-success">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <p>' . sprintf(__('Foram criados %d aluno(s)!', 'tutor-ead'), $created) . '</p>
                          </div>';
                } else {
                    echo '<div class="tutor-notification-error">
                            <span class="dashicons dashicons-no"></span>
                            <p>' . __('Nenhum aluno foi criado. Verifique se os dados estão corretos.', 'tutor-ead') . '</p>
                          </div>';
                }
            } else {
                echo '<div class="tutor-notification-error">
                        <span class="dashicons dashicons-no"></span>
                        <p>' . __('JSON inválido: não foi encontrada a chave "users".', 'tutor-ead') . '</p>
                      </div>';
            }
        }

        // Processar Bulk Actions na tabela de alunos
        if (!empty($_POST['mass_enroll_step']) && $_POST['mass_enroll_step'] === '2') {
            check_admin_referer('mass_enroll_nonce', 'mass_enroll_nonce_field');

            $students_ids = isset($_POST['mass_enroll_students']) ? array_map('intval', $_POST['mass_enroll_students']) : [];
            $courses_ids  = isset($_POST['enroll_courses']) ? array_map('intval', $_POST['enroll_courses']) : [];

            if (!empty($students_ids) && !empty($courses_ids)) {
                $countMatriculas = 0;
                foreach ($students_ids as $aluno_id) {
                    foreach ($courses_ids as $curso_id) {
                        $exists = $wpdb->get_var($wpdb->prepare(
                            "SELECT COUNT(*) FROM {$wpdb->prefix}matriculas WHERE user_id = %d AND course_id = %d",
                            $aluno_id, $curso_id
                        ));
                        if (!$exists) {
                            $wpdb->insert("{$wpdb->prefix}matriculas", [
                                'user_id'   => $aluno_id,
                                'course_id' => $curso_id,
                                'role'      => 'aluno' // CORREÇÃO: Adiciona o papel do aluno
                            ]);
                            $countMatriculas++;
                        }
                    }
                }
                echo '<div class="tutor-notification-success">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <p>' . sprintf(__('Matrícula em massa concluída! %d novas matrículas criadas.', 'tutor-ead'), $countMatriculas) . '</p>
                      </div>';
            } else {
                echo '<div class="tutor-notification-error">
                        <span class="dashicons dashicons-no"></span>
                        <p>' . __('Nenhum aluno ou curso foi selecionado para matrícula em massa.', 'tutor-ead') . '</p>
                      </div>';
            }
        } elseif ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_action_submit']) && isset($_POST['student_ids'])) {
            if (!isset($_POST['bulk_action_nonce']) || !wp_verify_nonce($_POST['bulk_action_nonce'], 'bulk_action_student')) {
                echo '<div class="tutor-notification-error">
                        <span class="dashicons dashicons-no"></span>
                        <p>' . __('Erro de segurança. Tente novamente.', 'tutor-ead') . '</p>
                      </div>';
            } else {
                $bulk_action = sanitize_text_field($_POST['bulk_action']);
                $student_ids = array_map('intval', $_POST['student_ids']);
                $affected = 0;
                if ($bulk_action === 'delete') {
                    foreach ($student_ids as $sid) {
                        wp_delete_user($sid);
                        $affected++;
                    }
                    if ($affected > 0) {
                        echo '<div class="tutor-notification-success">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <p>' . sprintf(__('Foram excluídos %d aluno(s)!', 'tutor-ead'), $affected) . '</p>
                              </div>';
                    }
                } elseif ($bulk_action === 'reset_password') {
                    foreach ($student_ids as $sid) {
                        wp_set_password('aluno01', $sid);
                        $affected++;
                    }
                    if ($affected > 0) {
                        echo '<div class="tutor-notification-success">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <p>' . sprintf(__('Foram resetadas as senhas de %d aluno(s)!', 'tutor-ead'), $affected) . '</p>
                              </div>';
                    }
                } elseif ($bulk_action === 'mass_enroll') {
                    if (!empty($student_ids)) {
                        $all_courses = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}tutoread_courses", ARRAY_A);
                        echo '<div class="tutor-notification-info">
                                <span class="dashicons dashicons-info"></span>
                                <p>' . __('Selecione os cursos abaixo para matricular os alunos escolhidos.', 'tutor-ead') . '</p>
                              </div>';
                        echo '<div class="tutor-card">';
                        echo '<form method="POST">';
                        wp_nonce_field('mass_enroll_nonce', 'mass_enroll_nonce_field');
                        foreach ($student_ids as $sid) {
                            echo '<input type="hidden" name="mass_enroll_students[]" value="' . intval($sid) . '">';
                        }
                        echo '<input type="hidden" name="mass_enroll_step" value="2">';
                        if ($all_courses) {
                            echo '<h3 class="section-subtitle">' . __('Escolha os cursos para matrícula em massa:', 'tutor-ead') . '</h3>';
                            echo '<div class="courses-grid">';
                            foreach ($all_courses as $c) {
                                echo '<label class="form-check">';
                                echo '<input type="checkbox" name="enroll_courses[]" value="' . intval($c['id']) . '"> ';
                                echo '<span>' . esc_html($c['title']) . '</span>';
                                echo '</label>';
                            }
                            echo '</div>';
                            echo '<p style="margin-top: 20px;"><button type="submit" class="btn-primary">' . __('Confirmar Matrícula', 'tutor-ead') . '</button></p>';
                        } else {
                            echo '<p>' . __('Nenhum curso disponível.', 'tutor-ead') . '</p>';
                        }
                        echo '</form>';
                        echo '</div>';
                        return;
                    }
                }
            }
        }

        if (isset($_GET['reset_success'])) {
            echo '<div class="tutor-notification-success">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <p>' . __('Senha resetada para "aluno01" com sucesso!', 'tutor-ead') . '</p>
                  </div>';
        }

        $students = get_users(['role' => 'tutor_aluno']);
        ?>

        <!-- Estilos Modernos -->
        <style>
            .tutor-students-wrap {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                background: #f3f4f6;
                margin: -20px;
                padding: 32px;
                min-height: 100vh;
            }
            
            .tutor-students-wrap * {
                box-sizing: border-box;
            }
            
            .students-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 32px;
            }
            
            .students-title {
                font-size: 32px;
                font-weight: 600;
                color: #1f2937;
                margin: 0 0 8px 0;
            }
            
            .students-subtitle {
                color: #6b7280;
                font-size: 16px;
                margin: 0;
            }
            
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 24px;
                margin-bottom: 32px;
            }
            
            .stat-card {
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                padding: 24px;
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
                text-align: center;
            }
            
            .stat-icon {
                width: 56px;
                height: 56px;
                background: #f3f4f6;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 0 auto 16px;
            }
            
            .stat-icon .dashicons {
                font-size: 32px;
                color: <?php echo $highlight_color; ?>;
            }
            
            .stat-value {
                font-size: 32px;
                font-weight: 700;
                color: #1f2937;
                margin-bottom: 4px;
            }
            
            .stat-label {
                font-size: 14px;
                color: #6b7280;
            }

            .stat-badge {
                display: inline-block;
                margin-top: 12px;
                padding: 4px 12px;
                background-color: <?php echo $highlight_color; ?>;
                color: #ffffff;
                border-radius: 9999px;
                font-size: 12px;
                font-weight: 600;
            }

            .stat-description {
                font-size: 12px;
                color: #9ca3af;
                margin-top: 4px;
            }
            
            .tutor-card {
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                padding: 24px;
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
                margin-bottom: 24px;
            }
            
            .card-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 20px;
            }
            
            .card-title {
                font-size: 20px;
                font-weight: 600;
                color: #1f2937;
                margin: 0;
                display: flex;
                justify-content: space-between; /* Adicionado */
                align-items: center; /* Adicionado */
                gap: 12px;
            }
            
            .card-title .dashicons {
                font-size: 24px;
                color: <?php echo $highlight_color; ?>;
            }
            
            .bulk-actions {
                display: flex;
                gap: 12px;
                align-items: center;
                flex-wrap: wrap;
            }
            
            .search-box {
                position: relative;
            }
            
            .search-box input {
                padding: 8px 12px 8px 36px;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                width: 250px;
                font-size: 14px;
            }
            
            .search-box .dashicons {
                position: absolute;
                left: 12px;
                top: 50%;
                transform: translateY(-50%);
                color: #6b7280;
                font-size: 18px;
            }
            
            .data-table {
                width: 100%;
                border-collapse: collapse;
                margin: 0;
            }
            
            .data-table thead {
                background: #f9fafb;
                border-bottom: 1px solid #e5e7eb;
            }
            
            .data-table th {
                text-align: left;
                padding: 12px 16px;
                font-weight: 600;
                color: #374151;
                font-size: 14px;
            }
            
            .data-table tbody tr {
                border-bottom: 1px solid #f3f4f6;
                transition: all 0.1s ease;
            }
            
            .data-table tbody tr:hover {
                background: #f9fafb;
            }
            
            .data-table td {
                padding: 16px;
                color: #1f2937;
                font-size: 14px;
                vertical-align: top;
            }
            
            .actions-links {
                display: flex;
                gap: 8px;
                flex-wrap: wrap;
            }

            /* Estilos para o Dropdown de Ações */
            .tutor-actions-dropdown {
                position: relative;
            }
            .tutor-actions-trigger {
                background: transparent;
                border: 1px solid transparent;
                padding: 4px;
                border-radius: 50%;
                cursor: pointer;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
                transition: background-color 0.2s ease, border-color 0.2s ease;
            }
            .tutor-actions-trigger:hover {
                background-color: #f0f0f1;
                border-color: #e0e0e1;
            }
            .tutor-actions-menu {
                display: none;
                position: absolute;
                right: 0;
                top: 100%;
                margin-top: 4px;
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.1);
                z-index: 10;
                width: 180px;
                padding: 8px 0;
            }
            .tutor-actions-menu.is-open {
                display: block;
            }
            .tutor-actions-menu-item {
                display: block;
                padding: 8px 16px;
                color: #374151;
                font-size: 14px;
                text-decoration: none;
                transition: background-color 0.2s ease;
            }
            .tutor-actions-menu-item:hover {
                background-color: #f9fafb;
                color: #1f2937;
            }

            .action-link {
                color: <?php echo $highlight_color; ?>;
                font-size: 13px;
                text-decoration: none;
                padding: 4px 8px;
                border-radius: 4px;
                transition: all 0.1s ease;
            }
            
            .action-link:hover {
                background: <?php echo $highlight_color; ?>10;
                text-decoration: none;
            }
            
            .btn-primary {
                background: <?php echo $highlight_color; ?>;
                color: #ffffff;
                border: none;
                padding: 12px 24px;
                border-radius: 8px;
                font-weight: 600;
                font-size: 14px;
                cursor: pointer;
                transition: all 0.2s ease;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }
            
            .btn-primary:hover {
                background: <?php echo $highlight_color; ?>e6;
                transform: translateY(-1px);
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }
            
            .btn-primary .dashicons {
                font-size: 18px;
            }
            
            .btn-secondary {
                background: #ffffff;
                color: #374151;
                border: 1px solid #e5e7eb;
                padding: 8px 16px;
                border-radius: 6px;
                font-weight: 500;
                font-size: 13px;
                cursor: pointer;
                transition: all 0.2s ease;
                display: inline-flex;
                align-items: center;
                gap: 6px;
            }
            
            .btn-secondary:hover {
                background: #f9fafb;
                border-color: #d1d5db;
            }

            .btn-secondary-alt {
                background: #f3f4f6;
                color: <?php echo $highlight_color; ?>;
                border: 1px solid #e5e7eb;
                padding: 12px 24px;
                border-radius: 8px;
                font-weight: 600;
                font-size: 14px;
                cursor: pointer;
                transition: all 0.2s ease;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }

            .btn-secondary-alt:hover {
                background: #e5e7eb;
                transform: translateY(-1px);
            }
            
            .form-control {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                font-size: 14px;
                transition: all 0.2s ease;
                background: #ffffff;
            }
            
            .form-control:focus {
                outline: none;
                border-color: <?php echo $highlight_color; ?>;
                box-shadow: 0 0 0 3px <?php echo $highlight_color; ?>20;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            .form-group label {
                display: block;
                font-weight: 600;
                color: #374151;
                font-size: 14px;
                margin-bottom: 8px;
            }
            
            .form-help {
                font-size: 13px;
                color: #6b7280;
                margin-top: 6px;
            }
            
            .form-check {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 12px;
            }
            
            .form-check input[type="checkbox"] {
                width: 18px;
                height: 18px;
                cursor: pointer;
            }
            
            .form-check label {
                cursor: pointer;
                margin: 0;
                font-weight: 500;
                color: #1f2937;
                font-size: 14px;
            }

            .form-row {
                display: flex;
                gap: 20px;
                margin-bottom: 20px;
            }

            .form-row .form-group {
                margin-bottom: 0;
                flex-grow: 1;
            }

            .form-col-50 { flex-basis: 50%; }
            .form-col-60 { flex-basis: 60%; }
            .form-col-40 { flex-basis: 40%; }
            .form-col-30 { flex-basis: 30%; }
            
            .courses-grid {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 16px;
            }
            
            .json-preview {
                background: #f9fafb;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 16px;
                font-family: 'Courier New', monospace;
                font-size: 13px;
                white-space: pre-wrap;
                word-break: break-all;
            }
            
            .empty-state {
                text-align: center;
                padding: 48px;
                color: #6b7280;
                font-size: 16px;
            }
            
            .empty-state .dashicons {
                font-size: 48px;
                margin-bottom: 16px;
                opacity: 0.3;
            }
            
            .tutor-notification-success,
            .tutor-notification-error,
            .tutor-notification-info {
                padding: 16px;
                margin-bottom: 24px;
                display: flex;
                align-items: center;
                gap: 12px;
                border-radius: 8px;
            }
            
            .tutor-notification-success {
                background: #d1fae5;
                border: 1px solid #a7f3d0;
                color: #059669;
            }
            
            .tutor-notification-error {
                background: #fee2e2;
                border: 1px solid #fecaca;
                color: #dc2626;
            }
            
            .tutor-notification-info {
                background: #e0e7ff;
                border: 1px solid #c7d2fe;
                color: #4f46e5;
            }
            
            .tutor-notification-success .dashicons,
            .tutor-notification-error .dashicons,
            .tutor-notification-info .dashicons {
                font-size: 24px;
                flex-shrink: 0;
            }
            
            .tutor-notification-success p,
            .tutor-notification-error p,
            .tutor-notification-info p {
                margin: 0;
                font-size: 14px;
                font-weight: 500;
            }
            
            .section-subtitle {
                font-size: 16px;
                font-weight: 600;
                color: #374151;
                margin: 0 0 16px 0;
            }
            
            .badge {
                display: inline-block;
                padding: 4px 8px;
                background: #f3f4f6;
                color: #6b7280;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 600;
            }

            .badge-last-access {
                background-color: #28a745; /* Verde */
                color: #ffffff;
                text-decoration: none; /* Remove o sublinhado */
            }
            
            /* Estilos para o ordenador de coluna */
            .tutor-sortable-column {
                cursor: pointer;
                user-select: none;
            }
            .tutor-sortable-column:hover {
                color: #000;
            }
            .tutor-sortable-column .dashicons {
                font-size: 16px;
                vertical-align: middle;
                transition: transform 0.2s ease;
            }
            .tutor-sortable-column.sort-asc .dashicons {
                transform: rotate(180deg);
            }

            
            

            /* Estilos do Modal */
            .tutor-modal-overlay {
                display: none; /* Oculto por padrão */
                position: fixed;
                z-index: 1001; /* Acima de outros conteúdos */
                left: 0;
                top: 0;
                width: 100%;
                height: 100%;
                overflow: auto;
                background-color: rgba(0,0,0,0.6);
                padding-top: 60px;
            }

            .tutor-modal-content {
                background-color: #f3f4f6;
                margin: 5% auto;
                padding: 0;
                border: 0;
                width: 90%;
                max-width: 700px;
                border-radius: 12px;
                box-shadow: 0 5px 15px rgba(0,0,0,0.3);
                animation: animatetop 0.4s;
            }

            @keyframes animatetop {
                from {top: -300px; opacity: 0}
                to {top: 0; opacity: 1}
            }

            .tutor-modal-close {
                color: #aaa;
                font-size: 28px;
                font-weight: bold;
                cursor: pointer;
            }

            .tutor-modal-close:hover,
            .tutor-modal-close:focus {
                color: black;
            }
            
            .bulk-preview-item {
                border: 1px solid #e5e7eb;
                padding: 16px;
                margin-bottom: 16px;
                border-radius: 8px;
            }
            
            .preview-new {
                background: #d1fae5;
                border-color: #a7f3d0;
            }
            
            .preview-update {
                background: #fef3c7;
                border-color: #fde68a;
            }
            
            .preview-error {
                background: #fee2e2;
                border-color: #fecaca;
            }
            
            .preview-same {
                background: #f3f4f6;
                border-color: #e5e7eb;
            }
            
            .errors li {
                color: #dc2626;
            }
            
            .warnings li {
                color: #d97706;
            }
            
            /* Media Queries para Responsividade */
            @media (max-width: 768px) {
                .tutor-students-wrap {
                    padding: 16px;
                }
                
                .stats-grid {
                    grid-template-columns: 1fr;
                }
                
                .card-header {
                    flex-direction: column;
                    align-items: flex-start;
                    gap: 16px;
                }
                
                .bulk-actions {
                    flex-direction: column;
                    width: 100%;
                }
                
                .search-box input {
                    width: 100%;
                }
                
                /* Responsividade da tabela */
                .data-table {
                    display: block;
                    overflow-x: hidden;
                }
                
                .data-table thead {
                    display: none;
                }
                
                .data-table tbody {
                    display: block;
                }
                
                .data-table tbody tr {
                    display: block;
                    margin-bottom: 16px;
                    padding: 16px;
                    background: #ffffff;
                    border: 1px solid #e5e7eb;
                    border-radius: 8px;
                    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
                }
                
                .data-table tbody td {
                    display: block;
                    padding: 8px 0;
                    text-align: left;
                    border: none;
                }
                
                /* Ocultar checkbox e ID no mobile */
                .data-table tbody td:nth-child(1),
                .data-table tbody td:nth-child(2) {
                    display: none;
                }
                
                /* Nome de usuário como cabeçalho do card */
                .data-table tbody td:nth-child(3) {
                    font-weight: 600;
                    font-size: 16px;
                    padding-bottom: 12px;
                    margin-bottom: 12px;
                    border-bottom: 1px solid #e5e7eb;
                    color: #1f2937;
                }
                
                /* Adicionar labels antes dos valores */
                .data-table tbody td:nth-child(4)::before {
                    content: "E-mail: ";
                    font-weight: 600;
                    color: #6b7280;
                    display: inline-block;
                    margin-right: 4px;
                }
                
                .data-table tbody td:nth-child(5)::before {
                    content: "Celular: ";
                    font-weight: 600;
                    color: #6b7280;
                    display: inline-block;
                    margin-right: 4px;
                }
                
                .data-table tbody td:nth-child(6)::before {
                    content: "Progresso: ";
                    font-weight: 600;
                    color: #6b7280;
                    display: inline-block;
                    margin-right: 4px;
                }
                
                /* Ações empilhadas verticalmente */
                .data-table tbody td:nth-child(7) {
                    margin-top: 12px;
                    padding-top: 12px;
                    border-top: 1px solid #e5e7eb;
                }
                
                .data-table tbody td:nth-child(7)::before {
                    content: "Ações:";
                    font-weight: 600;
                    color: #6b7280;
                    display: block;
                    margin-bottom: 8px;
                }
                
                .actions-links {
                    display: flex;
                    flex-direction: column;
                    gap: 8px;
                    align-items: stretch;
                }
                
                .action-link {
                    display: block;
                    padding: 10px 16px;
                    text-align: center;
                    background: #f9fafb;
                    border: 1px solid #e5e7eb;
                    border-radius: 6px;
                    transition: all 0.2s ease;
                }
                
                .action-link:hover {
                    background: <?php echo $highlight_color; ?>10;
                    border-color: <?php echo $highlight_color; ?>40;
                }
                
                /* Formulários responsivos */
                .form-grid {
                    grid-template-columns: 1fr;
                }
                
                .courses-grid {
                    grid-template-columns: 1fr;
                }
                
                /* Botão adicionar aluno */
                #add-student-btn {
                    width: 100%;
                    justify-content: center;
                }
            }
        </style>

        <div class="tutor-students-wrap">
            <!-- Header -->
            <div class="students-header">
                <div>
                    <h1 class="students-title"><?php _e('Gerenciar Alunos', 'tutor-ead'); ?></h1>
                    <p class="students-subtitle"><?php _e('Gerencie todos os alunos cadastrados no sistema', 'tutor-ead'); ?></p>
                </div>
                <div style="display: flex; gap: 12px;">
                    <button id="add-student-btn" class="btn-primary">
                        <span class="dashicons dashicons-plus-alt"></span>
                        <?php _e('Adicionar Novo Aluno', 'tutor-ead'); ?>
                    </button>
                    <button id="add-bulk-students-btn" class="btn-secondary-alt">
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('Adicionar Múltiplos Alunos', 'tutor-ead'); ?>
                    </button>
                </div>
            </div>

            <!-- Carrossel de Usuários Online -->
            <div id="admin-online-users-container" class="online-users-carousel-container">
                <h3><span class="dashicons dashicons-visibility" style="color: #48bb78;"></span> Alunos Online Agora</h3>
                <div id="admin-online-users-carousel" class="online-users-carousel">
                    <!-- Avatares serão inseridos aqui pelo JavaScript -->
                </div>
            </div>
            
            <!-- Acordeão de Estatísticas -->
            <div class="tutor-accordion-wrap">
                <div class="tutor-accordion-trigger">
                    <span><span class="dashicons dashicons-chart-bar"></span> Estatísticas Gerais</span>
                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                </div>
                <div class="tutor-accordion-content">
                    <?php
                    // Coleta de todas as métricas
                    global $wpdb;
                    $log_table = $wpdb->prefix . 'tutoread_student_activity_log';
                    $total_alunos = count($students);
                    $total_cursos = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tutoread_courses");
                    $total_matriculas = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}matriculas");
                    
                    // Alunos ativos (baseado na tabela de log)
                    $alunos_ativos_30d = $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT user_id) FROM {$log_table} WHERE access_time >= %s", date('Y-m-d H:i:s', strtotime('-30 days'))));
                    $alunos_ativos_7d = $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT user_id) FROM {$log_table} WHERE access_time >= %s", date('Y-m-d H:i:s', strtotime('-7 days'))));
                    $alunos_ativos_1d = $wpdb->get_var($wpdb->prepare("SELECT COUNT(DISTINCT user_id) FROM {$log_table} WHERE access_time >= %s", date('Y-m-d H:i:s', strtotime('-1 day'))));

                    $novos_alunos_30d = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(ID) FROM {$wpdb->prefix}users u JOIN {$wpdb->prefix}usermeta um ON u.ID = um.user_id WHERE u.user_registered >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND um.meta_key = %s AND um.meta_value LIKE %s",
                        $wpdb->prefix . 'capabilities',
                        '%"+tutor_aluno+"%' /* Changed from '%\"tutor_aluno\"%' to '%"tutor_aluno"%' */
                    ));
                    $alunos_sem_telefone = $wpdb->get_var($wpdb->prepare(
                        "SELECT COUNT(u.ID) FROM {$wpdb->prefix}users u LEFT JOIN {$wpdb->prefix}tutoread_user_info i ON u.ID = i.user_id JOIN {$wpdb->prefix}usermeta um ON u.ID = um.user_id WHERE (i.phone_number IS NULL OR i.phone_number = '' OR i.phone_number = 'null') AND um.meta_key = %s AND um.meta_value LIKE %s",
                        $wpdb->prefix . 'capabilities',
                        '%"+tutor_aluno+"%' /* Changed from '%\"tutor_aluno\"%' to '%"tutor_aluno"%' */
                    ));
                    $total_alunos_matriculados = $wpdb->get_var("SELECT COUNT(DISTINCT user_id) FROM {$wpdb->prefix}matriculas");
                    $alunos_com_progresso = $wpdb->get_var("SELECT COUNT(DISTINCT aluno_id) FROM {$wpdb->prefix}progresso_aulas");
                    $taxa_engajamento = ($total_alunos_matriculados > 0) ? ($alunos_com_progresso / $total_alunos_matriculados) * 100 : 0;

                    // Cálculo do Progresso Médio Geral
                    $all_enrollments = $wpdb->get_results("SELECT user_id, course_id FROM {$wpdb->prefix}matriculas");
                    $total_progress_sum = 0;
                    $valid_enrollments_count = 0;
                    if ($all_enrollments) {
                        foreach ($all_enrollments as $enrollment) {
                            $total_lessons = $wpdb->get_var($wpdb->prepare("SELECT COUNT(l.id) FROM {$wpdb->prefix}tutoread_lessons l JOIN {$wpdb->prefix}tutoread_modules m ON l.module_id = m.id WHERE m.course_id = %d", $enrollment->course_id));
                            if ($total_lessons > 0) {
                                $completed_lessons = $wpdb->get_var($wpdb->prepare("SELECT COUNT(pa.id) FROM {$wpdb->prefix}progresso_aulas pa JOIN {$wpdb->prefix}tutoread_lessons l ON pa.aula_id = l.id JOIN {$wpdb->prefix}tutoread_modules m ON l.module_id = m.id WHERE pa.aluno_id = %d AND m.course_id = %d AND pa.status = 'concluido'", $enrollment->user_id, $enrollment->course_id));
                                $total_progress_sum += ($completed_lessons / $total_lessons) * 100;
                                $valid_enrollments_count++;
                            }
                        }
                    }
                    $progresso_medio_geral = ($valid_enrollments_count > 0) ? $total_progress_sum / $valid_enrollments_count : 0;

                    // Cálculo da Cobertura de Notas
                    $notas_lancadas = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}boletins");
                    $notas_esperadas = $wpdb->get_var("SELECT COUNT(m.id) FROM {$wpdb->prefix}matriculas m JOIN {$wpdb->prefix}tutoread_course_activities ca ON m.course_id = ca.course_id");
                    ?>
                    <div class="stats-grid">                            <!-- Total de Alunos -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-groups"></span>
                    </div>
                    <div class="stat-value"><?php echo $total_alunos; ?></div>
                    <div class="stat-label"><?php _e('Total de Alunos', 'tutor-ead'); ?></div>
                    <div class="stat-badge"><?php _e('Novos Alunos:', 'tutor-ead'); ?> <?php echo intval($novos_alunos_30d); ?></div>
                </div>
                <!-- Cursos Disponíveis -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-welcome-learn-more"></span>
                    </div>
                    <div class="stat-value"><?php echo intval($total_cursos); ?></div>
                    <div class="stat-label"><?php _e('Cursos Disponíveis', 'tutor-ead'); ?></div>
                </div>
                <!-- Total de Matrículas -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-id"></span>
                    </div>
                    <div class="stat-value"><?php echo intval($total_matriculas); ?></div>
                    <div class="stat-label"><?php _e('Total de Matrículas', 'tutor-ead'); ?></div>
                </div>
                <!-- Cadastro Incompleto -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-phone"></span>
                    </div>
                    <div class="stat-value"><?php echo intval($alunos_sem_telefone); ?></div>
                    <div class="stat-label"><?php _e('Cadastro Incompleto', 'tutor-ead'); ?></div>
                    <div class="stat-description"><?php _e('Alunos sem telefone válido', 'tutor-ead'); ?></div>
                </div>
                <!-- Taxa de Engajamento -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-chart-line"></span>
                    </div>
                    <div class="stat-value"><?php echo round($taxa_engajamento, 1); ?>%</div>
                    <div class="stat-label"><?php _e('Taxa de Engajamento', 'tutor-ead'); ?></div>
                    <div class="stat-description"><?php _e('Alunos que iniciaram aulas', 'tutor-ead'); ?></div>
                </div>
                <!-- Progresso Médio -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-performance"></span>
                    </div>
                    <div class="stat-value"><?php echo round($progresso_medio_geral, 1); ?>%</div>
                    <div class="stat-label"><?php _e('Progresso Médio', 'tutor-ead'); ?></div>
                    <div class="stat-description"><?php _e('Média de conclusão dos cursos', 'tutor-ead'); ?></div>
                </div>
                <!-- Cobertura de Notas -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-feedback"></span>
                    </div>
                    <div class="stat-value"><?php echo intval($notas_lancadas); ?> de <?php echo intval($notas_esperadas); ?></div>
                    <div class="stat-label"><?php _e('Cobertura de Notas', 'tutor-ead'); ?></div>
                    <div class="stat-description"><?php _e('Total de notas lançadas no boletim', 'tutor-ead'); ?></div>
                </div>
                <!-- Ativos no Mês -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-calendar-alt"></span>
                    </div>
                    <div class="stat-value"><?php echo intval($alunos_ativos_30d); ?></div>
                    <div class="stat-label"><?php _e('Ativos no Mês', 'tutor-ead'); ?></div>
                    <div class="stat-description"><?php _e('Alunos online recentemente', 'tutor-ead'); ?></div>
                </div>
                <!-- Ativos na Semana -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-calendar-alt"></span>
                    </div>
                    <div class="stat-value"><?php echo intval($alunos_ativos_7d); ?></div>
                    <div class="stat-label"><?php _e('Ativos na Semana', 'tutor-ead'); ?></div>
                    <div class="stat-description"><?php _e('Alunos online recentemente', 'tutor-ead'); ?></div>
                </div>
                <!-- Ativos Hoje -->
                <div class="stat-card">
                    <div class="stat-icon">
                        <span class="dashicons dashicons-calendar-alt"></span>
                    </div>
                    										<div class="stat-value"><?php echo intval($alunos_ativos_1d); ?></div>
                    					                    <div class="stat-label"><?php _e('Ativos Hoje', 'tutor-ead'); ?></div>
                    					                                        <div class="stat-description"><?php _e('Alunos online recentemente', 'tutor-ead'); ?></div>
                    					                                    </div>
                    					                                </div>
                    					                    
                    					                                </div>
                    					                                </div>                    					            
                    					            <!-- Tabela de Alunos -->            <div class="tutor-card">
                <form method="POST" id="bulk-action-form">
                    <div class="card-header">
                        <h2 class="card-title">
                            <span class="dashicons dashicons-list-view"></span>
                            <?php _e('Lista de Alunos', 'tutor-ead'); ?>
                        </h2>
                        <div class="bulk-actions">
                            <button id="migrate-phone-numbers" class="btn-secondary">
                                <span class="dashicons dashicons-database-import"></span>
                                <?php _e('Migrar Dados', 'tutor-ead'); ?>
                            </button>
                            <div id="migration-tooltip" class="migration-tooltip">
                                <?php _e('Migra nomes completos e números de celular de alunos da tabela antiga do WordPress para a nova tabela do TutorEAD. Isso garante a consistência dos dados e o uso de funcionalidades futuras.', 'tutor-ead'); ?>
                            </div>
                            <select name="bulk_action" class="form-control" style="width: auto;">
                                <option value=""><?php _e('Ações em Massa', 'tutor-ead'); ?></option>
                                <option value="delete"><?php _e('Excluir Alunos', 'tutor-ead'); ?></option>
                                <option value="reset_password"><?php _e('Resetar Senha (aluno01)', 'tutor-ead'); ?></option>
                                <option value="mass_enroll"><?php _e('Matricular em Cursos', 'tutor-ead'); ?></option>
                            </select>
                            <button type="submit" name="bulk_action_submit" class="btn-secondary">
                                <?php _e('Aplicar', 'tutor-ead'); ?>
                            </button>
                            <?php wp_nonce_field('bulk_action_student', 'bulk_action_nonce'); ?>
                            <div class="search-box">
                                <span class="dashicons dashicons-search"></span>
                                <input type="text" id="student-search" placeholder="<?php _e('Pesquisar aluno...', 'tutor-ead'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($students): ?>
                        <table class="data-table" id="students-table">
                            <thead>
                                <tr>
                                    <th style="width: 40px;"><input type="checkbox" id="select-all"></th>
                                    <th id="sort-by-name" class="tutor-sortable-column"><?php _e('Aluno', 'tutor-ead'); ?> <span class="dashicons dashicons-arrow-down-alt2"></span></th>
                                    <?php /* <th><?php _e('Progresso', 'tutor-ead'); ?></th> */ ?>
                                    <th id="sort-by-access" class="tutor-sortable-column"><?php _e('Último Acesso', 'tutor-ead'); ?> <span class="dashicons dashicons-arrow-down-alt2"></span></th>
                                    <th><?php _e('Ações', 'tutor-ead'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($students as $student): ?>
                                    <?php
                                    $user_info = $wpdb->get_row($wpdb->prepare("SELECT full_name, phone_number, cpf FROM {$wpdb->prefix}tutoread_user_info WHERE user_id = %d", $student->ID));
                                    $celular = $user_info ? $user_info->phone_number : get_user_meta($student->ID, 'celular', true); // Fallback para o meta antigo
                                    $full_name = $user_info && !empty($user_info->full_name) ? $user_info->full_name : $student->display_name;
                                    $reset_url = admin_url('admin-post.php?action=reset_student_password&user_id=' . esc_attr($student->ID) . '&reset_nonce=' . wp_create_nonce('reset_student_password_' . $student->ID));
                                    // Lógica para cálculo do progresso pode ser implementada aqui
                                    $progresso = '<span class="badge">Em andamento</span>';
                                    ?>
                                    <tr>
                                        <td><input type="checkbox" name="student_ids[]" value="<?php echo esc_attr($student->ID); ?>"></td>
                                        <td>
                                            <?php
                                            $cpf = $user_info ? $user_info->cpf : '';
                                            echo esc_html($full_name);
                                            if (!empty($cpf)) :
                                                echo '<span class="badge" style="margin-top: 5px; display: block; width: fit-content; background-color: #0073aa; color: #ffffff; border-radius: 9999px; padding: 4px 8px; font-size: 11px;">' . esc_html($cpf) . '</span>';
                                            endif;

                                            // Busca e exibe os cursos do aluno como badges
                                            $enrolled_courses = $wpdb->get_results($wpdb->prepare(
                                                "SELECT c.title FROM {$wpdb->prefix}tutoread_courses c JOIN {$wpdb->prefix}matriculas m ON c.id = m.course_id WHERE m.user_id = %d",
                                                $student->ID
                                            ));

                                            if ($enrolled_courses) {
                                                echo '<div style="margin-top: 8px; display: flex; flex-wrap: wrap; gap: 6px;">';
                                                foreach ($enrolled_courses as $course) {
                                                    $style = self::get_badge_style_from_string($course->title);
                                                    echo '<span style="' . $style . ' padding: 3px 8px; border-radius: 12px; font-size: 11px; font-weight: 600;">' . esc_html($course->title) . '</span>';
                                                }
                                                echo '</div>';
                                            }
                                            ?>
                                        </td>
                                        <td><?php echo esc_html($student->user_email); ?></td>
                                        <td><?php echo esc_html($celular ?: '—'); ?></td>
                                        <?php /* <td><?php echo $progresso; ?></td> */ ?>
                                        <td>
                                            <?php
                                            $last_activity = $wpdb->get_row($wpdb->prepare(
                                                "SELECT * FROM {$wpdb->prefix}tutoread_student_activity_log WHERE user_id = %d ORDER BY access_time DESC LIMIT 1",
                                                $student->ID
                                            ));

                                            if ($last_activity) {
                                                $timestamp = strtotime($last_activity->access_time);
                                                echo '<a href="#" class="badge badge-last-access view-activity-log" data-student-id="' . esc_attr($student->ID) . '" data-timestamp="' . esc_attr($timestamp) . '">' . esc_html(human_time_diff($timestamp, current_time('timestamp'))) . '</a>';
                                            } else {
                                                echo '<span class="badge" data-timestamp="0">—</span>';
                                            }
                                            ?>
                                        </td>
                                        <td class="actions-cell">
                                            <div class="tutor-actions-dropdown">
                                                <button type="button" class="tutor-actions-trigger">
                                                    <span class="dashicons dashicons-ellipsis"></span>
                                                </button>
                                                <div class="tutor-actions-menu">
                                                    <?php
                                                    // --- LINK DE PREVIEW COMO ALUNO ---
                                                    $impersonate_token = 'tutoread_impersonate_' . bin2hex(random_bytes(16));
                                                    set_transient($impersonate_token, [
                                                        'admin_id'   => get_current_user_id(),
                                                        'student_id' => $student->ID
                                                    ], 5 * MINUTE_IN_SECONDS);

                                                    $dashboard_page = get_page_by_path('dashboard-aluno');
                                                    $dashboard_url = $dashboard_page ? get_permalink($dashboard_page->ID) : home_url('/');

                                                    $preview_dashboard_url = add_query_arg([
                                                        'impersonate_token' => $impersonate_token
                                                    ], $dashboard_url);
                                                    ?>
                                                    <a href="<?php echo esc_url($preview_dashboard_url); ?>" class="tutor-actions-menu-item" target="_blank"><?php _e('Ver como aluno', 'tutor-ead'); ?></a>
                                                    <a href="<?php echo admin_url('admin.php?page=tutor-ead-edit-user&user_id=' . esc_attr($student->ID)); ?>" class="tutor-actions-menu-item"><?php _e('Editar', 'tutor-ead'); ?></a>
                                                    <a href="<?php echo esc_url($reset_url); ?>" class="tutor-actions-menu-item"><?php _e('Resetar Senha', 'tutor-ead'); ?></a>
                                                    <a href="<?php echo admin_url('admin.php?page=tutor-ead-temp-login&student_id=' . esc_attr($student->ID)); ?>" class="tutor-actions-menu-item"><?php _e('Link de Login', 'tutor-ead'); ?></a>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div class="empty-state">
                            <span class="dashicons dashicons-info-outline"></span>
                            <p><?php _e('Nenhum aluno encontrado.', 'tutor-ead'); ?></p>
                        </div>
                    <?php endif; ?>
                </form>
            </div>

            

            <!-- Formulário de Adicionar Aluno em Modal -->
            <div id="add-student-modal" class="tutor-modal-overlay">
                <div class="tutor-modal-content">
                    <div id="add-student-form" class="tutor-card" style="margin-bottom: 0;">
                        <div class="card-title">
                            <h2 style="margin:0;font-size:20px;">
                                <span class="dashicons dashicons-admin-users"></span>
                                <?php _e('Adicionar Novo Aluno', 'tutor-ead'); ?>
                            </h2>
                            <span class="tutor-modal-close">&times;</span>
                        </div>
                        <form method="POST">
                            <div class="form-group">
                                <label for="student_username"><?php _e('Nome de Usuário', 'tutor-ead'); ?></label>
                                <input type="text" name="student_username" id="student_username" class="form-control" placeholder="<?php _e('Ex: joao.silva', 'tutor-ead'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="student_email"><?php _e('E-mail', 'tutor-ead'); ?></label>
                                <input type="email" name="student_email" id="student_email" class="form-control" placeholder="<?php _e('Ex: joao.silva@exemplo.com', 'tutor-ead'); ?>" required>
                            </div>
                            <div class="form-group">
                                <label for="student_celular"><?php _e('Celular', 'tutor-ead'); ?></label>
                                <input type="text" name="student_celular" id="student_celular" class="form-control" placeholder="(11) 98765-4321">
                            </div>
                            <div class="form-group">
                                <label for="student_password"><?php _e('Senha', 'tutor-ead'); ?></label>
                                <input type="password" name="student_password" id="student_password" class="form-control" placeholder="<?php _e('Mínimo 8 caracteres', 'tutor-ead'); ?>" required>
                            </div>

                            <?php
                            // Carrega os campos de registro personalizados
                            $registration_fields = get_option('tutor_ead_registration_fields', []);
                            $fields_config = [
                                'full_name' => ['label' => __('Nome Completo', 'tutor-ead'), 'always_show' => true],
                                'cpf' => ['label' => __('CPF', 'tutor-ead')],
                                'rg' => ['label' => __('RG', 'tutor-ead')],
                                'endereco' => ['label' => __('Endereço', 'tutor-ead')],
                                'cidade' => ['label' => __('Cidade', 'tutor-ead')],
                                'estado' => ['label' => __('Estado', 'tutor-ead')],
                                'cep' => ['label' => __('CEP', 'tutor-ead')],
                                'valor_contrato' => ['label' => __('Valor de Contrato', 'tutor-ead')]
                            ];

                            function render_field($key, $config) {
                                $placeholder = isset($config['placeholder']) ? esc_attr($config['placeholder']) : esc_attr($config['label']);
                                echo '<div class="form-group">';
                                echo '<label for="student_' . esc_attr($key) . '">' . esc_html($config['label']) . '</label>';
                                echo '<input type="text" name="student_' . esc_attr($key) . '" id="student_' . esc_attr($key) . '" class="form-control" placeholder="' . $placeholder . '">';
                                echo '</div>';
                            }

                            // Nome Completo (sempre visível)
                            render_field('full_name', $fields_config['full_name']);

                            // Linha CPF e RG
                            if (in_array('cpf', $registration_fields) || in_array('rg', $registration_fields)) {
                                echo '<div class="form-row">';
                                if (in_array('cpf', $registration_fields)) { echo '<div class="form-col-50">'; render_field('cpf', $fields_config['cpf']); echo '</div>'; }
                                if (in_array('rg', $registration_fields)) { echo '<div class="form-col-50">'; render_field('rg', $fields_config['rg']); echo '</div>'; }
                                echo '</div>';
                            }

                            // Linha Endereço e Cidade
                            if (in_array('endereco', $registration_fields) || in_array('cidade', $registration_fields)) {
                                echo '<div class="form-row">';
                                if (in_array('endereco', $registration_fields)) { echo '<div class="form-col-60">'; render_field('endereco', $fields_config['endereco']); echo '</div>'; }
                                if (in_array('cidade', $registration_fields)) { echo '<div class="form-col-40">'; render_field('cidade', $fields_config['cidade']); echo '</div>'; }
                                echo '</div>';
                            }

                            // Linha Estado e CEP
                            if (in_array('estado', $registration_fields) || in_array('cep', $registration_fields)) {
                                echo '<div class="form-row">';
                                if (in_array('estado', $registration_fields)) { echo '<div class="form-col-60">'; render_field('estado', $fields_config['estado']); echo '</div>'; }
                                if (in_array('cep', $registration_fields)) { echo '<div class="form-col-40">'; render_field('cep', $fields_config['cep']); echo '</div>'; }
                                echo '</div>';
                            }

                            // Linha Valor de Contrato
                            if (in_array('valor_contrato', $registration_fields)) {
                                echo '<div class="form-row">';
                                echo '<div class="form-col-30">';
                                render_field('valor_contrato', $fields_config['valor_contrato']);
                                echo '</div>';
                                echo '</div>';
                            }
                            ?>
                            
                            <!-- Matrículas do Novo Aluno -->
                            <div class="form-group">
                                <label><?php _e('Cursos do Aluno', 'tutor-ead'); ?></label>
                                <div class="courses-grid">
                                    <?php
                                    $all_courses = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}tutoread_courses", ARRAY_A);
                                    if ($all_courses) {
                                        foreach ($all_courses as $course) {
                                            echo '<label class="form-check">';
                                            echo '<input type="checkbox" name="new_student_courses[]" value="' . esc_attr($course['id']) . '">';
                                            echo '<span>' . esc_html($course['title']) . '</span>';
                                            echo '</label>';
                                        }
                                    } else {
                                        echo '<p class="form-help">' . __('Nenhum curso disponível.', 'tutor-ead') . '</p>';
                                    }
                                    ?>
                                </div>
                            </div>
                            <p style="margin-top: 24px;">
                                <button type="submit" name="add_student" class="btn-primary">
                                    <span class="dashicons dashicons-saved"></span>
                                    <?php _e('Salvar Aluno', 'tutor-ead'); ?>
                                </button>
                            </p>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Modal de Adicionar Vários Alunos via JSON -->            <div id="add-bulk-students-modal" class="tutor-modal-overlay">                <div class="tutor-modal-content">                    <div class="tutor-card" style="margin-bottom: 0;">                        <div class="card-title">                            <h2 style="margin:0;font-size:20px;">                                <span class="dashicons dashicons-upload"></span>                                <?php _e('Adicionar Vários Alunos via JSON', 'tutor-ead'); ?>                            </h2>                            <span class="tutor-modal-close">&times;</span>                        </div>                        <p class="form-help" style="margin-bottom: 20px;"><?php _e('Digite os dados dos alunos abaixo e use o seguinte prompt para convertê-los para JSON: "Converta os dados a seguir para o formato JSON com a chave \'users\'".', 'tutor-ead'); ?></p>                        <p style="margin-bottom: 16px;"><strong><?php _e('Exemplo de JSON:', 'tutor-ead'); ?></strong></p>                        <div class="json-preview">{  "users": [    {      "nome": "João da Silva",      "email": "joao@exemplo.com",      "username": "joaosilva",      "celular": "(11) 91234-5678",      "password": "minhasenha"    }  ]}</div>                        <form method="POST" id="bulk-import-form">                            <div class="form-group">                                <label for="bulk_students_json"><?php _e('Cole o JSON aqui:', 'tutor-ead'); ?></label>                                <textarea name="bulk_students_json" id="bulk_students_json" rows="10" class="form-control" placeholder="<?php _e('Cole o JSON dos alunos aqui...', 'tutor-ead'); ?>"></textarea>                            </div>                            <p>                                <button type="button" id="json-preview-btn" class="btn-secondary" style="margin-right: 8px;">                                    <span class="dashicons dashicons-visibility"></span>                                    <?php _e('Pré-visualizar', 'tutor-ead'); ?>                                </button>                                <button type="submit" name="bulk_add_students" class="btn-primary">                                    <span class="dashicons dashicons-upload"></span>                                    <?php _e('Importar Alunos', 'tutor-ead'); ?>                                </button>                            </p>                            <!-- A área de preview será atualizada via AJAX -->                            <div id="bulk-preview-area" style="margin-top:20px;"></div>                        </form>                    </div>                </div>            </div>

            <!-- Modal de Logs de Atividade -->
            <div id="activity-log-modal" class="tutor-modal-overlay">
                <div class="tutor-modal-content">
                    <div class="tutor-card" style="margin-bottom: 0;">
                        <div class="card-title">
                            <h2 style="margin:0;font-size:20px;">
                                <span class="dashicons dashicons-list-view"></span>
                                <?php _e('Logs de Atividade do Aluno', 'tutor-ead'); ?>
                            </h2>
                            <span class="tutor-modal-close">&times;</span>
                        </div>
                        <div id="log-details"></div>
                    </div>
                </div>
            </div>
        </div>

        <script>
            // Script para ações na listagem e formulários

            // Seleciona checkboxes na tabela principal
            document.getElementById("select-all").addEventListener("click", function(){
                var checkboxes = document.querySelectorAll("input[name='student_ids[]']");
                checkboxes.forEach(function(cb) {
                    cb.checked = document.getElementById("select-all").checked;
                });
            });

            (function(){
                var lastChecked = null;
                var checkboxes = document.querySelectorAll("input[name='student_ids[]']");
                checkboxes.forEach(function(checkbox) {
                    checkbox.addEventListener("click", function(e) {
                        if (!lastChecked) {
                            lastChecked = this;
                            return;
                        }
                        if (e.shiftKey) {
                            var start = Array.prototype.indexOf.call(checkboxes, lastChecked);
                            var end = Array.prototype.indexOf.call(checkboxes, this);
                            checkboxes.forEach(function(cb, i) {
                                if (i >= Math.min(start, end) && i <= Math.max(start, end)) {
                                    cb.checked = lastChecked.checked;
                                }
                            });
                        }
                        lastChecked = this;
                    });
                });
            })();

            // Controle do Modal de Adicionar Aluno
            document.addEventListener('DOMContentLoaded', function() {
                // Ordenação da tabela de alunos
                const sortTrigger = document.getElementById('sort-by-name');
                if (sortTrigger) {
                    sortTrigger.addEventListener('click', function() {
                        const tableBody = document.querySelector('#students-table tbody');
                        const rows = Array.from(tableBody.querySelectorAll('tr'));
                        const isAsc = this.classList.toggle('sort-asc');

                        rows.sort((a, b) => {
                            // A célula do nome do aluno é a segunda (índice 1)
                            const nameA = a.cells[1].textContent.trim().toLowerCase();
                            const nameB = b.cells[1].textContent.trim().toLowerCase();

                            if (nameA < nameB) {
                                return isAsc ? -1 : 1;
                            }
                            if (nameA > nameB) {
                                return isAsc ? 1 : -1;
                            }
                            return 0;
                        });

                        // Remonta a tabela com as linhas ordenadas
                        rows.forEach(row => tableBody.appendChild(row));
                    });
                }

                // Ordenação por Último Acesso
                const sortAccessTrigger = document.getElementById('sort-by-access');
                if (sortAccessTrigger) {
                    // Ordenação inicial (mais recente primeiro)
                    sortAccessTrigger.click();

                    sortAccessTrigger.addEventListener('click', function() {
                        const tableBody = document.querySelector('#students-table tbody');
                        const rows = Array.from(tableBody.querySelectorAll('tr'));
                        // Se não tem a classe, o próximo clique será ascendente (mais antigo primeiro)
                        // A lógica é invertida aqui: o padrão é decrescente (mais novo primeiro)
                        const isAsc = !this.classList.contains('sort-asc');
                        this.classList.toggle('sort-asc');

                        rows.sort((a, b) => {
                            // A célula de Último Acesso é a sexta (índice 5)
                            const timeA = parseInt(a.cells[5].firstElementChild.dataset.timestamp, 10);
                            const timeB = parseInt(b.cells[5].firstElementChild.dataset.timestamp, 10);

                            if (isAsc) {
                                return timeA - timeB; // Mais antigo primeiro
                            } else {
                                return timeB - timeA; // Mais novo primeiro
                            }
                        });

                        rows.forEach(row => tableBody.appendChild(row));
                    });
                }

                // Lógica para o novo dropdown de ações
                document.querySelectorAll('.tutor-actions-trigger').forEach(button => {
                    button.addEventListener('click', function(event) {
                        event.stopPropagation();
                        const menu = this.nextElementSibling;
                        const allMenus = document.querySelectorAll('.tutor-actions-menu');

                        // Fecha outros menus abertos
                        allMenus.forEach(m => {
                            if (m !== menu) {
                                m.classList.remove('is-open');
                            }
                        });

                        // Alterna o menu atual
                        menu.classList.toggle('is-open');
                    });
                });

                // Fecha o menu se clicar fora
                window.addEventListener('click', function() {
                    document.querySelectorAll('.tutor-actions-menu.is-open').forEach(menu => {
                        menu.classList.remove('is-open');
                    });
                });

                // Modal de Aluno Único
                var modalSingle = document.getElementById("add-student-modal");
                var btnSingle = document.getElementById("add-student-btn");
                var spanSingle = modalSingle.querySelector(".tutor-modal-close");

                if (btnSingle) {
                    btnSingle.onclick = function() { modalSingle.style.display = "block"; }
                }
                if (spanSingle) {
                    spanSingle.onclick = function() { modalSingle.style.display = "none"; }
                }

                // Modal de Alunos em Massa
                var modalBulk = document.getElementById("add-bulk-students-modal");
                var btnBulk = document.getElementById("add-bulk-students-btn");
                var spanBulk = modalBulk.querySelector(".tutor-modal-close");

                if (btnBulk) {
                    btnBulk.onclick = function() { modalBulk.style.display = "block"; }
                }
                if (spanBulk) {
                    spanBulk.onclick = function() { modalBulk.style.display = "none"; }
                }

                // Fechar ao clicar fora
                window.onclick = function(event) {
                    if (event.target == modalSingle) {
                        modalSingle.style.display = "none";
                    }
                    if (event.target == modalBulk) {
                        modalBulk.style.display = "none";
                    }
                }
            });

            // Busca simples de alunos
            document.getElementById("student-search").addEventListener("keyup", function() {
                var term = this.value.toLowerCase();
                var rows = document.querySelectorAll("#students-table tbody tr");
                rows.forEach(function(row) {
                    var text = row.textContent.toLowerCase();
                    row.style.display = text.indexOf(term) > -1 ? "" : "none";
                });
            });

            // Controle do Modal de Logs de Atividade
            var logModal = document.getElementById('activity-log-modal');
            var logClose = logModal.querySelector('.tutor-modal-close');
            logClose.onclick = function() {
                logModal.style.display = 'none';
            }

            document.querySelectorAll('.view-activity-log').forEach(function(btn) {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var studentId = this.dataset.studentId;
                    var modalBody = logModal.querySelector('#log-details');
                    modalBody.innerHTML = '<p>Carregando logs...</p>';
                    logModal.style.display = 'block';

                    jQuery.ajax({
                        url: TutorEAD_Ajax.ajax_url,
                        type: 'POST',
                        data: {
                            action: 'tutoread_get_student_activity_logs',
                            nonce: TutorEAD_Ajax.nonce,
                            student_id: studentId
                        },
                        success: function(response) {
                            if (response.success) {
                                var logs = response.data;
                                var html = '<table class="data-table"><thead><tr><th>Data</th><th>Atividade</th><th>Detalhes</th></tr></thead><tbody>';
                                if (logs.length > 0) {
                                    logs.forEach(function(log) {
                                        html += '<tr><td>' + log.access_time + '</td><td>' + log.activity_type + '</td><td>' + log.details + '</td></tr>';
                                    });
                                } else {
                                    html += '<tr><td colspan="3">Nenhum log de atividade encontrado.</td></tr>';
                                }
                                html += '</tbody></table>';
                                modalBody.innerHTML = html;
                            }
                        }
                    });
                });
            });

            // --- FUNÇÕES DE MÁSCARA E VALIDAÇÃO ---

            function applyValidation(input, validationFn, errorMessage) {
                if (!input) return;
                input.addEventListener('blur', function(e) {
                    var value = e.target.value;
                    var errorP = e.target.parentNode.querySelector('.form-error');
                    if (errorP) {
                        errorP.remove();
                    }
                    if (value.length > 0 && !validationFn(value)) {
                        e.target.style.borderColor = 'red';
                        var error = document.createElement('p');
                        error.className = 'form-error';
                        error.style.color = 'red';
                        error.style.fontSize = '12px';
                        error.textContent = errorMessage;
                        e.target.parentNode.appendChild(error);
                    } else {
                        e.target.style.borderColor = '';
                    }
                });
            }

            function applyMask(input, maskFn) {
                if (!input) return;
                input.addEventListener('input', maskFn);
            }

            // Validação de E-mail
            function validateEmail(email) {
                const re = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
                return re.test(String(email).toLowerCase());
            }

            // Validação de Telefone
            function validatePhone(phone) {
                var value = phone.replace(/\D/g, '');
                return value.length === 10 || value.length === 11;
            }

            // Máscara de Telefone
            var phoneMask = function(e) {
                var value = e.target.value.replace(/\D/g, '');
                var size = value.length;
                if (size > 10) {
                    value = value.replace(/^(\d\d)(\d{5})(\d{4}).*/, '($1) $2-$3');
                } else if (size > 5) {
                    value = value.replace(/^(\d\d)(\d{4})(\d{0,4}).*/, '($1) $2-$3');
                } else if (size > 1) {
                    value = value.replace(/^(\d*)(.*)/, '($1$2');
                } else {
                    value = value.replace(/^(\d*)/, '($1');
                }
                e.target.value = value.slice(0, 15);
            };

            // Validação de CPF
            function validateCPF(cpf) {
                cpf = cpf.replace(/[^\d]+/g, '');
                if (cpf == '' || cpf.length !== 11 || /^(\d)\1+$/.test(cpf)) return false;
                var add = 0;
                for (var i = 0; i < 9; i++) add += parseInt(cpf.charAt(i)) * (10 - i);
                var rev = 11 - (add % 11);
                if (rev == 10 || rev == 11) rev = 0;
                if (rev != parseInt(cpf.charAt(9))) return false;
                add = 0;
                for (i = 0; i < 10; i++) add += parseInt(cpf.charAt(i)) * (11 - i);
                rev = 11 - (add % 11);
                if (rev == 10 || rev == 11) rev = 0;
                if (rev != parseInt(cpf.charAt(10))) return false;
                return true;
            }

            // Máscara de CPF
            var cpfMask = function(e) {
                var value = e.target.value.replace(/\D/g, '');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d)/, '$1.$2');
                value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                e.target.value = value.slice(0, 14);
            };

            // Validação de RG (simples, verifica comprimento)
            function validateRG(rg) {
                return rg.length >= 5; // Exemplo: mínimo de 5 caracteres
            }

            // Máscara de CEP
            var cepMask = function(e) {
                var value = e.target.value.replace(/\D/g, '');
                value = value.replace(/^(\d{5})(\d)/, '$1-$2');
                e.target.value = value.slice(0, 9);
            };

            // Validação de CEP
            function validateCEP(cep) {
                return /^\d{5}-\d{3}$/.test(cep);
            }

            // Máscara de Moeda
            var currencyMask = function(e) {
                var value = e.target.value.replace(/\D/g, '');
                if (value === '') {
                    e.target.value = '';
                    return;
                }
                value = (parseInt(value, 10) / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
                e.target.value = value;
            };


            // --- LÓGICA PRINCIPAL PARA APLICAR AS MÁSCARAS ---

            var btnSingle = document.getElementById("add-student-btn");
            if (btnSingle) {
                btnSingle.addEventListener('click', function() {
                    setTimeout(function() { // Timeout para garantir que o modal esteja no DOM
                        var emailInput = document.getElementById('student_email');
                        var phoneInput = document.getElementById('student_celular');
                        var cpfInput = document.getElementById('student_cpf');
                        var rgInput = document.getElementById('student_rg');
                        var cepInput = document.getElementById('student_cep');
                        var contractValueInput = document.getElementById('student_valor_contrato');
                        
                        applyValidation(emailInput, validateEmail, 'Formato de e-mail inválido.');

                        applyMask(phoneInput, phoneMask);
                        applyValidation(phoneInput, validatePhone, 'Telefone inválido. Use (XX) XXXXX-XXXX ou (XX) XXXX-XXXX.');

                        applyMask(cpfInput, cpfMask);
                        applyValidation(cpfInput, validateCPF, 'CPF inválido.');

                        applyValidation(rgInput, validateRG, 'RG deve ter no mínimo 5 caracteres.');
                        
                        applyMask(cepInput, cepMask);
                        applyValidation(cepInput, validateCEP, 'Formato de CEP inválido. Use XXXXX-XXX.');
                        
                        applyMask(contractValueInput, currencyMask);
                    }, 100);
                });
            }
        </script>
    <?php
    }

    /**
     * Renderiza a página Editar Usuário.
     */
    public static function edit_user_page() {
        global $wpdb;
        
        // Pega a cor de destaque das opções
        $highlight_color = get_option('tutor_ead_highlight_color', '#0073aa');

        $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
        $user = get_userdata($user_id);

        if (!$user) {
            echo '<div class="tutor-notification-error">
                    <span class="dashicons dashicons-no"></span>
                    <p>' . __('Usuário não encontrado.', 'tutor-ead') . '</p>
                  </div>';
            return;
        }

        $user_role = isset($user->roles[0]) ? $user->roles[0] : '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $user_email    = sanitize_email($_POST['user_email']);
            $new_user_role = sanitize_text_field($_POST['user_role']);
            $student_celular = !empty($_POST['student_celular']) ? sanitize_text_field($_POST['student_celular']) : '';
            $course_ids    = isset($_POST['course_ids']) ? array_map('intval', $_POST['course_ids']) : [];

            wp_update_user(['ID' => $user_id, 'user_email' => $user_email]);
            update_user_meta($user_id, 'celular', $student_celular);

            if ($new_user_role !== $user_role) {
                $user->set_role($new_user_role);
            }

            // Atualiza as matrículas (cursos associados)
            $wpdb->delete("{$wpdb->prefix}matriculas", ['user_id' => $user_id]);
            foreach ($course_ids as $course_id) {
                $wpdb->insert(
                    "{$wpdb->prefix}matriculas",
                    ['user_id' => $user_id, 'course_id' => $course_id, 'role' => $new_user_role]
                );
            }

            // === Atualização do Progresso dos Cursos ===
            if (isset($_POST['module_progress']) && isset($_POST['lesson_progress'])) {
                foreach ($course_ids as $course_id) {
                    $mod_input = isset($_POST['module_progress'][$course_id]) ? intval($_POST['module_progress'][$course_id]) : 1;
                    $lesson_input = isset($_POST['lesson_progress'][$course_id]) ? intval($_POST['lesson_progress'][$course_id]) : 1;

                    // Recupera os módulos do curso ordenados
                    $modules = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}tutoread_modules WHERE course_id = %d ORDER BY id ASC",
                            $course_id
                        )
                    );
                    if (!$modules || count($modules) == 0) {
                        continue;
                    }
                    $mod_index = ($mod_input > count($modules)) ? count($modules) - 1 : $mod_input - 1;
                    $selected_module = $modules[$mod_index];

                    // Recupera as aulas do módulo ordenadas
                    $lessons_in_mod = $wpdb->get_results(
                        $wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}tutoread_lessons WHERE module_id = %d ORDER BY id ASC",
                            $selected_module->id
                        )
                    );
                    if (!$lessons_in_mod || count($lessons_in_mod) == 0) {
                        continue;
                    }
                    $lesson_index = ($lesson_input > count($lessons_in_mod)) ? count($lessons_in_mod) - 1 : $lesson_input - 1;
                    $selected_lesson = $lessons_in_mod[$lesson_index];

                    // Remove os registros antigos de progresso para este curso
                    $wpdb->query(
                        $wpdb->prepare(
                            "DELETE pa FROM {$wpdb->prefix}progresso_aulas pa
                             JOIN {$wpdb->prefix}tutoread_lessons tl ON pa.aula_id = tl.id
                             JOIN {$wpdb->prefix}tutoread_modules tm ON tl.module_id = tm.id
                             WHERE pa.aluno_id = %d AND tm.course_id = %d",
                            $user_id,
                            $course_id
                        )
                    );

                    // Recupera todas as aulas do curso ordenadas por módulo e aula
                    $all_lessons = $wpdb->get_col(
                        $wpdb->prepare(
                            "SELECT tl.id FROM {$wpdb->prefix}tutoread_lessons tl
                             JOIN {$wpdb->prefix}tutoread_modules tm ON tl.module_id = tm.id
                             WHERE tm.course_id = %d
                             ORDER BY tm.id ASC, tl.id ASC",
                            $course_id
                        )
                    );

                    // Determina o índice da aula selecionada
                    $target_index = array_search($selected_lesson->id, $all_lessons);
                    if ($target_index === false) {
                        $target_index = 0;
                    } else {
                        $target_index = $target_index + 1;
                    }

                    // Insere registros de progresso para todas as aulas até o ponto definido
                    foreach ($all_lessons as $idx => $lesson_id) {
                        if ($idx < $target_index) {
                            $wpdb->insert(
                                "{$wpdb->prefix}progresso_aulas",
                                [
                                    'aluno_id' => $user_id,
                                    'aula_id'  => $lesson_id,
                                    'status'   => 'concluido',
                                    'data_atualizacao' => current_time('mysql')
                                ]
                            );
                        }
                    }
                }
            }
            echo '<div class="tutor-notification-success">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <p>' . __('Usuário atualizado com sucesso!', 'tutor-ead') . '</p>
                  </div>';
        }

        $courses = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}tutoread_courses");
        $associated_courses = $wpdb->get_col(
            $wpdb->prepare("SELECT course_id FROM {$wpdb->prefix}matriculas WHERE user_id = %d", $user_id)
        );
        $student_celular = get_user_meta($user_id, 'celular', true);
        ?>
        
        <!-- Estilos Modernos para Editar Usuário -->
        <style>
            .tutor-edit-user-wrap {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                background: #f3f4f6;
                margin: -20px;
                padding: 32px;
                min-height: 100vh;
            }
            
            .tutor-edit-user-wrap * {
                box-sizing: border-box;
            }
            
            .edit-header {
                margin-bottom: 32px;
            }
            
            .edit-title {
                font-size: 32px;
                font-weight: 600;
                color: #1f2937;
                margin: 0 0 8px 0;
            }
            
            .edit-subtitle {
                color: #6b7280;
                font-size: 16px;
                margin: 0;
            }
            
            .edit-card {
                background: #ffffff;
                border: 1px solid #e5e7eb;
                border-radius: 12px;
                padding: 24px;
                box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
                margin-bottom: 24px;
            }
            
            .edit-card-title {
                font-size: 18px;
                font-weight: 600;
                color: #1f2937;
                margin: 0 0 20px 0;
                padding-bottom: 16px;
                border-bottom: 1px solid #e5e7eb;
                display: flex;
                align-items: center;
                gap: 12px;
            }
            
            .edit-card-title .dashicons {
                font-size: 24px;
                color: <?php echo $highlight_color; ?>;
            }
            
            .form-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                gap: 24px;
            }
            
            .progress-section {
                background: #f9fafb;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                padding: 20px;
                margin-top: 20px;
            }
            
            .progress-title {
                font-size: 16px;
                font-weight: 600;
                color: #374151;
                margin: 0 0 16px 0;
            }
            
            .progress-item {
                margin-bottom: 20px;
                padding-bottom: 20px;
                border-bottom: 1px solid #e5e7eb;
            }
            
            .progress-item:last-child {
                margin-bottom: 0;
                padding-bottom: 0;
                border-bottom: none;
            }
            
            .progress-course-name {
                font-weight: 600;
                color: #1f2937;
                margin-bottom: 12px;
                font-size: 15px;
            }
            
            .progress-inputs {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 16px;
                margin-bottom: 8px;
            }
            
            .progress-input-group {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .progress-input-group label {
                font-weight: 500;
                color: #6b7280;
                font-size: 14px;
                min-width: 50px;
            }
            
            .progress-input-group input {
                width: 60px;
                padding: 6px 10px;
                border: 1px solid #e5e7eb;
                border-radius: 6px;
                font-size: 14px;
                text-align: center;
            }
            
            .progress-info {
                font-size: 13px;
                color: #6b7280;
                line-height: 1.5;
            }
            
            /* Herdar estilos dos outros componentes */
            .form-control {
                width: 100%;
                padding: 10px 12px;
                border: 1px solid #e5e7eb;
                border-radius: 8px;
                font-size: 14px;
                transition: all 0.2s ease;
                background: #ffffff;
            }
            
            .form-control:focus {
                outline: none;
                border-color: <?php echo $highlight_color; ?>;
                box-shadow: 0 0 0 3px <?php echo $highlight_color; ?>20;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            .form-group label {
                display: block;
                font-weight: 600;
                color: #374151;
                font-size: 14px;
                margin-bottom: 8px;
            }
            
            .form-help {
                font-size: 13px;
                color: #6b7280;
                margin-top: 6px;
            }
            
            .form-check {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-bottom: 12px;
            }
            
            .form-check input[type="checkbox"] {
                width: 18px;
                height: 18px;
                cursor: pointer;
            }
            
            .form-check label {
                cursor: pointer;
                margin: 0;
                font-weight: 500;
                color: #1f2937;
                font-size: 14px;
            }
            
            .courses-selection {
                display: grid;
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
                gap: 16px;
            }
            
            .btn-primary {
                background: <?php echo $highlight_color; ?>;
                color: #ffffff;
                border: none;
                padding: 12px 24px;
                border-radius: 8px;
                font-weight: 600;
                font-size: 14px;
                cursor: pointer;
                transition: all 0.2s ease;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }
            
            .btn-primary:hover {
                background: <?php echo $highlight_color; ?>e6;
                transform: translateY(-1px);
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            }
            
            .btn-primary .dashicons {
                font-size: 18px;
            }
            
            .tutor-notification-success,
            .tutor-notification-error {
                padding: 16px;
                margin-bottom: 24px;
                display: flex;
                align-items: center;
                gap: 12px;
                border-radius: 8px;
            }
            
            .tutor-notification-success {
                background: #d1fae5;
                border: 1px solid #a7f3d0;
                color: #059669;
            }
            
            .tutor-notification-error {
                background: #fee2e2;
                border: 1px solid #fecaca;
                color: #dc2626;
            }
            
            .tutor-notification-success .dashicons,
            .tutor-notification-error .dashicons {
                font-size: 24px;
                flex-shrink: 0;
            }
            
            .tutor-notification-success p,
            .tutor-notification-error p {
                margin: 0;
                font-size: 14px;
                font-weight: 500;
            }
            
            @media (max-width: 768px) {
                .tutor-edit-user-wrap {
                    padding: 16px;
                }
                
                .form-grid {
                    grid-template-columns: 1fr;
                }
                
                .progress-inputs {
                    grid-template-columns: 1fr;
                }
                
                .courses-selection {
                    grid-template-columns: 1fr;
                }
            }
        </style>
        
        <div class="tutor-edit-user-wrap">
            <!-- Header -->
            <div class="edit-header">
                <h1 class="edit-title"><?php _e('Editar Usuário', 'tutor-ead'); ?></h1>
                <p class="edit-subtitle"><?php _e('Atualize as informações e configurações do usuário', 'tutor-ead'); ?></p>
            </div>
            
            <form method="POST">
                <!-- Informações Básicas -->
                <div class="edit-card">
                    <h2 class="edit-card-title">
                        <span class="dashicons dashicons-admin-users"></span>
                        <?php _e('Informações Básicas', 'tutor-ead'); ?>
                    </h2>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label><?php _e('Nome de Usuário', 'tutor-ead'); ?></label>
                            <div class="form-control" style="background: #f9fafb; cursor: not-allowed;">
                                <?php echo esc_html($user->user_login); ?>
                            </div>
                            <div class="form-help"><?php _e('O nome de usuário não pode ser alterado', 'tutor-ead'); ?></div>
                        </div>
                        
                        <div class="form-group">
                            <label for="user_email"><?php _e('E-mail', 'tutor-ead'); ?></label>
                            <input type="email" name="user_email" id="user_email" class="form-control" value="<?php echo esc_html($user->user_email); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="student_celular"><?php _e('Celular', 'tutor-ead'); ?></label>
                            <input type="text" name="student_celular" id="student_celular" class="form-control" value="<?php echo esc_attr($student_celular); ?>" placeholder="(11) 98765-4321">
                        </div>
                        
                        <div class="form-group">
                            <label for="user_role"><?php _e('Função', 'tutor-ead'); ?></label>
                            <select name="user_role" id="user_role" class="form-control" required>
                                <option value="tutor_aluno" <?php selected($user_role, 'tutor_aluno'); ?>><?php _e('Aluno', 'tutor-ead'); ?></option>
                                <option value="tutor_professor" <?php selected($user_role, 'tutor_professor'); ?>><?php _e('Professor', 'tutor-ead'); ?></option>
                            </select>
                        </div>
                    </div>
                </div>
                
                <!-- Cursos Associados -->
                <div class="edit-card">
                    <h2 class="edit-card-title">
                        <span class="dashicons dashicons-welcome-learn-more"></span>
                        <?php _e('Cursos Associados', 'tutor-ead'); ?>
                    </h2>
                    
                    <?php if (!empty($courses)): ?>
                        <div class="courses-selection">
                            <?php foreach ($courses as $course):
                                $checked = in_array($course->id, $associated_courses) ? 'checked' : '';
                                ?>
                                <label class="form-check">
                                    <input type="checkbox" name="course_ids[]" value="<?php echo esc_attr($course->id); ?>" <?php echo $checked; ?>>
                                    <span><?php echo esc_html($course->title); ?></span>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="form-help"><?php _e('Nenhum curso disponível.', 'tutor-ead'); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Seção de Progresso do Curso -->
                <?php if (!empty($associated_courses)): ?>
                    <div class="edit-card">
                        <h2 class="edit-card-title">
                            <span class="dashicons dashicons-chart-line"></span>
                            <?php _e('Progresso do Curso', 'tutor-ead'); ?>
                        </h2>
                        
                        <div class="progress-section">
                            <?php
                            // Para cada curso associado, exibe os campos para progresso
                            foreach ($associated_courses as $course_id):
                                $curso = $wpdb->get_row(
                                    $wpdb->prepare(
                                        "SELECT * FROM {$wpdb->prefix}tutoread_courses WHERE id = %d",
                                        $course_id
                                    )
                                );
                                if (!$curso) continue;

                                // Busca o registro de progresso mais recente para este curso
                                $progress = $wpdb->get_row(
                                    $wpdb->prepare(
                                        "SELECT pa.*, tl.id as lesson_id, tm.id as module_id 
                                         FROM {$wpdb->prefix}progresso_aulas pa
                                         JOIN {$wpdb->prefix}tutoread_lessons tl ON pa.aula_id = tl.id
                                         JOIN {$wpdb->prefix}tutoread_modules tm ON tl.module_id = tm.id
                                         WHERE pa.aluno_id = %d AND tm.course_id = %d
                                         ORDER BY pa.id DESC LIMIT 1",
                                        $user_id,
                                        $course_id
                                    )
                                );
                                if ($progress) {
                                    // Calcular ordinais com base no registro
                                    $modules = $wpdb->get_results(
                                        $wpdb->prepare(
                                            "SELECT id FROM {$wpdb->prefix}tutoread_modules WHERE course_id = %d ORDER BY id ASC",
                                            $course_id
                                        )
                                    );
                                    $module_ids = array_map(function($m) { return $m->id; }, $modules);
                                    $module_ordinal = array_search($progress->module_id, $module_ids);
                                    $module_ordinal = ($module_ordinal !== false) ? $module_ordinal + 1 : 1;

                                    $lessons = $wpdb->get_results(
                                        $wpdb->prepare(
                                            "SELECT id FROM {$wpdb->prefix}tutoread_lessons WHERE module_id = %d ORDER BY id ASC",
                                            $progress->module_id
                                        )
                                    );
                                    $lesson_ids = array_map(function($l) { return $l->id; }, $lessons);
                                    $lesson_ordinal = array_search($progress->lesson_id, $lesson_ids);
                                    $lesson_ordinal = ($lesson_ordinal !== false) ? $lesson_ordinal + 1 : 1;
                                } else {
                                    $module_ordinal = 1;
                                    $lesson_ordinal = 1;
                                }

                                // Obter total de módulos do curso
                                $modules_all = $wpdb->get_results(
                                    $wpdb->prepare(
                                        "SELECT id FROM {$wpdb->prefix}tutoread_modules WHERE course_id = %d ORDER BY id ASC",
                                        $course_id
                                    )
                                );
                                $total_modules = count($modules_all);

                                // Para o módulo selecionado, determinar o total de aulas
                                if ($progress) {
                                    $selected_module_id = $progress->module_id;
                                } else {
                                    $selected_module_id = (isset($modules_all[0]->id)) ? $modules_all[0]->id : 0;
                                }
                                $total_lessons_in_module = intval(
                                    $wpdb->get_var(
                                        $wpdb->prepare(
                                            "SELECT COUNT(*) FROM {$wpdb->prefix}tutoread_lessons WHERE module_id = %d",
                                            $selected_module_id
                                        )
                                    )
                                );
                            ?>
                            <div class="progress-item">
                                <div class="progress-course-name"><?php echo esc_html($curso->title); ?>:</div>
                                <div class="progress-inputs">
                                    <div class="progress-input-group">
                                        <label><?php _e('Módulo', 'tutor-ead'); ?>:</label>
                                        <input type="number" name="module_progress[<?php echo esc_attr($course_id); ?>]" value="<?php echo esc_attr($module_ordinal); ?>">
                                    </div>
                                    <div class="progress-input-group">
                                        <label><?php _e('Aula', 'tutor-ead'); ?>:</label>
                                        <input type="number" name="lesson_progress[<?php echo esc_attr($course_id); ?>]" value="<?php echo esc_attr($lesson_ordinal); ?>">
                                    </div>
                                </div>
                                <div class="progress-info">
                                    <?php printf(__('Total de módulos: %d', 'tutor-ead'), $total_modules); ?> • 
                                    <?php printf(__('Total de aulas neste módulo: %d', 'tutor-ead'), $total_lessons_in_module); ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <p style="margin-top: 24px;">
                    <button type="submit" class="btn-primary">
                        <span class="dashicons dashicons-saved"></span>
                        <?php _e('Salvar Alterações', 'tutor-ead'); ?>
                    </button>
                </p>
            </form>
        </div>
        <?php
        // ... (código existente)
    }

    /**
     * Gera um par de cores (fundo e texto) para os badges a partir de uma string.
     */
    private static function get_badge_style_from_string($str) {
        $colors = [
            ['bg' => '#e0e7ff', 'text' => '#3730a3'], // Indigo
            ['bg' => '#d1fae5', 'text' => '#047857'], // Green
            ['bg' => '#fef3c7', 'text' => '#92400e'], // Amber
            ['bg' => '#fee2e2', 'text' => '#991b1b'], // Red
            ['bg' => '#fce7f3', 'text' => '#9d174d'], // Pink
            ['bg' => '#e0f2fe', 'text' => '#0369a1'], // Sky
            ['bg' => '#f3e8ff', 'text' => '#6b21a8'], // Purple
            ['bg' => '#dcfce7', 'text' => '#166534'], // Lime
            ['bg' => '#fefce8', 'text' => '#854d0e'], // Yellow
            ['bg' => '#e5e7eb', 'text' => '#1f2937'], // Gray
        ];

        $hash = crc32($str);
        $index = abs($hash) % count($colors);
        $color_pair = $colors[$index];

        return sprintf(
            'background-color: %s; color: %s;',
            $color_pair['bg'],
            $color_pair['text']
        );
    }
}

// ------------------------------
// Registra as actions para os handlers AJAX
// ------------------------------
add_action('wp_ajax_preview_bulk_students', [StudentManager::class, 'ajax_preview_bulk_students']);
add_action('wp_ajax_nopriv_preview_bulk_students', [StudentManager::class, 'ajax_preview_bulk_students']);

add_action('admin_head', function(){
    echo '<style>
        .bulk-preview-item { border:1px solid #ddd; padding:10px; margin-bottom:10px; border-radius:4px; }
        .preview-new { background:#e6ffed; }
        .preview-update { background:#fff8e1; border-color:#ffeb3b; }
        .preview-same { background:#e0e0e0; } /* Cor neutra para entradas que permanecem iguais */
        .preview-error { background:#ffebee; border-color:#f44336; }
        .errors li { color:#c62828; }
        .warnings li { color:#ff8f00; }
    </style>';
});