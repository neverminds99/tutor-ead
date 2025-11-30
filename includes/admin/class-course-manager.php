<?php

namespace TutorEAD\Admin;

defined('ABSPATH') || exit;

class CourseManager {

    public function __construct() {
        add_action('wp_ajax_save_cropped_image', [$this, 'handle_save_cropped_image']);
        add_action('wp_ajax_upload_course_image_ajax', [$this, 'handle_upload_course_image_ajax']);
        add_action('wp_ajax_save_course_ajax', [$this, 'handle_save_course_ajax']);
        add_action('admin_init', [$this, 'handle_course_deletion']);
    }

    public function handle_course_deletion() {
        if (
            isset($_POST['delete_course']) &&
            isset($_POST['save_course_nonce']) &&
            isset($_GET['page']) && $_GET['page'] === 'tutor-ead-courses' &&
            isset($_GET['course_id'])
        ) {
            $course_id = intval($_GET['course_id']);
            if (wp_verify_nonce($_POST['save_course_nonce'], 'save_course_action')) {
                if ($course_id > 0) {
                    self::delete_course_and_dependencies($course_id);
                    wp_safe_redirect(add_query_arg(['message' => 'deleted'], admin_url('admin.php?page=tutor-ead-courses')));
                    exit;
                }
            } else {
                wp_die(__('Falha na verificação de segurança.', 'tutor-ead'), 'Erro', ['response' => 403]);
            }
        }
    }


    /**
     * Cria um novo curso.
     */
    public static function create_course($data) {
        global $wpdb;
        $wpdb->insert(
            "{$wpdb->prefix}tutoread_courses",
            [
                'title'        => sanitize_text_field($data['title']),
                'description'  => sanitize_textarea_field($data['description']),
                'professor_id' => intval($data['professor_id']),
                'max_students' => intval($data['max_students']),
            ],
            ['%s', '%s', '%d', '%d']
        );
        return $wpdb->insert_id;
    }

    /**
     * Atualiza um curso existente.
     */
    public static function update_course($id, $data) {
        global $wpdb;
        $wpdb->update(
            "{$wpdb->prefix}tutoread_courses",
            [
                'title'        => sanitize_text_field($data['title']),
                'description'  => sanitize_textarea_field($data['description']),
                'professor_id' => intval($data['professor_id']),
                'max_students' => intval($data['max_students']),
            ],
            ['id' => intval($id)],
            ['%s', '%s', '%d', '%d'],
            ['%d']
        );
    }

    /**
     * Remove um curso.
     */
    public static function delete_course($id) {
        global $wpdb;
        $wpdb->delete("{$wpdb->prefix}tutoread_courses", ['id' => intval($id)], ['%d']);
    }

