<?php
/**
 * Plugin Name: Tutor EAD
 * Description: Plugin de gerenciamento de cursos EAD.
 * Version: 1.0
 * Author: Seu Nome
 */

namespace TutorEAD;

defined('ABSPATH') || exit;

// Inclui manualmente os arquivos necessários
        require_once plugin_dir_path(__FILE__) . 'includes/admin/class-course-builder-manager.php';

        require_once plugin_dir_path(__FILE__) . 'includes/includes-loader.php';

        error_log('Classe CourseBuilderManager carregada.');


class Plugin {
    private $asset_manager;

    public function __construct() {
        // Registra os hooks de ativação e desativação
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        // Inicializa o plugin
        add_action('plugins_loaded', [$this, 'init']);
    }

    public function init() {
        

        // Inicializar AssetManager
        $this->asset_manager = new AssetManager();

        add_action('init', [\TutorEAD\Admin\CourseBuilderManager::class, 'init']);

        // Hooks de ações para enfileirar scripts
        //add_action('wp_enqueue_scripts', [$this->asset_manager, 'enqueue_assets'], 10);
        add_action('admin_enqueue_scripts', [$this->asset_manager, 'enqueue_admin_assets'], 10);
        add_action('admin_post_save_course_builder', [\TutorEAD\Admin\CourseBuilderManager::class, 'handle_save_course_builder']);
        add_action('admin_post_nopriv_save_course_builder', [\TutorEAD\Admin\CourseBuilderManager::class, 'handle_save_course_builder']);

        // Inicializar classes de administração
        $this->init_admin_classes();
    }

    /**
     * Inicializa as classes de administração.
     */
    private function init_admin_classes() {
        // Certifique-se de que estamos no admin
        if (!is_admin()) {
            return;
        }

        // Carregar helpers
        require_once plugin_dir_path(__FILE__) . 'includes/helpers.php';

        // Inicializar as classes de administração
        new \TutorEAD\admin\AdminMenus();
        new \TutorEAD\admin\CourseManager();
        new \TutorEAD\admin\StudentManager();
        new \TutorEAD\admin\TeacherManager();
        new \TutorEAD\admin\ActivityManager();
        new \TutorEAD\admin\DashboardManager();
        new \TutorEAD\admin\MetaBoxes();
        new \TutorEAD\admin\Settings();
        new \TutorEAD\admin\LicenseManager();
    }

    /**
     * Ativa o plugin e cria as tabelas no banco de dados.
     */
    public function activate() {
        // Criação das tabelas e outras configurações necessárias na ativação
        Database::create_tables();  // Criação das tabelas
        RoleManager::add_roles_and_capabilities();  // Se houver necessidade de adicionar roles
        PageManager::create_pages();  // Caso haja a criação de páginas
    }

    /**
     * Desativa o plugin e remove as tabelas no banco de dados.
     */
    public function deactivate() {
        // Remover as tabelas e limpar configurações ao desativar
        
        RoleManager::remove_roles_and_capabilities();  // Remove as roles, se houver
    }

}


class PageTemplates {
    private static $templates;

    public static function init() {
        self::$templates = [
            'templates/dashboard-administrador.php' => 'Dashboard Administrador',
            'templates/dashboard-professor.php'     => 'Dashboard Professor',
            'templates/dashboard-aluno.php'         => 'Dashboard Aluno',
            'templates/registro.php'                => 'Registro',
            'templates/template-curso.php'          => 'Visualizar Curso',
            'templates/login-tutor-ead.php'          => 'Login Tutor EAD',
        ];

        add_filter('theme_page_templates', [__CLASS__, 'add_templates']);
        add_filter('template_include', [__CLASS__, 'load_template']);
    }

    public static function add_templates($templates) {
        return array_merge($templates, self::$templates);
    }

    public static function load_template($template) {
        global $post;

        if (!$post) return $template;

        $template_file = get_post_meta($post->ID, '_wp_page_template', true);

        if (!empty($template_file) && isset(self::$templates[$template_file])) {
            $plugin_template = plugin_dir_path(__FILE__) . $template_file;
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }

        return $template;
    }
}

PageTemplates::init();




// Inicializa o plugin
new Plugin();
