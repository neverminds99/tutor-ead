<?php
/**
 * View para a página de Edição de Usuário.
 *
 * Este arquivo renderiza o formulário para editar os detalhes,
 * permissões e progresso de um usuário.
 *
 * @var WP_User $user               O objeto do usuário que está sendo editado.
 * @var int     $user_id            O ID do usuário.
 * @var string  $highlight_color    A cor de destaque do tema do plugin.
 * @var array   $courses            Uma lista de todos os cursos disponíveis no sistema.
 * @var array   $associated_courses Uma lista de IDs dos cursos nos quais o usuário está matriculado.
 * @var string  $student_celular    O número de celular do usuário.
 * @var string  $user_role          A função (role) atual do usuário.
 * @var wpdb    $wpdb               O objeto de banco de dados global do WordPress.
 */

defined('ABSPATH') || exit; // Previne acesso direto ao arquivo.
?>

<style>
    .tutor-edit-user-wrap {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        background: #f3f4f6;
        margin: -20px;
        padding: 32px;
        min-height: 100vh;
    }
    
    .tutor-edit-user-wrap * {
        box-sizing: border-box;
    }
    
    .edit-header {
        margin-bottom: 32px;
    }
    
    .edit-title {
        font-size: 32px;
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 8px 0;
    }
    
    .edit-subtitle {
        color: #6b7280;
        font-size: 16px;
        margin: 0;
    }
    
    .edit-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        margin-bottom: 24px;
    }
    
    .edit-card-title {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 20px 0;
        padding-bottom: 16px;
        border-bottom: 1px solid #e5e7eb;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .edit-card-title .dashicons {
        font-size: 24px;
        color: <?php echo $highlight_color; ?>;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 24px;
    }
    
    .progress-section {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 20px;
        margin-top: 20px;
    }
    
    .progress-title {
        font-size: 16px;
        font-weight: 600;
        color: #374151;
        margin: 0 0 16px 0;
    }
    
    .progress-item {
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .progress-item:last-child {
        margin-bottom: 0;
        padding-bottom: 0;
        border-bottom: none;
    }
    
    .progress-course-name {
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 12px;
        font-size: 15px;
    }
    
    .progress-inputs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        margin-bottom: 8px;
    }
    
    .progress-input-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .progress-input-group label {
        font-weight: 500;
        color: #6b7280;
        font-size: 14px;
        min-width: 50px;
    }
    
    .progress-input-group input {
        width: 60px;
        padding: 6px 10px;
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        font-size: 14px;
        text-align: center;
    }
    
    .progress-info {
        font-size: 13px;
        color: #6b7280;
        line-height: 1.5;
    }
    
    .form-control {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.2s ease;
        background: #ffffff;
    }
    
    .form-control:focus {
        outline: none;
        border-color: <?php echo $highlight_color; ?>;
        box-shadow: 0 0 0 3px <?php echo $highlight_color; ?>20;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        font-weight: 600;
        color: #374151;
        font-size: 14px;
        margin-bottom: 8px;
    }
    
    .form-help {
        font-size: 13px;
        color: #6b7280;
        margin-top: 6px;
    }
    
    .form-check {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 12px;
    }
    
    .form-check input[type="checkbox"] {
        width: 18px;
        height: 18px;
        cursor: pointer;
    }
    
    .form-check label {
        cursor: pointer;
        margin: 0;
        font-weight: 500;
        color: #1f2937;
        font-size: 14px;
    }
    
    .courses-selection {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 16px;
    }
    
    .btn-primary {
        background: <?php echo $highlight_color; ?>;
        color: #ffffff;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-primary:hover {
        background: <?php echo $highlight_color; ?>e6;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    .btn-primary .dashicons {
        font-size: 18px;
    }
    
    /* As classes de notificação foram herdadas da outra view */
    .tutor-notification-success,
    .tutor-notification-error {
        padding: 16px;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        border-radius: 8px;
    }
    
    .tutor-notification-success {
        background: #d1fae5;
        border: 1px solid #a7f3d0;
        color: #059669;
    }
    
    .tutor-notification-error {
        background: #fee2e2;
        border: 1px solid #fecaca;
        color: #dc2626;
    }
    
    .tutor-notification-success .dashicons,
    .tutor-notification-error .dashicons {
        font-size: 24px;
        flex-shrink: 0;
    }
    
    .tutor-notification-success p,
    .tutor-notification-error p {
        margin: 0;
        font-size: 14px;
        font-weight: 500;
    }
    
    @media (max-width: 768px) {
        .tutor-edit-user-wrap {
            padding: 16px;
        }
        
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .progress-inputs {
            grid-template-columns: 1fr;
        }
        
        .courses-selection {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="tutor-edit-user-wrap">
    <div class="edit-header">
        <h1 class="edit-title"><?php printf(__('Editar Usuário: %s', 'tutor-ead'), esc_html($user->display_name)); ?></h1>
        <p class="edit-subtitle"><?php _e('Atualize as informações e configurações do usuário', 'tutor-ead'); ?></p>
    </div>
    
    <form method="POST">
        <?php
        // Adicionar um nonce para segurança é uma boa prática
        wp_nonce_field('edit_user_nonce_' . $user_id, 'edit_user_nonce_field');
        ?>
        <div class="edit-card">
            <h2 class="edit-card-title">
                <span class="dashicons dashicons-admin-users"></span>
                <?php _e('Informações Básicas', 'tutor-ead'); ?>
            </h2>
            
            <div class="form-grid">
                <div class="form-group">
                    <label><?php _e('Nome de Usuário', 'tutor-ead'); ?></label>
                    <div class="form-control" style="background: #f9fafb; cursor: not-allowed;">
                        <?php echo esc_html($user->user_login); ?>
                    </div>
                    <div class="form-help"><?php _e('O nome de usuário não pode ser alterado', 'tutor-ead'); ?></div>
                </div>
                
                <div class="form-group">
                    <label for="user_email"><?php _e('E-mail', 'tutor-ead'); ?></label>
                    <input type="email" name="user_email" id="user_email" class="form-control" value="<?php echo esc_attr($user->user_email); ?>" placeholder="<?php _e('Digite o e-mail do usuário', 'tutor-ead'); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="student_celular"><?php _e('Celular', 'tutor-ead'); ?></label>
                    <input type="text" name="student_celular" id="student_celular" class="form-control" value="<?php echo esc_attr($student_celular); ?>" placeholder="(11) 98765-4321">
                </div>
                
                <div class="form-group">
                    <label for="user_role"><?php _e('Função', 'tutor-ead'); ?></label>
                    <select name="user_role" id="user_role" class="form-control" required>
                        <option value="tutor_aluno" <?php selected($user_role, 'tutor_aluno'); ?>><?php _e('Aluno', 'tutor-ead'); ?></option>
                        <option value="tutor_professor" <?php selected($user_role, 'tutor_professor'); ?>><?php _e('Professor', 'tutor-ead'); ?></option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="edit-card">
            <h2 class="edit-card-title">
                <span class="dashicons dashicons-welcome-learn-more"></span>
                <?php _e('Cursos Associados', 'tutor-ead'); ?>
            </h2>
            
            <?php if (!empty($courses)): ?>
                <div class="courses-selection">
                    <?php foreach ($courses as $course):
                        $checked = in_array($course->id, $associated_courses) ? 'checked' : '';
                        ?>
                        <label class="form-check">
                            <input type="checkbox" name="course_ids[]" value="<?php echo esc_attr($course->id); ?>" <?php echo $checked; ?>>
                            <span><?php echo esc_html($course->title); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="form-help"><?php _e('Nenhum curso disponível para matrícula.', 'tutor-ead'); ?></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($associated_courses)): ?>
            <div class="edit-card">
                <h2 class="edit-card-title">
                    <span class="dashicons dashicons-chart-line"></span>
                    <?php _e('Progresso do Curso', 'tutor-ead'); ?>
                </h2>
                
                <div class="progress-section">
                    <?php
                    foreach ($associated_courses as $course_id):
                        $curso = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tutoread_courses WHERE id = %d", $course_id));
                        if (!$curso) continue;

                        // Esta lógica de busca de progresso é específica para exibição
                        $progress = $wpdb->get_row(
                            $wpdb->prepare(
                                "SELECT pa.*, tl.id as lesson_id, tm.id as module_id
                                 FROM {$wpdb->prefix}progresso_aulas pa
                                 JOIN {$wpdb->prefix}tutoread_lessons tl ON pa.aula_id = tl.id
                                 JOIN {$wpdb->prefix}tutoread_modules tm ON tl.module_id = tm.id
                                 WHERE pa.aluno_id = %d AND tm.course_id = %d
                                 ORDER BY pa.id DESC LIMIT 1",
                                $user_id, $course_id
                            )
                        );

                        if ($progress) {
                            $modules = $wpdb->get_results($wpdb->prepare("SELECT id FROM {$wpdb->prefix}tutoread_modules WHERE course_id = %d ORDER BY id ASC", $course_id));
                            $module_ids = array_map(function($m) { return $m->id; }, $modules);
                            $module_ordinal = array_search($progress->module_id, $module_ids);
                            $module_ordinal = ($module_ordinal !== false) ? $module_ordinal + 1 : 1;

                            $lessons = $wpdb->get_results($wpdb->prepare("SELECT id FROM {$wpdb->prefix}tutoread_lessons WHERE module_id = %d ORDER BY id ASC", $progress->module_id));
                            $lesson_ids = array_map(function($l) { return $l->id; }, $lessons);
                            $lesson_ordinal = array_search($progress->lesson_id, $lesson_ids);
                            $lesson_ordinal = ($lesson_ordinal !== false) ? $lesson_ordinal + 1 : 1;
                        } else {
                            $module_ordinal = 1;
                            $lesson_ordinal = 1;
                        }

                        $modules_all = $wpdb->get_results($wpdb->prepare("SELECT id FROM {$wpdb->prefix}tutoread_modules WHERE course_id = %d ORDER BY id ASC", $course_id));
                        $total_modules = count($modules_all);
                        
                        $selected_module_id = $progress ? $progress->module_id : ($modules_all[0]->id ?? 0);
                        $total_lessons_in_module = (int) $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}tutoread_lessons WHERE module_id = %d", $selected_module_id));
                        ?>
                        <div class="progress-item">
                            <div class="progress-course-name"><?php echo esc_html($curso->title); ?>:</div>
                            <div class="progress-inputs">
                                <div class="progress-input-group">
                                    <label><?php _e('Módulo', 'tutor-ead'); ?>:</label>
                                    <input type="number" name="module_progress[<?php echo esc_attr($course_id); ?>]" value="<?php echo esc_attr($module_ordinal); ?>" placeholder="<?php _e('Módulo', 'tutor-ead'); ?>">
                                </div>
                                <div class="progress-input-group">
                                    <label><?php _e('Aula', 'tutor-ead'); ?>:</label>
                                    <input type="number" name="lesson_progress[<?php echo esc_attr($course_id); ?>]" value="<?php echo esc_attr($lesson_ordinal); ?>" placeholder="<?php _e('Aula', 'tutor-ead'); ?>">
                                </div>
                            </div>
                            <div class="progress-info">
                                <?php printf(__('Total de módulos: %d', 'tutor-ead'), $total_modules); ?> • 
                                <?php printf(__('Total de aulas neste módulo: %d', 'tutor-ead'), $total_lessons_in_module); ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <p style="margin-top: 24px;">
            <button type="submit" class="btn-primary">
                <span class="dashicons dashicons-saved"></span>
                <?php _e('Salvar Alterações', 'tutor-ead'); ?>
            </button>
        </p>
    </form>
</div>