<?php
/**
 * Template Name: Dashboard Aluno (ou Visualização do Curso)
 * Versão com lógica de personificação centralizada via SESSION.
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

// O resto do seu código do template continua a partir daqui...
// Ex: if (  is_user_logged_in() ) { ... }


if ( ! is_user_logged_in() ) {
    // Se o admin não estiver logado (ex: abriu em outro navegador) e houver um token, redireciona para o login de admin.
    if ( isset( $_GET['impersonate_token'] ) ) {
        auth_redirect();
    }
    wp_redirect( home_url( '/login' ) );
    exit;
}

global $wpdb;
$current_user    = wp_get_current_user();
$student_name    = $current_user->display_name;
$highlight_color = get_option( 'tutor_ead_highlight_color', '#0073aa' );

// Consulta os cursos em que o usuário está matriculado
$courses = $wpdb->get_results( $wpdb->prepare(
    "SELECT c.* 
     FROM {$wpdb->prefix}tutoread_courses c
     INNER JOIN {$wpdb->prefix}matriculas m ON c.id = m.course_id
     WHERE m.user_id = %d",
    $current_user->ID
) );

$dashboard_link_header = home_url('/dashboard-aluno');
if ($is_impersonation_mode) {
    $next_token_header = 'tutoread_impersonate_' . bin2hex(random_bytes(16));
    set_transient($next_token_header, ['admin_id' => $original_admin_id, 'student_id' => $current_user->ID], 5 * MINUTE_IN_SECONDS);
    $dashboard_link_header = add_query_arg('impersonate_token', $next_token_header, $dashboard_link_header);
}


$termo_de_uso_pendente = null;
$documento_pendente = null;
if ( get_option('tutor_ead_enable_certificado') === '1' ) {
/* --- Busca o primeiro TERMO DE USO pendente de assinatura --- */
$termo_de_uso_pendente = $wpdb->get_row($wpdb->prepare("
    SELECT d.id, d.titulo
    FROM {$wpdb->prefix}tutoread_documentos d
    LEFT JOIN {$wpdb->prefix}tutoread_assinaturas a ON d.id = a.id_documento AND a.id_aluno = %d
    WHERE a.id IS NULL
    AND d.tipo_documento = 'termo_de_uso'
    AND (d.publico_alvo = 'todos' OR (d.publico_alvo = 'especifico' AND d.id_usuario_alvo = %d))
    ORDER BY d.data_criacao ASC
    LIMIT 1
", $current_user->ID, $current_user->ID));

/* --- Busca o primeiro documento pendente (QUE NÃO SEJA TERMO DE USO) para notificação --- */
$documento_pendente = $wpdb->get_row($wpdb->prepare("
    SELECT d.id, d.titulo
    FROM {$wpdb->prefix}tutoread_documentos d
    LEFT JOIN {$wpdb->prefix}tutoread_assinaturas a ON d.id = a.id_documento AND a.id_aluno = %d
    WHERE a.id IS NULL
    AND d.tipo_documento != 'termo_de_uso'
    AND (d.publico_alvo = 'todos' OR (d.publico_alvo = 'especifico' AND d.id_usuario_alvo = %d))
    ORDER BY d.data_criacao ASC
    LIMIT 1
", $current_user->ID, $current_user->ID));
}

$assinatura_page_url = get_permalink(get_option('tutoread_assinatura_page_id'));
$notification_type = get_option('tutoread_assinatura_notification_type', 'barra'); // Pega a configuração global

// Se houver um termo de uso pendente, exibe o bloqueio e para a execução.
if ( $termo_de_uso_pendente && !$is_impersonation_mode ) {
    $assinatura_doc_url = home_url('/aluno-assinatura-documento/');
    $termo_viewer_url = add_query_arg('doc_id', $termo_de_uso_pendente->id, $assinatura_doc_url);
    ?>
    <!DOCTYPE html>
    <html lang="pt">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Termo de Uso</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
        <style>
            body { font-family: 'Inter', sans-serif; background-color: #f1f2f4; margin: 0; }
            .termo-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.6); display: flex; align-items: center; justify-content: center; z-index: 10000; }
            .termo-modal { background: #fff; border-radius: 12px; padding: 32px; max-width: 480px; text-align: center; box-shadow: 0 5px 20px rgba(0,0,0,.1); }
            .termo-modal p { color: #333; font-size: 16px; line-height: 1.6; margin: 0 0 24px 0; }
            .termo-modal p a { color: #0073aa; text-decoration: none; font-weight: 500; }
            .termo-modal p a:hover { text-decoration: underline; }
            .termo-modal button {
                background: #0085ba; color: #fff; border: none; padding: 12px 30px; border-radius: 8px;
                font-weight: 600; font-size: 16px; cursor: pointer; transition: background .2s; width: 100%;
            }
            .termo-modal button:hover { background: #0073aa; }
            .termo-modal button:disabled { background: #ccc; cursor: not-allowed; }
            .termo-modal .error-msg { color: #d9534f; font-size: 14px; margin-top: 15px; display: none; }
        </style>
    </head>
    <body>
        <div class="termo-overlay">
            <div class="termo-modal">
                <p>Ao acessar este dashboard, você concorda com os <a href="<?php echo esc_url($termo_viewer_url); ?>" target="_blank" rel="noopener">termos de uso</a>.</p>
                <button id="agree-btn" data-doc-id="<?php echo $termo_de_uso_pendente->id; ?>">Concordar e Continuar</button>
                <p class="error-msg" id="error-message"></p>
            </div>
        </div>
        <script>
            document.getElementById('agree-btn').addEventListener('click', function() {
                const btn = this;
                const docId = btn.dataset.docId;
                const errorMsg = document.getElementById('error-message');
                
                btn.disabled = true;
                btn.textContent = 'Processando...';
                errorMsg.style.display = 'none';

                const formData = new URLSearchParams();
                formData.append('action', 'aceitar_termo_de_uso');
                formData.append('id_documento', docId);
                formData.append('security', '<?php echo wp_create_nonce("termo_de_uso_nonce"); ?>');

                fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        errorMsg.textContent = 'Erro: ' + data.data.message;
                        errorMsg.style.display = 'block';
                        btn.disabled = false;
                        btn.textContent = 'Concordar e Continuar';
                    }
                })
                .catch(error => {
                    errorMsg.textContent = 'Ocorreu um erro de comunicação. Tente novamente.';
                    errorMsg.style.display = 'block';
                    btn.disabled = false;
                    btn.textContent = 'Concordar e Continuar';
                });
            });
        </script>
    </body>
    </html>
    <?php
    exit; // Impede que o resto do dashboard seja renderizado
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard do Aluno</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- Shepherd.js para Tour Guiado -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@11/dist/css/shepherd.css">
    
    <style>
    :root {
        --primary-color: <?php echo esc_attr( $highlight_color ); ?>;
        --bg-color: #ffffff;
        --text-color: #333333;
        --border-color: #e5e7eb;
        --light-gray: #f9fafb;
        --medium-gray: #e5e7eb;
        --hover-color: #f3f4f6;
        --sidebar-width: 250px;
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

    .mobile-menu-open .left-sidebar .nav-menu li a i {
        font-size: 24px; /* Increased icon size */
    }

    .mobile-menu-open .left-sidebar .nav-menu li a span {
        display: inline;
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
    
    body {
        background: var(--light-gray);
        color: var(--text-color);
    }
    
    /* Main Layout Wrapper */
    .main-wrapper {
        display: flex;
        min-height: 100vh;
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
        padding-top: 24px; /* Espaçamento no topo */
        padding-bottom: 24px; /* Espaçamento abaixo do menu */
        display: flex;
        flex-direction: column;
    }
    
    .sidebar-logo {
        text-align: center;
        margin-bottom: 24px; /* Espaçamento abaixo do logo */
    }

    .sidebar-logo img {
        max-height: 80px; /* Altura máxima do logo */
        width: auto;
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

    .sidebar-ad-space {
        width: 100%;
        aspect-ratio: 1 / 1;
        border: 2px dashed var(--border-color);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--medium-gray);
        font-size: 14px;
        font-weight: 500;
        border-radius: 8px;
        margin-top: 12px;
    }

    .sidebar-ad-space img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 6px;
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
    
    .mobile-nav-toggle {
        display: none; /* Oculta o botão de toggle no desktop */
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
    

    

    

    
    .nav-menu {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        list-style: none;
        gap: 16px;
        padding: 16px;
        margin-left: 0;
    }
    
    .nav-menu li a {
        display: flex;
        flex-direction: row;
        align-items: center;
        justify-content: flex-start;
        gap: 12px;
        text-decoration: none;
        color: var(--text-color);
        padding: 12px 16px;
        font-weight: 500;
        border-radius: 8px;
        width: 100%;
        height: auto;
        transition: all 0.2s;
    }

    .nav-menu li a span {
        display: inline;
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
        min-height: 600px;
    }

    .top-banner-ad-space {
        width: 100%;
        margin-bottom: 24px;
        overflow: hidden; /* Ensure image doesn't overflow */
    }

    .top-banner-ad-space img {
        width: 100%;
        height: auto;
        max-height: 320px; /* Desktop max height */
        object-fit: cover; /* Ensure image covers the area */
        border-radius: 8px;
    }

    @media (max-width: 768px) {
        .top-banner-ad-space img {
            max-height: 350px; /* Mobile max height */
        }
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
        max-width: 700px;
        margin: 0 0 32px 0; /* Left-aligned with bottom margin */
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
        max-width: 700px;
        margin: 0; /* Left-aligned */
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

    @media (max-width: 768px) {
        .left-sidebar, .sidebar-placeholder {
            display: none;
        }
        .main-content {
            margin-left: 0;
        }
        .mobile-nav-toggle {
            display: block;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: var(--text-color);
        }
        header.top-header .logo {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
        }
        .nav-menu {
            align-items: center;
            padding: 16px 0;
        }
        .nav-menu li a {
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 4px;
            width: 60px;
            height: 60px;
            padding: 12px 8px 4px 8px;
        }
        .nav-menu li a span {
            display: none;
        }
    }
</style>

    <?php wp_head(); ?>
</head>
<body>
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
    <?php
    /* -------------------------------------------------
     * BLOCO DE ALERTAS / AVISOS COM CONTROLE DE LIMITE
     * ------------------------------------------------*/
    $current_user_id   = get_current_user_id();
    
    /* cursos do aluno ---------------------------------------------------------*/
    $cursos_aluno_arr  = $wpdb->get_col(
        $wpdb->prepare(
            "SELECT course_id FROM {$wpdb->prefix}matriculas WHERE user_id = %d",
            $current_user_id
        )
    );
    $cursos_str = empty($cursos_aluno_arr) ? '0' : implode(',', array_map('intval', $cursos_aluno_arr));
    
    /* -----------------------------------------------------------
     * Busca alertas/avisos ainda dentro do limite de exibições
     * ----------------------------------------------------------*/
    $table_alertas = $wpdb->prefix . 'tutoread_alertas';
    $table_views   = $wpdb->prefix . 'tutoread_alertas_views';
    
    $sql = "
    SELECT a.id, a.mensagem, a.tipo, a.limite_exibicoes,
           COALESCE(v.views,0) AS ja_visto
    FROM   {$table_alertas} a
    LEFT JOIN {$table_views} v
           ON v.alert_id = a.id AND v.user_id = %d
    WHERE  a.status = 'ativo'
      AND  a.local_exibicao = 'dashboard'
      AND (
            a.user_id IS NULL OR a.user_id = '' OR a.user_id = 0
         OR FIND_IN_SET(%d, a.user_id)
      )
      AND (
            a.course_id IS NULL OR a.course_id = '' 
         OR FIND_IN_SET(a.course_id, '{$cursos_str}')
      )
      AND (
            a.limite_exibicoes IS NULL      /* ilimitado */
         OR COALESCE(v.views,0) < a.limite_exibicoes    /* ainda pode aparecer */
      )
    ORDER BY a.data_criacao DESC
    ";
    $rows = $wpdb->get_results( $wpdb->prepare($sql, $current_user_id, $current_user_id) );
    
    $avisos  = [];   // cards
    $alertas = [];   // pop-ups
    foreach ($rows as $row) {
        if ($row->tipo === 'aviso'  && count($avisos)  < 2) $avisos[]  = $row;
        if ($row->tipo === 'alerta' && count($alertas) < 2) $alertas[] = $row;
    }
    ?>
    
    <?php if ($documento_pendente && $notification_type === 'popup') : ?>
        <div class="alert-modal-overlay" id="alertModal" data-alert-id="<?php echo $documento_pendente->id; ?>">
            <div class="alert-modal">
                <i class="fa fa-exclamation-triangle alert-modal-icon"></i>
                <p>Você tem um documento pendente de assinatura:</p>
                <p><strong><?php echo esc_html($documento_pendente->titulo); ?></strong></p>
                <a href="<?php echo esc_url( home_url('/meus-documentos-tutoread/') ); ?>" class="alert-modal-close">Assinar Agora</a>
            </div>
        </div>
    <?php endif; ?>

    <?php if ( $alertas ) : ?>
        <div class="alert-modal-overlay" id="alertModal" data-alert-id="<?php echo $alertas[0]->id; ?>">
            <div class="alert-modal">
                <i class="fa fa-exclamation-triangle alert-modal-icon"></i>
                <?php foreach ( $alertas as $al ) : ?>
                    <p><?php echo esc_html($al->mensagem); ?></p>
                <?php endforeach; ?>
                <button class="alert-modal-close" aria-label="Fechar">OK</button>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Main Layout Wrapper -->
    <div class="main-wrapper">
        <!-- Left Sidebar -->
        <div class="left-sidebar">
            <div class="sidebar-content">
                <button class="mobile-menu-close">&times;</button>
                <div class="sidebar-top">
                    <div class="sidebar-logo">
                        <a href="<?php echo esc_url($dashboard_link_header); ?>">
                            <?php
                            $plugin_logo = get_option( 'tutor_ead_course_logo' );
                            if ( $plugin_logo ) {
                                echo '<img src="' . esc_url( $plugin_logo ) . '" alt="Logo do Curso">';
                            } else {
                                echo 'TutorEAD';
                            }
                            ?>
                        </a>
                    </div>
                    <ul class="nav-menu">
                        <li>
                            <a href="<?php echo esc_url($dashboard_link_header); ?>" class="active">
                                <i class="fa fa-book"></i>
                                <span>Meus Cursos</span>
                            </a>
                        </li>
                        <li>
                            <a href="<?php echo esc_url( home_url('/perfil-aluno/') ); ?>">
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
                <?php
                $ad = \TutorEAD\Admin\AdvertisementManager::get_active_advertisement_for_location('dashboard_aluno_sidebar');
                ?>
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
                    <div class="sidebar-ad-space" <?php if ($ad) echo 'data-ad-id="' . esc_attr($ad->id) . '"'; ?>>
                        <?php if ($ad) : ?>
                            <a href="<?php echo esc_url($ad->link_url); ?>" target="_blank" class="ad-click-trigger">
                                <img src="<?php echo esc_url($ad->image_url); ?>" alt="Publicidade" style="width: 100%; height: 100%; object-fit: cover; border-radius: 6px;">
                            </a>
                        <?php else : ?>
                            <!-- Ad Space -->
                        <?php endif; ?>
                    </div>
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
        

        
        <!-- Main Content Area -->
        <div class="main-content">
            <?php if ($documento_pendente && $notification_type === 'barra') : ?>
                <div class="top-notification-bar" data-alert-id="<?php echo $documento_pendente->id; ?>">
                    Você tem um documento pendente de assinatura: <strong><?php echo esc_html($documento_pendente->titulo); ?></strong>. <a href="<?php echo esc_url( home_url('/meus-documentos-tutoread/') ); ?>">Clique aqui para assinar.</a>
                </div>
            <?php endif; ?>

            <!-- Header -->
            <header class="top-header">
                <button class="mobile-nav-toggle"><i class="fa fa-bars"></i></button>
                <div class="logo">
                    <a href="<?php echo esc_url($dashboard_link_header); ?>">
                        <!-- Logo/Text removed as per user request -->
                    </a>
                </div>
                <div class="user-menu">
                    <div class="user-icon"><i class="fa fa-user"></i></div>
                    <div class="user-dropdown">
                        <a href="<?php echo esc_url($dashboard_link_header); ?>">
                            <i class="fa fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a href="<?php echo esc_url( home_url( '/perfil-aluno' ) ); ?>">
                            <i class="fa fa-user"></i> Meu Perfil
                        </a>
                        <a href="<?php echo esc_url( wp_logout_url( home_url() ) ); ?>" class="logout">
                            <i class="fa fa-sign-out-alt"></i> Sair
                        </a>
                    </div>
                </div>
            </header>
            

            
            <!-- Dashboard Container -->
            <div class="dashboard-container">
                <?php
                $top_banner_ad = \TutorEAD\Admin\AdvertisementManager::get_active_advertisement_for_location('dashboard_aluno_top_banner');
                if ($top_banner_ad) :
                ?>
                    <div class="top-banner-ad-space" data-ad-id="<?php echo esc_attr($top_banner_ad->id); ?>">
                        <a href="<?php echo esc_url($top_banner_ad->link_url); ?>" target="_blank" class="ad-click-trigger">
                            <img src="<?php echo esc_url($top_banner_ad->image_url); ?>" alt="Publicidade">
                        </a>
                    </div>
                <?php endif; ?>

                <?php if ( $avisos ) : ?>
                    <div class="avisos-wrapper">
                        <?php foreach ( $avisos as $a ) : ?>
                            <div class="alert-card" data-alert-id="<?php echo $a->id; ?>">
                                <i class="fa fa-exclamation-triangle alert-icon"></i>
                                <div class="alert-content"><p><?php echo esc_html($a->mensagem); ?></p></div>
                                <button class="alert-close" aria-label="Fechar">&times;</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Profile Section -->
                <div class="profile-section">
                    <?php
                    // Busca a foto de perfil da tabela personalizada
                    $user_info_table = $wpdb->prefix . 'tutoread_user_info';
                    $profile_photo_url = $wpdb->get_var($wpdb->prepare(
                        "SELECT profile_photo_url FROM {$user_info_table} WHERE user_id = %d",
                        $current_user->ID
                    ));

                    // Se houver foto personalizada, escapa a URL. Senão, gera o placeholder.
                    if ( !empty($profile_photo_url) ) {
                        $avatar_to_display = esc_url($profile_photo_url);
                    } else {
                        $avatar_to_display = TutorEAD\generate_placeholder_avatar($student_name);
                    }
                    ?>
                    <img src="<?php echo $avatar_to_display; ?>" alt="Perfil">
                    <div class="profile-info">
                        <h3><?php echo esc_html( $student_name ); ?></h3>
                        <p>Aluno</p>
                    </div>
                </div>
                
                <!-- Content -->
                <div class="content">
                    <h2>Meus Cursos</h2>
                    
                    <div class="courses-container">
                        <?php
                        if ( $courses ) :
                            foreach ( $courses as $course ) :
                                // Calcula o progresso do curso
                                $total_lessons = $wpdb->get_var( $wpdb->prepare(
                                    "SELECT COUNT(*) 
                                     FROM {$wpdb->prefix}tutoread_lessons l 
                                     INNER JOIN {$wpdb->prefix}tutoread_modules m 
                                     ON l.module_id = m.id 
                                     WHERE m.course_id = %d AND IFNULL(l.video_url, '') <> ''",
                                    $course->id
                                ) );
                                
                                $completed_lessons = $wpdb->get_var( $wpdb->prepare(
                                    "SELECT COUNT(*) 
                                     FROM {$wpdb->prefix}progresso_aulas pa 
                                     WHERE pa.aluno_id = %d 
                                       AND pa.aula_id IN (
                                           SELECT l.id FROM {$wpdb->prefix}tutoread_lessons l 
                                           INNER JOIN {$wpdb->prefix}tutoread_modules m 
                                           ON l.module_id = m.id 
                                           WHERE m.course_id = %d AND IFNULL(l.video_url, '') <> ''
                                       )
                                       AND pa.status = 'concluido'",
                                    $current_user->ID, $course->id
                                ) );
                                
                                $progress = ($total_lessons > 0) ? round(($completed_lessons / $total_lessons) * 100) : 0;
                                
                                // Pega a última aula assistida
                                $last_lesson_id = get_user_meta( $current_user->ID, "_tutoread_last_lesson_{$course->id}", true );
                                $last_lesson_title = '';
                                $next_lesson_id = null; // Inicializa a variável
                                
                                if ( $last_lesson_id ) {
                                    $last_lesson_title = $wpdb->get_var( $wpdb->prepare(
                                        "SELECT title FROM {$wpdb->prefix}tutoread_lessons WHERE id = %d",
                                        $last_lesson_id
                                    ) );
                                }
                                
                                // Define a label do botão e o link do curso
                                $button_label = ( $progress > 0 && $progress < 100 ) ? 'Continuar Curso' : 'Assistir Curso';
                                $course_link_base = home_url( '/visualizar-curso/?course_id=' . $course->id );

                                // A sessão já cuida do modo de preview, então o link não precisa de token.
$course_link = $course_link_base;

// ... (a lógica para adicionar lesson_id se houver progresso pode continuar)
if ( $progress > 0 && $progress < 100 ) {
    // ...
    if ( $next_lesson_id ) {
        $course_link = home_url( '/visualizar-curso/?course_id=' . $course->id . '&lesson_id=' . $next_lesson_id );
    }
}
                                ?>
                                <div class="course-card">
                                    <?php if ( ! empty( $course->capa_img ) ) : ?>
                                        <img src="<?php echo esc_url( $course->capa_img ); ?>" alt="<?php echo esc_attr( $course->title ); ?>">
                                    <?php else : ?>
                                        <img src="https://via.placeholder.com/240x160?text=Capa+do+Curso" alt="Capa do Curso">
                                    <?php endif; ?>
                                    
                                    <div class="course-card-content">
                                        <h4><?php echo esc_html( $course->title ); ?></h4>
                                        
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo $progress; ?>%"></div>
                                        </div>
                                        
                                        <p class="progress-text"><?php echo $progress; ?>% Concluído</p>
                                        
                                        <?php if ( $last_lesson_title && $progress > 0 && $progress < 100 ) : ?>
                                            <p class="last-lesson-text" title="<?php echo esc_attr( $last_lesson_title ); ?>">
                                                Última aula: <?php echo esc_html( mb_strimwidth( $last_lesson_title, 0, 30, '...' ) ); ?>
                                            </p>
                                        <?php endif; ?>
                                        
                                        <a href="<?php echo esc_url( $course_link ); ?>" class="course-button">
                                            <?php echo esc_html( $button_label ); ?>
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach;
                        else :
                            echo '<p>Nenhum curso encontrado.</p>';
                        endif;
                        ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($documento_pendente && $notification_type === 'card') : ?>
        <div class="alert-card document-pending-card-notification" data-alert-id="<?php echo $documento_pendente->id; ?>">
            <i class="fa fa-exclamation-triangle alert-icon"></i>
            <div class="alert-content">
                <p>Você tem um documento pendente de assinatura: <strong><?php echo esc_html($documento_pendente->titulo); ?></strong></p>
                <p><a href="<?php echo esc_url( home_url('/meus-documentos-tutoread/') ); ?>">Clique aqui para assinar.</a></p>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Shepherd.js - Carregado no final para evitar conflitos -->
    <script src="https://cdn.jsdelivr.net/npm/shepherd.js@11/dist/js/shepherd.min.js"></script>
    
    <script>
        // Script para o dropdown do menu de usuário e outros controles
        document.addEventListener('DOMContentLoaded', function(){
            var userIcon = document.querySelector('.user-icon');
            var userDropdown = document.querySelector('.user-dropdown');
            var helpButton = document.querySelector('.help-button');
            var helpMenu = document.querySelector('.help-menu');
            var leftSidebar = document.querySelector('.left-sidebar');
            var mainWrapper = document.querySelector('.main-wrapper');


            var mobileNavToggle = document.querySelector('.mobile-nav-toggle');
            if (mobileNavToggle) {
                mobileNavToggle.addEventListener('click', function() {
                    document.body.classList.toggle('mobile-menu-open');
                });
            }

            var mobileMenuClose = document.querySelector('.mobile-menu-close');
            if (mobileMenuClose) {
                mobileMenuClose.addEventListener('click', function() {
                    document.body.classList.remove('mobile-menu-open');
                });
            }
            
            if (userIcon) {
                userIcon.addEventListener('click', function(e){
                    e.stopPropagation();
                    userDropdown.style.display = (userDropdown.style.display === 'block') ? 'none' : 'block';
                });
            }
            
            // Atualizado com ajuste de posição para o help menu
            if (helpButton) {
                helpButton.addEventListener('click', function(e){
                    e.stopPropagation();
                    helpMenu.classList.toggle('active');
                    
                    // Se o menu está ativo, verifica se precisa ajustar a posição
                    if (helpMenu.classList.contains('active')) {
                        const rect = helpMenu.getBoundingClientRect();
                        const overflow = rect.bottom - window.innerHeight + 8; // 8px de folga
                        
                        if (overflow > 0) {
                            helpMenu.style.transform = `translateY(-${overflow}px)`;
                        } else {
                            helpMenu.style.transform = ''; // posi��ão normal
                        }
                    }
                });
            }
            
            document.addEventListener('click', function(){
                if (userDropdown) userDropdown.style.display = 'none';
                if (helpMenu) helpMenu.classList.remove('active');
            });
            
            /* envia para o servidor que o alerta foi visto */
            function markAlertViewed(id){
                fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
                    method: 'POST',
                    headers:{'Content-Type':'application/x-www-form-urlencoded'},
                    body:'action=tutor_ead_alert_viewed&alert_id='+id

                });
            }
            
            /* cards (avisos) */
            document.querySelectorAll('.alert-close').forEach(btn=>{
                btn.addEventListener('click', ()=>{
                    const wrapper = btn.closest('[data-alert-id]');
                    if(wrapper) {
                        markAlertViewed(wrapper.dataset.alertId);
                        wrapper.style.display='none';
                    }
                });
            });
            
            /* modal (alertas) */
            const modal = document.querySelector('#alertModal');
            if(modal){
                modal.querySelector('.alert-modal-close').addEventListener('click', ()=>{
                    markAlertViewed(modal.dataset.alertId);
                    modal.style.display='none';
                });
            }
            
            // Novo código para "Reassistir tutorial"
            const restartButton = document.getElementById('restart-tour');
            if (restartButton) {
                restartButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    localStorage.removeItem('tutorEad_dashboard_tour_v1');
                    if (helpMenu) helpMenu.classList.remove('active'); // fecha o popup
                    initTour(); // inicia novamente o tour
                });
            }
        });
        
        // Tour Guiado com Shepherd.js - em função separada
        function initTour() {
            // Verifica se o usuário já viu o tour
            const TOUR_KEY = 'tutorEad_dashboard_tour_v1';
            if (localStorage.getItem(TOUR_KEY) === 'done') return;
            
            // Verifica se o Shepherd está carregado
            if (typeof Shepherd === 'undefined') {
                console.error('Shepherd.js não está carregado');
                return;
            }
            
            // Configuração do tour
            const tour = new Shepherd.Tour({
                defaultStepOptions: {
                    classes: 'shepherd-theme-custom',
                    scrollTo: { behavior: 'smooth', block: 'center' },
                    cancelIcon: { enabled: false },
                    modalOverlayOpeningRadius: 4,
                    modalOverlayOpeningPadding: 4
                },
                useModalOverlay: true,
                exitOnEsc: false,
                keyboardNavigation: false,
                modalOverlayOpeningOpacity: 0.5 // Define opacidade do overlay para 50%
            });
            
            // Remove qualquer possibilidade de fechar clicando fora
            tour.on('show', function() {
                var overlay = document.querySelector('.shepherd-modal-overlay-container');
                if (overlay) {
                    overlay.addEventListener('click', function(e) {
                        e.stopPropagation();
                        e.preventDefault();
                    });
                }
            });
            
            // Verifica se os elementos existem antes de adicionar os passos
            if (document.querySelector('.nav-menu')) {
                tour.addStep({
                    id: 'menu',
                    text: 'Use o menu para voltar à <b>Home</b> ou acessar <b>Meus Cursos</b>.',
                    attachTo: { element: '.nav-menu', on: 'bottom' },
                    buttons: [{ text: 'Próximo', action: tour.next }]
                });
            }
            
            if (document.querySelector('.profile-section')) {
                tour.addStep({
                    id: 'profile-bar',
                    text: 'Aqui ficam seu nome e foto de perfil.',
                    attachTo: { element: '.profile-section', on: 'bottom' },
                    buttons: [
                        { text: 'Voltar', action: tour.back },
                        { text: 'Próximo', action: tour.next }
                    ]
                });
            }
            
            if (document.querySelector('.courses-container')) {
                tour.addStep({
                    id: 'cards-area',
                    text: 'Estes são os cartões dos cursos em que você está matriculado.',
                    attachTo: { element: '.courses-container', on: 'top' },
                    buttons: [
                        { text: 'Voltar', action: tour.back },
                        { text: 'Próximo', action: tour.next }
                    ]
                });
            }
            
            if (document.querySelector('.course-card:first-child .course-button')) {
                tour.addStep({
                    id: 'assistir-btn',
                    text: 'Clique em <b>Assistir</b> (ou <b>Continuar</b>) para entrar no curso.',
                    attachTo: { element: '.course-card:first-child .course-button', on: 'top' },
                    buttons: [
                        { text: 'Voltar', action: tour.back },
                        { text: 'Próximo', action: tour.next }
                    ]
                });
            }
            
            if (document.querySelector('.course-card:first-child .progress-bar')) {
                tour.addStep({
                    id: 'progress-bar',
                    text: 'Esta barra mostra quantos por cento do curso você já concluiu.',
                    attachTo: { element: '.course-card:first-child .progress-bar', on: 'top' },
                    buttons: [
                        { text: 'Voltar', action: tour.back },
                        { text: 'Próximo', action: tour.next }
                    ]
                });
            }
            
            if (document.querySelector('.help-button')) {
                tour.addStep({
                    id: 'help-btn',
                    text: 'Precisa de suporte? Use o botão <b>Ajuda</b> aqui.',
                    attachTo: { element: '.help-button', on: 'right' },
                    buttons: [
                        { text: 'Voltar', action: tour.back },
                        { text: 'Próximo', action: tour.next }
                    ]
                });
            }
            
            if (document.querySelector('.user-icon')) {
                tour.addStep({
                    id: 'user-icon',
                    text: 'No ícone de perfil você encontra a opção de sair da plataforma.',
                    attachTo: { element: '.user-icon', on: 'left' },
                    buttons: [
                        { text: 'Voltar', action: tour.back },
                        {
                            text: 'Concluir',
                            action() {
                                localStorage.setItem(TOUR_KEY, 'done');
                                tour.complete();
                            }
                        }
                    ]
                });
            }
            
            // Inicia o tour apenas se tiver pelo menos um passo
            if (tour.steps.length > 0) {
                tour.start();
            }
        }
        
        // Aguarda o carregamento completo da página e do Shepherd
        window.addEventListener('load', function() {
            setTimeout(initTour, 100); // Pequeno delay para garantir que tudo está carregado
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const adSpaces = document.querySelectorAll('.sidebar-ad-space, .top-banner-ad-space');
            
            adSpaces.forEach(adSpace => {
                if (adSpace && adSpace.dataset.adId) {
                    const adId = adSpace.dataset.adId;
                    const viewNonce = '<?php echo wp_create_nonce("track_ad_view_nonce"); ?>';
                    const clickNonce = '<?php echo wp_create_nonce("track_ad_click_nonce"); ?>';

                    const observer = new IntersectionObserver((entries) => {
                        if (entries[0].isIntersecting) {
                            const formData = new FormData();
                            formData.append('action', 'track_ad_view');
                            formData.append('ad_id', adId);
                            formData.append('nonce', viewNonce);

                            fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                                method: 'POST',
                                body: formData
                            });

                            observer.disconnect();
                        }
                    });

                    observer.observe(adSpace);

                    const adLink = adSpace.querySelector('.ad-click-trigger');
                    if (adLink) {
                        adLink.addEventListener('click', function(e) {
                            e.preventDefault();

                            const formData = new FormData();
                            formData.append('action', 'track_ad_click');
                            formData.append('ad_id', adId);
                            formData.append('nonce', clickNonce);

                            fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    window.open(data.data.redirect_url, '_blank');
                                }
                            });
                        });
                    }
                }
            });
        });
    </script>
    <?php wp_footer(); // Adicionado para garantir que os scripts enfileirados sejam carregados ?>
</body>
</html>