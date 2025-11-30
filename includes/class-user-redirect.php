<?php
namespace TutorEAD;

defined('ABSPATH') || exit;

// Análise do arquivo: /includes/class-user-redirect.php

// A classe "UserRedirect" gerencia os redirecionamentos dos usuários após o login no Tutor EAD.
// O método `redirect_user_to_dashboard()` é acionado ao usuário fazer login e redireciona para a página adequada conforme sua role:
//   - **administrator** → Sempre redirecionado para o painel administrativo do WordPress (wp-admin).
//   - **tutor_aluno** → Redirecionado para o painel do aluno (/dashboard-aluno).
//   - **tutor_professor** → Redirecionado para o painel do professor (/dashboard-professor).
//   - **tutor_admin** → Redirecionado para o painel administrativo customizado do Tutor EAD (/dashboard-administrador).
// Verifica se as roles do usuário são válidas antes de processar o redirecionamento.
// Usa `sanitize_text_field()` para garantir a segurança ao manipular as roles do usuário.
// Finaliza a execução com `exit` após cada `wp_redirect()` para evitar qualquer saída indesejada antes do redirecionamento.


class UserRedirect {
    public static function redirect_user_to_dashboard($user_login, $user) {
        // Verifica se o objeto usuário possui roles válidas
        if ( ! isset($user->roles) || ! is_array($user->roles) ) {
            return;
        }

        // Sanitiza as roles
        $roles = array_map('sanitize_text_field', $user->roles);

        // Se o usuário tem a role "administrator", redireciona para o wp-admin, SEMPRE!
        if ( in_array('administrator', $roles, true) ) {
            wp_redirect(admin_url());
            exit;
        }

        // Caso contrário, redireciona com base nas outras roles
        if ( in_array('tutor_aluno', $roles, true) ) {
            wp_redirect(home_url('/dashboard-aluno'));
            exit;
        } elseif ( in_array('tutor_professor', $roles, true) ) {
            wp_redirect(home_url('/dashboard-professor'));
            exit;
        } elseif ( in_array('tutor_admin', $roles, true) ) {
            wp_redirect(home_url('/dashboard-administrador'));
            exit;
        }
    }
}
