<?php
namespace TutorEAD;

defined('ABSPATH') || exit;

/**
 * Classe AssetManager
 *
 * Gerencia o carregamento de todos os scripts e estilos (CSS)
 * tanto para o painel de administração (backend) quanto para o site (frontend).
 */
class AssetManager {

    /**
     * Registra os hooks para enfileirar os scripts e estilos.
     */
    public function __construct() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_assets']);
    }

    /**
     * Enfileira os assets para o painel de administração.
     *
     * @param string $hook O hook da página atual do admin.
     */
    public function enqueue_admin_assets($hook) {
        // Define o caminho base dos assets para evitar repetição
        $plugin_assets_path = plugin_dir_path(__FILE__) . '../assets/';
        $plugin_assets_url = plugin_dir_url(__FILE__) . '../assets/';

        // Carrega o Socket.IO e o tracker na página de gerenciamento de alunos
        if ($hook === 'tutor-ead_page_tutor-ead-students') {
            wp_enqueue_style(
                'tutor-ead-student-accordion-styles',
                $plugin_assets_url . 'css/admin-student-accordion.css',
                [],
                filemtime($plugin_assets_path . 'css/admin-student-accordion.css')
            );

            wp_enqueue_script(
                'tutor-ead-student-accordion-script',
                $plugin_assets_url . 'js/admin-student-accordion.js',
                [],
                filemtime($plugin_assets_path . 'js/admin-student-accordion.js'),
                true
            );


        }

        wp_enqueue_style(
            'tutor-ead-view-course-styles',
            $plugin_assets_url . 'css/course-view.css',
            [],
            filemtime($plugin_assets_path . 'css/course-view.css')
        );

        		// Garante que os scripts sejam carregados apenas nas páginas do plugin
                if (strpos($hook, 'tutor-ead') !== false) {
        
					// CodeMirror Assets - Carregado para todas as páginas do Tutor EAD que possam precisar dele.
					wp_enqueue_style('codemirror-css', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.css');
					wp_enqueue_style('codemirror-theme-material', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/theme/material.min.css');
					wp_enqueue_script('codemirror-js', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/codemirror.min.js', [], '5.65.16', true);
					wp_enqueue_script('codemirror-mode-json', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/mode/javascript/javascript.min.js', ['codemirror-js'], '5.65.16', true);
					wp_enqueue_script('codemirror-addon-overlay', 'https://cdnjs.cloudflare.com/ajax/libs/codemirror/5.65.16/addon/mode/overlay.min.js', ['codemirror-js'], '5.65.16', true);
					
        			// Cropper.js para edição de imagem
        			wp_enqueue_style('cropper-css', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css', [], '1.6.1');
        			wp_enqueue_script('cropper-js', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js', [], '1.6.1', true);
                    // Carrega scripts e estilos gerais do admin, exceto na página de configurações
            if (!isset($_GET['page']) || $_GET['page'] !== 'tutor-ead-settings') {
                wp_enqueue_style(
                    'tutor-ead-admin-styles',
                    $plugin_assets_url . 'css/tutor-ead.css',
                    [],
                    filemtime($plugin_assets_path . 'css/tutor-ead.css')
                );

                wp_enqueue_script(
                    'tutor-ead-admin-scripts',
                    $plugin_assets_url . 'js/tutor-ead.js',
                    ['jquery'],
                    filemtime($plugin_assets_path . 'js/tutor-ead.js'),
                    true
                );
            }

			// Carrega assets da página de Importar/Exportar
			if ($hook === 'admin_page_tutor-ead-import-export' || $hook === 'tutor-ead_page_tutor-ead-import-export') {

				wp_enqueue_script(
					'tutor-ead-import-preview',
					$plugin_assets_url . 'js/admin-import-preview.js',
					['jquery'],
					filemtime($plugin_assets_path . 'js/admin-import-preview.js'),
					true
				);

				wp_localize_script(
					'tutor-ead-import-preview',
					'TutorEAD_Import',
					[
						'ajax_url' => admin_url('admin-ajax.php'),
						'nonce'    => wp_create_nonce('tutor_ead_import_preview_nonce'),
					]
				);
			}
        }
    }

    /**
     * Enfileira os assets para o frontend.
     */
    public function enqueue_frontend_assets() {
        $plugin_assets_path = plugin_dir_path(__FILE__) . '../assets/';
        $plugin_assets_url = plugin_dir_url(__FILE__) . '../assets/';



        $dashboard_data = [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('tutoread_dashboard_nonce')
        ];

        // Dashboard de Administrador e Editor de Curso
        if (is_page_template('templates/dashboard-administrador.php') || is_page_template('templates/dashboard-course-editor.php')) {
            wp_enqueue_style(
                'tutoread-dashboard-frontend-styles',
                $plugin_assets_url . 'css/dashboard-frontend.css',
                [],
                filemtime($plugin_assets_path . 'css/dashboard-frontend.css')
            );
            wp_enqueue_script(
                'tutoread-dashboard-admin-frontend-script',
                $plugin_assets_url . 'js/dashboard-admin-frontend.js',
                ['jquery'],
                filemtime($plugin_assets_path . 'js/dashboard-admin-frontend.js'),
                true
            );
            wp_localize_script(
                'tutoread-dashboard-admin-frontend-script',
                'tutoread_dashboard_data',
                $dashboard_data
            );

            wp_enqueue_script(
                'tutoread-dark-mode-script',
                $plugin_assets_url . 'js/dark-mode.js',
                ['jquery'],
                filemtime($plugin_assets_path . 'js/dark-mode.js'),
                true
            );
        } 
        // Dashboard de Professor
        elseif (is_page_template('templates/dashboard-professor.php')) {
             wp_enqueue_style(
                'tutoread-dashboard-frontend-styles',
                $plugin_assets_url . 'css/dashboard-frontend.css',
                [],
                filemtime($plugin_assets_path . 'css/dashboard-frontend.css')
            );
            wp_enqueue_style(
                'tutoread-boletim-dashboard-styles',
                $plugin_assets_url . 'css/boletim-dashboard.css',
                ['tutoread-dashboard-frontend-styles'],
                filemtime($plugin_assets_path . 'css/boletim-dashboard.css')
            );
            wp_enqueue_script(
                'tutoread-dashboard-professor-frontend-script',
                $plugin_assets_url . 'js/dashboard-professor-frontend.js',
                ['jquery'],
                filemtime($plugin_assets_path . 'js/dashboard-professor-frontend.js'),
                true
            );
            wp_localize_script(
                'tutoread-dashboard-professor-frontend-script',
                'tutoread_dashboard_data',
                $dashboard_data
            );

            wp_enqueue_script(
                'tutoread-dark-mode-script',
                $plugin_assets_url . 'js/dark-mode.js',
                ['jquery'],
                filemtime($plugin_assets_path . 'js/dark-mode.js'),
                true
            );
        }
    }
}