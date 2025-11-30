<?php

namespace TutorEAD;



defined('ABSPATH') || exit;

// Análise do arquivo: /includes/class-shortcodes.php

// Esta classe "Shortcodes" gerencia os shortcodes do Tutor EAD, permitindo a exibição dinâmica de conteúdos nas páginas do site.
// Inicializa quatro shortcodes principais:
//   - `[tutor_ead_dashboard_admin]`: Exibe o painel administrativo para usuários com permissão de gerenciamento de cursos.
//   - `[tutor_ead_dashboard_professor]`: Exibe o painel do professor, permitindo a gestão de turmas e alunos.
//   - `[tutor_ead_dashboard_aluno]`: Exibe o painel do aluno com os cursos matriculados e acesso às aulas.
//   - `[tutor_ead_register]`: Exibe o formulário de registro de novos usuários (alunos e professores).
// Cada shortcode verifica se o usuário está autenticado e possui as permissões adequadas antes de exibir o conteúdo.
// O painel do aluno busca no banco de dados os cursos em que o usuário está matriculado e os exibe de forma interativa.
// Implementa o processamento do formulário de registro, criando usuários com os papéis "tutor_aluno" ou "tutor_professor".
// Garante a segurança do registro verificando nonce e evitando a criação de usuários duplicados.
// Redireciona o usuário recém-registrado para o respectivo painel conforme seu papel no sistema.




// Evita a redefinição da classe

