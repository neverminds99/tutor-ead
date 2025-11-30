<?php
defined('ABSPATH') || exit;

$type = $_GET['type'] ?? 'padrao';
$title = '';
switch ($type) {
    case 'externa':
        $title = __('Nova Atividade Externa', 'tutor-ead');
        break;
    case 'presencial':
        $title = __('Nova Atividade Presencial', 'tutor-ead');
        break;
    case 'padrao':
    default:
        $title = __('Nova Atividade Padrão', 'tutor-ead');
        break;
}
?>
<div class="wrap" style="background:#fff; padding:20px; border-radius:8px;">
    <h1><?php echo esc_html($title); ?></h1>

    <form method="POST" id="unified-activity-form" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
        <input type="hidden" name="action" value="tutor_ead_unified_form">
        <input type="hidden" name="activity_type" value="<?php echo esc_attr($type); ?>">

        <div id="form-padrao" class="form-section">
            <?php include __DIR__ . '/partials/form-fields-padrao.php'; ?>
        </div>

        <div id="form-externa" class="form-section">
            <?php include __DIR__ . '/partials/form-fields-externa.php'; ?>
        </div>

        <div id="form-presencial" class="form-section">
            <?php include __DIR__ . '/partials/form-fields-sem-url.php'; ?>
        </div>

        <p><input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Salvar Atividade', 'tutor-ead' ); ?>"></p>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const type = '<?php echo esc_js($type); ?>';
    const formSections = document.querySelectorAll('.form-section');
    
    formSections.forEach(function(section) {
        // Hide the section
        section.style.display = 'none';
        // Disable all form elements within it
        section.querySelectorAll('input, textarea, select, button').forEach(function(el) {
            el.disabled = true;
        });
    });

    const activeSection = document.getElementById('form-' + type);
    if (activeSection) {
        // Show the active section
        activeSection.style.display = 'block';
        // Enable all form elements within it so they can be submitted
        activeSection.querySelectorAll('input, textarea, select, button').forEach(function(el) {
            el.disabled = false;
        });
    }
});
</script>
