<?php
/**
 * Template Name: Login Tutor EAD
 *
 * Este template exibe o formulário de login customizado e redireciona o usuário de acordo com a role.
 */

if ( is_user_logged_in() ) {
    // Se o usuário já estiver logado, redireciona para o dashboard correto.
    $current_user = wp_get_current_user();
    if ( in_array( 'aluno', $current_user->roles ) ) {
        wp_redirect( site_url('/dashboard-aluno/') );
    } elseif ( in_array( 'professor', $current_user->roles ) ) {
        wp_redirect( site_url('/dashboard-professor/') );
    } else {
        wp_redirect( admin_url() );
    }
    exit;
}

$errors = array();

// Processa o login se o formulário foi submetido
if ( isset( $_POST['login'] ) ) {
    // Verifica o nonce para segurança
    if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'tutor_ead_login' ) ) {
        $errors[] = 'Token de segurança inválido.';
    } else {
        $creds = array(
            'user_login'    => sanitize_user( $_POST['username'] ),
            'user_password' => $_POST['password'],
            'remember'      => isset( $_POST['remember'] ) ? true : false,
        );
        $user = wp_signon( $creds, false );
        if ( is_wp_error( $user ) ) {
            $errors[] = $user->get_error_message();
        } else {
            // Redireciona de acordo com a role do usuário
			if ( in_array( 'tutor_aluno', $user->roles ) ) {
			    wp_redirect( site_url('/dashboard-aluno/') );
			} elseif ( in_array( 'tutor_professor', $user->roles ) ) {
			    wp_redirect( site_url('/dashboard-professor/') );
			} else {
			    wp_redirect( site_url('/dashboard-administrador/') );
			}
			exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Tutor EAD</title>
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background: #f5f7fa;
            padding: 40px;
        }
        .login-container {
            max-width: 400px;
            margin: auto;
            background: #fff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .login-container h2 {
            text-align: center;
            margin-bottom: 20px;
            color: #0073aa;
        }
        .login-container form p {
            margin-bottom: 15px;
        }
        .login-container form label {
            display: block;
            margin-bottom: 5px;
            color: #333;
        }
        .login-container form input[type="text"],
        .login-container form input[type="password"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .login-container form input[type="checkbox"] {
            margin-right: 5px;
        }
        .login-container form input[type="submit"] {
            width: 100%;
            padding: 10px;
            background: #0073aa;
            color: #fff;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
        }
        .login-container form input[type="submit"]:hover {
            background: #005177;
        }
        .login-container .errors {
            margin-bottom: 20px;
            color: #d9534f;
        }
    </style>
</head>
<body>
<div class="login-container">
    <h2>Login Tutor EAD</h2>
    <?php if ( ! empty( $errors ) ) : ?>
        <div class="errors">
            <?php foreach ( $errors as $error ) : ?>
                <p><?php echo esc_html( $error ); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <form method="post">
        <?php wp_nonce_field( 'tutor_ead_login', '_wpnonce' ); ?>
        <p>
            <label for="username">Usuário</label>
            <input type="text" name="username" id="username" required>
        </p>
        <p>
            <label for="password">Senha</label>
            <input type="password" name="password" id="password" required>
        </p>
        <p>
            <label>
                <input type="checkbox" name="remember"> Lembrar-me
            </label>
        </p>
        <p>
            <input type="submit" name="login" value="Entrar">
        </p>
    </form>
</div>
</body>
</html>