    /**
     * Obtém um curso pelo ID.
     */
    public static function get_course($id) {
        global $wpdb;
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}tutoread_courses WHERE id = %d",
                intval($id)
            ),
            ARRAY_A
        );
    }

    /**
     * Obtém todos os cursos.
     */
    public static function get_all_courses() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT * FROM {$wpdb->prefix}tutoread_courses",
            ARRAY_A
        );
    }

    /**
     * Renderiza a meta box de Liberação de Conteúdo.
     *
     * @param array $course Dados do curso atual.
     */
    public static function render_drip_content_meta_box($course) {
        $release_scope = get_option('tutor_ead_lesson_release_scope', 'global');
        ?>
        <div class="postbox">
            <h2 class="hndle"><span><?php _e('Liberação de Conteúdo', 'tutor-ead'); ?></span></h2>
            <div class="inside">
                <?php if ($release_scope === 'global') : ?>
                    <p>
                        <?php
                        printf(
                            __('A liberação de conteúdo está sendo gerenciada <strong>globalmente</strong>. Para alterar, vá para %s.', 'tutor-ead'),
                            sprintf('<a href="%s">%s</a>', esc_url(admin_url('admin.php?page=tutor-ead-settings')), __('Configurações Gerais', 'tutor-ead'))
                        );
                        ?>
                    </p>
                <?php else : ?>
                    <?php 
                    wp_nonce_field('tutor_ead_save_drip_settings', 'tutor_ead_drip_nonce');
                    $drip_settings = get_option('tutor_ead_drip_settings_' . $course['id'], []);
                    $release_type = $drip_settings['release_type'] ?? 'unlocked';
                    $drip_quantity = $drip_settings['drip_quantity'] ?? 1;
                    $drip_frequency = $drip_settings['drip_frequency'] ?? 1;
                    $drip_unit = $drip_settings['drip_unit'] ?? 'days';
                    ?>
                    <div class="form-group">
                        <p>
                            <label>
                                <input type="radio" name="tutor_ead_course_release_type" value="unlocked" <?php checked($release_type, 'unlocked'); ?>>
                                <?php _e('Conteúdo 100% Liberado', 'tutor-ead'); ?>
                            </label>
                            <br>
                            <span class="form-help" style="margin-left: 20px;"><?php _e('Todas as aulas ficam disponíveis imediatamente.', 'tutor-ead'); ?></span>
                        </p>
                        <p>
                            <label>
                                <input type="radio" name="tutor_ead_course_release_type" value="drip" <?php checked($release_type, 'drip'); ?>>
                                <?php _e('Liberar aos Poucos (Gotejamento)', 'tutor-ead'); ?>
                            </label>
                            <br>
                            <span class="form-help" style="margin-left: 20px;"><?php _e('Libere aulas em um cronograma específico.', 'tutor-ead'); ?></span>
                        </p>
                    </div>

                    <div id="drip-options-container" style="<?php echo $release_type === 'drip' ? '' : 'display: none;'; ?>">
                        <hr>
                        <div class="form-group">
                            <label for="tutor_ead_course_drip_quantity" class="form-label" style="display:inline-block; margin-right: 5px;"><?php _e('Liberar', 'tutor-ead'); ?></label>
                            <input type="number" name="tutor_ead_course_drip_quantity" id="tutor_ead_course_drip_quantity" style="width: 70px;" value="<?php echo esc_attr($drip_quantity); ?>" min="1">
                            <label for="tutor_ead_course_drip_frequency" style="margin-left: 5px;"><?php _e('aula(s) a cada', 'tutor-ead'); ?></label>
                            <input type="number" name="tutor_ead_course_drip_frequency" id="tutor_ead_course_drip_frequency" style="width: 70px; margin-left: 5px;" value="<?php echo esc_attr($drip_frequency); ?>" min="1">
                            <select name="tutor_ead_course_drip_unit" id="tutor_ead_course_drip_unit">
                                <option value="days" <?php selected($drip_unit, 'days'); ?>><?php _e('Dias', 'tutor-ead'); ?></option>
                                <option value="weeks" <?php selected($drip_unit, 'weeks'); ?>><?php _e('Semanas', 'tutor-ead'); ?></option>
                                <option value="months" <?php selected($drip_unit, 'months'); ?>><?php _e('Meses', 'tutor-ead'); ?></option>
                            </select>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    public static function courses_page() {
        global $wpdb;

        $course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;

        // Display success message after redirect
        if (isset($_GET['message']) && $_GET['message'] === 'deleted') {
            echo '<div class="notice notice-success is-dismissible"><p>' . __('Curso excluído com sucesso.', 'tutor-ead') . '</p></div>';
        }

        $highlight_color = get_option('tutor_ead_highlight_color', '#0073aa');
        
        // Enqueue Cropper.js assets
        wp_enqueue_style('cropper-css', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.css', [], '1.5.13');
        wp_enqueue_script('cropper-js', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.13/cropper.min.js', [], '1.5.13', true);
        wp_enqueue_media();


        // INÍCIO DO CABEÇALHO INJETADO DIRETAMENTE
        $header_style = 'padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); width: 100%; margin-bottom: 24px;';
        $dashboard_url = admin_url('admin.php?page=tutor-ead-dashboard');

        echo "<div style='" . esc_attr($header_style) . "'>"; // A barra branca
        echo "<div>"; // Container do lado esquerdo
        echo "<div><a href='" . esc_url($dashboard_url) . "'><img src='" . TUTOR_EAD_LOGO_URL . "' style='width: 100px; height: auto;' alt='Tutor EAD Logo'></a></div>"; // Logo
        
        if (isset($_GET['course_id'])) {
            $courses_url = admin_url('admin.php?page=tutor-ead-courses');
            $button_style = 'background-color: ' . esc_attr($highlight_color) . '; color: #fff; width: 40px; height: 40px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; margin-top: 15px;';
            $icon_style = 'font-size: 20px; line-height: 1;';
            echo "<a href='" . esc_url($courses_url) . "' style='" . esc_attr($button_style) . "' title='Voltar para Cursos'>";
            echo "<span class='dashicons dashicons-arrow-left-alt' style='" . esc_attr($icon_style) . "'></span>";
            echo "</a>";
        }
        echo "</div>";
        echo "</div>";

        echo '<style>
            .tutor-ead-course-page { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; background: #f3f4f6; margin: 0; padding: 32px; min-height: 100vh; }
            .tutor-ead-course-page * { box-sizing: border-box; }
            .page-header { margin-bottom: 32px; }
            .page-title { font-size: 32px; font-weight: 600; color: #1f2937; margin: 0 0 8px 0; }
            .page-subtitle { color: #6b7280; font-size: 16px; margin: 0; }
            .content-container { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 32px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05); }
            .courses-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 24px; }
            .course-card { background: #ffffff; border: 1px solid #e5e7eb; border-radius: 12px; overflow: hidden; transition: all 0.2s ease; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05); }
            .course-card:hover { transform: translateY(-2px); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
            .course-image { width: 100%; aspect-ratio: 360 / 200; object-fit: cover; background: #f9fafb; }
            .course-card-content { padding: 20px; }
            .course-card-title { font-size: 18px; font-weight: 600; color: #1f2937; margin: 0 0 8px 0; }
            .course-card-description { color: #6b7280; font-size: 14px; line-height: 1.5; margin-bottom: 16px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
            .course-card-actions { display: flex; gap: 8px; flex-wrap: wrap; }
            .tutor-btn { padding: 8px 16px; border-radius: 8px; font-size: 14px; font-weight: 500; text-decoration: none; border: 1px solid transparent; cursor: pointer; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center; gap: 6px; line-height: 1.5; }
            .tutor-btn-primary { background: ' . $highlight_color . '; color: #ffffff; border-color: ' . $highlight_color . '; }
            .tutor-btn-secondary { background: #ffffff; color: #374151; border-color: #e5e7eb; }
            .tutor-btn-danger { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
            .form-group { margin-bottom: 24px; }
            .form-label { display: block; font-weight: 600; color: #374151; margin-bottom: 8px; font-size: 14px; }
            .form-control { width: 100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 8px; font-size: 14px; }
            .form-actions { margin-top: 32px; padding-top: 24px; border-top: 1px solid #e5e7eb; display: flex; gap: 12px; align-items: center; }
            .image-preview-wrapper { position: relative; display: inline-block; }
            .image-preview img { display: block; max-width: 100%; height: auto; max-height: 200px; }
            .edit-course-layout { display: flex; gap: 24px; align-items: flex-start; }
            .main-column { flex: 1; min-width: 0; }
            .sidebar-column { width: 300px; flex-shrink: 0; }
            .postbox { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; }
            .postbox .hndle { font-size: 16px; padding: 12px 16px; margin: 0; border-bottom: 1px solid #e5e7eb; font-weight: 600; }
            .postbox .inside { padding: 16px; }
        </style>';

        if ($course_id !== null) {
            self::render_course_form($course_id);
        } else {
            // MODO LISTAGEM
            $courses = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}tutoread_courses", ARRAY_A);

            echo '<div class="wrap tutor-ead-course-page">';
            echo '<div class="page-header"><h1 class="page-title">' . __('Gerenciar Cursos', 'tutor-ead') . '</h1><p class="page-subtitle">' . __('Visualize e gerencie todos os cursos cadastrados', 'tutor-ead') . '</p></div>';
            echo '<div class="content-container">';
            echo '<div style="margin-bottom: 24px;"><a href="' . admin_url('admin.php?page=tutor-ead-courses&course_id=0') . '" class="tutor-btn tutor-btn-primary tutor-btn-large"><span class="dashicons dashicons-plus-alt"></span>' . __('Adicionar Novo Curso', 'tutor-ead') . '</a> <a href="' . admin_url('admin.php?page=tutor-ead-import-export') . '" class="tutor-btn tutor-btn-secondary tutor-btn-large"><span class="dashicons dashicons-download"></span>' . __('Importar/Exportar', 'tutor-ead') . '</a></div>';

            if ($courses) {
                echo '<div class="courses-grid">';
                foreach ($courses as $course) {
                    $capa_img_url = !empty($course['capa_img']) ? esc_url($course['capa_img']) : 'data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgZmlsbD0iI2Y5ZmFmYiIvPjx0ZXh0IHRleHQtYW5jaG9yPSJtaWRkbGUiIHg9IjE1MCIgeT0iMTA1IiBmaWxsPSIjOWNhM2FmIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTgiPkltYWdlbSBkbyBDdXJzbzwvdGV4dD48L3N2Zz4=';
                    echo '<div class="course-card">';
                    echo '<img src="' . $capa_img_url . '" alt="' . esc_attr($course['title']) . '" class="course-image">';
                    echo '<div class="course-card-content">';
                    echo '<h3 class="course-card-title">' . esc_html($course['title']) . '</h3>';
                    echo '<p class="course-card-description">' . esc_html($course['description']) . '</p>';
                    echo '<div class="course-card-actions">';
                    echo '<a href="' . home_url('/tutor-ead-builder/' . $course['id']) . '" class="tutor-btn tutor-btn-primary"><span class="dashicons dashicons-admin-customizer"></span>' . __('Editor Visual', 'tutor-ead') . '</a>';
                    echo ' <a href="' . admin_url('admin.php?page=tutor-ead-courses&course_id=' . $course['id']) . '" class="tutor-btn tutor-btn-secondary"><span class="dashicons dashicons-edit"></span>' . __('Editar', 'tutor-ead') . '</a>';
                    $preview_url = add_query_arg(['course_id' => $course['id']], home_url('/visualizar-curso/'));
                    echo ' <a href="' . esc_url($preview_url) . '" target="_blank" class="tutor-btn tutor-btn-secondary tutor-btn-icon" title="' . esc_attr__('Preview', 'tutor-ead') . '"><span class="dashicons dashicons-visibility"></span></a>';
                    echo '</div></div></div>';
                }
                echo '</div>';
            } else {
                echo '<div class="empty-state">';
                echo '<h3 class="empty-state-title">' . __('Nenhum curso cadastrado', 'tutor-ead') . '</h3>';
                echo '<a href="' . admin_url('admin.php?page=tutor-ead-courses&course_id=0') . '" class="tutor-btn tutor-btn-primary"><span class="dashicons dashicons-plus-alt"></span>' . __('Adicionar Primeiro Curso', 'tutor-ead') . '</a>';
                echo '</div>';
            }
            echo '</div></div>';
        }
    }

    private static function delete_course_and_dependencies($course_id) {
        global $wpdb;
        $course_id = intval($course_id);
        if ($course_id <= 0) {
            return;
        }

        $courses_table = $wpdb->prefix . 'tutoread_courses';
        $modules_table = $wpdb->prefix . 'tutoread_modules';
        $lessons_table = $wpdb->prefix . 'tutoread_lessons';
        $matriculas_table = $wpdb->prefix . 'matriculas';

        $wpdb->query('START TRANSACTION');

        try {
            $module_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM $modules_table WHERE course_id = %d", $course_id));

            if (!empty($module_ids)) {
                $ids_sql = implode(',', array_map('intval', $module_ids));
                $wpdb->query("DELETE FROM $lessons_table WHERE module_id IN ($ids_sql)");
            }

            $wpdb->delete($modules_table, ['course_id' => $course_id], ['%d']);
            $wpdb->delete($matriculas_table, ['course_id' => $course_id], ['%d']);
            $wpdb->delete($courses_table, ['id' => $course_id], ['%d']);

            delete_option('tutoread_view_mode_' . $course_id);
            delete_option('tutor_ead_drip_settings_' . $course_id);

            $wpdb->query('COMMIT');
        } catch (\Exception $e) {
            $wpdb->query('ROLLBACK');
        }
    }


    private static function render_course_form($course_id) {
        global $wpdb;
        $is_new = $course_id === 0;
        $course = $is_new ? null : $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tutoread_courses WHERE id = %d", $course_id), ARRAY_A);

        if (!$is_new && !$course) {
            echo '<div class="tutor-ead-notice tutor-ead-notice-error"><p>' . __('Curso não encontrado.', 'tutor-ead') . '</p></div>';
            return;
        }

        $professores = get_users(['role' => 'tutor_professor']);
        $title = $is_new ? __('Adicionar Novo Curso', 'tutor-ead') : __('Editar Curso', 'tutor-ead');
        $subtitle = $is_new ? __('Preencha as informações do novo curso', 'tutor-ead') : sprintf(__('Editando: %s', 'tutor-ead'), esc_html($course['title']));

        echo '<div class="wrap tutor-ead-course-page">';
        echo '<div class="page-header"><h1 class="page-title">' . $title . '</h1><p class="page-subtitle">' . $subtitle . '</p></div>';
        echo '<form id="tutor-ead-edit-course-form" method="POST" enctype="multipart/form-data">';
        wp_nonce_field('save_course_action', 'save_course_nonce');
        
        echo '<div class="edit-course-layout">';
        echo '<div class="main-column"><div class="content-container">';

        echo '<div class="form-group"><label for="course_title" class="form-label">' . __('Título do Curso', 'tutor-ead') . '</label><input type="text" name="course_title" id="course_title" class="form-control" value="' . esc_attr($course['title'] ?? '') . '" required></div>';
        echo '<div class="form-group"><label for="course_description" class="form-label">' . __('Descrição do Curso', 'tutor-ead') . '</label><textarea name="course_description" id="course_description" class="form-control" rows="5" required>' . esc_textarea($course['description'] ?? '') . '</textarea></div>';
        echo '<div class="form-group"><label for="professor_associado" class="form-label">' . __('Professor Associado', 'tutor-ead') . '</label><select name="professor_associado" id="professor_associado" class="form-control"><option value="">' . __('Selecione um Professor', 'tutor-ead') . '</option>';
        foreach ($professores as $prof) {
            $selected = selected($course['professor_id'] ?? '', $prof->ID, false);
            echo '<option value="' . esc_attr($prof->ID) . '" ' . $selected . '>' . esc_html($prof->display_name) . '</option>';
        }
        echo '</select></div>';

        $view_mode = $is_new ? 'expanded' : get_option('tutoread_view_mode_' . $course_id, 'expanded');
        echo '<div class="form-group"><label for="tutor_ead_view_mode" class="form-label">' . __('Modo de Visualização', 'tutor-ead') . '</label><select name="tutor_ead_view_mode" id="tutor_ead_view_mode" class="form-control">';
        echo '<option value="expanded" ' . selected($view_mode, 'expanded', false) . '>' . __('Expandido', 'tutor-ead') . '</option>';
        echo '<option value="levels" ' . selected($view_mode, 'levels', false) . '>' . __('Por Níveis', 'tutor-ead') . '</option>';
        echo '</select></div>';

        echo '<div class="form-group"><label for="max_students" class="form-label">' . __('Número Máximo de Alunos', 'tutor-ead') . '</label><input type="number" name="max_students" id="max_students" class="form-control" value="' . esc_attr($course['max_students'] ?? 0) . '" min="0"></div>';
        
        // Image Uploader
        echo '<div class="form-group"><label class="form-label">' . __('Imagem de Capa', 'tutor-ead') . '</label>';
        echo '<div class="image-preview-wrapper">';
        $img_src = $course['capa_img'] ?? '';
        echo '<div class="image-preview"><img src="' . esc_url($img_src) . '" style="' . (empty($img_src) ? 'display:none;' : '') . '"></div>';
        echo '<input type="hidden" name="current_capa_img" id="current_capa_img" value="' . esc_url($img_src) . '">';
        echo '</div>';
        echo '<div><button type="button" id="upload-image-btn" class="button">' . __('Selecionar Imagem', 'tutor-ead') . '</button>';
        echo '<button type="button" id="remove-image-btn" class="button" style="' . (empty($img_src) ? 'display:none;' : '') . '">' . __('Remover Imagem', 'tutor-ead') . '</button></div></div>';

        echo '<div class="form-actions">';
        echo '<button type="submit" class="tutor-btn tutor-btn-primary"><span class="dashicons dashicons-saved"></span>' . ($is_new ? __('Salvar Curso', 'tutor-ead') : __('Salvar Alterações', 'tutor-ead')) . '</button>';
        if (!$is_new) {
            echo '<a href="' . admin_url('admin.php?page=tutor-ead-course-builder&course_id=' . $course_id) . '" class="tutor-btn tutor-btn-secondary"><span class="dashicons dashicons-admin-customizer"></span>' . __('Construtor de Curso', 'tutor-ead') . '</a>';
            echo '<button type="submit" name="delete_course" id="delete-course-btn" class="tutor-btn tutor-btn-danger" style="margin-left: auto;"><span class="dashicons dashicons-trash"></span>' . __('Excluir Curso', 'tutor-ead') . '</button>';
        }
        echo '</div>';

        echo '</div></div>'; // .content-container, .main-column

        if (!$is_new) {
            echo '<div class="sidebar-column">';
            self::render_drip_content_meta_box($course);
            echo '</div>';
        }
        
        echo '</div>'; // .edit-course-layout
        echo '</form>';

        // Cropper Modal
        echo '<div id="cropper-modal" style="display:none; position: fixed; z-index: 99999; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.7);"><div style="background-color: #fefefe; margin: 5% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 700px;">';
        echo '<h2>Recortar Imagem</h2><div style="height: 400px;"><img id="cropper-image" src=""></div>';
        echo '<button type="button" id="crop-image-btn" class="button button-primary">Recortar</button><button type="button" id="cancel-crop-btn" class="button">Cancelar</button>';
        echo '</div></div>';

        echo '</div>'; // .wrap

        ?>
        <script type="text/javascript">
            jQuery(document).ready(function($) {
                var cropper;
                var frame;

                $('#upload-image-btn').on('click', function(e) {
                    e.preventDefault();
                    if (frame) { frame.open(); return; }
                    frame = wp.media({
                        title: 'Selecione uma imagem',
                        button: { text: 'Usar esta imagem' },
                        multiple: false
                    });
                    frame.on('select', function() {
                        var attachment = frame.state().get('selection').first().toJSON();
                        $('#cropper-modal').show();
                        var image = document.getElementById('cropper-image');
                        image.src = attachment.url;
                        cropper = new Cropper(image, {
                            aspectRatio: 16 / 9,
                            viewMode: 1,
                        });
                    });
                    frame.open();
                });

                $('#cancel-crop-btn').on('click', function() {
                    $('#cropper-modal').hide();
                    if (cropper) {
                        cropper.destroy();
                    }
                });

                $('#crop-image-btn').on('click', function() {
                    if (!cropper) return;
                    var canvas = cropper.getCroppedCanvas({
                        width: 800,
                        height: 450,
                    });
                    var croppedImageUrl = canvas.toDataURL('image/jpeg');
                    $('.image-preview img').attr('src', croppedImageUrl).show();
                    $('#current_capa_img').val(croppedImageUrl);
                    $('#remove-image-btn').show();
                    $('#cropper-modal').hide();
                    cropper.destroy();
                });

                $('#remove-image-btn').on('click', function() {
                    $('.image-preview img').attr('src', '').hide();
                    $('#current_capa_img').val('');
                    $(this).hide();
                });

                $('#delete-course-btn').on('click', function(e) {
                    if (!confirm('<?php _e("Tem certeza que deseja excluir este curso? Esta ação não pode ser desfeita e removerá todos os módulos, aulas e matrículas associados a ele.", "tutor-ead"); ?>')) {
                        e.preventDefault();
                    }
                });

                $('#tutor-ead-edit-course-form').on('submit', function(e) {
                    if ($(document.activeElement).attr('name') === 'delete_course') {
                        return; // Deixa o handler de deleção cuidar disso
                    }
                    e.preventDefault();
                    var form = $(this);
                    var button = form.find('button[type="submit"]');
                    button.text('Salvando...').prop('disabled', true);

                    var formData = new FormData(this);
                    formData.append('action', 'save_course_ajax');
                    var urlParams = new URLSearchParams(window.location.search);
                    if (urlParams.has('course_id')) {
                        formData.append('course_id', urlParams.get('course_id'));
                    }

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            if (response.success) {
                                button.text('Salvo!');
                                if (window.history.replaceState && response.data.new_course_id && urlParams.get('course_id') == '0') {
                                    var newUrl = window.location.pathname + '?page=tutor-ead-courses&course_id=' + response.data.new_course_id;
                                    window.history.replaceState({ path: newUrl }, '', newUrl);
                                    setTimeout(function() { window.location.href = newUrl; }, 1000);
                                }
                            } else {
                                button.text('Erro!');
                                alert('Erro: ' + response.data.message);
                            }
                        },
                        error: function() {
                            button.text('Erro de Conexão!');
                            alert('Ocorreu um erro de comunicação.');
                        },
                        complete: function() {
                            setTimeout(function() {
                                button.text('<?php echo $is_new ? 'Salvar Curso' : 'Salvar Alterações'; ?>').prop('disabled', false);
                            }, 2000);
                        }
                    });
                });
            });
        </script>
        <?php
    }

    // ... (restante da classe, como view_course_page, enrollment_list_page, etc.)

    public function handle_save_cropped_image() {
        global $wpdb;

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tutor-ead-crop-nonce')) {
            wp_send_json_error(['message' => 'Validação de segurança falhou.']);
        }

        if (!current_user_can('manage_options') || !isset($_POST['course_id'], $_POST['image_data'])) {
            wp_send_json_error(['message' => 'Dados insuficientes ou permissão negada.']);
        }

        $course_id = intval($_POST['course_id']);
        $image_data_base64 = $_POST['image_data'];

        // Remove o cabeçalho do base64
        list($type, $image_data_base64) = explode(';', $image_data_base64);
        list(, $image_data_base64)      = explode(',', $image_data_base64);
        $image_data = base64_decode($image_data_base64);

        // Gera um nome de arquivo único
        $filename = 'course-' . $course_id . '-cropped-' . time() . '.jpg';

        // Salva o arquivo na pasta de uploads do WordPress
        $upload = wp_upload_bits($filename, null, $image_data);

        if (!empty($upload['error'])) {
            wp_send_json_error(['message' => 'Erro ao salvar o arquivo: ' . $upload['error']]);
        }

        // Atualiza o banco de dados com a nova URL
        $result = $wpdb->update(
            "{$wpdb->prefix}tutoread_courses",
            ['capa_img' => $upload['url']],
            ['id' => $course_id],
            ['%s'],
            ['%d']
        );

        if ($result === false) {
            wp_send_json_error(['message' => 'Erro ao atualizar o banco de dados.']);
        }

        // Limpa os dados de recorte antigos, já que a imagem agora está permanentemente recortada
        $wpdb->update(
            "{$wpdb->prefix}tutoread_courses",
            ['capa_img_crop' => ''],
            ['id' => $course_id]
        );

        wp_send_json_success(['new_url' => $upload['url']]);
    }

    public function handle_upload_course_image_ajax() {
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'tutor-ead-upload-nonce')) {
            wp_send_json_error(['message' => 'Validação de segurança falhou.']);
        }

        if (!current_user_can('manage_options') || empty($_FILES['course_image'])) {
            wp_send_json_error(['message' => 'Nenhum arquivo enviado ou permissão negada.']);
        }

        if (!function_exists('wp_handle_upload')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $movefile = wp_handle_upload($_FILES['course_image'], ['test_form' => false]);

        if ($movefile && !isset($movefile['error'])) {
            wp_send_json_success(['url' => $movefile['url']]);
        } else {
            wp_send_json_error(['message' => 'Erro ao fazer upload: ' . $movefile['error']]);
        }
    }

    public function handle_save_course_ajax() {
        global $wpdb;

        if (!isset($_POST['save_course_nonce']) || !wp_verify_nonce($_POST['save_course_nonce'], 'save_course_action')) {
            wp_send_json_error(['message' => 'Erro de validação de segurança.']);
        }

        $course_id = isset($_POST['course_id']) ? intval($_POST['course_id']) : 0;

        $capa_img = $_POST['current_capa_img'] ?? '';

        if (strpos($capa_img, 'data:image') === 0) {
            $upload = self::handle_base64_image_upload($capa_img);
            if (!$upload['error']) {
                $capa_img = $upload['url'];
            } else {
                $capa_img = '';
            }
        } elseif (!empty($_FILES['course_image']['tmp_name'])) {
            if (!function_exists('wp_handle_upload')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }
            $movefile = wp_handle_upload($_FILES['course_image'], ['test_form' => false]);
            if ($movefile && !isset($movefile['error'])) {
                $capa_img = $movefile['url'];
            }
        }


        $course_data = [
            'title'        => sanitize_text_field($_POST['course_title']),
            'description'  => sanitize_textarea_field($_POST['course_description']),
            'professor_id' => intval($_POST['professor_associado']),
            'max_students' => intval($_POST['max_students']),
            'capa_img'     => $capa_img,
        ];
        $course_format = ['%s', '%s', '%d', '%d', '%s'];

        if ($course_id > 0) {
            $result = $wpdb->update("{$wpdb->prefix}tutoread_courses", $course_data, ['id' => $course_id], $course_format, ['%d']);
        } else {
            $result = $wpdb->insert("{$wpdb->prefix}tutoread_courses", $course_data, $course_format);
            $course_id = $wpdb->insert_id;
        }

        if ($result === false) {
            wp_send_json_error(['message' => 'Erro ao salvar o curso no banco de dados.']);
        }

        if (isset($_POST['tutor_ead_view_mode'])) {
            update_option('tutoread_view_mode_' . $course_id, sanitize_text_field($_POST['tutor_ead_view_mode']));
        }

        if (isset($_POST['tutor_ead_drip_nonce']) && wp_verify_nonce($_POST['tutor_ead_drip_nonce'], 'tutor_ead_save_drip_settings')) {
            if (get_option('tutor_ead_lesson_release_scope', 'global') === 'per_course') {
                $drip_settings = [
                    'release_type'   => sanitize_text_field($_POST['tutor_ead_course_release_type'] ?? 'unlocked'),
                    'drip_quantity'  => intval($_POST['tutor_ead_course_drip_quantity'] ?? 1),
                    'drip_frequency' => intval($_POST['tutor_ead_course_drip_frequency'] ?? 1),
                    'drip_unit'      => sanitize_text_field($_POST['tutor_ead_course_drip_unit'] ?? 'days'),
                ];
                update_option('tutor_ead_drip_settings_' . $course_id, $drip_settings);
            }
        }

        wp_send_json_success(['message' => 'Curso salvo com sucesso!', 'new_course_id' => $course_id]);
    }

    private static function handle_base64_image_upload($base64_img) {
        $upload_dir = wp_upload_dir();
        
        if (preg_match('/^data:image\/(\w+);base64,/', $base64_img, $type)) {
            $data = substr($base64_img, strpos($base64_img, ',') + 1);
            $type = strtolower($type[1]); 

            if (!in_array($type, ['jpg', 'jpeg', 'gif', 'png'])) {
                return ['error' => 'Tipo de imagem inválido.'];
            }
            $data = base64_decode($data);
            if ($data === false) {
                return ['error' => 'Falha ao decodificar a imagem base64.'];
            }
        } else {
            return ['error' => 'String base64 inválida.'];
        }

        $filename = 'capa_curso_' . uniqid() . '.' . $type;
        $filepath = $upload_dir['path'] . '/' . $filename;

        $result = file_put_contents($filepath, $data);

        if ($result === false) {
            return ['error' => 'Não foi possível salvar a imagem no servidor.'];
        }

        $attachment = [
            'guid'           => $upload_dir['url'] . '/' . $filename,
            'post_mime_type' => 'image/' . $type,
            'post_title'     => preg_replace('/\.[^.]+$/', '', $filename),
            'post_content'   => '',
            'post_status'    => 'inherit'
        ];

        $attach_id = wp_insert_attachment($attachment, $filepath);
        
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
        wp_update_attachment_metadata($attach_id, $attach_data);

        return ['url' => $upload_dir['url'] . '/' . $filename, 'error' => false];
    }
}