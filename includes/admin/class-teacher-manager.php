<?php

namespace TutorEAD\Admin;

defined('ABSPATH') || exit;

/**
 * Classe TeacherManager
 *
 * Gerencia os professores no sistema Tutor EAD, permitindo:
 * - Cadastro completo de professores (nome, username, e-mail, telefone e senha);
 * - Listagem dos professores cadastrados;
 * - Edição de professor em uma página separada, permitindo associar cursos ao professor.
 */
class TeacherManager {

    /**
     * Enfileira scripts específicos para a página de professores.
     *
     * @param string $hook O hook da página.
     */
    public function enqueue_scripts($hook) {
        if ($hook !== 'tutor-ead_page_tutor-ead-teachers') {
            return;
        }
        // Enfileire seus scripts e estilos aqui, se necessário.
    }

    /**
     * Renderiza a página de Gerenciamento de Professores.
     *
     * Exibe a listagem dos professores com as colunas "ID", "Nome de Usuário", "E-mail", "Cursos" e "Ações".
     * Também exibe um formulário oculto para cadastro de novos professores.
     */
    public static function teachers_page() {
        global $wpdb;
    
        // Processa o formulário de adição de professor
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_teacher'])) {
            $teacher_name     = sanitize_text_field($_POST['teacher_name']);
            $teacher_username = sanitize_text_field($_POST['teacher_username']);
            $teacher_email    = sanitize_email($_POST['teacher_email']);
            $teacher_phone    = sanitize_text_field($_POST['teacher_phone']);
            $teacher_password = sanitize_text_field($_POST['teacher_password']);
    
            // Cria o usuário utilizando a função nativa do WP
            $user_id = wp_create_user($teacher_username, $teacher_password, $teacher_email);
            if (!is_wp_error($user_id)) {
                $user = new \WP_User($user_id);
                $user->set_role('tutor_professor');
    
                // Atualiza o display name com o nome completo do professor
                wp_update_user([
                    'ID'           => $user_id,
                    'display_name' => $teacher_name,
                ]);
    
                // Salva o telefone como user meta
                update_user_meta($user_id, 'teacher_phone', $teacher_phone);
    
                echo '<div class="notice notice-success is-dismissible"><p>' . __('Professor registrado com sucesso!', 'tutor-ead') . '</p></div>';
            } else {
                echo '<div class="notice notice-error is-dismissible"><p>' . $user_id->get_error_message() . '</p></div>';
            }
        }
    
        // Define o meta_key dinamicamente para capacidades (ex.: "wp_capabilities" ou similar)
        $capabilities_meta_key = $wpdb->prefix . 'capabilities';
    
        // Obter professores existentes (com role tutor_professor)
        $teachers = $wpdb->get_results(
            "SELECT ID, user_login, user_email 
             FROM {$wpdb->prefix}users 
             WHERE EXISTS (
                 SELECT 1 FROM {$wpdb->prefix}usermeta 
                 WHERE {$wpdb->prefix}usermeta.user_id = {$wpdb->prefix}users.ID 
                   AND meta_key = '$capabilities_meta_key'
                   AND meta_value LIKE '%tutor_professor%'
             )"
        );
    
        // Container principal com fonte maior (20px)
        echo '<div class="wrap" style="font-size: 20px;">';
        echo '<h1>' . __('Gerenciar Professores', 'tutor-ead') . '</h1>';
    
