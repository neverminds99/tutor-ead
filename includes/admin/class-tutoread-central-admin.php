<?php
/**
 * Arquivo: wp-content/plugins/tutoread/includes/admin/class-tutoread-central-admin.php
 * Descrição: Contém a classe TutorEAD_Central_Admin, responsável por:
 *   - gerar automaticamente o JWT Secret na primeira exibição da página (se não existir);
 *   - exibir o campo “Identificador” e “JWT Secret” (readonly) na página de Configurações da Central;
 *   - enviar o pedido de registro à Central usando URL fixa;
 *   - salvar apenas o identificador do site.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Define a URL fixa da Central (sempre a mesma)
if ( ! defined( 'TUTOREAD_CENTRAL_URL' ) ) {
    define( 'TUTOREAD_CENTRAL_URL', 'https://tutoread.com.br' );
}

class TutorEAD_Central_Admin {

    public function __construct() {
        // Hook para processar submissões de formulário (salvar identificador / enviar pedido)
        add_action( 'admin_init', [ $this, 'handle_form_submission' ] );
    }

    /**
     * Processa o salvamento do identificador e o envio de pedido para a Central.
     */
    public function handle_form_submission() {
        if ( ! is_admin() ) {
            return;
        }

        // 1) Salvar Identificador
        if ( isset( $_POST['tutoread_central_nonce'] ) && isset( $_POST['save_settings'] ) ) {
            if ( ! wp_verify_nonce( $_POST['tutoread_central_nonce'], 'tutoread_central_save' ) ) {
                return;
            }
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }
            $this->save_identifier();
        }

        // 2) Enviar Pedido de Registro
        if ( isset( $_POST['tutoread_central_nonce'] ) && isset( $_POST['submit_request'] ) ) {
            if ( ! wp_verify_nonce( $_POST['tutoread_central_nonce'], 'tutoread_central_request' ) ) {
                return;
            }
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }
            $this->send_registration_request();
        }
    }

    /**
     * Salva o identificador do site no banco de dados.
     */
            private function save_identifier() {

                $identifier = isset( $_POST['identifier'] ) ? sanitize_text_field( $_POST['identifier'] ) : '';

                update_option( 'tutoread_my_identifier', $identifier );

        

                add_settings_error(

                    'tutoread_central_messages',

                    'tutoread_central_saved',

                    __( 'Identificador salvo com sucesso.', 'tutoread' ),

                    'updated'

                );

            }

    /**
     * Envia o pedido de registro à Central usando a URL fixa e o JWT Secret já existente.
     */
    private function send_registration_request() {
        $identifier = get_option( 'tutoread_my_identifier', '' );
        $jwt_secret = get_option( 'tutoread_jwt_secret', '' );
        $site_url   = get_site_url();

        // Monta o payload JSON
        $payload = [
            'identifier' => $identifier,
            'site_url'   => $site_url,
            'jwt_secret' => $jwt_secret,
        ];

        // Usa a constante fixa para a Central
        $endpoint = trailingslashit( TUTOREAD_CENTRAL_URL ) . 'wp-json/tutoread-central/v1/register-request';

        $response = wp_remote_post(
            $endpoint,
            [
                'headers' => [
                    'Content-Type' => 'application/json; charset=utf-8',
                ],
                'body'    => wp_json_encode( $payload ),
                'timeout' => 15,
            ]
        );

        if ( is_wp_error( $response ) ) {
            $redirect = add_query_arg( 'tutoread_reg_status', 'error' );
            wp_safe_redirect( $redirect );
            exit;
        }

        $code = intval( wp_remote_retrieve_response_code( $response ) );
        if ( $code === 200 ) {
            $redirect = add_query_arg( 'tutoread_reg_status', 'success' );
        } elseif ( $code === 409 ) {
            $redirect = add_query_arg( 'tutoread_reg_status', 'exists' );
        } else {
            $redirect = add_query_arg( 'tutoread_reg_status', 'error' );
        }

        wp_safe_redirect( $redirect );
        exit;
    }

    /**
     * Renderiza o formulário de Configurações da Central.
     * Gera um JWT Secret automático se ainda não existir, exibe somente identificador e secret readonly.
     */
    public function render_settings_page() {
        // 1) Se JWT Secret não existir (opção vazia), gera automaticamente 32 bytes aleatórios em hex
        $jwt_secret = get_option( 'tutoread_jwt_secret', '' );
        if ( empty( $jwt_secret ) ) {
            try {
                $jwt_secret = bin2hex( random_bytes( 32 ) ); // 64 caracteres hex
            } catch ( Exception $e ) {
                $jwt_secret = wp_generate_password( 64, false, false ); // fallback em caso de falha
            }
            update_option( 'tutoread_jwt_secret', $jwt_secret );
        }

                        // 2) Obtém identificador existente (se houver)


                        $identifier = get_option( 'tutoread_my_identifier', '' );


                


                        // 3) Mensagens de erro/sucesso


                        settings_errors( 'tutoread_central_messages' );

        // 4) Status do registro na URL (?tutoread_reg_status=)
        $reg_status = isset( $_GET['tutoread_reg_status'] ) ? sanitize_text_field( $_GET['tutoread_reg_status'] ) : '';

        // 5) Inclui o arquivo de view, que exibirá $identifier e $jwt_secret
        require_once __DIR__ . '/views/central-settings.php';
    }
}

// Instancia a classe para registrar o hook admin_init. A inclusão do submenu se dá em class-admin-menus.php.
new TutorEAD_Central_Admin();
