<?php
/**
 * Plugin Name:       Tutor EAD
 * Plugin URI:        https://tutoread.com.br
 * Description:       Plugin de gerenciamento de cursos EAD para WordPress.
 * Version:           2.0.5
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Guilherme S. Azevedo
 * Author URI:        https://guilhermeazevedo.com.br
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       tutor-ead
 * Domain Path:       /languages
 */

namespace TutorEAD;

defined('ABSPATH') || exit;

// =========================================================================
// 1. CONSTANTS
// =========================================================================

if (!defined('TUTOREAD_CENTRAL_URL')) {
    define('TUTOREAD_CENTRAL_URL', 'https://tutoread.com.br');
}
if (!defined('TUTOR_EAD_URL')) {
    define('TUTOR_EAD_URL', plugin_dir_url(__FILE__));
}
if (!defined('TUTOR_EAD_IMG_URL')) {
    define('TUTOR_EAD_IMG_URL', plugin_dir_url(__FILE__) . 'img/');
}
if (!defined('TUTOR_EAD_LOGO_URL')) {
    define('TUTOR_EAD_LOGO_URL', plugin_dir_url(__FILE__) . 'img/tutureadlogo.png');
}
if (!defined('TUTOR_EAD_VERSION')) {
    define('TUTOR_EAD_VERSION', '2.0.5');
}
if (!defined('TUTOR_EAD_PATH')) {
    define('TUTOR_EAD_PATH', plugin_dir_path(__FILE__));
}

// =========================================================================
// 1.5 ATUALIZAÇÃO AUTOMÁTICA (GITHUB - MODO PÚBLICO)
// =========================================================================

// Verifica se a biblioteca existe na pasta especificada
if (file_exists(TUTOR_EAD_PATH . 'plugin-update-checker-master/vendor/autoload.php')) {
    
    require_once TUTOR_EAD_PATH . 'plugin-update-checker-master/vendor/autoload.php';
    
    // Configuração para Repositório PÚBLICO (Sem Token)
    $myUpdateChecker = \YahnisElsts\PluginUpdateChecker\v5p6\PucFactory::buildUpdateChecker(
        'https://github.com/neverminds99/tutor-ead', // URL do seu repositório
        __FILE__,
        'tutor-ead'
    );

    // Define a branch principal
    $myUpdateChecker->setBranch('main');
}

// =========================================================================
// 2. MAIN PLUGIN CLASS
// =========================================================================

class Plugin {
    private $asset_manager;
    private static $instance = null;