        // Tabela de listagem de professores com estilos customizados
        if ($teachers) {
            echo '<table class="widefat fixed striped" style="margin-bottom: 20px; border-collapse: collapse; width: 100%; font-size: 20px;">';
            // Cabeçalho com fundo cinza claro (#e0e0e0)
            echo '<thead style="background-color: #e0e0e0;">';
            echo '<tr>';
            echo '<th style="padding: 15px; border: 1px solid #ddd; background-color: #e0e0e0;">' . __('ID', 'tutor-ead') . '</th>';
            echo '<th style="padding: 15px; border: 1px solid #ddd; background-color: #e0e0e0;">' . __('Nome de Usuário', 'tutor-ead') . '</th>';
            echo '<th style="padding: 15px; border: 1px solid #ddd; background-color: #e0e0e0;">' . __('E-mail', 'tutor-ead') . '</th>';
            echo '<th style="padding: 15px; border: 1px solid #ddd; background-color: #e0e0e0;">' . __('Cursos', 'tutor-ead') . '</th>';
            echo '<th style="padding: 15px; border: 1px solid #ddd; background-color: #e0e0e0;">' . __('Ações', 'tutor-ead') . '</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';
            foreach ($teachers as $teacher) {
                // Consulta para obter os cursos associados a este professor
                $courses = $wpdb->get_results(
                    $wpdb->prepare("SELECT title FROM {$wpdb->prefix}tutoread_courses WHERE professor_id = %d", $teacher->ID)
                );
                $course_titles = [];
                if ($courses) {
                    foreach ($courses as $course) {
                        $course_titles[] = $course->title;
                    }
                }
                $course_list = !empty($course_titles) ? implode(', ', $course_titles) : __('Nenhum', 'tutor-ead');
    
                echo '<tr>';
                echo '<td style="padding: 15px; border: 1px solid #ddd;">' . esc_html($teacher->ID) . '</td>';
                echo '<td style="padding: 15px; border: 1px solid #ddd;">' . esc_html($teacher->user_login) . '</td>';
                echo '<td style="padding: 15px; border: 1px solid #ddd;">' . esc_html($teacher->user_email) . '</td>';
                echo '<td style="padding: 15px; border: 1px solid #ddd;">' . esc_html($course_list) . '</td>';
                // Link de edição direciona para a nova página de edição específica do professor
                echo '<td style="padding: 15px; border: 1px solid #ddd;"><a href="' . admin_url('admin.php?page=tutor-ead-edit-teacher&user_id=' . esc_attr($teacher->ID)) . '">' . __('Editar', 'tutor-ead') . '</a></td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        } else {
            echo '<p>' . __('Nenhum professor encontrado.', 'tutor-ead') . '</p>';
        }
    
        // Botão para adicionar novo professor
        echo '<button id="add-teacher-btn" class="button button-primary" style="margin-bottom: 20px; padding: 10px 15px; font-size: 16px;">' . __('Adicionar Novo Professor', 'tutor-ead') . '</button>';
    
        // Formulário de adição de professor (inicialmente oculto)
// Formulário de adição de professor (inicialmente oculto)
echo '<div id="add-teacher-form" style="display:none; margin-top: 20px;">';
echo '<form method="POST">';
echo '<h2 style="font-size: 18px;">' . __('Adicionar Novo Professor', 'tutor-ead') . '</h2>';

echo '<div style="margin-bottom: 15px;">';
echo '<label for="teacher_name" style="font-size: 20px; display: block; margin-bottom: 5px;">' . __('Nome do Professor', 'tutor-ead') . '</label>';
echo '<input type="text" name="teacher_name" id="teacher_name" required style="padding: 8px; font-size: 16px; width: 100%;">';
echo '</div>';

echo '<div style="margin-bottom: 15px;">';
echo '<label for="teacher_username" style="font-size: 20px; display: block; margin-bottom: 5px;">' . __('Username', 'tutor-ead') . '</label>';
echo '<input type="text" name="teacher_username" id="teacher_username" required style="padding: 8px; font-size: 16px; width: 100%;">';
echo '</div>';

echo '<div style="margin-bottom: 15px;">';
echo '<label for="teacher_email" style="font-size: 20px; display: block; margin-bottom: 5px;">' . __('E-mail', 'tutor-ead') . '</label>';
echo '<input type="email" name="teacher_email" id="teacher_email" required style="padding: 8px; font-size: 16px; width: 100%;">';
echo '</div>';

echo '<div style="margin-bottom: 15px;">';
echo '<label for="teacher_phone" style="font-size: 20px; display: block; margin-bottom: 5px;">' . __('Telefone', 'tutor-ead') . '</label>';
echo '<input type="text" name="teacher_phone" id="teacher_phone" style="padding: 8px; font-size: 16px; width: 100%;">';
echo '</div>';

echo '<div style="margin-bottom: 15px;">';
echo '<label for="teacher_password" style="font-size: 20px; display: block; margin-bottom: 5px;">' . __('Senha', 'tutor-ead') . '</label>';
echo '<input type="password" name="teacher_password" id="teacher_password" required style="padding: 8px; font-size: 16px; width: 100%;">';
echo '</div>';

echo '<p><input type="submit" name="add_teacher" class="button button-primary" value="' . __('Salvar Professor', 'tutor-ead') . '" style="padding: 10px 15px; font-size: 16px;"></p>';
echo '</form>';
echo '</div>';



    
        // Script para alternar a exibição do formulário
        echo '<script>
                document.getElementById("add-teacher-btn").addEventListener("click", function() {
                    var form = document.getElementById("add-teacher-form");
                    form.style.display = (form.style.display === "none" || form.style.display === "") ? "block" : "none";
                });
              </script>';
    
        echo '</div>';
    }
    
