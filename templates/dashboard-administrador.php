<?php
/**
 * Template Name: Painel Administrador TutorEAD
 * Template Post Type: page
 */

// Controle de Acesso
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

$user = wp_get_current_user();
$allowed_roles = ['administrator', 'tutor_admin'];

if (!array_intersect($allowed_roles, $user->roles)) {
    wp_redirect(home_url('/?error=access_denied'));
    exit;
}

// Carrega o cabeçalho personalizado
require_once TUTOR_EAD_PATH . 'templates/partials/header-dashboard.php';
?>

<div class="dashboard-container admin-dashboard">
    <div class="dashboard-header-bar">
        <h2>Painel do Administrador</h2>
        <p>Bem-vindo, <?php echo esc_html($user->display_name); ?>!</p>
    </div>

    <!-- Abas de Navegação -->
    <div class="dashboard-tabs">
        <button class="tab-link active" data-tab="alunos">Gerenciar Alunos</button>
        <button class="tab-link" data-tab="cursos">Gerenciar Cursos</button>
        <button class="tab-link" data-tab="professores">Gerenciar Professores</button>
    </div>

    <!-- Conteúdo das Abas -->
    <div id="alunos" class="tab-content active">
        <h3>Alunos</h3>
        <div class="actions-bar">
            <input type="text" id="search-students" placeholder="Buscar aluno por nome ou e-mail...">
            <button id="add-new-student" class="button-primary">Adicionar Novo Aluno</button>
        </div>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Aluno</th>
                    <th>E-mail</th>
                    <th>Data de Cadastro</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="students-table-body">
                <!-- Os dados dos alunos serão inseridos aqui via JavaScript -->
                <tr><td colspan="4">Carregando...</td></tr>
            </tbody>
        </table>
    </div>

    <div id="cursos" class="tab-content">
        <h3>Cursos</h3>
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

    <div id="professores" class="tab-content">
        <h3>Professores</h3>
        <div class="actions-bar">
            <input type="text" id="search-professors" placeholder="Buscar professor por nome ou e-mail...">
            <button id="add-new-professor" class="button-primary">Adicionar Novo Professor</button>
        </div>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>E-mail</th>
                    <th>Cursos Lecionados</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="professors-table-body">
                <!-- Os dados dos professores serão inseridos aqui via JavaScript -->
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