    /**
     * Singleton Pattern para garantir apenas uma instância do plugin
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function __construct() {
        // Hooks de ativação/desativação
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        // Carregamento inicial
        add_action('plugins_loaded', [$this, 'load_dependencies']);
        add_action('plugins_loaded', [$this, 'init']);
        add_action('plugins_loaded', [$this, 'update_db_check']);

        // Adiciona o filtro de redirecionamento de login com prioridade alta
        add_filter('login_redirect', [$this, 'tutoread_login_redirect'], 5, 3);
    }

    public function load_dependencies() {
        // Carregamento de arquivos essenciais globais
        $files = [
            'includes/includes-loader.php',
            'includes/admin/class-advertisement-manager.php',
            'includes/admin/class-temp-login.php',
            'includes/helpers.php',
            'includes/ajax-handlers.php',
            'includes/class-database.php' // Garante que Database esteja disponível
        ];

        foreach ($files as $file) {
            if (file_exists(TUTOR_EAD_PATH . $file)) {
                require_once TUTOR_EAD_PATH . $file;
            }
        }

        // Carrega API se existir
        if (file_exists(TUTOR_EAD_PATH . 'api/api.php')) {
            require_once TUTOR_EAD_PATH . 'api/api.php';
        }
    }

    public function init() {
        // Rotinas robustas de atualização para roles e páginas
        if (is_admin()) {
            \TutorEAD\RoleManager::check_version();
            // Força a criação de páginas para garantir que o novo dashboard exista.
            // Esta chamada pode ser removida após a transição.
            \TutorEAD\PageManager::create_pages(); 
        }

        // Inicializa gerenciadores
        if (class_exists('\TutorEAD\AssetManager')) {
            $this->asset_manager = new \TutorEAD\AssetManager();
        }

        // Hooks do Admin e Funcionalidades
        add_action('init', [\TutorEAD\Admin\TemporaryLogin::class, 'process_temp_login_token'], 0); // Restaurado
        add_action('admin_enqueue_scripts', [\TutorEAD\Admin\StudentManager::class, 'enqueue_scripts']);
        add_action('admin_init', [$this, 'wizard_redirect']);

        // Hooks Globais
        add_action('admin_post_delete_course', [$this, 'handle_delete_course']);
        add_action('admin_post_reset_student_password', [$this, 'handle_reset_password']);
        
        // Inicializa classes administrativas apenas se estiver no admin
        if (is_admin()) {
            $this->init_admin_classes();
        }

        // Force update templates logic
        if (get_transient('tutor_ead_force_template_update')) {
            if (class_exists('\TutorEAD\PageManager')) {
                \TutorEAD\PageManager::create_pages();
            }
            delete_transient('tutor_ead_force_template_update');
        }
    }

    /**
     * Redireciona os usuários para seus respectivos dashboards após o login.
     *
     * @param string  $redirect_to A URL de redirecionamento padrão.
     * @param object  $request     O objeto da requisição original.
     * @param WP_User $user        O objeto do usuário que fez login.
     * @return string A URL de redirecionamento modificada.
     */
    public function tutoread_login_redirect($redirect_to, $request, $user) {
        if (isset($user->roles) && is_array($user->roles)) {
            $roles = $user->roles;

            // Administradores sempre vão para o wp-admin.
            if (in_array('administrator', $roles, true)) {
                return admin_url();
            }
            
            // Redirecionamento para roles customizadas do TutorEAD
            if (in_array('tutor_admin', $roles, true)) {
                return home_url('/dashboard-administrador');
            } elseif (in_array('tutor_course_editor', $roles, true)) {
                return home_url('/dashboard-course-editor');
            } elseif (in_array('tutor_professor', $roles, true)) {
                return home_url('/dashboard-professor');
            } elseif (in_array('tutor_aluno', $roles, true)) {
                return home_url('/dashboard-aluno');
            }
        }

        // Para todos os outros usuários, retorna o redirecionamento padrão.
        return $redirect_to;
    }


    private function init_admin_classes() {
        // Garante o carregamento dos arquivos específicos do admin que estavam no original
        $admin_files = [
            'includes/admin/class-activity-association-manager.php',
            'includes/admin/class-enrollment-manager.php',
            'includes/admin/class-import-export-courses.php',
            'includes/admin/class-report-manager.php'
        ];

        foreach ($admin_files as $file) {
            if (file_exists(TUTOR_EAD_PATH . $file)) {
                require_once TUTOR_EAD_PATH . $file;
            }
        }

        new \TutorEAD\Admin\AdvertisementManager();
        new \TutorEAD\Admin\AdminMenus();
        new \TutorEAD\Admin\CourseManager();
        new \TutorEAD\Admin\StudentManager();
        new \TutorEAD\Admin\TeacherManager();
        new \TutorEAD\Admin\ActivityManager();
        new \TutorEAD\Admin\DashboardManager();
        new \TutorEAD\Admin\MetaBoxes();
        new \TutorEAD\Admin\Settings();
        new \TutorEAD\Admin\LicenseManager();
        new \TutorEAD\Admin\ActivityAssociationManager();
        new \TutorEAD\Admin\SetupWizard();

        // Report Manager Hooks
        add_action('admin_post_create_boletim', [\TutorEAD\Admin\ReportManager::class, 'handle_create_boletim']);
        add_action('admin_post_edit_boletim',   [\TutorEAD\Admin\ReportManager::class, 'handle_edit_boletim']);
        add_action('admin_post_delete_boletim', [\TutorEAD\Admin\ReportManager::class, 'handle_delete_boletim']);
        
        $report_manager = new \TutorEAD\Admin\ReportManager();
        add_action('admin_enqueue_scripts', [$report_manager, 'enqueue_scripts'], 10);
    }

