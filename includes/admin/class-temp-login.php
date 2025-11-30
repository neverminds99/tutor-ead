<?php
/**
 * Temporary Login – Tutor EAD
 * Gera, lista e processa logins temporários de alunos.
 *
 * Estrutura refatorada:
 *  • Funções de lógica ficam aqui.
 *  • A view em admin/views/temp-login-page.php cuida do HTML.
 *
 * @package TutorEAD\Admin
 */

namespace TutorEAD\Admin;
defined( 'ABSPATH' ) || exit;

class TemporaryLogin {

    /* ------------------------------------------------------------------ */
    public static function init() {
        add_action( 'template_redirect', [ __CLASS__, 'process_temp_login_token' ] );

        // NOVO ‑ trata POST vindo do admin‑post.php
        add_action( 'admin_post_tutor_ead_generate_temp_login',
            [ __CLASS__, 'handle_generate' ] );
    }

    /* ---------- NOVO: handler que gera o link ------------------------- */
    public static function handle_generate() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Sem permissão.', 'tutor-ead' ) );
        }

        if ( get_option( 'tutor_ead_enable_temp_login_links', '0' ) !== '1' ) {
            wp_die( __( 'Funcionalidade desativada.', 'tutor-ead' ) );
        }

        check_admin_referer( 'temp_login_nonce_action', 'temp_login_nonce_field' );

        global $wpdb;
        $table      = $wpdb->prefix . 'temp_login_tokens';
        $student_id = intval( $_POST['student_id'] );
        $duration   = intval( $_POST['duration'] );
        $token      = wp_generate_password( 20, false );
        $expiration = date( 'Y-m-d H:i:s', current_time( 'timestamp' ) + ( $duration * 60 ) );

        $wpdb->insert( $table, [
            'user_id'    => $student_id,
            'token'      => $token,
            'expiration' => $expiration,
            'status'     => 'active',
        ] );

        $link = add_query_arg(
            [ 'temp_login_token' => $token, 'user_id' => $student_id ],
            site_url()
        );

        /* redireciona de volta para a página, agora sem erro de cabeçalho */
        wp_safe_redirect( add_query_arg( [
            'page'        => 'tutor-ead-temp-login',
            'link_notice' => rawurlencode( $link ),
            'student_id'  => $student_id,
        ], admin_url( 'admin.php' ) ) );
        exit;
    }

    /* ---------- render_page() continua igual, mas sem POST ------------- */
    public static function render_page() {
        /* … REMOVA TODO o bloco que processava POST aqui … */

        global $wpdb;
        $table           = $wpdb->prefix . 'temp_login_tokens';
        $notice_link     = isset( $_GET['link_notice'] ) ? rawurldecode( $_GET['link_notice'] ) : '';
        $selected_student= isset( $_GET['student_id'] ) ? intval( $_GET['student_id'] ) : 0;
        $students        = get_users( [ 'role' => 'tutor_aluno' ] );
        $tokens          = $wpdb->get_results( "SELECT * FROM $table ORDER BY created_at DESC" );

        require plugin_dir_path( __FILE__ ) . 'views/temp-login-page.php';
    }

	/* ------------------------------------------------------------------ *
	 * Processa o token no front‑end
	 * ------------------------------------------------------------------ */
	public static function process_temp_login_token() {

		// Ignora crawlers (WhatsApp / Facebook)
		$ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
		if ( stripos( $ua, 'WhatsApp' ) !== false || stripos( $ua, 'facebookexternalhit' ) !== false ) {
			return;
		}

		if ( empty( $_GET['temp_login_token'] ) || empty( $_GET['user_id'] ) ) {
			return;
		}

		$provided_token = sanitize_text_field( $_GET['temp_login_token'] );
		$user_id        = intval( $_GET['user_id'] );

		global $wpdb;
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}temp_login_tokens WHERE user_id = %d AND token = %s",
				$user_id,
				$provided_token
			)
		);

		if ( ! $row ) {
			wp_die( __( 'Token inválido.', 'tutor-ead' ) );
		}

		if ( current_time( 'timestamp' ) > strtotime( $row->expiration ) ) {
			wp_die( __( 'Token expirado.', 'tutor-ead' ) );
		}

		$user = get_userdata( $user_id );
		if ( ! $user || ! in_array( 'tutor_aluno', (array) $user->roles, true ) ) {
			wp_die( __( 'Usuário não possui a role adequada.', 'tutor-ead' ) );
		}

		// Faz login
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id );

		// Inativa token
		$wpdb->update(
			"{$wpdb->prefix}temp_login_tokens",
			[
				'status'   => 'inactive',
				'login_at' => current_time( 'mysql' ),
			],
			[ 'id' => $row->id ]
		);

		wp_redirect( site_url( '/dashboard-aluno' ) );
		exit;
	}
}

/* Init */
TemporaryLogin::init();
