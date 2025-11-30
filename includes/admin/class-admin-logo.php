<?php
namespace TutorEAD\Admin;

defined('ABSPATH') || exit;

class AdminLogo {
    public function __construct() {
        add_action('admin_notices', [$this, 'display_admin_header_elements']);
    }

    public function display_admin_header_elements() {
        $screen = get_current_screen();

        // Adicionado para não duplicar o cabeçalho na página de cursos
        if ($screen && $screen->id === 'admin_page_tutor-ead-courses') {
            return;
        }

        if ($screen && strpos($screen->id, 'tutor-ead') !== false) {
            $dashboard_url = admin_url('admin.php?page=tutor-ead-dashboard');
            $activities_list_url = admin_url('admin.php?page=tutor-ead-atividades');
            
            echo "<div style='display: flex; justify-content: space-between; align-items: flex-start; padding: 10px 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); width: 100%;'>";
            
            // Lado Esquerdo: Logo e Botão "Voltar"
            echo "<div>";
            // Logo
            echo "<div><a href='" . esc_url($dashboard_url) . "'><img src='" . TUTOR_EAD_LOGO_URL . "' style='width: 100px; height: auto;' alt='Tutor EAD Logo'></a></div>";

            // Lógica para exibir o botão "Voltar para Cursos"
            $show_back_to_courses = false;
            $course_builder_pages = [
                'admin_page_tutor-ead-course-builder',
                'admin_page_tutor-ead-import-export'
            ];

            if (in_array($screen->id, $course_builder_pages, true)) {
                $show_back_to_courses = true;
            }
            
            if ($screen->id === 'admin_page_tutor-ead-courses' && isset($_GET['course_id'])) {
                $show_back_to_courses = true;
            }


            if ($show_back_to_courses) {
                $courses_url = admin_url('admin.php?page=tutor-ead-courses');
                $button_style = 'background: #0073aa; color: #fff; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 14px; font-weight: 500; display: inline-block; margin-top: 10px;';
                echo "<a href='" . esc_url($courses_url) . "' style='" . esc_attr($button_style) . "'>Voltar para Cursos</a>";
            }
            echo "</div>";

            // Lado Direito: Outros botões contextuais
            echo "<div>";
            if ($screen->id === 'admin_page_tutor-ead-associar-atividade') {
                $button_style = 'background: #0073aa; color: #fff; padding: 8px 15px; border-radius: 5px; text-decoration: none; font-size: 14px; font-weight: 500;';
                echo "<a href='" . esc_url($activities_list_url) . "' style='" . esc_attr($button_style) . "'>Voltar para Lista de Atividades</a>";
            }
            echo "</div>";
            
            echo "</div>";
        }
    }
}
