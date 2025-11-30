<?php
namespace TutorEAD\Admin;

defined('ABSPATH') || exit;

class SetupWizard {

    private $current_step;
    private $page_hook;

    public function __construct() {
        add_action('admin_menu', [$this, 'register_wizard_page']);
        add_action('admin_init', [$this, 'handle_wizard_submission']);
    }

    public function register_wizard_page() {
        $this->page_hook = add_dashboard_page(
            __('Setup Wizard', 'tutor-ead'),
            '',
            'manage_options',
            'tutor-ead-setup-wizard',
            [$this, 'render_wizard_page']
        );

        add_action('load-' . $this->page_hook, [$this, 'load_wizard_page']);
    }

    public function load_wizard_page() {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        
        $this->current_step = isset($_GET['step']) ? sanitize_key($_GET['step']) : 'welcome';

        $data = [
            'course_name'     => get_option('tutor_ead_course_name', ''),
            'course_logo'     => get_option('tutor_ead_course_logo', ''),
            'highlight_color' => get_option('tutor_ead_highlight_color', '#0073aa'),
        ];

        include_once TUTOR_EAD_PATH . 'includes/admin/views/wizard/wizard-template.php';
        
        exit;
    }

    public function render_wizard_page() {
        // O conteúdo é renderizado no hook 'load', então este método fica vazio.
    }

    public function enqueue_scripts() {
        wp_enqueue_style('wp-color-picker');
        wp_enqueue_media();
        wp_enqueue_script('jquery');
        wp_enqueue_script('wp-color-picker');

        wp_enqueue_style(
            'tutor-ead-wizard-style',
            TUTOR_EAD_URL . 'assets/css/admin-wizard.css',
            ['wp-admin', 'buttons', 'colors-fresh'],
            TUTOR_EAD_VERSION
        );

        wp_enqueue_script(
            'tutor-ead-wizard-script',
            TUTOR_EAD_URL . 'assets/js/admin-wizard.js',
            ['jquery', 'wp-color-picker'],
            TUTOR_EAD_VERSION,
            true
        );
    }

    public function handle_wizard_submission() {
        if (!isset($_POST['tutor_ead_wizard_nonce']) || !wp_verify_nonce($_POST['tutor_ead_wizard_nonce'], 'tutor_ead_wizard_action')) {
            return;
        }

        $step = isset($_POST['current_step']) ? sanitize_key($_POST['current_step']) : '';

        if ($step === 'course_name') {
            if (isset($_POST['tutor_ead_course_name'])) {
                update_option('tutor_ead_course_name', sanitize_text_field($_POST['tutor_ead_course_name']));
            }
        } elseif ($step === 'logo') {
            if (isset($_POST['tutor_ead_course_logo'])) {
                update_option('tutor_ead_course_logo', esc_url_raw($_POST['tutor_ead_course_logo']));
            }
        } elseif ($step === 'color') {
            if (isset($_POST['tutor_ead_highlight_color'])) {
                update_option('tutor_ead_highlight_color', sanitize_hex_color($_POST['tutor_ead_highlight_color']));
            }
        }

        $next_step_url = $this->get_next_step_url($step);
        wp_redirect($next_step_url);
        exit;
    }

    private function get_next_step_url($current_step) {
        switch ($current_step) {
            case 'welcome':
                return admin_url('index.php?page=tutor-ead-setup-wizard&step=course_name');
            case 'course_name':
                return admin_url('index.php?page=tutor-ead-setup-wizard&step=logo');
            case 'logo':
                return admin_url('index.php?page=tutor-ead-setup-wizard&step=color');
            case 'color':
                return admin_url('index.php?page=tutor-ead-setup-wizard&step=finish');
            case 'finish':
            default:
                delete_option('tutor_ead_run_setup_wizard');
                return admin_url('admin.php?page=tutor-ead-dashboard');
        }
    }
}