    public function update_db_check() {
        if (!class_exists('\TutorEAD\Database')) return;
        
        $current_db_version = get_option('tutoread_db_version', '1.0.0');
        if (version_compare($current_db_version, \TutorEAD\Database::VERSION, '<')) {
            \TutorEAD\Database::create_tables();
            update_option('tutoread_db_version', \TutorEAD\Database::VERSION);
        }
        
        // Check plugin version for notice
        $installed_plugin_version = get_option('tutoread_plugin_version', '0.0.0');
        if (version_compare(TUTOR_EAD_VERSION, $installed_plugin_version, '>')) {
            set_transient('tutor_ead_show_db_update_notice', true, DAY_IN_SECONDS);
        }
        update_option('tutoread_plugin_version', TUTOR_EAD_VERSION);
    }

    public function wizard_redirect() {
        if (get_transient('tutor_ead_activation_redirect')) {
            delete_transient('tutor_ead_activation_redirect');
            if (!isset($_GET['activate-multi'])) {
                wp_safe_redirect(admin_url('index.php?page=tutor-ead-setup-wizard'));
                exit;
            }
        }
    }

    public function activate() {
        if (empty(get_option('tutoread_jwt_secret', ''))) {
            update_option('tutoread_jwt_secret', bin2hex(random_bytes(32)));
        }
        
        // Include database class explicitly for activation
        if (file_exists(TUTOR_EAD_PATH . 'includes/class-database.php')) {
            require_once TUTOR_EAD_PATH . 'includes/class-database.php';
            \TutorEAD\Database::create_tables();
        }

        if (class_exists('\TutorEAD\RoleManager')) {
            \TutorEAD\RoleManager::add_roles_and_capabilities();
        }
        
        if (class_exists('\TutorEAD\PageManager')) {
            \TutorEAD\PageManager::create_pages();
        }
        
        flush_rewrite_rules();

        set_transient('tutor_ead_activation_redirect', true, 30);
        set_transient('tutor_ead_force_template_update', true, 30);

        // API Key generation
        $option_name = 'tutoread_instance_api_key';
        if (!get_option($option_name)) {
            $new_api_key = wp_generate_uuid4();
            add_option($option_name, $new_api_key, '', 'no');
        }
    }

    public function deactivate() {
        if (class_exists('\TutorEAD\RoleManager')) {
            \TutorEAD\RoleManager::remove_roles_and_capabilities();
        }
        flush_rewrite_rules();
    }

    // Form Handlers moved inside class
    public function handle_delete_course() {
        if (!current_user_can('manage_options') || !isset($_GET['course_id'], $_GET['delete_nonce'])) {
            wp_die(__('Ação inválida.', 'tutor-ead'));
        }
        $course_id = intval($_GET['course_id']);
        if (!wp_verify_nonce($_GET['delete_nonce'], 'delete_course_' . $course_id)) {
            wp_die(__('Nonce de segurança inválido.', 'tutor-ead'));
        }
        \TutorEAD\Admin\CourseManager::delete_course($course_id);
        wp_safe_redirect(admin_url('admin.php?page=tutor-ead-courses'));
        exit;
    }

    public function handle_reset_password() {
        if (!isset($_GET['user_id'], $_GET['reset_nonce'])) {
            wp_die(__('Parâmetros inválidos.', 'tutor-ead'));
        }
        $user_id = intval($_GET['user_id']);
        if (!wp_verify_nonce($_GET['reset_nonce'], 'reset_student_password_' . $user_id)) {
            wp_die(__('Nonce de segurança inválido.', 'tutor-ead'));
        }
        wp_set_password('aluno01', $user_id);
        wp_redirect(add_query_arg('reset_success', 1, admin_url('admin.php?page=tutor-ead-students')));
        exit;
    }
}

// Start Plugin
Plugin::get_instance();


// =========================================================================
// 3. DATABASE UPDATE & NOTICES
// =========================================================================

add_action('admin_action_tutoread_manual_db_update', __NAMESPACE__ . '\\tutoread_handle_manual_db_update');

function tutoread_handle_manual_db_update() {
    if (!current_user_can('manage_options')) {
        wp_die(__('Você não tem permissão para realizar esta ação.', 'tutor-ead'));
    }
    if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'tutoread_manual_db_update_nonce')) {
        wp_die(__('Nonce de segurança inválido.', 'tutor-ead'));
    }

    $plugin = Plugin::get_instance();
    $plugin->update_db_check();

    delete_transient('tutor_ead_show_db_update_notice');
    update_option('tutoread_plugin_version', TUTOR_EAD_VERSION);

    wp_redirect(add_query_arg('tutoread_db_updated', 'true', admin_url('plugins.php')));
    exit;
}

