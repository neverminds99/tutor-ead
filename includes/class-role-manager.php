<?php



namespace TutorEAD;







defined('ABSPATH') || exit;







// Análise do arquivo: /includes/class-role-manager.php







// Esta classe "RoleManager" gerencia os papéis (roles) e permissões dos usuários no Tutor EAD.



// Define três papéis principais no sistema:



//   - **tutor_admin** (Administrador EAD): Pode gerenciar cursos, visualizar alunos, avaliar alunos e acessar o painel administrativo.



//   - **tutor_professor** (Professor): Pode gerenciar cursos, visualizar alunos e avaliar alunos, mas sem permissões administrativas completas.



//   - **tutor_aluno** (Aluno): Tem acesso restrito apenas à visualização dos cursos e ao painel do aluno.



// Se os papéis já existirem, garante que possuem todas as capacidades necessárias, adicionando permissões se necessário.



// Há um bloco opcional para conceder algumas permissões específicas também ao papel de "administrator" nativo do WordPress.



// A função `remove_roles_and_capabilities()` remove os papéis ao desativar o plugin, evitando deixar roles órfãos no sistema.



// Também permite remover capacidades adicionadas ao "administrator" caso tenham sido ativadas anteriormente.











class RoleManager {

    /**
     * Versão das roles para controle de atualização.
     * Incrementar este número ao modificar roles/capacidades.
     */
    const ROLES_VERSION = '1.2'; // 1.1 para editor de curso, 1.2 para sistema de versão
    const ROLES_VERSION_OPTION_KEY = 'tutoread_roles_version';

    /**
     * Verifica a versão das roles e atualiza se necessário.
     * Este método é robusto contra falhas do gancho de ativação.
     */
    public static function check_version() {
        $installed_version = get_option(self::ROLES_VERSION_OPTION_KEY, '1.0');

        if (version_compare($installed_version, self::ROLES_VERSION, '<')) {
            self::add_roles_and_capabilities();
            update_option(self::ROLES_VERSION_OPTION_KEY, self::ROLES_VERSION);
        }
    }







    /**



     * Cria ou atualiza os papéis (roles) e suas capacidades.



     */



// dentro da sua classe RoleManager

public static function get_tutor_capabilities() {
    return [
        'manage_courses'        => __('Gerenciar Cursos', 'tutor-ead'),
        'view_students'         => __('Visualizar Alunos', 'tutor-ead'),
        'grade_students'        => __('Avaliar Alunos', 'tutor-ead'),
        'access_dashboard'      => __('Acessar Painel EAD', 'tutor-ead'),
        'view_boletim'          => __('Visualizar Boletim', 'tutor-ead'),
        'manage_atividades'     => __('Gerenciar Atividades', 'tutor-ead'),
        'manage_tutor_settings' => __('Gerenciar Configurações EAD', 'tutor-ead'),
    ];
}

public static function add_roles_and_capabilities() {
    // --- Nossas Novas Capacidades Granulares ---
    $tutor_caps = [
        // Capacidades existentes que você já tinha
        'manage_courses'   => true, // Gerenciar cursos
        'view_students'    => true, // Ver alunos
        'grade_students'   => true, // Avaliar alunos
        'access_dashboard' => true, // Acessar o painel
        
        // Novas capacidades baseadas nos seus arquivos de view
        'view_boletim'         => true, // Acessar a página boletim-list.php
        'manage_atividades'    => true, // Acessar a página atividades-list.php
        'manage_tutor_settings'=> true, // Acessar a página central-settings.php
    ];
    
    // --- TUTOR ADMIN ---
    $tutor_admin_caps = $tutor_caps; // O admin pode tudo que definimos
    
    if ( ! get_role('tutor_admin') ) {
        add_role(
            'tutor_admin',
            __( 'Administrador EAD', 'tutor-ead' ),
            $tutor_admin_caps
        );
    } else {
        $role = get_role('tutor_admin');
        if ( $role ) {
            foreach ( $tutor_admin_caps as $cap => $grant ) {
                $role->add_cap( $cap, $grant );
            }
        }
    }

    // --- TUTOR PROFESSOR ---
        $tutor_professor_caps = [
            'read'              => true,
            'edit_posts'        => true, // <-- ADICIONE ESTA LINHA. É a chave para o painel.
            'manage_courses'    => true,
            'view_students'     => true,
            'grade_students'    => true,
            'view_boletim'      => true,
            'manage_atividades' => true,
        ];

    if ( ! get_role('tutor_professor') ) {
        add_role(
            'tutor_professor',
            __( 'Professor', 'tutor-ead' ),
            $tutor_professor_caps
        );
    } else {
        $role = get_role('tutor_professor');
        if ( $role ) {
            foreach ( $tutor_professor_caps as $cap => $grant ) {
                $role->add_cap( $cap, $grant );
            }
        }
    }

    // --- EDITOR DE CURSO ---
    $tutor_course_editor_caps = [
        'read'             => true, // Acesso básico ao admin
        'manage_courses'   => true, // Gerenciar cursos
        'access_dashboard' => true, // Acessar o painel EAD
    ];

    if ( ! get_role('tutor_course_editor') ) {
        add_role(
            'tutor_course_editor',
            __( 'Editor de Curso', 'tutor-ead' ),
            $tutor_course_editor_caps
        );
    } else {
        $role = get_role('tutor_course_editor');
        if ( $role ) {
            foreach ( $tutor_course_editor_caps as $cap => $grant ) {
                $role->add_cap( $cap, $grant );
            }
        }
    }

    // --- TUTOR ALUNO ---
    $tutor_aluno_caps = [
        'read'             => true,
        'access_dashboard' => true,
    ];

    if ( ! get_role('tutor_aluno') ) {
        add_role(
            'tutor_aluno',
            __( 'Aluno', 'tutor-ead' ),
            $tutor_aluno_caps
        );
    } // A lógica de update para aluno pode ser mantida como estava, se não houver novas caps.

    // (OPCIONAL) Adicionar capacidades ao 'administrator' nativo
    $admin_role = get_role( 'administrator' );
    if ( $admin_role ) {
        foreach ( $tutor_caps as $cap => $grant ) {
            $admin_role->add_cap( $cap, $grant );
        }
    }
}

// O seu método remove_roles_and_capabilities() também deve ser atualizado para remover as novas capacidades.
// Certifique-se de que a variável $capabilities dentro dele contenha as novas caps que criamos.







    /**



     * Remove os papéis (roles) e capacidades criadas.



     * (Use com cautela. Normalmente é chamado ao desativar plugin.)



     */



    public static function remove_roles_and_capabilities() {

        // Remove apenas os roles customizados.
        // Não removemos mais as capacidades do role 'administrator' para evitar
        // que o menu suma em caso de falha no gancho de reativação.
        // O sistema de versionamento cuidará de manter as permissões atualizadas.
        if ( get_role('tutor_admin') ) {
            remove_role('tutor_admin');
        }
        if ( get_role('tutor_professor') ) {
            remove_role('tutor_professor');
        }
        if ( get_role('tutor_course_editor') ) {
            remove_role('tutor_course_editor');
        }
        if ( get_role('tutor_aluno') ) {
            remove_role('tutor_aluno');
        }
    }



}



