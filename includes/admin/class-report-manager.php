<?php

namespace TutorEAD\Admin;

defined( 'ABSPATH' ) || exit;

class ReportManager {
    private static function view_path( string $file ): string {
        return __DIR__ . '/views/' . ltrim( $file, '/' );
    }
    private static function log_debug( string $msg ): void {
        if ( get_current_user_id() === 1 ) {
            error_log( '[TutorEAD Debug] ' . $msg );
        }
    }
    public function enqueue_scripts( string $hook ): void {
        $allowed = [
            'admin_page_tutor-ead-boletim',
            'admin_page_tutor-ead-create-boletim',
            'admin_page_tutor-ead-edit-boletim',
            'tutor-ead_page_tutor-ead-boletim',
            'tutor-ead_page_tutor-ead-create-boletim',
            'tutor-ead_page_tutor-ead-edit-boletim',
        ];
        if ( ! in_array( $hook, $allowed, true ) ) {
            return;
        }
        wp_enqueue_script(
            'tutor-ead-boletim',
            TUTOR_EAD_URL . 'assets/js/boletim.js',
            [ 'jquery' ],
            '1.3',
            true
        );
        wp_localize_script( 'tutor-ead-boletim', 'tutorEadAjax', [
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'tutoread_nonce' ),
        ] );
        wp_enqueue_style(
            'tutor-ead-boletim-css',
            TUTOR_EAD_URL . 'assets/css/boletim.css',
            [],
            '1.0'
        );
        wp_enqueue_style(
            'tutor-ead-main-css',
            TUTOR_EAD_URL . 'assets/css/tutor-ead.css',
            [],
            '1.0'
        );
    }
    public static function boletim_page(): void {
        if ( ! current_user_can( 'view_boletim' ) ) {
            wp_die( __( 'Você não tem permissão para acessar essa página.', 'tutor-ead' ) );
        }
        global $wpdb;
        $notas_lancadas = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}boletins");
        $notas_esperadas = $wpdb->get_var("SELECT COUNT(m.id) FROM {$wpdb->prefix}matriculas m JOIN {$wpdb->prefix}tutoread_course_activities ca ON m.course_id = ca.course_id");

        if ( isset( $_GET['created'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Boletim criado com sucesso!', 'tutor-ead' ) . '</p></div>';
        }
        if ( isset( $_GET['updated'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Boletim atualizado com sucesso!', 'tutor-ead' ) . '</p></div>';
        }
        if ( isset( $_GET['deleted'] ) ) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Nota excluída com sucesso!', 'tutor-ead' ) . '</p></div>';
        }
        if ( isset( $_GET['delete_error'] ) ) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Erro ao excluir a nota. Tente novamente.', 'tutor-ead' ) . '</p></div>';
        }
        if ( isset( $_GET['error'] ) ) {
            echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Ocorreu um erro. Por favor, tente novamente.', 'tutor-ead' ) . '</p></div>';
        }
        if ( isset( $_GET['missing'] ) ) {
            $campos = explode( ',', rawurldecode( $_GET['missing'] ) );
            printf(
                '<div class="notice notice-error is-dismissible"><p>%s</p></div>',
                esc_html( sprintf(
                    __( 'Preencha os seguintes campos obrigatórios: %s.', 'tutor-ead' ),
                    implode( ', ', $campos )
                ) )
            );
        }

        $filters = [
            'course_id'    => isset( $_GET['course_id'] )    ? absint( $_GET['course_id'] )    : 0,
            'atividade_id' => isset( $_GET['atividade_id'] ) ? absint( $_GET['atividade_id'] ) : 0,
            'aluno_id'     => isset( $_GET['aluno_id'] )     ? absint( $_GET['aluno_id'] )     : 0,
            'date_from'    => isset( $_GET['date_from'] )    ? sanitize_text_field( $_GET['date_from'] ) : '',
            'date_to'      => isset( $_GET['date_to'] )      ? sanitize_text_field( $_GET['date_to'] )   : '',
        ];
        $where   = 'WHERE 1=1';
        $params  = [];
        foreach ( [ 'course_id', 'atividade_id', 'aluno_id' ] as $f ) {
            if ( $filters[ $f ] ) {
                $where  .= " AND {$f} = %d";
                $params[] = $filters[ $f ];
            }
        }
        if ( $filters['date_from'] ) { $where .= ' AND data_atualizacao >= %s'; $params[] = $filters['date_from']; }
        if ( $filters['date_to']   ) { $where .= ' AND data_atualizacao <= %s'; $params[] = $filters['date_to']; }
        $per_page     = 6;
        $current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
        $offset       = ( $current_page - 1 ) * $per_page;
        $sql_total = "SELECT COUNT(*) FROM {$wpdb->prefix}boletins {$where}";
        if ( $params ) { $sql_total = $wpdb->prepare( $sql_total, $params ); }
        $total_rows = (int) $wpdb->get_var( $sql_total );
        $total_pages = (int) ceil( $total_rows / $per_page );
        $sql = $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}boletins {$where} ORDER BY data_atualizacao DESC LIMIT %d, %d",
            array_merge( $params, [ $offset, $per_page ] )
        );
        $rows = $wpdb->get_results( $sql );
        $courses    = $wpdb->get_results( "SELECT id, title FROM {$wpdb->prefix}tutoread_courses" );
        $atividades = $wpdb->get_results( "SELECT id, titulo FROM {$wpdb->prefix}atividades" );
        $data = compact( 'filters', 'rows', 'courses', 'atividades', 'current_page', 'total_pages', 'per_page', 'total_rows' );
        require self::view_path( 'boletim-list.php' );
    }
    public static function handle_create_boletim(): void {
        if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'create_boletim_action', 'create_boletim_nonce' ) ) {
            wp_die( __( 'Permissão negada.', 'tutor-ead' ) );
        }
        global $wpdb;
        $table = $wpdb->prefix . 'boletins';
        $course_id       = intval( $_POST['course_id'] ?? 0 );
        $atividade_id    = intval( $_POST['atividade_id'] ?? 0 );
        $aluno_id        = intval( $_POST['aluno_id'] ?? 0 );
        $nota            = ( $_POST['nota'] !== '' ) ? floatval( $_POST['nota'] ) : 0;
        $feedback        = sanitize_textarea_field( $_POST['feedback'] ?? '' );
        $course_title    = sanitize_text_field( $_POST['course_title'] ?? '' );
        $atividade_title = sanitize_text_field( $_POST['atividade_title'] ?? '' );
        $missing = [];
        if ( $course_id    <= 0 ) $missing[] = 'Curso';
        if ( $atividade_id <= 0 ) $missing[] = 'Atividade';
        if ( $aluno_id     <= 0 ) $missing[] = 'Aluno';
        if ( $missing ) {
            wp_safe_redirect( admin_url( "admin.php?page=tutor-ead-create-boletim&missing=" . rawurlencode( implode( ',', $missing ) ) ) );
            exit;
        }
        $existing_entry = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE aluno_id = %d AND atividade_id = %d", $aluno_id, $atividade_id ) );
        if ( $existing_entry ) {
            wp_safe_redirect( admin_url( 'admin.php?page=tutor-ead-create-boletim&exists=1' ) );
            exit;
        }
        $ok = $wpdb->insert( $table, compact( 'course_id','course_title','atividade_id','atividade_title','nota','feedback','aluno_id' ), [ '%d','%s','%d','%s','%f','%s','%d' ] );
        if ( $ok ) {
            wp_safe_redirect( admin_url( 'admin.php?page=tutor-ead-boletim&created=1' ) );
        } else {
            wp_safe_redirect( admin_url( 'admin.php?page=tutor-ead-create-boletim&error=1' ) );
        }
        exit;
    }
    public static function handle_edit_boletim(): void {
        if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'edit_boletim_action', 'edit_boletim_nonce' ) ) {
            wp_die( __( 'Permissão negada.', 'tutor-ead' ) );
        }
        global $wpdb;
        $table = $wpdb->prefix . 'boletins';
        $id    = intval( $_POST['boletim_id'] ?? 0 );
        if ( $id <= 0 ) {
            wp_die( __( 'ID de boletim inválido.', 'tutor-ead' ) );
        }
        $course_id       = intval( $_POST['course_id'] ?? 0 );
        $atividade_id    = intval( $_POST['atividade_id'] ?? 0 );
        $aluno_id        = intval( $_POST['aluno_id'] ?? 0 );
        $nota            = ( $_POST['nota'] !== '' ) ? floatval( $_POST['nota'] ) : 0;
        $feedback        = sanitize_textarea_field( $_POST['feedback'] ?? '' );
        $course_title    = sanitize_text_field( $_POST['course_title'] ?? '' );
        $atividade_title = sanitize_text_field( $_POST['atividade_title'] ?? '' );
        $missing = [];
        if ( $course_id    <= 0 ) $missing[] = 'Curso';
        if ( $atividade_id <= 0 ) $missing[] = 'Atividade';
        if ( $aluno_id     <= 0 ) $missing[] = 'Aluno';
        if ( $missing ) {
            wp_safe_redirect( admin_url( "admin.php?page=tutor-ead-edit-boletim&id={$id}&missing=" . rawurlencode( implode( ',', $missing ) ) ) );
            exit;
        }
        $ok = $wpdb->update( $table, compact( 'course_id','course_title','atividade_id','atividade_title','nota','feedback','aluno_id' ), [ 'id' => $id ], [ '%d','%s','%d','%s','%f','%s','%d' ], [ '%d' ] );
        if ( $ok !== false ) {
            wp_safe_redirect( admin_url( 'admin.php?page=tutor-ead-boletim&updated=1' ) );
        } else {
            wp_safe_redirect( admin_url( "admin.php?page=tutor-ead-edit-boletim&id={$id}&error=1" ) );
        }
        exit;
    }
    public static function handle_delete_boletim(): void {
        if ( ! current_user_can( 'manage_options' ) || ! isset( $_GET['nonce'] ) || ! wp_verify_nonce( $_GET['nonce'], 'delete_boletim_nonce' ) ) {
            wp_die( __( 'Permissão negada.', 'tutor-ead' ) );
        }
        global $wpdb;
        $id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        if ( $id > 0 ) {
            $deleted = $wpdb->delete( $wpdb->prefix . 'boletins', [ 'id' => $id ], [ '%d' ] );
            if ( $deleted ) {
                wp_safe_redirect( admin_url( 'admin.php?page=tutor-ead-boletim&deleted=1' ) );
            } else {
                wp_safe_redirect( admin_url( 'admin.php?page=tutor-ead-boletim&delete_error=1' ) );
            }
        } else {
            wp_safe_redirect( admin_url( 'admin.php?page=tutor-ead-boletim&delete_error=1' ) );
        }
        exit;
    }
    public static function create_boletim_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Você não tem permissão para acessar essa página.', 'tutor-ead' ) );
        }
        global $wpdb;
        $courses = $wpdb->get_results( "SELECT id, title FROM {$wpdb->prefix}tutoread_courses" );
        require self::view_path( 'create-boletim.php' );
    }
    public static function edit_boletim_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'Você não tem permissão para acessar essa página.', 'tutor-ead' ) );
        }
        global $wpdb;
        $id       = absint( $_GET['id'] ?? 0 );
        $boletim  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}boletins WHERE id = %d", $id ) );
        if ( ! $boletim ) {
            wp_die( __( 'Boletim não encontrado.', 'tutor-ead' ) );
        }
        $courses = $wpdb->get_results( "SELECT id, title FROM {$wpdb->prefix}tutoread_courses" );
        require self::view_path( 'edit-boletim.php' );
    }
}
