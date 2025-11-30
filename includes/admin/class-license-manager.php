<?php
namespace TutorEAD\Admin;

defined('ABSPATH') || exit;

// Análise do arquivo: class-license-manager.php

// **Objetivo:** 
// Gerenciar a ativação e desativação da licença do Tutor EAD Pro.

// **Principais funcionalidades:**
// - Exibir status da licença no menu do WordPress, com cores indicativas.
// - Validar a chave da licença via API externa (`api.oguiazevedo.com`).
// - Ativar ou desativar a licença no banco de dados do WordPress.
// - Remover opções do plugin ao desativar a licença, desativando funcionalidades Pro.
// - Exibir um formulário de ativação/desativação na interface administrativa.

// **Resumo:**
// A `LicenseManager` facilita o controle de acesso a recursos premium, garantindo que apenas usuários licenciados utilizem funcionalidades avançadas do Tutor EAD.


class LicenseManager {
    /**
     * Obtém o título do submenu de ativação com base no status da licença.
     *
     * @return string
     */
    public static function get_activation_menu_title() {
        $status = get_option('tutoread_license_status', 'inactive');
        $color = ($status === 'active') ? 'green' : 'red';

        return sprintf(
            '<span style="color: %s;">%s</span>',
            esc_attr($color),
            __('Ativação', 'tutor-ead')
        );
    }

    /**
     * Valida a licença.
     *
     * @param string $license_key
     * @return bool
     */
    public static function validate_license($license_key) {
        $api_url = 'https://api.oguiazevedo.com/index.php?action=validate';

        $response = wp_remote_post($api_url, [
            'body' => [
                'license_key' => $license_key,
                'site_url'    => home_url()
            ]
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body   = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        return isset($result['success']) && $result['success'] === true;
    }

    /**
     * Desativa a licença.
     *
     * @param string $license_key
     * @return bool
     */
    public static function deactivate_license($license_key) {
        $api_url = 'https://api.oguiazevedo.com/index.php?action=deactivate';

        $response = wp_remote_post($api_url, [
            'body' => [
                'license_key' => $license_key,
                'site_url'    => home_url(),
            ],
        ]);

        if (is_wp_error($response)) {
            error_log('Erro na solicitação de desativação da licença: ' . $response->get_error_message());
            return false;
        }

        $body   = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (empty($result) || !is_array($result) || !isset($result['success'])) {
            error_log('Resposta inesperada da API de desativação: ' . print_r($body, true));
            return false;
        }

        if ($result['success'] === true) {
            // Limpar opções relacionadas à licença
            delete_option('tutoread_license_key');
            delete_option('tutoread_license_status');

            // Resetar configurações Pro
            delete_option('tutor_ead_enable_boletim');
            delete_option('tutor_ead_enable_atividades');

            // Mensagem de sucesso (opcional)
            add_settings_error(
                'tutor_ead_license',
                'license_deactivated',
                __('Licença desativada com sucesso.', 'tutor-ead'),
                'updated'
            );
        }

        return $result['success'] === true;
    }

    /**
     * Renderiza a página de ativação da licença.
     */
    public static function activation_page() {
    // Processar envio do formulário para ativar ou desativar a licença
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['license_key'])) {
            // Ativar licença
            $license_key = sanitize_text_field($_POST['license_key']);
            $is_valid = self::validate_license($license_key);

            if ($is_valid) {
                update_option('tutoread_license_key', $license_key);
                update_option('tutoread_license_status', 'active');
                echo '<div class="notice notice-success"><p>' . __('Licença ativada com sucesso!', 'tutor-ead') . '</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>' . __('Licença inválida. Verifique sua chave e tente novamente.', 'tutor-ead') . '</p></div>';
            }
        } elseif (isset($_POST['deactivate_license'])) {
            // Desativar licença
            $license_key = get_option('tutoread_license_key', '');
            if (!empty($license_key)) {
                $is_deactivated = self::deactivate_license($license_key);

                if ($is_deactivated) {
                    echo '<div class="notice notice-success"><p>' . __('Licença desativada com sucesso.', 'tutor-ead') . '</p></div>';
                } else {
                    echo '<div class="notice notice-error"><p>' . __('Falha ao desativar a licença. Tente novamente.', 'tutor-ead') . '</p></div>';
                }
            } else {
                echo '<div class="notice notice-warning"><p>' . __('Nenhuma licença ativa encontrada para desativar.', 'tutor-ead') . '</p></div>';
            }
        }
    }

    // Renderizar o formulário de ativação e desativação
    ?>
    <div class="wrap">
        <h1><?php echo esc_html__('Ativação do Plugin Pro', 'tutor-ead'); ?></h1>
        <form method="post">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="license_key"><?php echo esc_html__('Chave de Licença', 'tutor-ead'); ?></label>
                    </th>
                    <td>
                        <input type="text" name="license_key" id="license_key" value="<?php echo esc_attr(get_option('tutoread_license_key', '')); ?>" class="regular-text" />
                    </td>
                </tr>
            </table>
            <?php submit_button(__('Ativar Licença', 'tutor-ead')); ?>
        </form>

        <?php if (get_option('tutoread_license_status', 'inactive') === 'active') : ?>
            <form method="post" style="margin-top: 20px;">
                <input type="hidden" name="deactivate_license" value="1" />
                <?php submit_button(__('Desativar Licença', 'tutor-ead'), 'secondary'); ?>
            </form>
        <?php endif; ?>
    </div>
    <?php
}
}
