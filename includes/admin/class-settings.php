<?php

namespace TutorEAD\Admin;

defined('ABSPATH') || exit;

class Settings {

    public function register_settings() {
        register_setting('tutor_ead_settings_group', 'tutor_ead_enable_boletim');
        register_setting('tutor_ead_settings_group', 'tutor_ead_enable_atividades');
        register_setting('tutor_ead_settings_group', 'tutor_ead_course_name');
        register_setting('tutor_ead_settings_group', 'tutor_ead_course_logo');
        register_setting('tutor_ead_settings_group', 'tutor_ead_highlight_color');
        register_setting('tutor_ead_settings_group', 'tutor_ead_aluno_negocio_enabled');
        register_setting('tutor_ead_settings_group', 'tutor_ead_enable_temp_login_links');
        register_setting('tutor_ead_settings_group', 'tutor_ead_comments_mode');
        register_setting('tutor_ead_settings_group', 'tutor_ead_show_support_contact');
        register_setting('tutor_ead_settings_group', 'tutor_ead_support_whatsapp');
        register_setting('tutor_ead_settings_group', 'tutor_ead_login_bg_image');
        register_setting('tutor_ead_settings_group', 'tutor_ead_login_logo_only');
        register_setting('tutor_ead_settings_group', 'tutor_ead_logo_width');
        register_setting('tutor_ead_settings_group', 'tutoread_enable_central', [
            'type'              => 'boolean',
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default'           => false,
        ]);
        register_setting('tutor_ead_settings_group', 'tutor_ead_lesson_release_scope');
        register_setting('tutor_ead_settings_group', 'tutor_ead_global_release_type');
        register_setting('tutor_ead_settings_group', 'tutor_ead_global_drip_quantity');
        register_setting('tutor_ead_settings_group', 'tutor_ead_global_drip_frequency');
        register_setting('tutor_ead_settings_group', 'tutor_ead_global_drip_unit');
        register_setting('tutor_ead_settings_group', 'tutor_ead_registration_fields');
        register_setting('tutor_ead_settings_group', 'tutor_ead_gemini_api_key', [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => '',
        ]);
    }

