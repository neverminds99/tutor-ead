<?php
/**
 * Template Name: Meu Boletim
 */

// --- Bloco de Definição de Modo de Preview (VERSÃO CORRIGIDA) ---
$is_impersonation_mode = (isset($_SESSION['is_impersonating']) && $_SESSION['is_impersonating'] === true);

// Define a variável $original_admin_id SOMENTE se estivermos no modo de preview.
// Isso evita o erro "Undefined variable".
$original_admin_id = null; // Inicia como nulo por padrão
if ($is_impersonation_mode) {
    $original_admin_id = isset($_SESSION['original_admin_id']) ? (int)$_SESSION['original_admin_id'] : null;
}
// --- Fim do Bloco de Definição ---

if ( ! is_user_logged_in() ) {
    wp_redirect( home_url( '/login' ) );
    exit;
}

global $wpdb;
$current_user = wp_get_current_user();
$user_id = $current_user->ID;
$highlight_color = get_option( 'tutor_ead_highlight_color', '#0073aa' );

// Logic to fetch report card data
$enable_boletim = get_option('tutor_ead_enable_boletim', '0');
if ( $enable_boletim === '1' ) {
    $table_boletins = $wpdb->prefix . 'boletins';
    $sql = "
        SELECT b.*
        FROM $table_boletins b
        INNER JOIN (
            SELECT atividade_id, aluno_id, MAX(data_atualizacao) AS max_data
            FROM $table_boletins
            WHERE aluno_id = %d
            GROUP BY atividade_id, aluno_id
        ) latest
        ON b.atividade_id = latest.atividade_id
        AND b.aluno_id = latest.aluno_id
        AND b.data_atualizacao = latest.max_data
        ORDER BY b.data_atualizacao DESC
    ";
    $boletins = $wpdb->get_results($wpdb->prepare($sql, $user_id));
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Boletim - TutorEAD</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
    :root {
        --primary-color: <?php echo esc_attr( $highlight_color ); ?>;
        --bg-color: #ffffff;
        --text-color: #333333;
        --border-color: #e5e7eb;
        --light-gray: #f9fafb;
        --medium-gray: #e5e7eb;
        --hover-color: #f3f4f6;
        --sidebar-width: 80px;
    }
    body, button, input, textarea, select { font-family: 'Inter', sans-serif; }
    .fa, .fas, .far, .fal, .fab, .fa-solid, .fa-regular, .fa-brands { font-family: "Font Awesome 6 Free"; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { background: var(--light-gray); color: var(--text-color); }
    .main-wrapper { display: flex; min-height: 100vh; }
    .left-sidebar { width: var(--sidebar-width); background: var(--bg-color); border-right: 1px solid var(--border-color); position: fixed; height: 100vh; display: flex; flex-direction: column; z-index: 90; }
    .sidebar-placeholder { width: var(--sidebar-width); flex-shrink: 0; }
    .sidebar-content { flex: 1; display: flex; flex-direction: column; }
    .sidebar-top { flex: 1; }
    .sidebar-bottom { padding: 16px; display: flex; flex-direction: column; gap: 12px; margin-top: auto; }
    .sidebar-button { width: 48px; height: 48px; border-radius: 8px; border: 1px solid var(--border-color); background: var(--bg-color); color: var(--text-color); display: flex; align-items: center; justify-content: center; cursor: pointer; transition: all 0.2s; text-decoration: none; position: relative; font-size: 20px; }
    .sidebar-button:hover { background: var(--hover-color); border-color: var(--primary-color); }
    .main-content { flex: 1; display: flex; flex-direction: column; }
    header.top-header { display: flex; justify-content: space-between; align-items: center; padding: 16px 24px; background: var(--bg-color); border-bottom: 1px solid var(--border-color); position: sticky; top: 0; z-index: 100; }
    header.top-header .logo { font-size: 24px; font-weight: 600; }
    header.top-header .logo img { max-height: 40px; }
    .user-menu { position: relative; }
    .user-icon { cursor: pointer; font-size: 20px; color: var(--text-color); padding: 8px; border-radius: 8px; transition: background 0.2s; }
    .user-icon:hover { background: var(--hover-color); }
    .user-dropdown { display: none; position: absolute; right: 0; background: var(--bg-color); min-width: 180px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); border: 1px solid var(--border-color); border-radius: 8px; margin-top: 8px; z-index: 100; overflow: hidden; }
    .user-dropdown a { display: block; padding: 12px 16px; color: var(--text-color); text-decoration: none; font-size: 14px; transition: background 0.2s; }
    .user-dropdown a:hover { background: var(--hover-color); }
    .user-dropdown a.logout { color: #dc2626; border-top: 1px solid var(--border-color); }
    .user-dropdown a i { margin-right: 8px; width: 16px; text-align: center; }
    .main-nav { background: var(--bg-color); border-bottom: 1px solid var(--border-color); padding-left: 24px; position: relative; }
    .nav-menu { display: flex; justify-content: flex-start; list-style: none; gap: 32px; padding: 0; margin-left: 20px; }
    .nav-menu li a { display: flex; align-items: center; gap: 8px; text-decoration: none; color: var(--text-color); padding: 16px 0; font-weight: 500; border-bottom: 2px solid transparent; transition: all 0.2s; }
    .nav-menu li a:hover, .nav-menu li a.active { color: var(--primary-color); border-bottom-color: var(--primary-color); }
    .nav-menu li a i { font-size: 18px; }
    .dashboard-container { padding: 24px; }
    .boletim-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .boletim-table th, .boletim-table td { padding: 15px; border-bottom: 1px solid #e9ecef; text-align: left; }
    .boletim-table th { background-color: #f8f9fa; font-weight: 600; color: #495057; }
    .boletim-table tbody tr:hover { background-color: #f1f3f5; }
    </style>
</head>
<body class="tutoread-dashboard">
<?php
// Exibe o banner SOMENTE se a sessão de personificação estiver ativa.
// A variável $is_impersonation_mode foi definida no topo do arquivo.
if ( $is_impersonation_mode ) :
    // Gera a URL correta para a ação de SAÍDA que criamos no plugin principal.
    $exit_impersonation_url = admin_url('admin.php?action=exit_impersonation');
    $impersonated_user = wp_get_current_user();
?>
<style>
    .impersonation-banner {
        position: fixed; top: 0; left: 0; width: 100%;
        background-color: #d9534f; /* Cor de alerta */
        color: #fff; padding: 10px 20px;
        display: flex; justify-content: center; align-items: center;
        font-size: 14px; z-index: 99999; box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    .impersonation-banner p { margin: 0; padding: 0; }
    .impersonation-banner a {
        color: #fff; background-color: rgba(255, 255, 255, 0.2);
        border: 1px solid #fff; border-radius: 4px;
        padding: 5px 15px; text-decoration: none; margin-left: 20px;
        font-weight: bold; transition: background-color 0.2s ease;
    }
    .impersonation-banner a:hover { background-color: rgba(255, 255, 255, 0.4); }
    body { padding-top: 45px; /* Empurra o conteúdo para baixo para não sobrepor */ }
</style>
<div class="impersonation-banner">
    <p>
        <span class="dashicons dashicons-admin-users"></span>
        Você está navegando como o aluno: <strong><?php echo esc_html($impersonated_user->display_name); ?></strong>.
    </p>
    <a href="<?php echo esc_url($exit_impersonation_url); ?>" target="_self">Sair do Modo Preview</a>
</div>
<?php endif; ?>
    <div class="main-wrapper">
        <div class="left-sidebar">
            <div class="sidebar-content">
                <div class="sidebar-top"></div>
                <div class="sidebar-bottom">
                    <button class="sidebar-button help-button" title="Ajuda"><i class="fa fa-question"></i></button>
                </div>
            </div>
        </div>
        <div class="sidebar-placeholder"></div>
        <div class="main-content">
            <header class="top-header">
                <div class="logo">
                    <a href="<?php echo esc_url( home_url( '/dashboard-aluno' ) ); ?>">
                        <?php
                        $plugin_logo = get_option( 'tutor_ead_course_logo' );
                        echo $plugin_logo ? '<img src="' . esc_url( $plugin_logo ) . '" alt="Logo do Curso">' : 'TutorEAD';
                        ?>
                    </a>
                </div>
                <div class="user-menu">
                    <div class="user-icon"><i class="fa fa-user"></i></div>
                    <div class="user-dropdown">
                        <a href="<?php echo esc_url( home_url( '/dashboard-aluno' ) ); ?>"><i class="fa fa-tachometer-alt"></i> Dashboard</a>
                        <a href="<?php echo esc_url( home_url( '/perfil-aluno' ) ); ?>"><i class="fa fa-user"></i> Meu Perfil</a>
                        <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="logout"><i class="fa fa-sign-out-alt"></i> Sair</a>
                    </div>
                </div>
            </header>
            <nav class="main-nav">
                <ul class="nav-menu">
                    <li><a href="<?php echo esc_url( home_url('/dashboard-aluno/') ); ?>"><i class="fa fa-book"></i><span>Meus Cursos</span></a></li>
                    <li><a href="<?php echo esc_url( home_url('/perfil-aluno/') ); ?>"><i class="fa fa-user"></i><span>Meu Perfil</span></a></li>
                    <?php if ( get_option('tutor_ead_enable_boletim') == '1' ) : ?>
                        <li><a href="<?php echo esc_url( home_url('/meu-boletim') ); ?>" class="active"><i class="fa fa-file-alt"></i><span>Boletim</span></a></li>
                    <?php endif; ?>
                    <?php if ( get_option('tutor_ead_enable_certificado') === '1' ) : ?>
                        <li><a href="<?php echo esc_url( home_url('/certificado-tutoread') ); ?>"><i class="fa fa-certificate"></i><span>Certificados</span></a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <div class="dashboard-container">
                <h2>Meu Boletim</h2>
                <?php if ( $enable_boletim === '1' ) : ?>
                    <table class="boletim-table">
                        <thead>
                            <tr>
                                <th>Curso</th>
                                <th>Atividade</th>
                                <th>Nota</th>
                                <th>Feedback</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ( !empty($boletins) ) : ?>
                                <?php foreach ( $boletins as $boletim ) : ?>
                                    <tr>
                                        <td><?php echo esc_html( $boletim->course_title ); ?></td>
                                        <td><?php echo esc_html( $boletim->atividade_title ); ?></td>
                                        <td><?php echo esc_html( $boletim->nota ); ?></td>
                                        <td><?php echo esc_html( $boletim->feedback ); ?></td>
                                        <td><?php echo esc_html( date_i18n( 'd/m/Y', strtotime( $boletim->data_atualizacao ) ) ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">Nenhuma nota encontrada.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php else : ?>
                    <p>A função de boletim não está ativa no momento.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            var userIcon = document.querySelector('.user-icon');
            var userDropdown = document.querySelector('.user-dropdown');
            if (userIcon) {
                userIcon.addEventListener('click', function(e){
                    e.stopPropagation();
                    userDropdown.style.display = (userDropdown.style.display === 'block') ? 'none' : 'block';
                });
            }
            document.addEventListener('click', function(){
                if (userDropdown) userDropdown.style.display = 'none';
            });
        });
    </script>
</body>
</html>