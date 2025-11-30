<?php
/**
 * Template Name: Painel Editor de Cursos TutorEAD
 * Template Post Type: page
 */

// Controle de Acesso
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

$user = wp_get_current_user();
// Permite acesso ao editor de cursos, administrador do tutor e administrador geral.
$allowed_roles = ['tutor_course_editor', 'tutor_admin', 'administrator'];

if (!array_intersect($allowed_roles, $user->roles)) {
    wp_redirect(home_url('/?error=access_denied'));
    exit;
}

// Carrega o cabeçalho personalizado
require_once TUTOR_EAD_PATH . 'templates/partials/header-dashboard.php';
?>

<div class="dashboard-container course-editor-dashboard">
    <div class="dashboard-header-bar">
        <h2>Painel do Editor de Cursos</h2>
        <p>Bem-vindo, <?php echo esc_html($user->display_name); ?>!</p>
    </div>

    <!-- Conteúdo de Gerenciamento de Cursos -->
    <div id="cursos" class="tab-content active" style="display: block;">
        <h3>Gerenciar Cursos</h3>
        <div class="actions-bar">
            <input type="text" id="search-courses" placeholder="Buscar curso por título...">
            <button id="add-new-course" class="button-primary">Adicionar Novo Curso</button>
        </div>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Título do Curso</th>
                    <th>Professor</th>
                    <th>Alunos Matriculados</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="courses-table-body">
                <!-- Os dados dos cursos serão inseridos aqui via JavaScript -->
                 <tr><td colspan="4">Carregando...</td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para Adicionar/Editar -->
<div id="entity-modal" class="modal-overlay" style="display:none;">
    <div class="modal-content">
        <h3 id="modal-title"></h3>
        <form id="modal-form">
            <!-- Campos do formulário serão inseridos aqui via JavaScript -->
        </form>
        <button class="close-modal-button">&times;</button>
    </div>
</div>


<?php
// Carrega o rodapé personalizado
require_once TUTOR_EAD_PATH . 'templates/partials/footer-dashboard.php';
?>