    private static function is_meu_negocio_installed() {
        if (!function_exists('is_plugin_active')) {
            require_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        return function_exists('is_plugin_active') && is_plugin_active('meu-negocio-tutoread/meu-negocio-tutoread.php');
    }

    public static function settings_page() {
        $license_status = get_option('tutoread_license_status', 'inactive');
        $is_pro = ($license_status === 'active');
        $is_meu_negocio_active = self::is_meu_negocio_installed();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (current_user_can('manage_options')) {
                if (isset($_POST['tutor_permissions'])) {
                    $permissions_data = $_POST['tutor_permissions'];
                    $editable_roles = get_editable_roles();
                    $tutor_caps = \TutorEAD\RoleManager::get_tutor_capabilities();
                    foreach ($editable_roles as $role_slug => $role_details) {
                        if (!array_key_exists($role_slug, $permissions_data)) {
                            continue;
                        }
                        $role = get_role($role_slug);
                        if (!$role) continue;
                        foreach ($tutor_caps as $cap_slug => $cap_name) {
                            if (isset($permissions_data[$role_slug][$cap_slug])) {
                                $role->add_cap($cap_slug, true);
                            } else {
                                $role->remove_cap($cap_slug);
                            }
                        }
                    }
                }
                if (isset($_POST['user_permissions']['user_id']) && !empty($_POST['user_permissions']['user_id'])) {
                    $user_id = absint($_POST['user_permissions']['user_id']);
                    $user = get_user_by('id', $user_id);
                    if ($user) {
                        $submitted_caps = isset($_POST['user_permissions']['caps']) ? (array) $_POST['user_permissions']['caps'] : [];
                        $tutor_caps = \TutorEAD\RoleManager::get_tutor_capabilities();
                        foreach ($tutor_caps as $cap_slug => $cap_name) {
                            if (array_key_exists($cap_slug, $submitted_caps)) {
                                $user->add_cap($cap_slug, true);
                            } else {
                                $user->remove_cap($cap_slug);
                            }
                        }
                    }
                }
            }

            if ($is_pro) {
                $enable_boletim    = isset($_POST['enable_boletim']) ? '1' : '0';
                $enable_atividades = isset($_POST['enable_atividades']) ? '1' : '0';
            } else {
                $enable_boletim    = '0';
                $enable_atividades = '0';
            }
            update_option('tutor_ead_enable_boletim', $enable_boletim);
            update_option('tutor_ead_enable_atividades', $enable_atividades);

            if (isset($_POST['tutor_ead_course_name'])) {
                update_option('tutor_ead_course_name', sanitize_text_field($_POST['tutor_ead_course_name']));
            }
            if (isset($_POST['tutor_ead_course_logo'])) {
                update_option('tutor_ead_course_logo', esc_url_raw($_POST['tutor_ead_course_logo']));
            }
            if (isset($_POST['tutor_ead_highlight_color'])) {
                update_option('tutor_ead_highlight_color', sanitize_text_field($_POST['tutor_ead_highlight_color']));
            }
            if (isset($_POST['tutor_ead_comments_mode'])) {
                update_option('tutor_ead_comments_mode', sanitize_text_field($_POST['tutor_ead_comments_mode']));
            }

            $show_support_contact = isset($_POST['tutor_ead_show_support_contact']) ? '1' : '0';
            update_option('tutor_ead_show_support_contact', $show_support_contact);
            if (isset($_POST['tutor_ead_support_whatsapp'])) {
                update_option('tutor_ead_support_whatsapp', sanitize_text_field($_POST['tutor_ead_support_whatsapp']));
            }

            if (isset($_POST['tutor_ead_login_bg_image'])) {
                update_option('tutor_ead_login_bg_image', esc_url_raw($_POST['tutor_ead_login_bg_image']));
            }
            $login_logo_only = isset($_POST['tutor_ead_login_logo_only']) ? '1' : '0';
            update_option('tutor_ead_login_logo_only', $login_logo_only);
            if (isset($_POST['tutor_ead_logo_width'])) {
                update_option('tutor_ead_logo_width', absint($_POST['tutor_ead_logo_width']));
            }

            $temp_login = isset($_POST['tutor_ead_enable_temp_login_links']) ? '1' : '0';
            update_option('tutor_ead_enable_temp_login_links', $temp_login);

            if ($is_meu_negocio_active) {
                $aluno_negocio_enabled = isset($_POST['tutor_ead_aluno_negocio_enabled']) ? '1' : '0';
            } else {
                $aluno_negocio_enabled = '0';
            }
            update_option('tutor_ead_aluno_negocio_enabled', $aluno_negocio_enabled);

            $central_enabled = isset($_POST['tutoread_enable_central']) ? '1' : '0';
            update_option('tutoread_enable_central', $central_enabled);

            if (isset($_POST['tutor_ead_lesson_release_scope'])) {
                update_option('tutor_ead_lesson_release_scope', sanitize_text_field($_POST['tutor_ead_lesson_release_scope']));
            }
            if (isset($_POST['tutor_ead_global_release_type'])) {
                update_option('tutor_ead_global_release_type', sanitize_text_field($_POST['tutor_ead_global_release_type']));
            }
            if (isset($_POST['tutor_ead_global_drip_quantity'])) {
                update_option('tutor_ead_global_drip_quantity', absint($_POST['tutor_ead_global_drip_quantity']));
            }
            if (isset($_POST['tutor_ead_global_drip_frequency'])) {
                update_option('tutor_ead_global_drip_frequency', absint($_POST['tutor_ead_global_drip_frequency']));
            }
            if (isset($_POST['tutor_ead_global_drip_unit'])) {
                update_option('tutor_ead_global_drip_unit', sanitize_text_field($_POST['tutor_ead_global_drip_unit']));
            }
            if (isset($_POST['tutor_ead_registration_fields'])) {
                $reg_fields = array_map('sanitize_text_field', $_POST['tutor_ead_registration_fields']);
                update_option('tutor_ead_registration_fields', $reg_fields);
            } else {
                update_option('tutor_ead_registration_fields', []);
            }
            if (isset($_POST['tutor_ead_gemini_api_key'])) {
                update_option('tutor_ead_gemini_api_key', sanitize_text_field($_POST['tutor_ead_gemini_api_key']));
            }

            echo '<div class="tutor-notification-success"><span class="dashicons dashicons-yes-alt"></span><p>' . __('Configurações salvas com sucesso!', 'tutor-ead') . '</p></div>';
        }

        $enable_boletim       = get_option('tutor_ead_enable_boletim', '0');
        $enable_atividades    = get_option('tutor_ead_enable_atividades', '0');
        $course_name          = get_option('tutor_ead_course_name', '');
        $course_logo          = get_option('tutor_ead_course_logo', '');
        $highlight_color      = get_option('tutor_ead_highlight_color', '#0073aa');
        $comments_mode        = get_option('tutor_ead_comments_mode', 'general');
        $show_support_contact = get_option('tutor_ead_show_support_contact', '0');
        $support_whatsapp     = get_option('tutor_ead_support_whatsapp', '');
        $login_bg_image       = get_option('tutor_ead_login_bg_image', '');
        $login_logo_only      = get_option('tutor_ead_login_logo_only', '0');
        $logo_width           = get_option('tutor_ead_logo_width', 80);
        $temp_login           = get_option('tutor_ead_enable_temp_login_links', '0');
        $aluno_negocio_enabled= get_option('tutor_ead_aluno_negocio_enabled', '0');
        $lesson_release_scope   = get_option('tutor_ead_lesson_release_scope', 'global');
        $global_release_type    = get_option('tutor_ead_global_release_type', 'full');
        $global_drip_quantity   = get_option('tutor_ead_global_drip_quantity', 1);
        $global_drip_frequency  = get_option('tutor_ead_global_drip_frequency', 1);
        $global_drip_unit       = get_option('tutor_ead_global_drip_unit', 'days');
        ?>
        <style>
            .tutor-settings-wrap{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif;background:#f3f4f6;margin:-20px;padding:32px;min-height:100vh}.tutor-settings-wrap *{box-sizing:border-box}.settings-header{margin-bottom:32px}.settings-title{font-size:32px;font-weight:600;color:#1f2937;margin:0 0 8px 0}.settings-subtitle{color:#6b7280;font-size:16px;margin:0}.settings-sections{display:flex;gap:32px;flex-wrap:wrap}.settings-main{flex:1;min-width:300px}.settings-sidebar{width:320px;min-width:300px}.settings-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;box-shadow:0 1px 3px 0 rgba(0,0,0,.05);margin-bottom:24px}.section-title{font-size:20px;font-weight:600;color:#1f2937;margin:0 0 20px 0;padding-bottom:16px;border-bottom:1px solid #e5e7eb;display:flex;align-items:center;gap:12px}.section-title .dashicons{font-size:24px;color:<?php echo $highlight_color; ?>}.subsection-title{font-size:16px;font-weight:600;color:#374151;margin:24px 0 16px 0;display:flex;align-items:center;gap:8px}.form-group{margin-bottom:20px}.form-group label{display:block;font-weight:600;color:#374151;font-size:14px;margin-bottom:8px}.form-control{width:100%;padding:10px 12px;border:1px solid #e5e7eb;border-radius:8px;font-size:14px;transition:all .2s ease;background:#fff}.form-control:focus{outline:none;border-color:<?php echo $highlight_color; ?>;box-shadow:0 0 0 3px <?php echo $highlight_color; ?>20}.form-check{display:flex;align-items:center;gap:8px;margin-bottom:16px}.form-check input[type=checkbox],.form-check input[type=radio]{width:18px;height:18px;cursor:pointer}.form-check label{cursor:pointer;margin:0;font-weight:500;color:#1f2937}.form-help{font-size:13px;color:#6b7280;margin-top:6px;line-height:1.5}.form-error{font-size:13px;color:#dc2626;margin-top:6px}.btn-primary{background:<?php echo $highlight_color; ?>;color:#fff;border:none;padding:12px 24px;border-radius:8px;font-weight:600;font-size:14px;cursor:pointer;transition:all .2s ease;display:inline-flex;align-items:center;gap:8px}.btn-primary:hover:not(:disabled){background:<?php echo $highlight_color; ?>e6;transform:translateY(-1px);box-shadow:0 4px 6px -1px rgba(0,0,0,.1),0 2px 4px -1px rgba(0,0,0,.06)}.btn-primary:disabled{opacity:.5;cursor:not-allowed}.btn-secondary{background:#fff;color:#374151;border:1px solid #e5e7eb;padding:8px 16px;border-radius:6px;font-weight:500;font-size:13px;cursor:pointer;transition:all .2s ease;display:inline-flex;align-items:center;gap:6px}.btn-secondary:hover{background:#f9fafb;border-color:#d1d5db}.btn-secondary .dashicons{font-size:16px}.form-row{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:20px;align-items:flex-end}.media-preview{margin-top:12px;padding:16px;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;text-align:center}.media-preview img{max-width:100%;height:auto;border-radius:6px;box-shadow:0 1px 3px 0 rgba(0,0,0,.1)}.media-preview-empty{color:#6b7280;font-size:14px;padding:20px}.color-picker-wrapper{position:relative}.color-preview{position:absolute;right:8px;top:50%;transform:translateY(-50%);width:32px;height:32px;border-radius:6px;border:1px solid #e5e7eb;cursor:pointer}.range-slider-wrapper{display:flex;align-items:center;gap:16px}.range-slider{flex:1;height:6px;border-radius:3px;background:#e5e7eb;outline:none;cursor:pointer}.range-slider::-webkit-slider-thumb{-webkit-appearance:none;appearance:none;width:20px;height:20px;border-radius:50%;background:<?php echo $highlight_color; ?>;cursor:pointer;border:3px solid #fff;box-shadow:0 1px 3px 0 rgba(0,0,0,.1)}.range-slider::-moz-range-thumb{width:20px;height:20px;border-radius:50%;background:<?php echo $highlight_color; ?>;cursor:pointer;border:3px solid #fff;box-shadow:0 1px 3px 0 rgba(0,0,0,.1)}.range-value{font-weight:600;color:#1f2937;min-width:60px;text-align:right}.pro-badge{display:inline-flex;align-items:center;gap:4px;background:#fee2e2;color:#dc2626;padding:4px 12px;border-radius:16px;font-size:12px;font-weight:600;margin-left:8px}.pro-feature{position:relative}.pro-feature:not(.is-pro){opacity:.6}.tutor-notification-success{background:#d1fae5;border:1px solid #a7f3d0;border-radius:8px;padding:16px;margin-bottom:24px;display:flex;align-items:center;gap:12px;color:#059669}.tutor-notification-success .dashicons{font-size:24px;flex-shrink:0}.tutor-notification-success p{margin:0;font-size:14px;font-weight:500}.settings-logo-footer{position:absolute;bottom:20px;right:20px}.settings-logo-footer img{max-width:80px;opacity:.5}@media (max-width:1024px){.settings-sections{flex-direction:column}.settings-sidebar{width:100%}}
        </style>
        <div class="tutor-settings-wrap">
            <div class="settings-header">
                <h1 class="settings-title"><?php _e('Configurações do Tutor EAD', 'tutor-ead'); ?></h1>
                <p class="settings-subtitle"><?php _e('Personalize e configure todos os aspectos do seu sistema de ensino', 'tutor-ead'); ?></p>
                <a href="<?php echo esc_url(admin_url('index.php?page=tutor-ead-setup-wizard')); ?>" class="page-title-action"><?php _e('Reabrir Wizard de Configuração', 'tutor-ead'); ?></a>
            </div>
            <form method="post" action="">
                <?php settings_fields('tutor_ead_settings_group'); ?>
                <div class="settings-sections">
                    <div class="settings-main">
                        <div class="settings-card">
                            <h2 class="section-title"><span class="dashicons dashicons-admin-settings"></span><?php _e('Configurações Gerais', 'tutor-ead'); ?></h2>
                            <h3 class="subsection-title"><span class="dashicons dashicons-welcome-learn-more" style="font-size: 18px;"></span><?php _e('Personalização do Curso', 'tutor-ead'); ?></h3>
                            <div class="form-group">
                                <label for="tutor_ead_course_name"><?php _e('Nome do Curso', 'tutor-ead'); ?></label>
                                <input type="text" id="tutor_ead_course_name" name="tutor_ead_course_name" class="form-control" value="<?php echo esc_attr($course_name); ?>" placeholder="<?php _e('Digite o nome do seu curso', 'tutor-ead'); ?>">
                            </div>
                            <div class="form-group">
                                <label for="tutor_ead_course_logo"><?php _e('Logotipo do Curso', 'tutor-ead'); ?></label>
                                <div style="display: flex; gap: 12px;">
                                    <input type="text" id="tutor_ead_course_logo" name="tutor_ead_course_logo" class="form-control" value="<?php echo esc_attr($course_logo); ?>" placeholder="<?php _e('URL da imagem do logotipo', 'tutor-ead'); ?>">
                                    <button type="button" id="upload_image_button" class="btn-secondary"><span class="dashicons dashicons-upload"></span><?php _e('Escolher', 'tutor-ead'); ?></button>
                                </div>
                                <div class="form-help"><?php _e('Selecione ou envie a imagem do logotipo.', 'tutor-ead'); ?></div>
                                <?php if ($course_logo) : ?>
                                    <div class="media-preview"><img id="logo_preview" src="<?php echo esc_url($course_logo); ?>" alt="<?php _e('Logo Preview', 'tutor-ead'); ?>" style="max-height: 100px;"></div>
                                <?php endif; ?>
                            </div>
                            <div class="form-group">
                                <label for="tutor_ead_highlight_color"><?php _e('Cor de Destaque', 'tutor-ead'); ?></label>
                                <div class="color-picker-wrapper">
                                    <input type="text" id="tutor_ead_highlight_color" name="tutor_ead_highlight_color" class="form-control" value="<?php echo esc_attr($highlight_color); ?>" placeholder="#0073aa">
                                    <div class="color-preview" style="background-color: <?php echo esc_attr($highlight_color); ?>;"></div>
                                </div>
                                <div class="form-help"><?php _e('Exemplo: #0073aa', 'tutor-ead'); ?></div>
                            </div>
                        </div>
                        <?php self::add_gemini_api_key_field(); ?>
                        <div class="settings-card">
                            <h2 class="section-title"><span class="dashicons dashicons-calendar-alt"></span><?php _e('Liberação de Conteúdo (Drip)', 'tutor-ead'); ?></h2>
                            <div class="form-group">
                                <label><?php _e('Como deseja configurar a liberação das aulas?', 'tutor-ead'); ?></label>
                                <div class="form-check"><input type="radio" id="tutor_ead_lesson_release_scope_global" name="tutor_ead_lesson_release_scope" value="global" <?php checked($lesson_release_scope, 'global'); ?>/><label for="tutor_ead_lesson_release_scope_global"><?php _e('Global (Regra única para todos os cursos)', 'tutor-ead'); ?></label></div>
                                <div class="form-check"><input type="radio" id="tutor_ead_lesson_release_scope_per_course" name="tutor_ead_lesson_release_scope" value="per_course" <?php checked($lesson_release_scope, 'per_course'); ?>/><label for="tutor_ead_lesson_release_scope_per_course"><?php _e('Por Curso (Configurar em cada curso individualmente)', 'tutor-ead'); ?></label></div>
                            </div>
                            <div id="tutor_ead_global_release_settings" style="<?php echo $lesson_release_scope === 'global' ? '' : 'display: none;'; ?>">
                                <hr style="margin: 20px 0;">
                                <h3 class="subsection-title"><?php _e('Configuração Global', 'tutor-ead'); ?></h3>
                                <div class="form-group">
                                    <label><?php _e('Tipo de Liberação', 'tutor-ead'); ?></label>
                                    <div class="form-check"><input type="radio" id="tutor_ead_global_release_type_full" name="tutor_ead_global_release_type" value="full" <?php checked($global_release_type, 'full'); ?>/><label for="tutor_ead_global_release_type_full"><?php _e('Conteúdo 100% Liberado', 'tutor-ead'); ?></label></div>
                                    <div class="form-check"><input type="radio" id="tutor_ead_global_release_type_drip" name="tutor_ead_global_release_type" value="drip" <?php checked($global_release_type, 'drip'); ?>/><label for="tutor_ead_global_release_type_drip"><?php _e('Liberar aos Poucos (Gotejamento)', 'tutor-ead'); ?></label></div>
                                </div>
                                <div id="tutor_ead_drip_settings" style="<?php echo $global_release_type === 'drip' ? '' : 'display: none;'; ?>">
                                    <p class="form-help" style="margin-bottom: 20px;"><?php _e('Defina o intervalo para a liberação de novas aulas após a matrícula do aluno.', 'tutor-ead'); ?></p>
                                    <div class="form-row">
                                        <div class="form-group"><label for="tutor_ead_global_drip_quantity"><?php _e('Liberar', 'tutor-ead'); ?></label><input type="number" id="tutor_ead_global_drip_quantity" name="tutor_ead_global_drip_quantity" class="form-control" value="<?php echo esc_attr($global_drip_quantity); ?>" min="1"><div class="form-help"><?php _e('aulas(s)', 'tutor-ead'); ?></div></div>
                                        <div class="form-group"><label for="tutor_ead_global_drip_frequency"><?php _e('a cada', 'tutor-ead'); ?></label><input type="number" id="tutor_ead_global_drip_frequency" name="tutor_ead_global_drip_frequency" class="form-control" value="<?php echo esc_attr($global_drip_frequency); ?>" min="1"></div>
                                        <div class="form-group">
                                            <label for="tutor_ead_global_drip_unit">&nbsp;</label>
                                            <select id="tutor_ead_global_drip_unit" name="tutor_ead_global_drip_unit" class="form-control">
                                                <option value="hours" <?php selected($global_drip_unit, 'hours'); ?>><?php _e('Hora(s)', 'tutor-ead'); ?></option>
                                                <option value="days" <?php selected($global_drip_unit, 'days'); ?>><?php _e('Dia(s)', 'tutor-ead'); ?></option>
                                                <option value="weeks" <?php selected($global_drip_unit, 'weeks'); ?>><?php _e('Semana(s)', 'tutor-ead'); ?></option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="settings-card">
                            <h2 class="section-title"><span class="dashicons dashicons-id-alt"></span><?php _e('Formulário de Registro de Aluno', 'tutor-ead'); ?></h2>
                            <h3 class="subsection-title"><?php _e('Campos Adicionais', 'tutor-ead'); ?></h3>
                            <p class="form-help" style="margin-bottom: 20px;"><?php _e('Selecione quais campos adicionais devem ser solicitados no formulário de registro de novos alunos.', 'tutor-ead'); ?></p>
                            <?php
                            $registration_fields = get_option('tutor_ead_registration_fields', []);
                            $possible_fields = ['cpf' => __('CPF', 'tutor-ead'), 'rg' => __('RG', 'tutor-ead'), 'endereco' => __('Endereço Completo', 'tutor-ead'), 'cep' => __('CEP', 'tutor-ead'), 'cidade' => __('Cidade', 'tutor-ead'), 'estado' => __('Estado', 'tutor-ead'), 'valor_contrato' => __('Valor de Contrato', 'tutor-ead')];
                            foreach ($possible_fields as $field_key => $field_label) : ?>
                                <div class="form-check"><input type="checkbox" id="reg_field_<?php echo esc_attr($field_key); ?>" name="tutor_ead_registration_fields[]" value="<?php echo esc_attr($field_key); ?>" <?php checked(in_array($field_key, $registration_fields)); ?>/><label for="reg_field_<?php echo esc_attr($field_key); ?>"><?php echo esc_html($field_label); ?></label></div>
                            <?php endforeach; ?>
                        </div>
                        <div class="settings-card">
                            <h2 class="section-title"><span class="dashicons dashicons-lock"></span><?php _e('Personalização da Página de Login', 'tutor-ead'); ?></h2>
                            <div class="form-group">
                                <label for="tutor_ead_login_bg_image"><?php _e('Imagem de Fundo', 'tutor-ead'); ?></label>
                                <div style="display: flex; gap: 12px;">
                                    <input type="text" id="tutor_ead_login_bg_image" name="tutor_ead_login_bg_image" class="form-control" value="<?php echo esc_attr($login_bg_image); ?>" placeholder="<?php _e('URL da imagem de fundo', 'tutor-ead'); ?>">
                                    <button type="button" id="upload_login_bg_button" class="btn-secondary"><span class="dashicons dashicons-upload"></span><?php _e('Escolher', 'tutor-ead'); ?></button>
                                </div>
                                <div class="form-help"><?php _e('Selecione ou envie a imagem de fundo para a página de login.', 'tutor-ead'); ?></div>
                                <?php if ($login_bg_image) : ?>
                                    <div class="media-preview"><img id="tutor_ead_login_bg_preview" src="<?php echo esc_url($login_bg_image); ?>" alt="<?php _e('Background Preview', 'tutor-ead'); ?>" style="max-height: 150px;"></div>
                                <?php endif; ?>
                            </div>
                            <div class="form-check"><input type="checkbox" id="tutor_ead_login_logo_only" name="tutor_ead_login_logo_only" value="1" <?php checked('1', $login_logo_only); ?>/><label for="tutor_ead_login_logo_only"><?php _e('Manter apenas a logo no formulário de login', 'tutor-ead'); ?></label></div>
                            <div class="form-help" style="margin-bottom: 20px;"><?php _e('Exibir somente a logo em vez do título no formulário de login.', 'tutor-ead'); ?></div>
                            <div class="form-group" id="tutor_ead_logo_width_row" <?php echo ($login_logo_only === '1' ? '' : 'style="display:none;"'); ?>>
                                <label><?php _e('Largura da Logo', 'tutor-ead'); ?></label>
                                <div class="range-slider-wrapper">
                                    <input type="range" id="tutor_ead_logo_width" name="tutor_ead_logo_width" class="range-slider" min="50" max="200" value="<?php echo esc_attr($logo_width); ?>">
                                    <span class="range-value" id="tutor_ead_logo_width_value"><?php echo esc_html($logo_width); ?>px</span>
                                </div>
                                <?php if ($course_logo) : ?>
                                    <div class="media-preview" style="margin-top: 16px;"><img id="tutor_ead_logo_preview_2" src="<?php echo esc_url($course_logo); ?>" alt="<?php _e('Logo Preview', 'tutor-ead'); ?>" style="width: <?php echo intval($logo_width); ?>px; height: auto;"></div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php
                        if (current_user_can('manage_options')) :
                            $tutor_caps = \TutorEAD\RoleManager::get_tutor_capabilities();
                            $editable_roles = get_editable_roles();
                            $loaded_user = null;
                            $user_feedback_message = '';
                            if (isset($_POST['load_user_permissions']) && !empty($_POST['tutor_user_search'])) {
                                $search_term = sanitize_text_field($_POST['tutor_user_search']);
                                if (is_numeric($search_term)) {
                                    $loaded_user = get_user_by('ID', $search_term);
                                }
                                if (!$loaded_user) {
                                    $loaded_user = get_user_by('email', $search_term);
                                }
                                if (!$loaded_user) {
                                    $loaded_user = get_user_by('login', $search_term);
                                }
                                if (!$loaded_user) {
                                    $user_feedback_message = '<div class="form-error" style="margin-top: 10px;">' . __('Usuário não encontrado.', 'tutor-ead') . '</div>';
                                }
                            } elseif (isset($_POST['user_permissions']['user_id'])) {
                                $user_id = absint($_POST['user_permissions']['user_id']);
                                if ($user_id > 0) {
                                    $loaded_user = get_user_by('ID', $user_id);
                                }
                            }
                        ?>
                        <div class="settings-card">
                            <h2 class="section-title"><span class="dashicons dashicons-admin-users"></span><?php _e('Gerenciamento de Permissões', 'tutor-ead'); ?></h2>
                            <h3 class="subsection-title"><?php _e('Permissões por Função (Role)', 'tutor-ead'); ?></h3>
                            <p class="form-help" style="margin-bottom: 20px;"><?php _e('Marque as capacidades que cada função terá. As alterações são salvas ao clicar em "Salvar Configurações".', 'tutor-ead'); ?></p>
                            <table class="wp-list-table widefat striped">
                                <thead>
                                    <tr>
                                        <th scope="col"><?php _e('Capacidade', 'tutor-ead'); ?></th>
                                        <?php foreach ($editable_roles as $role_slug => $role_details) : ?>
                                            <?php if (in_array($role_slug, ['tutor_admin', 'tutor_professor', 'tutor_aluno'])) : ?>
                                                <th scope="col" style="text-align: center;"><?php echo esc_html($role_details['name']); ?></th>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tutor_caps as $cap_slug => $cap_name) : ?>
                                        <tr>
                                            <td><strong><?php echo esc_html($cap_name); ?></strong><br><small><code><?php echo esc_html($cap_slug); ?></code></small></td>
                                            <?php foreach ($editable_roles as $role_slug => $role_details) : ?>
                                                <?php if (in_array($role_slug, ['tutor_admin', 'tutor_professor', 'tutor_aluno'])) : ?>
                                                    <?php $role = get_role($role_slug); ?>
                                                    <td style="text-align: center;"><input type="checkbox" name="tutor_permissions[<?php echo esc_attr($role_slug); ?>][<?php echo esc_attr($cap_slug); ?>]" value="1" <?php checked($role->has_cap($cap_slug)); ?> <?php if ($role_slug === 'tutor_admin' && $cap_slug === 'manage_tutor_settings') echo 'disabled'; ?>/></td>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                            <h3 class="subsection-title" style="margin-top: 40px;"><?php _e('Permissões por Usuário Específico', 'tutor-ead'); ?></h3>
                            <p class="form-help" style="margin-bottom: 20px;"><?php _e('Conceda ou revogue capacidades para um usuário individual, sobrepondo as permissões de sua função.', 'tutor-ead'); ?></p>
                            <div class="form-group">
                                <label for="tutor_user_search"><?php _e('Carregar Permissões do Usuário', 'tutor-ead'); ?></label>
                                <div style="display: flex; gap: 12px; align-items: center;">
                                    <input type="text" id="tutor_user_search" name="tutor_user_search" class="form-control" value="<?php echo $loaded_user ? esc_attr($loaded_user->user_login) : ''; ?>" placeholder="Digite o ID, e-mail ou login">
                                    <button type="submit" name="load_user_permissions" value="1" class="btn-secondary"><?php _e('Carregar', 'tutor-ead'); ?></button>
                                </div>
                                <?php echo $user_feedback_message; ?>
                            </div>
                            <?php if ($loaded_user) : ?>
                                <hr style="margin: 30px 0;">
                                <h4><?php printf(__('Editando permissões para: %s', 'tutor-ead'), '<strong>' . esc_html($loaded_user->display_name) . '</strong> (ID: ' . $loaded_user->ID . ')'); ?></h4>
                                <input type="hidden" name="user_permissions[user_id]" value="<?php echo esc_attr($loaded_user->ID); ?>">
                                <table class="wp-list-table widefat striped" style="margin-top: 20px;">
                                    <thead>
                                        <tr>
                                            <th><?php _e('Capacidade', 'tutor-ead'); ?></th>
                                            <th style="text-align: center;"><?php _e('Permitido', 'tutor-ead'); ?></th>
                                            <th><?php _e('Herdado da Função', 'tutor-ead'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tutor_caps as $cap_slug => $cap_name) :
                                            $role_has_cap = false;
                                            if (!empty($loaded_user->roles)) {
                                                foreach($loaded_user->roles as $role_name) {
                                                    $role = get_role($role_name);
                                                    if ($role && $role->has_cap($cap_slug)) {
                                                        $role_has_cap = true;
                                                        break;
                                                    }
                                                }
                                            }
                                        ?>
                                            <tr>
                                                <td><strong><?php echo esc_html($cap_name); ?></strong></td>
                                                <td style="text-align: center;"><input type="checkbox" name="user_permissions[caps][<?php echo esc_attr($cap_slug); ?>]" value="1" <?php checked($loaded_user->has_cap($cap_slug)); ?>/></td>
                                                <td><?php if ($role_has_cap) : ?><span class="dashicons dashicons-yes-alt" style="color: #4ade80;" title="<?php _e('Sim', 'tutor-ead'); ?>"></span><?php else: ?><span class="dashicons dashicons-no-alt" style="color: #f87171;" title="<?php _e('Não', 'tutor-ead'); ?>"></span><?php endif; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="settings-sidebar">
                        <div class="settings-card">
                            <h2 class="section-title"><span class="dashicons dashicons-sos"></span><?php _e('Suporte', 'tutor-ead'); ?></h2>
                            <div class="form-check"><input type="checkbox" id="tutor_ead_show_support_contact" name="tutor_ead_show_support_contact" value="1" <?php checked('1', $show_support_contact); ?>/><label for="tutor_ead_show_support_contact"><?php _e('Mostrar contato de suporte no painel do aluno', 'tutor-ead'); ?></label></div>
                            <div class="form-group">
                                <label for="tutor_ead_support_whatsapp"><?php _e('WhatsApp do Suporte', 'tutor-ead'); ?></label>
                                <input type="text" id="tutor_ead_support_whatsapp" name="tutor_ead_support_whatsapp" class="form-control" value="<?php echo esc_attr($support_whatsapp); ?>" placeholder="+55 11 98765-4321">
                                <div class="form-help"><?php _e('Número com código do país e DDD', 'tutor-ead'); ?></div>
                            </div>
                        </div>
                        <div class="settings-card">
                            <h2 class="section-title"><span class="dashicons dashicons-visibility"></span><?php _e('Visualização do Curso', 'tutor-ead'); ?></h2>
                            <div class="form-group">
                                <label for="tutor_ead_comments_mode"><?php _e('Modo de Comentários', 'tutor-ead'); ?></label>
                                <select id="tutor_ead_comments_mode" name="tutor_ead_comments_mode" class="form-control">
                                    <option value="general" <?php selected($comments_mode, 'general'); ?>><?php _e('Geral', 'tutor-ead'); ?></option>
                                    <option value="restricted" <?php selected($comments_mode, 'restricted'); ?>><?php _e('Apenas administradores e Professores', 'tutor-ead'); ?></option>
                                </select>
                                <div class="form-help"><?php _e('Defina quem pode visualizar e fazer comentários nos vídeos.', 'tutor-ead'); ?></div>
                            </div>
                        </div>
                        <div class="settings-card">
                            <h2 class="section-title"><span class="dashicons dashicons-star-filled"></span><?php _e('Configurações Pro', 'tutor-ead'); ?><?php if (!$is_pro) : ?><span class="pro-badge">PRO</span><?php endif; ?></h2>
                            <div class="form-check pro-feature <?php echo $is_pro ? 'is-pro' : ''; ?>"><input type="checkbox" id="tutor_ead_enable_temp_login_links" name="tutor_ead_enable_temp_login_links" value="1" <?php checked('1', $temp_login); ?> <?php echo ($is_pro ? '' : 'disabled'); ?>/><label for="tutor_ead_enable_temp_login_links"><?php _e('Links de Login Temporários', 'tutor-ead'); ?></label></div>
                            <div class="form-help" style="margin-bottom: 20px;"><?php _e('Habilita a geração de links de login que autenticam automaticamente alunos sem senha.', 'tutor-ead'); ?></div>
                            <div class="form-check pro-feature <?php echo $is_pro ? 'is-pro' : ''; ?>"><input type="checkbox" id="enable_boletim" name="enable_boletim" value="1" <?php checked('1', $enable_boletim); ?> <?php echo ($is_pro ? '' : 'disabled'); ?>/><label for="enable_boletim"><?php _e('Ativar Boletim', 'tutor-ead'); ?></label></div>
                            <div class="form-help" style="margin-bottom: 20px;"><?php _e('Sistema de boletim com notas e avaliações.', 'tutor-ead'); ?></div>
                            <div class="form-check pro-feature <?php echo $is_pro ? 'is-pro' : ''; ?>"><input type="checkbox" id="enable_atividades" name="enable_atividades" value="1" <?php checked('1', $enable_atividades); ?> <?php echo ($is_pro ? '' : 'disabled'); ?>/><label for="enable_atividades"><?php _e('Ativar Atividades', 'tutor-ead'); ?></label></div>
                            <div class="form-help"><?php _e('Sistema completo de atividades e exercícios.', 'tutor-ead'); ?></div>
                            <div class="form-check">
                                <?php $central_enabled = get_option('tutoread_enable_central', false); ?>
                                <input type="checkbox" id="tutoread_enable_central" name="tutoread_enable_central" value="1" <?php checked( true, $central_enabled ); ?>/>
                                <label for="tutoread_enable_central"><?php _e('Ativar Integração com a Central', 'tutor-ead'); ?></label>
                            </div>
                            <div class="form-help" style="margin-bottom: 20px;"><?php _e('Habilita o submenu de “Central de Identificadores” e permite registrar seu plugin na Central.', 'tutor-ead'); ?></div>
                            <h3 class="subsection-title"><span class="dashicons dashicons-businessman" style="font-size: 18px;"></span><?php _e('Aluno Negócio', 'tutor-ead'); ?></h3>
                            <div class="form-check">
                                <input type="checkbox" id="tutor_ead_aluno_negocio_enabled" name="tutor_ead_aluno_negocio_enabled" value="1" <?php checked('1', $aluno_negocio_enabled); ?> <?php echo ($is_meu_negocio_active ? '' : 'disabled'); ?>/>
                                <label for="tutor_ead_aluno_negocio_enabled"><?php _e('Ativar Aluno Negócio', 'tutor-ead'); ?></label>
                            </div>
                            <?php if (!$is_meu_negocio_active) : ?>
                                <div class="form-error"><?php _e('O plugin complementar "Meu Negócio TutorEAD" não está instalado ou ativo.', 'tutor-ead'); ?></div>
                            <?php else : ?>
                                <div class="form-help"><?php _e('Habilita o plugin complementar "Meu Negócio TutorEAD".', 'tutor-ead'); ?></div>
                            <?php endif; ?>
                        </div>
                        <div style="margin-top: 32px;"><button type="submit" class="btn-primary"><span class="dashicons dashicons-saved"></span><?php _e('Salvar Configurações', 'tutor-ead'); ?></button></div>
                    </div>
                </div>
            </form>
            <div class="settings-logo-footer"><img src="<?php echo esc_url(TUTOR_EAD_URL . 'img/tutureadlogo.png'); ?>" alt="Tutor EAD Logo"></div>
        </div>
        <script>
            jQuery(document).ready(function($){$('#tutor_ead_highlight_color').wpColorPicker({change:function(event,ui){$('.color-preview').css('background-color',ui.color.toString())}});var mediaUploader;$('#upload_image_button').on('click',function(e){e.preventDefault();if(mediaUploader){mediaUploader.open();return}
            mediaUploader=wp.media({title:'<?php _e("Escolha o logotipo","tutor-ead");?>',button:{text:'<?php _e("Usar esta imagem","tutor-ead");?>'},multiple:false});mediaUploader.on('select',function(){var attachment=mediaUploader.state().get('selection').first().toJSON();$('#tutor_ead_course_logo').val(attachment.url);if($('#logo_preview').length){$('#logo_preview').attr('src',attachment.url)}else{var newPreview='<div class="media-preview"><img id="logo_preview" src="'+attachment.url+'" alt="<?php _e('Logo Preview','tutor-ead');?>" style="max-height: 100px;"></div>';$('#upload_image_button').closest('.form-group').append(newPreview)}});mediaUploader.open()});var loginBgUploader;$('#upload_login_bg_button').on('click',function(e){e.preventDefault();if(loginBgUploader){loginBgUploader.open();return}
            loginBgUploader=wp.media({title:'<?php _e("Escolha a imagem de fundo","tutor-ead");?>',button:{text:'<?php _e("Usar esta imagem","tutor-ead");?>'},multiple:false});loginBgUploader.on('select',function(){var attachment=loginBgUploader.state().get('selection').first().toJSON();$('#tutor_ead_login_bg_image').val(attachment.url);if($('#tutor_ead_login_bg_preview').length){$('#tutor_ead_login_bg_preview').attr('src',attachment.url)}else{var newPreview='<div class="media-preview"><img id="tutor_ead_login_bg_preview" src="'+attachment.url+'" alt="<?php _e('Background Preview','tutor-ead');?>" style="max-height: 150px;"></div>';$('#upload_login_bg_button').closest('.form-group').append(newPreview)}});loginBgUploader.open()});$('#tutor_ead_login_logo_only').on('change',function(){if($(this).is(':checked')){$('#tutor_ead_logo_width_row').slideDown()}else{$('#tutor_ead_logo_width_row').slideUp()}});$('#tutor_ead_logo_width').on('input',function(){var newWidth=$(this).val();$('#tutor_ead_logo_width_value').text(newWidth+'px');$('#tutor_ead_logo_preview_2').css('width',newWidth+'px')});$('input[name="tutor_ead_lesson_release_scope"]').on('change',function(){if($(this).val()==='global'){$('#tutor_ead_global_release_settings').slideDown()}else{$('#tutor_ead_global_release_settings').slideUp()}});$('input[name="tutor_ead_global_release_type"]').on('change',function(){if($(this).val()==='drip'){$('#tutor_ead_drip_settings').slideDown()}else{$('#tutor_ead_drip_settings').slideUp()}})});
        </script>
        <?php
    }

    private static function add_gemini_api_key_field() {
        $gemini_api_key = get_option('tutor_ead_gemini_api_key', '');
        ?>
        <div class="settings-card">
            <h2 class="section-title">
                <span class="dashicons dashicons-star-filled"></span>
                <?php _e('Configurações de IA (Gemini)', 'tutor-ead'); ?>
            </h2>
            <div class="form-group">
                <label for="tutor_ead_gemini_api_key"><?php _e('Chave API do Gemini', 'tutor-ead'); ?></label>
                <input type="text"
                       id="tutor_ead_gemini_api_key"
                       name="tutor_ead_gemini_api_key"
                       class="form-control"
                       value="<?php echo esc_attr($gemini_api_key); ?>"
                       placeholder="<?php _e('Cole sua chave API aqui', 'tutor-ead'); ?>">
                <div class="form-help">
                    <?php 
                    echo sprintf(
                        __('Para usar os recursos de IA no construtor de cursos, você precisa de uma chave API do Gemini. %s', 'tutor-ead'),
                        '<a href="https://aistudio.google.com/app/apikey" target="_blank">' . __('Obtenha sua chave aqui', 'tutor-ead') . '</a>.'
                    );
                    ?>
                </div>
            </div>
        </div>
        <?php
    }
}