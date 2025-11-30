<?php
defined('ABSPATH') || exit;

global $wpdb;

// Get activity_id from URL (still needed to display activity title)
$activity_id = isset($_GET['activity_id']) ? intval($_GET['activity_id']) : 0;
if (!$activity_id) {
    wp_die('ID da atividade não fornecido.');
}

// Get activity details
$activity = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}atividades WHERE id = %d", $activity_id));
if (!$activity) {
    wp_die('Atividade não encontrada.');
}

// Get all courses from the custom table, including capa_img
$courses = $wpdb->get_results("SELECT id, title, capa_img FROM {$wpdb->prefix}tutoread_courses ORDER BY title ASC");

$highlight_color = get_option('tutor_ead_highlight_color', '#0073aa');
?>

<style>
    :root {
        --tutor-ead-primary: <?php echo esc_attr($highlight_color); ?>;
        --tutor-ead-primary-light: #e0f2fe;
        --tutor-ead-text-dark: #1f2937;
        --tutor-ead-text-medium: #4b5563;
        --tutor-ead-text-light: #6b7280;
        --tutor-ead-border: #e5e7eb;
        --tutor-ead-bg-light: #f9fafb;
        --tutor-ead-bg-dark: #f3f4f6;
    }

    .tutor-course-selection-wrap {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        background: var(--tutor-ead-bg-dark);
        margin: -20px;
        padding: 32px;
        min-height: 100vh;
    }
    .tutor-course-selection-wrap * { box-sizing: border-box; }

    .selection-header {
        margin-bottom: 32px;
        text-align: center;
    }
    .selection-title {
        font-size: 32px;
        font-weight: 700;
        color: var(--tutor-ead-text-dark);
        margin: 0 0 8px 0;
    }
    .selection-subtitle {
        color: var(--tutor-ead-text-medium);
        font-size: 16px;
        margin: 0;
    }
    .selection-activity-name {
        font-weight: 600;
        color: var(--tutor-ead-primary);
    }

    .course-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 24px;
        max-width: 1200px;
        margin: 0 auto;
    }

    .course-card {
        background: #ffffff;
        border: 1px solid var(--tutor-ead-border);
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        display: flex;
        flex-direction: column;
    }
    .course-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
    }

    .course-card-image {
        width: 100%;
        height: 180px;
        object-fit: cover;
        background-color: var(--tutor-ead-bg-light);
        display: block;
    }
    .course-card-image.placeholder {
        background-color: #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--tutor-ead-text-light);
        font-size: 14px;
        text-align: center;
    }

    .course-card-content {
        padding: 16px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .course-card-title {
        font-size: 18px;
        font-weight: 600;
        color: var(--tutor-ead-text-dark);
        margin-bottom: 10px;
    }

    .btn-associate {
        background: var(--tutor-ead-primary);
        color: #ffffff;
        border: none;
        padding: 10px 15px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        text-decoration: none;
        width: 100%;
        margin-top: 10px;
    }
    .btn-associate:hover {
        filter: brightness(1.1);
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        color: #ffffff;
    }

    .empty-courses-message {
        text-align: center;
        font-size: 18px;
        color: var(--tutor-ead-text-medium);
        margin-top: 50px;
    }
</style>

<div class="wrap tutor-course-selection-wrap">
    <div class="selection-header">
        <h1 class="selection-title"><?php _e('Associar Atividade a um Curso', 'tutor-ead'); ?></h1>
        <p class="selection-subtitle">
            <?php printf(__('Selecione o curso ao qual você deseja associar a atividade: <span class="selection-activity-name">%s</span>', 'tutor-ead'), esc_html($activity->titulo)); ?>
        </p>
    </div>

    <?php if (!empty($courses)) : ?>
        <div class="course-grid">
            <?php foreach ($courses as $course) : ?>
                <div class="course-card">
                    <?php if (!empty($course->capa_img)) : ?>
                        <img src="<?php echo esc_url($course->capa_img); ?>" alt="<?php echo esc_attr($course->title); ?>" class="course-card-image">
                    <?php else : ?>
                        <div class="course-card-image placeholder">
                            <?php esc_html_e('Sem Imagem', 'tutor-ead'); ?><br><?php echo esc_html($course->title); ?>
                        </div>
                    <?php endif; ?>
                    <div class="course-card-content">
                        <h3 class="course-card-title"><?php echo esc_html($course->title); ?></h3>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=tutor-ead-associar-atividade&course_id=' . $course->id)); ?>" class="btn-associate">
                            <?php _e('Associar a este curso', 'tutor-ead'); ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <p class="empty-courses-message"><?php _e('Nenhum curso encontrado. Por favor, crie um curso antes de associar uma atividade.', 'tutor-ead'); ?></p>
    <?php endif; ?>
</div>