add_action('admin_notices', __NAMESPACE__ . '\\tutoread_display_db_update_notice');

function tutoread_display_db_update_notice() {
    if (get_transient('tutor_ead_show_db_update_notice')) {
        $update_link = esc_url(wp_nonce_url(admin_url('admin.php?action=tutoread_manual_db_update'), 'tutoread_manual_db_update_nonce'));
        $permalinks_link = esc_url(admin_url('options-permalink.php'));
        
        echo '<div class="notice notice-warning is-dismissible"><p>';
        echo sprintf(
            __('O plugin Tutor EAD foi atualizado para a versão %1$s. Por favor, <a href="%2$s">clique aqui para atualizar seu banco de dados</a>. Se houver erro 404, <a href="%3$s">salve os Links Permanentes</a>.', 'tutor-ead'),
            TUTOR_EAD_VERSION,
            $update_link,
            $permalinks_link
        );
        echo '</p></div>';
    }
}

// FIX: Usar plugin_basename para garantir que o link apareça mesmo se a pasta for renomeada
add_filter('plugin_action_links_' . plugin_basename(__FILE__), __NAMESPACE__ . '\\tutoread_add_db_update_link');

function tutoread_add_db_update_link($links) {
    $update_link = '<a href="' . esc_url(wp_nonce_url(admin_url('admin.php?action=tutoread_manual_db_update'), 'tutoread_manual_db_update_nonce')) . '">' . __('Atualizar Banco de Dados', 'tutor-ead') . '</a>';
    array_unshift($links, $update_link);
    return $links;
}


// =========================================================================
// 4. PAGE TEMPLATES & ROUTING
// =========================================================================

class PageTemplates {
    private static $templates;

    public static function init() {
        self::$templates = [
            'templates/dashboard-administrador.php' => 'Dashboard Administrador',
            'templates/dashboard-professor.php'     => 'Dashboard Professor',
            'templates/dashboard-course-editor.php' => 'Painel Editor de Cursos TutorEAD',
            'templates/dashboard-aluno.php'         => 'Dashboard Aluno',
            'templates/registro.php'                => 'Registro',
            'templates/template-curso.php'          => 'Visualizar Curso',
            'templates/login-tutor-ead.php'         => 'Login Tutor EAD',
            'templates/meu-boletim.php'             => 'Meu Boletim',
            'templates/perfil-aluno.php'            => 'Perfil do Aluno'
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
            $plugin_template = TUTOR_EAD_PATH . $template_file;
            if (file_exists($plugin_template)) {
                return $plugin_template;
            }
        }
        return $template;
    }
}
PageTemplates::init();


// =========================================================================
// 5. GLOBAL HELPERS & ASSETS
// =========================================================================

// Redirecionamento de Login Customizado
add_filter('login_url', function ($login_url, $redirect, $force_reauth) {
    return home_url('/login-tutor-ead');
}, 10, 3);

add_action('init', function () {
    if (strpos($_SERVER['REQUEST_URI'], 'wp-login.php') !== false && !isset($_GET['action'])) {
        wp_redirect(home_url('/login-tutor-ead'));
        exit;
    }
});

// Assets Frontend
add_action('wp_enqueue_scripts', function () {
    if (is_page_template('templates/template-curso.php')) {
        wp_enqueue_style('tutor-ead-front-course-view', TUTOR_EAD_URL . 'assets/css/front-course-view.css', [], '1.2');
        $highlight_color = get_option('tutor_ead_highlight_color', '#0073aa');
        wp_add_inline_style('tutor-ead-front-course-view', ":root { --main-color: " . esc_attr($highlight_color) . "; }");
    }
    wp_enqueue_script('jquery');
});

add_action('admin_enqueue_scripts', function($hook_suffix) {
    wp_enqueue_script('jquery');
});

// Admin Styles
add_action('admin_head', function () {
    $highlight_color = get_option('tutor_ead_highlight_color', '#0073aa');
    echo "<style>:root { --tutor-highlight: " . esc_attr($highlight_color) . "; }</style>";
});

