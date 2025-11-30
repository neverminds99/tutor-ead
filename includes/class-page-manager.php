<?php

namespace TutorEAD;

defined('ABSPATH') || exit;

/**
 * Classe PageManager
 *
 * Gerencia a criação automática de páginas essenciais para o Tutor EAD.
 * As páginas são criadas ou atualizadas automaticamente durante a ativação do plugin,
 * garantindo a estrutura mínima necessária.
 *
 * Páginas gerenciadas:
 * - Dashboard Administrador
 * - Dashboard Professor
 * - Dashboard Aluno
 * - Registro
 * - Visualizar Curso
 * - Login Tutor EAD
 * - Meu Boletim (novo)
 */
class PageManager {

    /**
     * Cria as páginas essenciais do Tutor EAD se ainda não existirem.
     */
    public static function create_pages() {

        $pages = [
            [
                'title'    => 'Dashboard Administrador',
                'slug'     => 'dashboard-administrador',
                'content'  => '[tutor_ead_dashboard type="admin"]',
                'template' => 'templates/dashboard-administrador.php'
            ],
            [
                'title'    => 'Dashboard Professor',
                'slug'     => 'dashboard-professor',
                'content'  => '[tutor_ead_dashboard type="professor"]',
                'template' => 'templates/dashboard-professor.php'
            ],
            [
                'title'    => 'Dashboard Editor de Curso',
                'slug'     => 'dashboard-course-editor',
                'content'  => '', // O conteúdo é gerenciado diretamente pelo template.
                'template' => 'templates/dashboard-course-editor.php'
            ],
            [
                'title'    => 'Dashboard Aluno',
                'slug'     => 'dashboard-aluno',
                'content'  => '[tutor_ead_dashboard type="aluno"]',
                'template' => 'templates/dashboard-aluno.php'
            ],
            [
                'title'    => 'Registro',
                'slug'     => 'registro',
                'content'  => '[tutor_ead_register]',
                'template' => 'templates/registro.php'
            ],
            [
                'title'    => 'Visualizar Curso',
                'slug'     => 'visualizar-curso',
                'content'  => '',
                'template' => 'templates/template-curso.php'
            ],
            [
                'title'    => 'Login Tutor EAD',
                'slug'     => 'login-tutor-ead',
                'content'  => '',
                'template' => 'templates/login-tutor-ead.php'
            ],
            [
                'title'    => 'Meu Boletim',
                'slug'     => 'meu-boletim',
                'content'  => '', // Conteúdo padrão ou vazio
                'template' => 'templates/meu-boletim.php'
            ],
        ];

        foreach ($pages as $page) {
            $existing_page = get_page_by_path($page['slug']);

            if (!$existing_page) {
                $page_id = wp_insert_post([
                    'post_title'   => $page['title'],
                    'post_name'    => $page['slug'],
                    'post_content' => $page['content'],
                    'post_status'  => 'publish',
                    'post_type'    => 'page',
                ]);

                if (!is_wp_error($page_id) && $page_id) {
                    update_post_meta($page_id, '_wp_page_template', $page['template']);
                }
            } else {
                update_post_meta($existing_page->ID, '_wp_page_template', $page['template']);
            }
        }
    }
}