if (!class_exists('TutorEAD\Shortcodes')) {



    class Shortcodes {

        /**

         * Inicializa os shortcodes.

         */

        public static function init() {

            add_shortcode('tutor_ead_dashboard_admin', [__CLASS__, 'dashboard_admin_shortcode']);

            add_shortcode('tutor_ead_dashboard_professor', [__CLASS__, 'dashboard_professor_shortcode']);

            add_shortcode('tutor_ead_dashboard_aluno', [__CLASS__, 'dashboard_aluno_shortcode']);

            add_shortcode('tutor_ead_register', [__CLASS__, 'register_shortcode']);





            // Ação para processar o formulário de registro

            add_action('admin_post_nopriv_tutoread_register', [__CLASS__, 'process_registration']);

            add_action('admin_post_tutoread_register', [__CLASS__, 'process_registration']);

        }







        /**

         * Shortcode para Dashboard do Administrador.

         */

         public static function dashboard_admin_shortcode() {
            if ( ! is_user_logged_in() ) {
                return '<p>Por favor, faça login para acessar o dashboard.</p>';
            }
        
            if ( ! current_user_can( 'manage_courses' ) ) {
                return '<p>Você não tem permissão para acessar esta página.</p>';
            }
        
            ob_start();
            ?>
            <h1>Bem-vindo, Administrador</h1>
            <p>Gerencie alunos, cursos e relatórios.</p>
            <p>
                <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="button">
                    Sair
                </a>
            </p>
            <?php
            return ob_get_clean();
        }
        



        /**

         * Shortcode para Dashboard do Professor.

         */

        public static function dashboard_professor_shortcode() {

            if (!is_user_logged_in()) {

                return '<p>Por favor, faça login para acessar o dashboard.</p>';

            }



            if (!current_user_can('grade_students')) {

                return '<p>Você não tem permissão para acessar esta página.</p>';

            }



            ob_start();

            ?>

            <h1>Bem-vindo, Professor</h1>

            <p>Gerencie suas turmas e alunos.</p>

            <?php

            return ob_get_clean();

        }



/**

 * Shortcode para Dashboard do Aluno.

 */

public static function dashboard_aluno_shortcode() {

    if (!is_user_logged_in()) {

        return '<p>Por favor, faça login para acessar o dashboard.</p>';

    }



    global $wpdb;

    $user_id = get_current_user_id();



    // Buscar os cursos nos quais o aluno está matriculado

    $cursos = $wpdb->get_results($wpdb->prepare("

        SELECT c.id, c.title, c.description, c.capa_img

        FROM {$wpdb->prefix}matriculas AS m

        INNER JOIN {$wpdb->prefix}tutoread_courses AS c ON m.course_id = c.id

        WHERE m.user_id = %d

        ORDER BY c.title ASC

    ", $user_id), ARRAY_A);



    // Verifica se o aluno tem cursos associados

    if (empty($cursos)) {

        return '<p>Você ainda não está matriculado em nenhum curso.</p>';

    }



    // URL base para a visualização do curso

    $course_page_url = home_url('/visualizar-curso');



    ob_start();

    ?>

    <div class="dashboard-aluno">

        <div class="cursos-container">

            <?php foreach ($cursos as $curso) : ?>

                <div class="curso-card">

                    <img src="<?php echo esc_url(!empty($curso['capa_img']) ? $curso['capa_img'] : 'https://via.placeholder.com/400x250'); ?>" 

                         alt="<?php echo esc_attr($curso['title']); ?>">

                    <div class="curso-info">

                        <h3><?php echo esc_html($curso['title']); ?></h3>

                        <p><?php echo esc_html($curso['description']); ?></p>

                        <a href="<?php echo esc_url($course_page_url . '?course_id=' . $curso['id']); ?>" class="btn-acessar">

                            Acessar Curso

                        </a>

                    </div>

                </div>

            <?php endforeach; ?>

        </div>

    </div>



    <style>

        @import url('https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600&display=swap');



        .dashboard-aluno {

            display: flex;

            justify-content: center;

            margin-top: 30px;

            font-family: 'Open Sans', sans-serif;

        }



        .cursos-container { 

            display: flex; 

            flex-wrap: wrap; 

            gap: 20px; 

            justify-content: center; 

            max-width: 1200px;

        }



        .curso-card {

            width: 360px;

            background: #fff; 

            border-radius: 15px; 

            box-shadow: 0 4px 10px rgba(0,0,0,0.08);

            text-align: left; 

            overflow: hidden;

            transition: transform 0.3s ease, box-shadow 0.3s ease;

            display: flex;

            flex-direction: column;

        }



        .curso-card:hover {

            transform: scale(1.03);

            box-shadow: 0 8px 20px rgba(0,0,0,0.12);

        }



        .curso-card img {

            width: 100%; 

            height: 200px; 

            object-fit: cover; 

            border-radius: 15px 15px 0 0;

        }



        .curso-info {

            padding: 20px;

            display: flex;

            flex-direction: column;

            flex-grow: 1;

        }



        .curso-info h3 {

            font-size: 20px;

            font-weight: 600;

            color: #333;

            margin-bottom: 10px;

        }



        .curso-info p {

            font-size: 15px;

            color: #666;

            flex-grow: 1;

        }



        .btn-acessar {

            display: inline-block; 

            margin-top: 15px; 

            padding: 12px; 

            background: #0073aa; 

            color: #fff; 

            text-decoration: none; 

            border-radius: 8px; 

            text-align: center;

            font-weight: 600;

            transition: background 0.3s ease;

        }



        .btn-acessar:hover {

            background: #005177;

        }

    </style>

    <?php

    return ob_get_clean();

}











        /**

         * Shortcode para Página de Registro.

         */

        public static function register_shortcode() {

            ob_start();

            ?>

            <h1>Registro de Usuário</h1>

            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">

                <input type="hidden" name="action" value="tutoread_register">

                <?php wp_nonce_field('tutor_ead_register_action', 'tutor_ead_register_nonce'); ?>

                <p>

                    <label for="role">Tipo de Usuário:</label>

                    <select name="role" required>

                        <option value="tutor_aluno">Aluno</option>

                        <option value="tutor_professor">Professor</option>

                    </select>

                </p>

                <p>

                    <label for="username">Usuário:</label>

                    <input type="text" name="username" placeholder="<?php _e('Digite seu nome de usuário', 'tutor-ead'); ?>" required>

                </p>

                <p>

                    <label for="email">E-mail:</label>

                    <input type="email" name="email" placeholder="<?php _e('Digite seu e-mail', 'tutor-ead'); ?>" required>

                </p>

                <p>

                    <label for="password">Senha:</label>

                    <input type="password" name="password" placeholder="<?php _e('Digite sua senha', 'tutor-ead'); ?>" required>

                </p>

                <p>

                    <button type="submit">Registrar</button>

                </p>

            </form>

            <?php

            return ob_get_clean();

        }



        /**

         * Processamento do Formulário de Registro.

         */

        public static function process_registration() {

            if (!isset($_POST['tutor_ead_register_nonce']) || !wp_verify_nonce($_POST['tutor_ead_register_nonce'], 'tutor_ead_register_action')) {

                wp_die(__('Nonce inválido. Por favor, tente novamente.', 'tutor-ead'));

            }



            $username = sanitize_user($_POST['username']);

            $email = sanitize_email($_POST['email']);

            $password = sanitize_text_field($_POST['password']);

            $role = sanitize_text_field($_POST['role']);



            $allowed_roles = ['tutor_aluno', 'tutor_professor'];

            if (!in_array($role, $allowed_roles)) {

                wp_die(__('Tipo de usuário inválido.', 'tutor-ead'));

            }



            if (username_exists($username) || email_exists($email)) {

                wp_die(__('Usuário ou e-mail já existem!', 'tutor-ead'));

            }



            $user_id = wp_create_user($username, $password, $email);

            if (is_wp_error($user_id)) {

                wp_die($user_id->get_error_message());

            }



            $user = new \WP_User($user_id);

            $user->set_role($role);



            wp_redirect(home_url('/dashboard-' . ($role === 'tutor_aluno' ? 'aluno' : 'professor')));

            exit;

        }

    }



    Shortcodes::init();

}