// =========================================================================
// 6. SESSION & IMPERSONATION
// =========================================================================

// Inicialização segura de sessão
add_action('init', function() {
    if (!session_id() && !headers_sent()) {
        session_start();
    }
}, 1);

// Log de Login do Aluno
add_action('wp_login', function($user_login, $user) {
    if (in_array('aluno', (array) $user->roles) && function_exists('\TutorEAD\record_student_activity')) {
        \TutorEAD\record_student_activity($user->ID, 'login');
    }
}, 10, 2);

// Gerenciamento de Impersonation (Logar como Aluno)
add_action('wp_loaded', function() {
    // 1. Iniciar personificação
    if (isset($_GET['impersonate_token']) && current_user_can('manage_options')) {
        $token = sanitize_key($_GET['impersonate_token']);
        $transient_data = get_transient($token);
        
        if ($transient_data && isset($transient_data['admin_id']) && $transient_data['admin_id'] === get_current_user_id()) {
            $_SESSION['is_impersonating'] = true;
            $_SESSION['original_admin_id'] = (int) $transient_data['admin_id'];
            $_SESSION['impersonated_student_id'] = (int) $transient_data['student_id'];
            delete_transient($token);
        }
    }

    // 2. Manter personificação
    if (isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating'] === true) {
        if (isset($_SESSION['impersonated_student_id'])) {
             wp_set_current_user((int) $_SESSION['impersonated_student_id']);
        } else {
             unset($_SESSION['is_impersonating']);
        }
    }
});

// Sair da personificação
add_action('init', function() {
    if (isset($_GET['action']) && $_GET['action'] == 'exit_impersonation' && isset($_SESSION['is_impersonating'])) {
        $admin_id = (int) $_SESSION['original_admin_id'];
        
        session_unset();
        session_destroy();
        wp_clear_auth_cookie();
        
        // Reloga o admin original
        wp_set_auth_cookie($admin_id, true);
        wp_set_current_user($admin_id);
        
        wp_safe_redirect(admin_url('admin.php?page=tutor-ead-students'));
        exit;
    }
});

// Controle da Barra de Admin
add_action('after_setup_theme', function() {
    if (!current_user_can('manage_options')) {
        show_admin_bar(false);
    }
    if (current_user_can('manage_options') && isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating'] === true) {
        show_admin_bar(false);
    }
});

// =========================================================================
// 7. EXTERNAL INTEGRATIONS (Meu Negócio Plugin)
// =========================================================================

add_action('tutoread_aluno_dashboard_menu_items', function () {
    if (!function_exists('is_plugin_active')) {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }

    if (is_user_logged_in() && is_plugin_active('meu-negocio-tutoread/meu-negocio-tutoread.php')) {
        global $wpdb;
        $current_user_id = get_current_user_id();
        $show_menu = false;

        $tabela_documentos = $wpdb->prefix . 'tutoread_documentos';
        $tabela_assinaturas = $wpdb->prefix . 'tutoread_assinaturas';

        // Verifica existência das tabelas antes de consultar (cacheado na var)
        $has_docs_table = $wpdb->get_var("SHOW TABLES LIKE '{$tabela_documentos}'") == $tabela_documentos;
        
        if ($has_docs_table) {
            $documentos_pendentes = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$tabela_documentos} WHERE aluno_id = %d AND status = 'pendente'",
                $current_user_id
            ));
            if ($documentos_pendentes > 0) $show_menu = true;
        }

        if (!$show_menu) {
            $has_signs_table = $wpdb->get_var("SHOW TABLES LIKE '{$tabela_assinaturas}'") == $tabela_assinaturas;
            if ($has_signs_table) {
                $documentos_assinados = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM {$tabela_assinaturas} WHERE aluno_id = %d",
                    $current_user_id
                ));
                if ($documentos_assinados > 0) $show_menu = true;
            }
        }

        if ($show_menu) {
            ?>
            <li>
                <a href="<?php echo esc_url(home_url('/documentos')); ?>">
                    <i class="fa fa-file-text"></i>
                    <span>Documentos</span>
                </a>
            </li>
            <?php
        }
    }
});
