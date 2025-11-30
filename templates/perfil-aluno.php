<?php
/**
 * Template Name: Perfil do Aluno
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

// Buscar informações do perfil
$user_info = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}tutoread_user_info WHERE user_id = %d",
    $user_id
));

$full_name = $user_info && !empty($user_info->full_name) ? $user_info->full_name : $current_user->display_name;
$phone_number = $user_info && !empty($user_info->phone_number) ? $user_info->phone_number : '';
$bio = $user_info && !empty($user_info->bio) ? $user_info->bio : '';
$avatar_url = $user_info && !empty($user_info->profile_photo_url) ? $user_info->profile_photo_url : TutorEAD\generate_placeholder_avatar($full_name);
$has_custom_avatar = !empty($user_info->profile_photo_url);

$dashboard_link_header = home_url('/dashboard-aluno');
if ($is_impersonation_mode) {
    $next_token_header = 'tutoread_impersonate_' . bin2hex(random_bytes(16));
    set_transient($next_token_header, ['admin_id' => $original_admin_id, 'student_id' => $current_user->ID], 5 * MINUTE_IN_SECONDS);
    $dashboard_link_header = add_query_arg('impersonate_token', $next_token_header, $dashboard_link_header);
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meu Perfil - TutorEAD</title>
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
    
    /* Fonte padrão só para elementos de texto */
    body, button, input, textarea, select {
        font-family: 'Inter', sans-serif;
    }
    
    /* Garantir que Font Awesome funcione corretamente */
    .fa, .fas, .far, .fal, .fab,
    .fa-solid, .fa-regular, .fa-brands {
        font-family: "Font Awesome 6 Free";
    }
    
    /* Reset básico sem interferir nos ícones */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    /* Customização do Shepherd Tour */
    .shepherd-element {
        background: var(--bg-color) !important;
        border: 1px solid #d1d5db !important;
        border-radius: 8px !important;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05) !important;
    }
    
    .shepherd-arrow:before {
        background: var(--bg-color) !important;
        border: 1px solid #d1d5db !important;
    }
    
    /* Overlay escuro com 50% de opacidade */
    .shepherd-modal-overlay-container {
        opacity: 0.5 !important;
    }
    
    .shepherd-modal-overlay-container svg {
        opacity: 1 !important;
    }
    
    .shepherd-modal {
        z-index: 9999 !important;
    }
    
    .shepherd-element.shepherd-element-attached-bottom .shepherd-arrow {
        border-bottom-color: #d1d5db !important;
    }
    
    .shepherd-element.shepherd-element-attached-top .shepherd-arrow {
        border-top-color: #d1d5db !important;
    }
    
    .shepherd-element.shepherd-element-attached-left .shepherd-arrow {
        border-left-color: #d1d5db !important;
    }
    
    .shepherd-element.shepherd-element-attached-right .shepherd-arrow {
        border-right-color: #d1d5db !important;
    }
    
    .shepherd-button {
        background: var(--primary-color) !important;
        border: none !important;
        color: white !important;
        padding: 8px 16px !important;
        border-radius: 6px !important;
        font-weight: 500 !important;
        transition: opacity 0.2s !important;
        margin: 0 4px !important;
    }
    
    .shepherd-button:hover {
        opacity: 0.9 !important;
    }
    
    .shepherd-button.shepherd-button-secondary {
        background: #f3f4f6 !important;
        color: var(--text-color) !important;
        border: 1px solid #d1d5db !important;
    }
    
    .shepherd-button.shepherd-button-secondary:hover {
        background: #e5e7eb !important;
    }
    
    .shepherd-content {
        padding: 16px !important;
    }
    
    .shepherd-text {
        font-size: 15px !important;
        line-height: 1.5 !important;
        color: var(--text-color) !important;
    }
    
    .shepherd-footer {
        border-top: 1px solid #e5e7eb !important;
        padding: 12px 16px !important;
    }
    
    /* Remove qualquer possibilidade de fechar */
    .shepherd-cancel-icon {
        display: none !important;
    }
    
    body {
        background: var(--light-gray);
        color: var(--text-color);
    }
    
    /* Main Layout Wrapper */
    .main-wrapper {
        display: flex;
        min-height: 100vh;
    }

    .left-sidebar, .main-content {
        transition: all 0.3s ease;
    }

    .sidebar-expanded .left-sidebar {
        width: 250px;
    }

    .sidebar-expanded .main-content {
        margin-left: 250px;
    }

    .sidebar-expanded .nav-menu li a span {
        display: inline;
    }
    
    .sidebar-expanded .nav-menu li a span {
        display: inline;
        margin-left: 8px; /* Space between icon and text */
    }

    .sidebar-expanded .nav-menu li a {
        flex-direction: row;
        justify-content: flex-start; /* Align content to start */
        padding: 8px 12px; /* Adjust padding for horizontal layout */
        width: auto; /* Allow width to expand */
        height: 60px; /* Match collapsed height */
    }

    .mobile-menu-open .left-sidebar {
        display: flex;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }

    .mobile-menu-open .left-sidebar .sidebar-content {
        background: var(--bg-color);
        border-radius: 12px;
        padding: 24px;
        width: 90%;
        max-width: 400px;
        position: relative;
    }

    .mobile-menu-open .left-sidebar .nav-menu {
        align-items: flex-start;
    }

    .mobile-menu-open .left-sidebar .nav-menu li a {
        flex-direction: row;
        width: 100%;
        height: auto;
        padding: 16px; /* Increased padding */
        font-size: 18px; /* Increased font size */
    }

    .mobile-menu-open .left-sidebar .nav-menu li a span {
        display: inline;
    }

    .mobile-menu-open .left-sidebar .nav-menu li a i {
        font-size: 24px; /* Increased icon size */
    }

    .mobile-menu-close {
        display: none;
    }

    .mobile-menu-open .mobile-menu-close {
        display: block;
        position: absolute;
        top: 16px;
        right: 16px;
        background: none;
        border: none;
        font-size: 32px;
        color: var(--text-color);
        cursor: pointer;
    }
    
    /* Left Sidebar */
    .left-sidebar {
        width: var(--sidebar-width);
        background: var(--bg-color);
        /* border-right: 1px solid var(--border-color); */
        position: fixed;
        height: 100vh;
        display: flex;
        flex-direction: column;
        z-index: 90;
    }
    
    .sidebar-placeholder {
        width: var(--sidebar-width);
        flex-shrink: 0;
    }
    
    .sidebar-content {
        flex: 1;
        display: flex;
        flex-direction: column;
    }
    
    .sidebar-top {
        flex: 1;
        padding-top: 200px;
    }
    
    .sidebar-bottom {
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        margin-top: auto;
    }
    
    .sidebar-button {
        width: 48px;
        height: 48px;
        border-radius: 8px;
        border: 1px solid var(--border-color);
        background: var(--bg-color);
        color: var(--text-color);
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
        position: relative;
        font-size: 20px;
    }
    
    .sidebar-button:hover {
        background: var(--hover-color);
        border-color: var(--primary-color);
    }
    
    .sidebar-button.primary {
        background: var(--primary-color);
        color: white;
        border-color: var(--primary-color);
    }
    
    .sidebar-button.primary:hover {
        opacity: 0.9;
    }
    
    /* Help Menu - ATUALIZADO */
    .help-menu {
        display: none;
        position: absolute;
        left: calc(100% + 8px);
        bottom: auto;
        background: var(--bg-color);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        width: 200px;
        z-index: 100;
        max-height: 80vh;
        overflow-y: auto;
    }
    
    .help-menu.active {
        display: block;
    }
    
    .help-menu a {
        display: block;
        padding: 12px 16px;
        color: var(--text-color);
        text-decoration: none;
        font-size: 14px;
        transition: background 0.2s;
        border-bottom: 1px solid var(--border-color);
    }
    
    .help-menu a:last-child {
        border-bottom: none;
    }
    
    .help-menu a:hover {
        background: var(--hover-color);
    }
    
    .help-menu a i {
        margin-right: 8px;
        color: var(--primary-color);
    }
    
    /* Main Content Area */
    .main-content {
        flex: 1;
        display: flex;
        flex-direction: column;
        margin-left: var(--sidebar-width);
        position: relative;
        background: var(--bg-color); /* Fundo branco */
    }
    
    /* Header */
    header.top-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 16px 24px;
        background: var(--bg-color);
        /* border-bottom: 1px solid var(--border-color); */
        position: sticky;
        top: 0;
        z-index: 100;
    }
    
    header.top-header .logo {
        font-size: 24px;
        font-weight: 600;
    }
    
    header.top-header .logo img {
        max-height: 40px;
    }
    
    /* User Menu */
    .user-menu {
        position: relative;
    }
    
    .user-icon {
        cursor: pointer;
        font-size: 20px;
        color: var(--text-color);
        padding: 8px;
        border-radius: 8px;
        transition: background 0.2s;
    }
    
    .user-icon:hover {
        background: var(--hover-color);
    }
    
    .user-dropdown {
        display: none;
        position: absolute;
        right: 0;
        background: var(--bg-color);
        min-width: 180px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        margin-top: 8px;
        z-index: 100;
        overflow: hidden;
    }
    
    .user-dropdown a {
        display: block;
        padding: 12px 16px;
        color: var(--text-color);
        text-decoration: none;
        font-size: 14px;
        transition: background 0.2s;
    }
    
    .user-dropdown a:hover {
        background: var(--hover-color);
    }
    
    .user-dropdown a.logout {
        color: #dc2626;
        border-top: 1px solid var(--border-color);
    }
    
    .user-dropdown a i {
        margin-right: 8px;
        width: 16px;
        text-align: center;
    }

    .mobile-nav-toggle {
        display: none;
    }
    

    

    

    
    .nav-menu {
        display: flex;
        flex-direction: column;
        align-items: center;
        list-style: none;
        gap: 16px;
        padding: 16px 0;
        margin-left: 0;
    }
    
    .nav-menu li a {
        display: flex;
        flex-direction: column; /* This needs to change on expand */
        align-items: center;
        justify-content: center;
        gap: 4px;
        text-decoration: none;
        color: var(--text-color);
        padding: 12px 8px 4px 8px; /* Adjusted padding */
        font-weight: 500;
        border-radius: 8px;
        width: 60px;
        height: 60px;
        transition: all 0.2s;
    }

    .nav-menu li a span {
        display: none; /* Oculta o texto */
    }
    
    .nav-menu li a:hover,
    .nav-menu li a.active {
        color: var(--primary-color);
        border-bottom-color: var(--primary-color);
    }
    
    .nav-menu li a i {
        font-size: 18px;
    }
    
    /* Dashboard Container */
    .dashboard-container {
        padding: 24px;
        background: var(--light-gray);
        border-radius: 12px;
    }
    
    /* ----- Alert Card (Avisos) ------------------------------- */
    .avisos-wrapper {
        margin-bottom: 32px;
    }
    
    .alert-card {
        display: flex;
        align-items: flex-start;
        gap: 16px;
        padding: 20px;
        border-radius: 12px;
        background: #fffbe6;
        border: 1px solid #fde68a;
        margin-bottom: 16px;
    }
    
    .alert-card:last-child {
        margin-bottom: 0;
    }
    
    .alert-icon {
        font-size: 24px;
        color: #d97706;
        flex-shrink: 0;
        margin-top: 2px;
    }
    
    .alert-content p {
        margin: 0;
        font-size: 14px;
        color: #92400e;
    }
    
    .alert-close {
        background: none;
        border: none;
        font-size: 18px;
        font-weight: 700;
        color: #92400e;
        cursor: pointer;
        margin-left: auto;
        transition: opacity .2s;
    }
    .alert-close:hover { opacity: .7; }
    
    /* ----- Modal (Alertas) ---------------------------------- */
    .alert-modal-overlay{
        position: fixed;
        inset: 0;
        background: rgba(0,0,0,.55);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }
    
    .alert-modal{
        background: #fffbe6;
        border: 1px solid #fde68a;
        border-radius: 12px;
        padding: 32px 24px;
        max-width: 420px;
        text-align: center;
        box-shadow: 0 10px 25px rgba(0,0,0,.2);
    }
    
    .alert-modal-icon{
        font-size: 32px;
        color: #d97706;
        margin-bottom: 12px;
    }
    
    .alert-modal p{
        color: #92400e;
        margin: 0 0 18px 0;
    }
    
    .alert-modal p:last-of-type {
        margin-bottom: 24px;
    }
    
    .alert-modal-close{
        background: #d97706;
        color: #fff;
        border: none;
        padding: 8px 24px;
        border-radius: 8px;
        font-weight: 600;
        cursor: pointer;
        transition: opacity .2s;
    }
    
    .alert-modal-close:hover{ opacity: .85; }

    /* Top Notification Bar */
    .top-notification-bar {
        background-color: #fff3cd;
        color: #856404;
        padding: 15px 24px;
        border-bottom: 1px solid #ffeeba;
        text-align: center;
        font-size: 15px;
        position: sticky;
        top: 0;
        z-index: 99;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }
    .top-notification-bar a {
        color: #856404;
        font-weight: 600;
        text-decoration: underline;
    }
    .top-notification-bar a:hover {
        color: #665003;
    }

    /* Floating Notification Card for Documents */
    .document-pending-card-notification {
        position: fixed;
        bottom: 24px;
        right: 24px;
        z-index: 2000;
        max-width: 380px;
        box-shadow: 0 5px 15px rgba(0,0,0,0.2);
    }
    
    /* Profile Section */
    .profile-section {
        display: flex;
        align-items: center;
        gap: 16px;
        margin-bottom: 32px;
        padding: 20px;
        background: var(--bg-color);
        border: 1px solid var(--border-color);
        border-radius: 12px;
    }
    
    .profile-section img {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        border: 2px solid var(--border-color);
    }
    
    .profile-info h3 {
        font-size: 20px;
        font-weight: 600;
        color: var(--text-color);
        margin-bottom: 4px;
    }
    
    .profile-info p {
        font-size: 14px;
        color: #6b7280;
    }


    
    /* Content Area */
    .content {
        background: var(--bg-color);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        padding: 24px;
    }
    
    .content h2 {
        font-size: 24px;
        font-weight: 600;
        margin-bottom: 24px;
    }
    
    /* Course Grid */
    .courses-container {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
        gap: 24px;
        justify-content: start;
    }
    
    .course-card {
        background: var(--bg-color);
        border: 1px solid var(--border-color);
        border-radius: 12px;
        overflow: hidden;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .course-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    .course-card img {
        width: 100%;
        height: 160px;
        object-fit: cover;
    }
    
    .course-card-content {
        padding: 16px;
    }
    
    .course-card h4 {
        font-size: 16px;
        font-weight: 600;
        margin-bottom: 8px;
        color: var(--text-color);
        overflow: hidden;
        text-overflow: ellipsis;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
    }
    
    .progress-bar {
        width: 100%;
        height: 6px;
        background: var(--light-gray);
        border-radius: 3px;
        overflow: hidden;
        margin-bottom: 8px;
    }
    
    .progress-fill {
        height: 100%;
        background: var(--primary-color);
        border-radius: 3px;
        transition: width 0.3s ease;
    }
    
    .progress-text {
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 12px;
    }
    
    .last-lesson-text {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 12px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    
    .course-button {
        display: block;
        width: 100%;
        padding: 10px 16px;
        background: var(--primary-color);
        color: white;
        text-align: center;
        border: none;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 500;
        font-size: 14px;
        transition: opacity 0.2s;
    }
    
    .course-button:hover {
        opacity: 0.9;
    }

    .profile-header { text-align: center; margin-bottom: 40px; position: relative; }
    .profile-avatar-wrapper { position: relative; display: inline-block; }
    .profile-avatar { width: 120px; height: 120px; border-radius: 50%; margin-bottom: 20px; border: 4px solid #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
    .edit-avatar-btn { position: absolute; bottom: 20px; right: 0; background: var(--primary-color); color: #fff; width: 36px; height: 36px; border-radius: 50%; border: 2px solid #fff; display: flex; align-items: center; justify-content: center; cursor: pointer; }
    .profile-header h2 { font-size: 28px; font-weight: 700; margin-bottom: 5px; }
    .profile-header p { font-size: 16px; color: #6c757d; margin: 0; }
    .profile-details { background-color: #fff; border-radius: 12px; padding: 30px 40px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06); max-width: 700px; margin: 0 auto; }
    .profile-details-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1px solid #dee2e6; padding-bottom: 15px; }
    .profile-details-header h3 { font-size: 22px; font-weight: 600; margin: 0; }
    .profile-view ul, .profile-form ul { list-style: none; padding: 0; margin: 0; }
    .profile-view li, .profile-form li { display: flex; flex-wrap: wrap; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #e9ecef; }
    .profile-view li:last-child, .profile-form li:last-child { border-bottom: none; }
    .profile-view li strong, .profile-form li strong { font-weight: 600; color: #495057; width: 100%; margin-bottom: 8px; }
    .profile-view li span, .profile-view li p { color: #212529; text-align: left; width: 100%; }
    .profile-view li p { margin: 0; line-height: 1.6; }
    .profile-form input[type="text"], .profile-form textarea { width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 6px; font-size: 15px; }
    .profile-form textarea { min-height: 120px; resize: vertical; }
    .form-actions { text-align: right; }
    .button-primary, .button-secondary, .button-danger { padding: 10px 20px; border: none; border-radius: 6px; font-weight: 600; cursor: pointer; transition: background-color 0.2s; }
    .button-primary { background-color: var(--primary-color); color: #fff; }
    .button-primary:hover { opacity: 0.9; }
    .button-secondary { background-color: #6c757d; color: #fff; }
    .button-secondary:hover { background-color: #5a6268; }
    .button-danger { background-color: #dc3545; color: #fff; }
    .button-danger:hover { background-color: #c82333; }
    #save-profile-btn, #cancel-edit-btn, #remove-avatar-btn { display: none; }
    #profile-form { display: none; }
    
    /* Mobile Responsiveness */
    @media (max-width: 768px) {
        .left-sidebar, .sidebar-placeholder {
            display: none;
        }

        .mobile-nav-toggle {
            display: block;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
        }

        .main-content {
            margin-left: 0; /* Full width on mobile */
        }

        :root {
            --sidebar-width: 60px;
        }
        
        header.top-header {
            padding: 12px 16px;
        }
        
        .main-nav {
            padding-left: 16px;
        }
        
        .nav-menu {
            gap: 16px;
            margin-left: 10px;
        }
        
        .nav-menu li a {
            padding: 12px 0;
            font-size: 14px;
        }
        
        .nav-menu li a span {
            display: none;
        }
        
        .nav-menu li a i {
            font-size: 20px;
        }
        
        .dashboard-container {
            padding: 16px;
        }
        
        .courses-container {
            grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 16px;
        }
        
        .course-card h4 {
            font-size: 14px;
        }
        
        .sidebar-button {
            width: 40px;
            height: 40px;
            font-size: 18px;
        }
        
        .alert-card {
            flex-direction: column;
            gap: 8px;
        }
        
        .alert-icon {
            font-size: 20px;
        }
    }
    
    @media (max-width: 640px) {
        .courses-container {
            grid-template-columns: repeat(2, 1fr);
        }
    }
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
        <!-- Left Sidebar -->
        <div class="left-sidebar">
            <div class="sidebar-content">
                <button class="mobile-menu-close">&times;</button>
                <div class="sidebar-top">
                    <ul class="nav-menu">
                        <li>
                            <a href="<?php echo esc_url($dashboard_link_header); ?>">
                                <i class="fa fa-book"></i>
                                <span>Meus Cursos</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url( home_url('/perfil-aluno/') ); ?>" class="active">
                                <i class="fa fa-user"></i>
                                <span>Meu Perfil</span>
                            </a>
                        </li>
                        <?php if ( get_option('tutor_ead_enable_boletim') == '1' ) : ?>
                        <li>
                            <a href="<?php echo esc_url( home_url('/meu-boletim') ); ?>">
                                <i class="fa fa-file-alt"></i>
                                <span>Boletim</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php if ( get_option('tutor_ead_enable_certificado') === '1' ) : ?>
                        <li>
                            <a href="<?php echo esc_url( home_url('/certificado-tutoread') ); ?>">
                                <i class="fa fa-certificate"></i>
                                <span>Certificados</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        <?php do_action('tutoread_aluno_dashboard_menu_items'); ?>
                    </ul>
                </div>
                <div class="sidebar-bottom">
                    <?php
                    $tutor_ead_aluno_negocio_enabled = get_option('tutor_ead_aluno_negocio_enabled');
                    if ( $tutor_ead_aluno_negocio_enabled ) {
                        $aluno_negocio_page_id = get_user_meta( $current_user->ID, 'aluno_negocio_page_id', true );
                        if ( $aluno_negocio_page_id ) {
                            $aluno_negocio_url = get_permalink( $aluno_negocio_page_id );
                            echo '<a href="' . esc_url( $aluno_negocio_url ) . '" class="sidebar-button primary" title="Acessar minha página de negócio"><i class="fa fa-store"></i></a>';
                        } else {
                            echo '<a href="' . esc_url( site_url('/cadastro-meu-negocio') ) . '" class="sidebar-button primary" title="Criar minha página de negócio"><i class="fa fa-plus"></i></a>';
                        }
                    }
                    ?>
                    <button class="sidebar-button help-button" title="Ajuda">
                        <i class="fa fa-question"></i>
                    </button>
                    <div class="help-menu">
                        <?php
                        // Exibe link de WhatsApp só se o checkbox estiver marcado E o número existir
                        if ( get_option('tutor_ead_show_support_contact') === '1' ) {
                            $whatsapp_number = trim( get_option('tutor_ead_support_whatsapp', '') );
                            if ( $whatsapp_number !== '' ) {
                                // remove tudo que não for dígito para garantir formato wa.me/5511999999999
                                $whatsapp_link = 'https://wa.me/' . preg_replace('/\D/', '', $whatsapp_number);
                                ?>
                                <a href="<?php echo esc_url( $whatsapp_link ); ?>" target="_blank" rel="noopener">
                                    <i class="fa fa-whatsapp"></i> Falar via WhatsApp
                                </a>
                                <?php
                            }
                        }
                        ?>
                        <a href="<?php echo esc_url( home_url('/ajuda') ); ?>">
                            <i class="fa fa-book"></i> Página de Ajuda
                        </a>
                        <!-- NOVO LINK -->
                        <a href="#" id="restart-tour">
                            <i class="fa fa-route"></i> Reassistir tutorial
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="main-content">
            <header class="top-header">
                <button class="mobile-nav-toggle"><i class="fa fa-bars"></i></button>
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

            <div class="dashboard-container">
                <div class="profile-header">
                    <div class="profile-avatar-wrapper">
                        <?php
                        // Se houver foto personalizada, escapa a URL. Senão, gera o placeholder.
                        if ( !empty($avatar_url) && $has_custom_avatar) {
                            $avatar_to_display = esc_url($avatar_url);
                        } else {
                            $avatar_to_display = TutorEAD\generate_placeholder_avatar($full_name);
                        }
                        ?>
                        <img src="<?php echo $avatar_to_display; ?>" alt="Foto de Perfil" class="profile-avatar" id="profile-avatar-img">
                        <label for="avatar-upload" class="edit-avatar-btn" id="edit-avatar-btn"><i class="fa fa-pencil"></i></label>
                    </div>
                    <h2 id="profile-full-name-header"><?php echo esc_html($full_name); ?></h2>
                    <p><?php echo esc_html($current_user->user_email); ?></p>
                </div>
                <div class="profile-details">
                    <div class="profile-details-header">
                        <h3>Detalhes do Perfil</h3>
                        <div class="form-actions">
                            <button type="button" id="edit-profile-btn" class="button-secondary">Editar Perfil</button>
                            <button type="button" id="save-profile-btn" class="button-primary">Salvar Alterações</button>
                            <button type="button" id="cancel-edit-btn" class="button-secondary">Cancelar</button>
                        </div>
                    </div>
                    <div id="profile-view" class="profile-view">
                        <ul>
                            <li><strong>Nome Completo:</strong><span id="view-full-name"><?php echo esc_html($full_name); ?></span></li>
                            <li><strong>Telefone:</strong><span id="view-phone-number"><?php echo esc_html($phone_number ? $phone_number : 'Não informado'); ?></span></li>
                            <li><strong>Biografia:</strong><p id="view-bio"><?php echo nl2br(esc_html($bio ? $bio : 'Nenhuma biografia disponível.')); ?></p></li>
                        </ul>
                    </div>
                    <form id="profile-form" class="profile-form">
                        <input type="file" id="avatar-upload" name="avatar" accept="image/*" style="display: none;">
                        <ul>
                            <li><strong>Nome Completo:</strong><input type="text" name="full_name" value="<?php echo esc_attr($full_name); ?>"></li>
                            <li><strong>E-mail:</strong><input type="text" value="<?php echo esc_attr($current_user->user_email); ?>" disabled></li>
                            <li><strong>Telefone:</strong><input type="text" name="phone_number" value="<?php echo esc_attr($phone_number); ?>"></li>
                            <li><strong>Biografia:</strong><textarea name="bio"><?php echo esc_textarea($bio); ?></textarea></li>
                        </ul>
                        <?php if ($has_custom_avatar): ?>
                        <div class="form-actions">
                            <button type="button" id="remove-avatar-btn" class="button-danger">Remover Foto</button>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            var userIcon = document.querySelector('.user-icon');
            var userDropdown = document.querySelector('.user-dropdown');
            var leftSidebar = document.querySelector('.left-sidebar');
            var mainWrapper = document.querySelector('.main-wrapper');

            if (leftSidebar && mainWrapper) {
                if (window.innerWidth > 768) {
                    leftSidebar.addEventListener('mouseenter', function() {
                        mainWrapper.classList.add('sidebar-expanded');
                    });
                    leftSidebar.addEventListener('mouseleave', function() {
                        mainWrapper.classList.remove('sidebar-expanded');
                    });
                }
            }

            var mobileNavToggle = document.querySelector('.mobile-nav-toggle');
            if (mobileNavToggle) {
                mobileNavToggle.addEventListener('click', function() {
                    mainWrapper.classList.toggle('mobile-menu-open');
                });
            }

            var mobileMenuClose = document.querySelector('.mobile-menu-close');
            if (mobileMenuClose) {
                mobileMenuClose.addEventListener('click', function() {
                    mainWrapper.classList.remove('mobile-menu-open');
                });
            }

            if (userIcon) {
                userIcon.addEventListener('click', function(e){
                    e.stopPropagation();
                    userDropdown.style.display = (userDropdown.style.display === 'block') ? 'none' : 'block';
                });
            }
            document.addEventListener('click', function(){
                if (userDropdown) userDropdown.style.display = 'none';
            });

            const editBtn = document.getElementById('edit-profile-btn');
            const saveBtn = document.getElementById('save-profile-btn');
            const cancelBtn = document.getElementById('cancel-edit-btn');
            const removeAvatarBtn = document.getElementById('remove-avatar-btn');
            const viewDiv = document.getElementById('profile-view');
            const form = document.getElementById('profile-form');
            const avatarImg = document.getElementById('profile-avatar-img');
            const avatarUpload = document.getElementById('avatar-upload');

            editBtn.addEventListener('click', () => {
                viewDiv.style.display = 'none';
                form.style.display = 'block';
                editBtn.style.display = 'none';
                saveBtn.style.display = 'inline-block';
                cancelBtn.style.display = 'inline-block';
                if(removeAvatarBtn) removeAvatarBtn.style.display = 'inline-block';
            });

            function switchToViewMode() {
                viewDiv.style.display = 'block';
                form.style.display = 'none';
                editBtn.style.display = 'inline-block';
                saveBtn.style.display = 'none';
                cancelBtn.style.display = 'none';
                if(removeAvatarBtn) removeAvatarBtn.style.display = 'none';
            }

            cancelBtn.addEventListener('click', () => {
                switchToViewMode();
                form.reset();
                avatarImg.src = '<?php echo esc_url($avatar_url); ?>';
            });

            avatarUpload.addEventListener('change', () => {
                const file = avatarUpload.files[0];
                if (!file) return;

                const formData = new FormData();
                formData.append('action', 'tutoread_upload_avatar');
                formData.append('nonce', '<?php echo wp_create_nonce("tutoread_upload_avatar_nonce"); ?>');
                formData.append('avatar', file);

                // Show loading indicator
                avatarImg.style.opacity = '0.5';

                fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        avatarImg.src = data.data.avatar_url;
                        alert('Foto de perfil atualizada com sucesso!');
                    } else {
                        alert('Erro ao fazer upload da foto: ' + data.data.message);
                    }
                })
                .catch(err => {
                    alert('Ocorreu um erro de conexão ao fazer upload da foto. Tente novamente.');
                })
                .finally(() => {
                    avatarImg.style.opacity = '1';
                });
            });

            form.addEventListener('submit', (e) => {
                e.preventDefault();
                const formData = new FormData(form);
                formData.append('action', 'tutoread_update_profile');
                formData.append('nonce', '<?php echo wp_create_nonce("tutoread_update_profile_nonce"); ?>');

                saveBtn.textContent = 'Salvando...';
                saveBtn.disabled = true;

                fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        // Atualiza os campos de visualização com os novos dados
                        document.getElementById('view-full-name').textContent = formData.get('full_name');
                        document.getElementById('profile-full-name-header').textContent = formData.get('full_name');
                        document.getElementById('view-phone-number').textContent = formData.get('phone_number') || 'Não informado';
                        document.getElementById('view-bio').innerHTML = (formData.get('bio') || 'Nenhuma biografia disponível.').replace(/\n/g, '<br>');
                        switchToViewMode();
                    } else {
                        alert('Erro: ' + data.data.message);
                    }
                })
                .catch(err => {
                    alert('Ocorreu um erro. Tente novamente.');
                })
                .finally(() => {
                    saveBtn.textContent = 'Salvar Alterações';
                    saveBtn.disabled = false;
                });
            });

            if(removeAvatarBtn) {
                removeAvatarBtn.addEventListener('click', () => {
                    if(!confirm('Tem certeza que deseja remover sua foto de perfil?')) return;

                    const formData = new FormData();
                    formData.append('action', 'tutoread_remove_avatar');
                    formData.append('nonce', '<?php echo wp_create_nonce("tutoread_remove_avatar_nonce"); ?>');

                    // Show loading indicator
                    avatarImg.style.opacity = '0.5';

                    fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if(data.success) {
                            avatarImg.src = data.data.default_avatar_url;
                            alert('Foto de perfil removida.');
                            // Hide remove button if no custom avatar
                            if(removeAvatarBtn) removeAvatarBtn.style.display = 'none';
                        } else {
                            alert('Erro: ' + data.data.message);
                        }
                    })
                    .catch(err => {
                        alert('Ocorreu um erro de conexão ao remover a foto. Tente novamente.');
                    })
                    .finally(() => {
                        avatarImg.style.opacity = '1';
                    });
                });
            }
        });
    </script>
</body>
</html>