<?php
/**
 * Template Name: Login Tutor EAD
 *
 * Este template exibe o formulário de login customizado e redireciona o usuário de acordo com a role.
 * Versão Reorganizada
 */

// --- FUNÇÃO DE APOIO PARA REDIRECIONAMENTO ---

/**
 * Redireciona o usuário com base em seu perfil (role) após o login.
 *
 * @param WP_User $user O objeto do usuário do WordPress.
 */
function tutor_ead_redirect_user_by_role( $user ) {
    if ( ! is_a( $user, 'WP_User' ) ) {
        return;
    }

    // Define a URL de redirecionamento padrão
    $redirect_url = home_url(); // Página inicial como fallback

    if ( in_array( 'administrator', $user->roles ) ) {
        // Role 'administrator' vai para o painel do WP
        $redirect_url = admin_url();
    } elseif ( in_array( 'tutor_admin', $user->roles ) ) {
        // Role 'tutor_admin' vai para o dashboard de admin no front-end
        $redirect_url = site_url( '/dashboard-administrador/' );
    } elseif ( in_array( 'tutor_professor', $user->roles ) ) {
        // Role 'tutor_professor' vai para o dashboard de professor no front-end
        $redirect_url = site_url( '/dashboard-professor/' );
    } elseif ( in_array( 'tutor_aluno', $user->roles ) ) {
        // Role 'tutor_aluno' vai para o dashboard de aluno no front-end
        $redirect_url = site_url( '/dashboard-aluno/' );
    }

    wp_redirect( $redirect_url );
    exit; // Encerra o script após o redirecionamento.
}


// --- LÓGICA PRINCIPAL DO TEMPLATE ---

// 1. VERIFICA SE O USUÁRIO JÁ ESTÁ LOGADO
if ( is_user_logged_in() ) {
    $current_user = wp_get_current_user();
    
    // O comportamento original para administradores já logados foi mantido: exibir uma mensagem.
    if ( in_array( 'administrator', $current_user->roles ) || in_array( 'tutor_admin', $current_user->roles ) ) {
        echo '<!DOCTYPE html>
        <html lang="pt-BR">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Painel Administrativo</title>
            <style>
                body { font-family: "Open Sans", sans-serif; background: #f5f7fa; padding: 40px; margin: 0; }
                .notice { max-width: 400px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center; }
                .notice a { display: inline-block; margin-top: 20px; padding: 10px 20px; background: #0073aa; color: #fff; text-decoration: none; border-radius: 4px; }
                .notice a:hover { filter: brightness(90%); }
            </style>
        </head>
        <body>
            <div class="notice">
                <p>Você já está logado como administrador.</p>
                <p>Acesse o <a href="' . admin_url( 'admin.php?page=tutor-ead-dashboard' ) . '">Painel Administrativo</a></p>
            </div>
        </body>
        </html>';
        exit;
    } else {
        // Para outras roles já logadas, usa a função de redirecionamento.
        tutor_ead_redirect_user_by_role( $current_user );
    }
}

// 2. PROCESSA A SUBMISSÃO DO FORMULÁRIO DE LOGIN
$errors = array();
if ( 'POST' === $_SERVER['REQUEST_METHOD'] && isset( $_POST['login'] ) ) {
    
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'tutor_ead_login' ) ) {
        $errors[] = 'Token de segurança inválido.';
    } else {
        $creds = array(
            'user_login'    => sanitize_user( $_POST['username'] ),
            'user_password' => $_POST['password'],
            'remember'      => isset( $_POST['remember'] ),
        );

        $secure_cookie = is_ssl();
        $user = wp_signon( $creds, $secure_cookie );

        if ( is_wp_error( $user ) ) {
            $errors[] = $user->get_error_message();
        } else {
            // Adicionado: Registra o login do aluno na tabela de log
            if ( in_array( 'tutor_aluno', $user->roles ) ) {
                if (function_exists('TutorEAD\record_student_activity')) {
                    \TutorEAD\record_student_activity($user->ID, 'login');
                }
            }

            // Se houver um redirecionamento específico na URL, prioriza-o.
            if ( ! empty( $_POST['redirect'] ) ) {
                wp_redirect( esc_url_raw( urldecode( $_POST['redirect'] ) ) );
                exit;
            }
            
            // Caso contrário, usa a função de redirecionamento padrão baseada na role.
            tutor_ead_redirect_user_by_role( $user );
        }
    }
}

// 3. RECUPERA OPÇÕES DE CUSTOMIZAÇÃO PARA EXIBIR NA PÁGINA
$course_name          = get_option( 'tutor_ead_course_name' );
$course_logo          = get_option( 'tutor_ead_course_logo' );
$highlight_color      = get_option( 'tutor_ead_highlight_color' );
$login_bg_image       = get_option( 'tutor_ead_login_bg_image', '' );
$login_logo_only      = get_option( 'tutor_ead_login_logo_only', '0' );
$show_support_contact = get_option( 'tutor_ead_show_support_contact', '0' );
$support_whatsapp     = get_option( 'tutor_ead_support_whatsapp', '' );
$logo_width           = get_option( 'tutor_ead_logo_width', 80 );