    /**
     * Renderiza a página de edição de professor.
     *
     * Permite que o administrador edite os dados do professor (nome e telefone) e associe cursos.
     */
    public static function edit_teacher_page() {
        global $wpdb;
        
        // Verifica se o parâmetro user_id foi informado
        if (!isset($_GET['user_id']) || empty($_GET['user_id'])) {
            echo '<div class="notice notice-error is-dismissible"><p>' . __('ID do professor não informado.', 'tutor-ead') . '</p></div>';
            return;
        }
        
        $teacher_id = intval($_GET['user_id']);
        $user = get_userdata($teacher_id);
        if (!$user || !in_array('tutor_professor', $user->roles)) {
            echo '<div class="notice notice-error is-dismissible"><p>' . __('Professor não encontrado.', 'tutor-ead') . '</p></div>';
            return;
        }
        
        // Processa o envio do formulário de atualização
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_teacher'])) {
            if (!isset($_POST['update_teacher_nonce']) || !wp_verify_nonce($_POST['update_teacher_nonce'], 'update_teacher_action')) {
                wp_die(__('Erro de validação. Por favor, tente novamente.', 'tutor-ead'));
            }
            
            $teacher_name  = sanitize_text_field($_POST['teacher_name']);
            $teacher_phone = sanitize_text_field($_POST['teacher_phone']);
            
            // Atualiza os dados do professor
            wp_update_user([
                'ID'           => $teacher_id,
                'display_name' => $teacher_name,
            ]);
            update_user_meta($teacher_id, 'teacher_phone', $teacher_phone);
            
            // Processa a associação dos cursos
            $selected_courses = isset($_POST['teacher_courses']) ? array_map('intval', $_POST['teacher_courses']) : [];
            
            // Atualiza os cursos selecionados: define professor_id para o professor
            if (!empty($selected_courses)) {
                foreach ($selected_courses as $course_id) {
                    $wpdb->update(
                        "{$wpdb->prefix}tutoread_courses",
                        ['professor_id' => $teacher_id],
                        ['id' => $course_id],
                        ['%d'],
                        ['%d']
                    );
                }
            }
            
            // Para os cursos que estão atualmente associados mas não foram selecionados, remove a associação
            $current_courses = $wpdb->get_col(
                $wpdb->prepare("SELECT id FROM {$wpdb->prefix}tutoread_courses WHERE professor_id = %d", $teacher_id)
            );
            if ($current_courses) {
                foreach ($current_courses as $course_id) {
                    if (!in_array($course_id, $selected_courses)) {
                        $wpdb->update(
                            "{$wpdb->prefix}tutoread_courses",
                            ['professor_id' => 0],
                            ['id' => $course_id],
                            ['%d'],
                            ['%d']
                        );
                    }
                }
            }
            
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Professor atualizado com sucesso!', 'tutor-ead') . '</p></div>';
            // Atualiza os dados do usuário
            $user = get_userdata($teacher_id);
        }
        
