<?php

/**

 * Class AdminMenus

 * Gerencia todos os menus administrativos do Tutor EAD.

 *

 * @package TutorEAD\Admin

 */



namespace TutorEAD\Admin;



defined( 'ABSPATH' ) || exit;



// garante que a classe exista antes de registrar o submenu

require_once plugin_dir_path( __FILE__ ) . 'class-import-export-courses.php';

require_once plugin_dir_path( __FILE__ ) . 'class-temp-login.php';





use TutorEAD\Admin\{

	DashboardManager,

	CourseManager,

	CourseBuilderManager,

	StudentManager,

	TeacherManager,

	UserManager,

	ReportManager,

	ActivityManager,

	LicenseManager,

	Settings,

    TemporaryLogin,

	ImportExportCourses          // <-- NOVO: submenu Importar/Exportar

};



class AdminMenus {







	public static $course_builder_hook;







	/**



	 * Construtor.



	 */



	public function __construct() {



		add_action( 'admin_menu', [ $this, 'register_menus' ] );



        add_action('admin_post_tutor_ead_unified_form', [ActivityManager::class, 'handle_unified_add']);



	}







	/**



	 * Registra menus e sub‑menus.



	 */



	public static function register_menus() {







    /* -----------------------------------------



     * MENU PRINCIPAL “Tutor EAD”



     * ---------------------------------------*/



    add_menu_page(



        __( 'Tutor EAD', 'tutor-ead' ),



        __( 'Tutor EAD', 'tutor-ead' ),



        'manage_courses', // <-- Capacidade base para ver o menu



        'tutor-ead-dashboard',



        [ __CLASS__, 'dashboard_page' ],



        'dashicons-welcome-learn-more',



        6



    );







    /* ---------- CURSOS ---------- */



    add_submenu_page(



        'tutor-ead-dashboard', 'Gerenciar Cursos', 'Cursos',



        'manage_courses', 'tutor-ead-courses', [ CourseManager::class, 'courses_page' ]



    );



    add_submenu_page(



        null, 'Ver Curso', 'Ver Curso',



        'manage_courses', 'tutor-ead-view-course', [ CourseManager::class, 'view_course_page' ]



    );



    self::$course_builder_hook = add_submenu_page(



        null, 'Course Builder', 'Course Builder',



        'manage_courses', 'tutor-ead-course-builder', [ CourseBuilderManager::class, 'course_builder_page' ]



    );
    add_submenu_page(
        null, 'Importar/Exportar Cursos', 'Importar/Exportar',
        'manage_tutor_settings', 'tutor-ead-import-export', [ ImportExportCourses::class, 'render_page' ]
    );

    /* ---------- ALUNOS & MATRÍCULAS ---------- */
    add_submenu_page(
        'tutor-ead-dashboard', 'Gerenciar Alunos', 'Alunos',
        'view_students', 'tutor-ead-students', [ StudentManager::class, 'students_page' ]
    );
    add_submenu_page(
        'tutor-ead-dashboard', 'Lista de Matrículas', 'Matrículas',
        'view_students', 'tutor-ead-enrollment-list', [ 'TutorEAD\Admin\EnrollmentManager', 'enrollment_list_page' ]
    );
    add_submenu_page(
        null, 'Editar Usuário', 'Editar Usuário',
        'view_students', 'tutor-ead-edit-user', [ StudentManager::class, 'edit_user_page' ]
    );

    /* ---------- PROFESSORES ---------- */
    add_submenu_page(
        'tutor-ead-dashboard', 'Gerenciar Professores', 'Professores',
        'manage_tutor_settings', 'tutor-ead-teachers', [ TeacherManager::class, 'teachers_page' ]
    );
    add_submenu_page(
        null, 'Editar Professor', 'Editar Professor',
        'manage_tutor_settings', 'tutor-ead-edit-teacher', [ TeacherManager::class, 'edit_teacher_page' ]
    );

    /* ---------- BOLETIM ---------- */
    if ( get_option( 'tutor_ead_enable_boletim', '0' ) === '1' ) {
        add_submenu_page( 'tutor-ead-dashboard', 'Boletim', 'Boletim', 'view_boletim', 'tutor-ead-boletim', [ ReportManager::class, 'boletim_page' ] );
        add_submenu_page( null, 'Criar Boletim', 'Criar Boletim', 'view_boletim', 'tutor-ead-create-boletim', [ ReportManager::class, 'create_boletim_page' ] );
        add_submenu_page( null, 'Editar Boletim', 'Editar Boletim', 'view_boletim', 'tutor-ead-edit-boletim', [ ReportManager::class, 'edit_boletim_page' ] );
    }

    /* ---------- ATIVIDADES ---------- */
    if ( get_option( 'tutor_ead_enable_atividades', '0' ) === '1' ) {
        add_submenu_page(
            'tutor-ead-dashboard',
            __('Gerenciar Atividades', 'tutor-ead'),
            __('Atividades', 'tutor-ead'),
            'manage_atividades',
            'tutor-ead-atividades',
            [ ActivityManager::class, 'atividades_page' ]
        );
        add_submenu_page(
            'tutor-ead-atividades',
            __('Adicionar Nova', 'tutor-ead'),
            __('Adicionar Nova', 'tutor-ead'),
            'manage_atividades',
            'tutor-ead-add-new-activity',
            [ ActivityManager::class, 'add_new_activity_page' ]
        );
        add_submenu_page(
            null,
            __('Formulário de Atividade', 'tutor-ead'),
            __('Formulário de Atividade', 'tutor-ead'),
            'manage_atividades',
            'tutor-ead-unified-form',
            [ ActivityManager::class, 'unified_form_page' ]
        );
        add_submenu_page( null, 'Editar Atividade Padrão', 'Editar Atividade Padrão', 'manage_atividades', 'tutor-ead-edit-atividade-padrao', [ ActivityManager::class, 'edit_atividade_padrao_page' ] );
        add_submenu_page( null, 'Editar Atividade Externa', 'Editar Atividade Externa', 'manage_atividades', 'tutor-ead-edit-atividade-externa', [ ActivityManager::class, 'edit_atividade_externa_page' ] );
        add_submenu_page( null, 'Excluir Atividade', 'Excluir Atividade', 'manage_atividades', 'tutor-ead-delete-atividade', [ ActivityManager::class, 'delete_atividade' ] );
        add_submenu_page( null, 'Excluir Associação', 'Excluir Associação', 'manage_atividades', 'tutor-ead-delete-associacao', [ ActivityManager::class, 'delete_associacao' ] );
    }
    add_submenu_page( null, 'Editar Atividade', 'Editar Atividade', 'manage_atividades', 'tutor-ead-edit-atividade', [ ActivityManager::class, 'edit_atividade_page' ] );
    add_submenu_page( null, 'Associar Atividade', 'Associar Atividade', 'manage_atividades', 'tutor-ead-associar-atividade', [ 'TutorEAD\admin\ActivityAssociationManager', 'associar_atividade_curso_page' ] );
    add_submenu_page( null, 'Selecionar Curso para Associar', 'Selecionar Curso', 'manage_atividades', 'tutor-ead-associate-activity-select-course', [ ActivityManager::class, 'associate_activity_select_course_page' ] );
    
    // Capacidade para seções de admin
    $admin_capability = 'manage_tutor_settings';

    /* ---------- LICENÇA PRO ---------- */
    add_submenu_page( 'tutor-ead-dashboard', 'Ativação do Plugin Pro', LicenseManager::get_activation_menu_title(), $admin_capability, 'tutor-ead-activation', [ LicenseManager::class, 'activation_page' ] );

    /* ---------- CONFIGURAÇÕES ---------- */
    add_submenu_page( 'tutor-ead-dashboard', 'Configurações', 'Configurações', $admin_capability, 'tutor-ead-settings', [ Settings::class, 'settings_page' ] );

    /* ---------- CENTRAL DE IDENTIFICAÇÃO PARA APP ---------- */
    if ( get_option( 'tutoread_enable_central', false ) ) {
        
        // ===============================================
        //  INÍCIO DA CORREÇÃO - Bloco original restaurado
        // ===============================================
        // Antes de registrar o callback, garante que a classe seja carregada:
        $central_class_file = __DIR__ . '/class-tutoread-central-admin.php';
        if ( file_exists( $central_class_file ) ) {
            require_once $central_class_file;
        }
        // Instancia para usar método de instância
        $central_admin = new \TutorEAD_Central_Admin();
        // ===============================================
        //  FIM DA CORREÇÃO
        // ===============================================

        add_submenu_page(
            'tutor-ead-dashboard',
            __( 'Central de Identificadores', 'tutor-ead' ),
            __( 'Central de Identificadores', 'tutor-ead' ),
            $admin_capability, // Capacidade corrigida
            'tutor-ead-central-settings',
            [ $central_admin, 'render_settings_page' ]
        );
    }

    /* ---------- ALERTAS & AVISOS ---------- */
    add_submenu_page( 'tutor-ead-dashboard', 'Gerenciar Alertas e Avisos', 'Alertas & Avisos', $admin_capability, 'tutor-ead-alertas', [ __CLASS__, 'render_alertas_page' ] );

    /* ---------- PUBLICIDADE ---------- */
    add_submenu_page(
        'tutor-ead-dashboard',
        'Publicidade',
        'Publicidade',
        'manage_tutor_settings',
        'tutor-ead-advertisements',
        [ __CLASS__, 'advertisements_page' ]
    );

    /* ---------- LOGINS TEMPORÁRIOS ---------- */
    if ( get_option( 'tutor_ead_enable_temp_login_links', '0' ) === '1' ) {
        add_submenu_page( null, 'Logins Temporários', 'Logins Temporários', $admin_capability, 'tutor-ead-temp-login', [ \TutorEAD\Admin\TemporaryLogin::class, 'render_page' ] );
    }
}





