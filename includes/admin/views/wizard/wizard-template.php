<?php
defined('ABSPATH') || exit;

// Passos do wizard
$steps = [
    'welcome' => [
        'title' => __('Bem-vindo', 'tutor-ead'),
        'icon'  => 'dashicons-admin-home',
    ],
    'course_name' => [
        'title' => __('Nome', 'tutor-ead'),
        'icon'  => 'dashicons-edit-large',
    ],
    'logo' => [
        'title' => __('Logotipo', 'tutor-ead'),
        'icon'  => 'dashicons-format-image',
    ],
    'color' => [
        'title' => __('Cor', 'tutor-ead'),
        'icon'  => 'dashicons-admin-customizer',
    ],
    'finish' => [
        'title' => __('Concluído', 'tutor-ead'),
        'icon'  => 'dashicons-yes-alt',
    ],
];

$current_step_key = isset($_GET['step']) ? sanitize_key($_GET['step']) : 'welcome';

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta name="viewport" content="width=device-width" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?php _e('Tutor EAD &rsaquo; Setup Wizard', 'tutor-ead'); ?></title>
    <?php do_action('admin_enqueue_scripts', $this->page_hook); ?>
    <?php do_action('admin_print_styles'); ?>
    <?php do_action('admin_head'); ?>
</head>
<body>
    <div class="tutor-ead-setup-wizard-wrap">
        <div class="tutor-ead-wizard-container">
            <div class="tutor-ead-wizard-header">
                <h1><?php _e('Configuração Inicial', 'tutor-ead'); ?></h1>
            </div>

            <div class="tutor-ead-wizard-steps">
                <?php foreach ($steps as $key => $step) : ?>
                    <?php
                    $is_active = ($key === $current_step_key);
                    $is_completed = (array_search($key, array_keys($steps)) < array_search($current_step_key, array_keys($steps)));
                    $class = $is_active ? 'active' : ($is_completed ? 'completed' : '');
                    ?>
                    <div class="step <?php echo $class; ?>">
                        <div class="step-icon">
                            <span class="dashicons <?php echo $step['icon']; ?>"></span>
                        </div>
                        <div class="step-title"><?php echo $step['title']; ?></div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="tutor-ead-wizard-content">
                <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="tutor_ead_setup_wizard">
                    <input type="hidden" name="current_step" value="<?php echo esc_attr($current_step_key); ?>">
                    <?php wp_nonce_field('tutor_ead_wizard_action', 'tutor_ead_wizard_nonce'); ?>

                    <?php
                    $step_template = TUTOR_EAD_PATH . 'includes/admin/views/wizard/step-' . $current_step_key . '.php';
                    if (file_exists($step_template)) {
                        include $step_template;
                    }
                    ?>
                </form>
            </div>
        </div>
        <div class="wizard-logo-footer">
            <img src="<?php echo esc_url(TUTOR_EAD_URL . 'img/tutureadlogo.png'); ?>" alt="Tutor EAD Logo">
        </div>
    </div>
</body>
</html>