?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo esc_html( $course_name ? $course_name : 'Curso' ); ?> - Login</title>
  <style>
      body {
          font-family: 'Open Sans', sans-serif;
          background: <?php echo !empty($login_bg_image) ? "url('" . esc_url($login_bg_image) . "') no-repeat center center / cover" : "#f5f7fa"; ?>;
          min-height: 100vh;
          margin: 0;
          display: flex;
          flex-direction: column;
          justify-content: center;
          align-items: center;
      }
      .login-container {
          width: 100%;
          max-width: 400px;
          background: #fff;
          padding: 30px;
          border-radius: 8px;
          box-shadow: 0 5px 15px rgba(0,0,0,0.1);
          margin-bottom: 20px;
      }
      .login-container h2 {
          text-align: center;
          margin-bottom: 20px;
          color: <?php echo esc_attr( $highlight_color ? $highlight_color : '#0073aa' ); ?>;
      }
      .login-container h2 img.course-logo {
          vertical-align: middle;
          max-width: 80px;
          margin-right: <?php echo ($login_logo_only === '1') ? '0' : '10px'; ?>;
      }
      .login-container h2 span.course-name { vertical-align: middle; }
      <?php if ($login_logo_only === '1'): ?>
      .login-container h2 img.course-logo { width: <?php echo intval($logo_width); ?>px; max-width: none; }
      .login-container h2 span.course-name { display: none; }
      <?php endif; ?>
      .login-container form p { margin-bottom: 15px; }
      .login-container form label { display: block; margin-bottom: 5px; color: #333; }
      .login-container form input[type="text"],
      .login-container form input[type="password"] {
          width: 100%;
          padding: 8px;
          border: 1px solid #ddd;
          border-radius: 4px;
          box-sizing: border-box;
      }
      .login-container form input[type="checkbox"] { margin-right: 5px; }
      .login-container form input[type="submit"] {
          width: 100%;
          padding: 10px;
          background: <?php echo esc_attr( $highlight_color ? $highlight_color : '#0073aa' ); ?>;
          color: #fff;
          border: none;
          border-radius: 4px;
          font-size: 16px;
          cursor: pointer;
      }
      .login-container form input[type="submit"]:hover { filter: brightness(90%); }
      .login-container .errors { margin-bottom: 20px; color: #d9534f; }
      .login-tips {
          max-width: 400px;
          width: 100%;
          background: #e9f7ef;
          border: 1px solid #d4edda;
          padding: 15px;
          border-radius: 4px;
          text-align: center;
      }
      .login-tips p { margin: 0 0 10px; color: #155724; }
      .support-button {
          display: inline-block;
          padding: 10px 20px;
          background: #28a745;
          color: #fff;
          text-decoration: none;
          border-radius: 4px;
      }
      .support-button:hover { background: #218838; }
  </style>
</head>
<body>
<div class="login-container">
    <h2>
        <?php if ( $course_logo ) : ?>
            <img src="<?php echo esc_url( $course_logo ); ?>" alt="Logo do Curso" class="course-logo">
        <?php endif; ?>
        <span class="course-name"><?php echo esc_html( $course_name ? $course_name : 'Curso' ); ?></span>
    </h2>
    <?php if ( ! empty( $errors ) ) : ?>
        <div class="errors">
            <?php foreach ( $errors as $error ) : ?>
                <p><?php echo esc_html( $error ); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post">
        <?php wp_nonce_field( 'tutor_ead_login', '_wpnonce' ); ?>
        <input type="hidden" name="redirect" value="<?php echo esc_attr( isset( $_GET['redirect'] ) ? urlencode( $_GET['redirect'] ) : '' ); ?>">
        <p><label for="username">Usuário</label><input type="text" name="username" id="username" placeholder="<?php _e('Digite seu nome de usuário', 'tutor-ead'); ?>" required></p>
        <p><label for="password">Senha</label><input type="password" name="password" id="password" placeholder="<?php _e('Digite sua senha', 'tutor-ead'); ?>" required></p>
        <p><label><input type="checkbox" name="remember"> Lembrar-me</label></p>
        <p><input type="submit" name="login" value="Entrar"></p>
    </form>
</div>
<?php if ( $show_support_contact === '1' && !empty($support_whatsapp) ) : ?>
<div class="login-tips">
    <p><strong>Dica:</strong> Se ao tentar fazer login você receber a mensagem "Token de segurança inválido.", por favor, repita o login.</p>
    <p>A senha pode ser conferida entrando em contato com o suporte via WhatsApp.</p>
    <p>Entre em contato com o suporte:</p>
    <a href="https://wa.me/<?php echo esc_attr($support_whatsapp); ?>" target="_blank" class="support-button">WhatsApp do Suporte</a>
</div>
<?php endif; ?>
</body>
</html>