	public static function render_alertas_page() {
        require_once \TUTOR_EAD_PATH . 'includes/admin/class-admin-alertas.php';
    }

    public static function advertisements_page() {
        require_once \TUTOR_EAD_PATH . 'includes/admin/views/publicidade-admin-page.php';
    }

	/**

	 * Dashboard principal.

	 */

	public static function dashboard_page() {

		$current_user = wp_get_current_user();



		if ( empty( $current_user->roles ) ) {

			echo '<h1>' . __( 'Acesso não autorizado: sem roles atribuídas', 'tutor-ead' ) . '</h1>';

			return;

		}



				$roles = $current_user->roles;



		



				echo '<div class="wrap">';



				if ( in_array( 'tutor_admin', $roles, true ) || in_array( 'administrator', $roles, true ) || in_array( 'tutor_course_editor', $roles, true ) ) {



		



					DashboardManager::dashboard_admin();



		



				} elseif ( in_array( 'tutor_professor', $roles, true ) ) {

			DashboardManager::dashboard_professor();

		} elseif ( in_array( 'tutor_aluno', $roles, true ) ) {

			DashboardManager::dashboard_aluno();

		} else {

			echo '<h1>' . __( 'Acesso não autorizado', 'tutor-ead' ) . '</h1>';

		}

		echo '</div>';

	}

}

