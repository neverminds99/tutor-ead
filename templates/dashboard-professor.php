<?php
/**
 * Template Name: Painel Professor TutorEAD
 * Template Post Type: page
 */

// Controle de Acesso
if (!is_user_logged_in()) {
    wp_redirect(wp_login_url(get_permalink()));
    exit;
}

$user = wp_get_current_user();
$allowed_roles = ['tutor_professor'];

if (!array_intersect($allowed_roles, $user->roles)) {
    wp_redirect(home_url('/?error=access_denied'));
    exit;
}

// Carrega o cabeçalho personalizado
require_once TUTOR_EAD_PATH . 'templates/partials/header-dashboard.php';
?>

<div class="dashboard-container professor-dashboard">
    <div class="dashboard-header-bar">
        <h2>Painel do Professor</h2>
        <p>Bem-vindo(a), Professor(a) <?php echo esc_html($user->display_name); ?>!</p>
    </div>

    <!-- Abas de Navegação -->
    <div class="dashboard-tabs">
        <button class="tab-link active" data-tab="meus-cursos">Meus Cursos</button>
        <button class="tab-link" data-tab="meus-alunos">Meus Alunos</button>
        <button class="tab-link" data-tab="boletim">Boletim</button>
    </div>

    <!-- Conteúdo das Abas -->
    <div id="meus-cursos" class="tab-content active">
        <h3>Cursos que você leciona</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Título do Curso</th>
                    <th>Alunos Matriculados</th>
                    <th>Ações</th>
                </tr>
            </thead>
            <tbody id="professor-courses-table-body">
                <!-- Os dados dos cursos serão inseridos aqui via JavaScript -->
                <tr><td colspan="3">Carregando...</td></tr>
            </tbody>
        </table>
    </div>

    <div id="meus-alunos" class="tab-content">
        <h3>Seus Alunos</h3>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Nome do Aluno</th>
                    <th>E-mail</th>
                    <th>Curso(s) Matriculado(s)</th>
                </tr>
            </thead>
            <tbody id="professor-students-table-body">
                <!-- Os dados dos alunos serão inseridos aqui via JavaScript -->
                 <tr><td colspan="3">Carregando...</td></tr>
            </tbody>
        </table>
    </div>

    <div id="boletim" class="tab-content">
        <h3>Lançamento de Notas</h3>
        <div class="boletim-grid">
            <div class="boletim-form-container">
                <h4>Lançar Nova Nota</h4>
                <form id="boletim-form">
                    <p>
                        <label for="boletim-course">Curso:</label>
                        <select id="boletim-course" name="course_id" required>
                            <option value="">Selecione um curso...</option>
                        </select>
                    </p>
                    <p>
                        <label for="boletim-student">Aluno:</label>
                        <select id="boletim-student" name="aluno_id" required disabled>
                            <option value="">Selecione um curso primeiro...</option>
                        </select>
                    </p>
                    <p>
                        <label for="boletim-activity">Atividade:</label>
                        <select id="boletim-activity" name="atividade_id" required disabled>
                            <option value="">Selecione um curso primeiro...</option>
                        </select>
                    </p>
                    <p>
                        <label for="boletim-nota">Nota:</label>
                        <input type="number" id="boletim-nota" name="nota" step="0.1" min="0" required>
                    </p>
                    <p>
                        <label for="boletim-feedback">Feedback (opcional):</label>
                        <textarea id="boletim-feedback" name="feedback" rows="3"></textarea>
                    </p>
                    <p>
                        <button type="submit" class="button-primary">Salvar Nota</button>
                    </p>
                </form>
            </div>
            <div class="boletim-history-container">
                <h4>Histórico de Lançamentos</h4>
                <div class="table-wrapper">
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th>Aluno</th>
                                <th>Curso</th>
                                <th>Atividade</th>
                                <th>Nota</th>
                                <th>Data</th>
                            </tr>
                        </thead>
                        <tbody id="boletim-history-table-body">
                            <!-- Histórico de notas será inserido aqui -->
                            <tr><td colspan="5">Carregando histórico...</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Carrega o rodapé personalizado
require_once TUTOR_EAD_PATH . 'templates/partials/footer-dashboard.php';