        $teacher_name  = $user->display_name;
        $teacher_email = $user->user_email;
        $teacher_phone = get_user_meta($teacher_id, 'teacher_phone', true);
        
        // Obter todos os cursos
        $courses = $wpdb->get_results(
            "SELECT id, title, professor_id FROM {$wpdb->prefix}tutoread_courses",
            ARRAY_A
        );
        $associated_courses = [];
        if ($courses) {
            foreach ($courses as $course) {
                if (intval($course['professor_id']) === $teacher_id) {
                    $associated_courses[] = $course['id'];
                }
            }
        }
        
        // Container principal para a página de edição
        echo '<div class="wrap" style="font-size: 20px;">';
        echo '<h1>' . __('Editar Professor', 'tutor-ead') . '</h1>';
        echo '<form method="POST">';
        wp_nonce_field('update_teacher_action', 'update_teacher_nonce');
        
        // Campo: Nome do Professor
        echo '<div style="margin-bottom: 15px;">';
        echo '<label for="teacher_name" style="font-size: 24px; display: block; margin-bottom: 5px;">' . __('Nome do Professor', 'tutor-ead') . '</label>';
        echo '<input type="text" name="teacher_name" id="teacher_name" value="' . esc_attr($teacher_name) . '" required style="padding: 15px; font-size: 18px; width: 100%;">';
        echo '</div>';
        
        // Campo: E-mail (desabilitado)
        echo '<div style="margin-bottom: 15px;">';
        echo '<label for="teacher_email" style="font-size: 24px; display: block; margin-bottom: 5px;">' . __('E-mail', 'tutor-ead') . '</label>';
        echo '<input type="email" name="teacher_email" id="teacher_email" value="' . esc_attr($teacher_email) . '" disabled style="padding: 15px; font-size: 18px; width: 100%;">';
        echo '</div>';
        
        // Campo: Telefone
        echo '<div style="margin-bottom: 15px;">';
        echo '<label for="teacher_phone" style="font-size: 24px; display: block; margin-bottom: 5px;">' . __('Telefone', 'tutor-ead') . '</label>';
        echo '<input type="text" name="teacher_phone" id="teacher_phone" value="' . esc_attr($teacher_phone) . '" style="padding: 15px; font-size: 18px; width: 100%;">';
        echo '</div>';
        
        // Campo: Cursos Associados
        echo '<div style="margin-bottom: 15px;">';
        echo '<label for="teacher_courses" style="font-size: 24px; display: block; margin-bottom: 5px;">' . __('Cursos Associados', 'tutor-ead') . '</label>';
        echo '<select name="teacher_courses[]" id="teacher_courses" multiple style="min-width: 300px; padding: 15px; font-size: 18px; width: 100%;">';
        if ($courses) {
            foreach ($courses as $course) {
                $selected = in_array($course['id'], $associated_courses) ? 'selected' : '';
                echo '<option value="' . esc_attr($course['id']) . '" ' . $selected . '>' . esc_html($course['title']) . '</option>';
            }
        }
        echo '</select>';
        echo '<p class="description" style="font-size: 18px;">' . __('Segure Ctrl (Windows) ou Command (Mac) para selecionar múltiplos cursos.', 'tutor-ead') . '</p>';
        echo '</div>';
        
        // Botão de envio
        echo '<p><input type="submit" name="update_teacher" class="button button-primary" value="' . __('Atualizar Professor', 'tutor-ead') . '" style="padding: 15px 25px; font-size: 18px;"></p>';
        
        echo '</form>';
        echo '</div>';
    }
    
}
