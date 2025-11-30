<?php



namespace TutorEAD\admin;



defined('ABSPATH') || exit;



/**

 * Classe DashboardManager

 * 

 * Gerencia e renderiza os dashboards para Administradores, Professores e Alunos.

 * 

 * Funcionalidades:

 * - Exibe estatísticas gerais do sistema (alunos, cursos, atividades, módulos, aulas e matrículas).

 * - Renderiza gráficos utilizando Chart.js, incluindo:

 *      * Alunos registrados ao longo do tempo.

 *      * Matrículas por Curso.

 */

class DashboardManager {



    /**

     * Renderiza o dashboard com base no role do usuário.

     */

    public static function render_dashboard() {

        $current_user = wp_get_current_user();



        if (empty($current_user->roles)) {

            echo '<h1>' . __('Acesso não autorizado: sem roles atribuídas', 'tutor-ead') . '</h1>';

            return;

        }



        $roles = $current_user->roles;



        if (in_array('tutor_admin', $roles) || in_array('administrator', $roles)) {

            self::dashboard_admin();

        } elseif (in_array('tutor_professor', $roles)) {

            self::dashboard_professor();

        } elseif (in_array('tutor_aluno', $roles)) {

            self::dashboard_aluno();

        } else {

            echo '<h1>' . __('Acesso não autorizado', 'tutor-ead') . '</h1>';

        }

    }



    /**
 * Renderiza o dashboard para Administradores.
 */
public static function dashboard_admin() {
    global $wpdb;
    // Define as tabelas usando o prefixo do WordPress
    $table_users    = $wpdb->prefix . 'users';
    $table_usermeta = $wpdb->prefix . 'usermeta';
    
    // Define a meta key de capabilities dinamicamente
    $capabilities_meta_key = $wpdb->prefix . 'capabilities';
    
    // Pega a cor de destaque das opções
    $highlight_color = get_option('tutor_ead_highlight_color', '#0073aa');

    // Conta os alunos com role tutor_aluno
    $total_alunos = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_users u
             INNER JOIN $table_usermeta m ON u.ID = m.user_id
             WHERE m.meta_key = %s
               AND m.meta_value LIKE %s",
            $capabilities_meta_key,
            '%"tutor_aluno"%'
        )
    );

    $total_cursos     = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tutoread_courses");
    $total_atividades = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}atividades");
    $total_modulos    = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tutoread_modules");
    $total_aulas      = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}tutoread_lessons");
    $total_matriculas = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}matriculas WHERE role = 'aluno'");

    $alunos_por_mes = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT DATE_FORMAT(u.user_registered, '%%Y-%%m') AS mes, COUNT(*) AS total
             FROM $table_users u
             INNER JOIN $table_usermeta m ON u.ID = m.user_id
             WHERE m.meta_key = %s
               AND m.meta_value LIKE %s
             GROUP BY mes
             ORDER BY mes ASC",
            $capabilities_meta_key,
            '%"tutor_aluno"%'
        )
    );
    $meses  = [];
    $totais = [];
    foreach ($alunos_por_mes as $registro) {
        $meses[]  = $registro->mes;
        $totais[] = $registro->total;
    }

    $cursos_matriculas = $wpdb->get_results(
        "SELECT c.title as curso, COUNT(m.id) as total
         FROM {$wpdb->prefix}tutoread_courses c
         LEFT JOIN {$wpdb->prefix}matriculas m ON m.course_id = c.id AND m.role = 'aluno'
         GROUP BY c.id
         ORDER BY total DESC"
    );
    $curso_labels = [];
    $curso_totals = [];
    foreach ($cursos_matriculas as $registro) {
        $curso_labels[] = $registro->curso;
        $curso_totals[] = $registro->total;
    }

    // Estilos modernos e limpos
    echo '<style>
        .tutor-ead-dashboard {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            background: #f3f4f6;
            margin: -20px;
            padding: 32px;
            min-height: 100vh;
        }
        
        .tutor-ead-dashboard * {
            box-sizing: border-box;
        }
        
        .dashboard-header {
            margin-bottom: 32px;
        }
        
        .dashboard-title {
            font-size: 32px;
            font-weight: 600;
            color: #1f2937;
            margin: 0 0 8px 0;
        }
        
        .dashboard-subtitle {
            color: #6b7280;
            font-size: 16px;
            margin: 0;
        }
        
        .section-title {
            font-size: 20px;
            font-weight: 600;
            color: #1f2937;
            margin: 32px 0 16px 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        
        .section-title:after {
            content: "";
            flex: 1;
            height: 1px;
            background: #e5e7eb;
        }
        
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 32px;
        }
        
        .action-card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            text-decoration: none;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 16px;
            color: #374151;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        
        .action-card:hover {
            border-color: ' . $highlight_color . ';
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transform: translateY(-2px);
            color: ' . $highlight_color . ';
        }
        
        .action-card .dashicons {
            font-size: 24px;
            width: 40px;
            height: 40px;
            background: #f3f4f6;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: ' . $highlight_color . ';
            flex-shrink: 0;
        }
        
        .action-card:hover .dashicons {
            background: ' . $highlight_color . ';
            color: #ffffff;
        }
        
        .action-card-title {
            font-weight: 600;
            font-size: 15px;
            line-height: 1.4;
        }
        
        .dashboard-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 32px;
            margin-top: 32px;
        }
        
        .charts-section {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        
        .chart-container {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        }
        
        .chart-title {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 20px;
        }
        
        .stats-section {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
            height: fit-content;
        }
        
        .stats-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        
        .stat-item {
            display: flex;
            align-items: center;
            padding: 16px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .stat-item:last-child {
            border-bottom: none;
        }
        
        .stat-icon {
            width: 48px;
            height: 48px;
            background: #f3f4f6;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            flex-shrink: 0;
        }
        
        .stat-icon .dashicons {
            font-size: 24px;
            color: ' . $highlight_color . ';
        }
        
        .stat-content {
            flex: 1;
        }
        
        .stat-label {
            font-size: 14px;
            color: #6b7280;
            margin-bottom: 4px;
        }
        
        .stat-value {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
        }
        
        @media (max-width: 1024px) {
            .dashboard-content {
                grid-template-columns: 1fr;
            }
            
            .quick-actions {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .tutor-ead-dashboard {
                padding: 16px;
            }
            
            .quick-actions {
                grid-template-columns: 1fr;
            }
            
            .action-card {
                padding: 16px;
            }
            
            .chart-container,
            .stats-section {
                padding: 16px;
            }
        }
    </style>';

    // Wrap principal
    echo '<div class="wrap tutor-ead-dashboard">';
    
    // Header
    echo '<div class="dashboard-header">';
    echo '<h1 class="dashboard-title">' . __('Dashboard do Administrador', 'tutor-ead') . '</h1>';
    echo '<p class="dashboard-subtitle">' . __('Visão geral do sistema e estatísticas importantes', 'tutor-ead') . '</p>';
    echo '</div>';

    // Ações Rápidas
    echo '<h2 class="section-title">' . __('Ações Rápidas', 'tutor-ead') . '</h2>';
    echo '<div class="quick-actions">';
        echo self::render_action_block('Criar Atividade', admin_url('admin.php?page=tutor-ead-add-atividade'), 'dashicons-edit');
        echo self::render_action_block('Adicionar Curso', admin_url('admin.php?page=tutor-ead-courses'), 'dashicons-welcome-learn-more');
        echo self::render_action_block('Registrar Aluno', admin_url('admin.php?page=tutor-ead-students'), 'dashicons-groups');
        echo self::render_action_block('Gerenciar Professores', admin_url('admin.php?page=tutor-ead-teachers'), 'dashicons-businessman');
        if ( get_option('tutor_ead_enable_temp_login_links', '0') === '1' ) {
            echo self::render_action_block('Criar Link de Login', admin_url('admin.php?page=tutor-ead-temp-login'), 'dashicons-admin-network');
        }
    echo '</div>';

    // Container principal
    echo '<div class="dashboard-content">';

        // Coluna esquerda - gráficos
        echo '<div class="charts-section">';
            // Gráfico: Alunos Registrados
            echo '<div class="chart-container">';
                echo '<h3 class="chart-title">' . __('Alunos Registrados por Mês', 'tutor-ead') . '</h3>';
                echo '<canvas id="alunosPorMes" style="max-height: 300px;"></canvas>';
            echo '</div>';
            // Gráfico: Matrículas por Curso
            echo '<div class="chart-container">';
                echo '<h3 class="chart-title">' . __('Matrículas por Curso', 'tutor-ead') . '</h3>';
                echo '<canvas id="matriculasPorCurso" style="max-height: 300px;"></canvas>';
            echo '</div>';
        echo '</div>';

        // Coluna direita - estatísticas
        echo '<div class="stats-section">';
            echo '<h3 class="chart-title">' . __('Estatísticas Gerais', 'tutor-ead') . '</h3>';
            echo '<ul class="stats-list">';
                echo '<li class="stat-item">'
                    . '<div class="stat-icon"><span class="dashicons dashicons-admin-users"></span></div>'
                    . '<div class="stat-content">'
                    . '<div class="stat-label">' . __('Total de Alunos', 'tutor-ead') . '</div>'
                    . '<div class="stat-value">' . intval($total_alunos) . '</div>'
                    . '</div>'
                    . '</li>';
                echo '<li class="stat-item">'
                    . '<div class="stat-icon"><span class="dashicons dashicons-welcome-learn-more"></span></div>'
                    . '<div class="stat-content">'
                    . '<div class="stat-label">' . __('Total de Cursos', 'tutor-ead') . '</div>'
                    . '<div class="stat-value">' . intval($total_cursos) . '</div>'
                    . '</div>'
                    . '</li>';
                echo '<li class="stat-item">'
                    . '<div class="stat-icon"><span class="dashicons dashicons-edit"></span></div>'
                    . '<div class="stat-content">'
                    . '<div class="stat-label">' . __('Total de Atividades', 'tutor-ead') . '</div>'
                    . '<div class="stat-value">' . intval($total_atividades) . '</div>'
                    . '</div>'
                    . '</li>';
                echo '<li class="stat-item">'
                    . '<div class="stat-icon"><span class="dashicons dashicons-admin-page"></span></div>'
                    . '<div class="stat-content">'
                    . '<div class="stat-label">' . __('Total de Módulos', 'tutor-ead') . '</div>'
                    . '<div class="stat-value">' . intval($total_modulos) . '</div>'
                    . '</div>'
                    . '</li>';
                echo '<li class="stat-item">'
                    . '<div class="stat-icon"><span class="dashicons dashicons-format-video"></span></div>'
                    . '<div class="stat-content">'
                    . '<div class="stat-label">' . __('Total de Aulas', 'tutor-ead') . '</div>'
                    . '<div class="stat-value">' . intval($total_aulas) . '</div>'
                    . '</div>'
                    . '</li>';
                echo '<li class="stat-item">'
                    . '<div class="stat-icon"><span class="dashicons dashicons-index-card"></span></div>'
                    . '<div class="stat-content">'
                    . '<div class="stat-label">' . __('Total de Matrículas', 'tutor-ead') . '</div>'
                    . '<div class="stat-value">' . intval($total_matriculas) . '</div>'
                    . '</div>'
                    . '</li>';
            echo '</ul>';
        echo '</div>';

    echo '</div>'; // fim do container principal

    // Enfileiramento dos scripts para os gráficos
    self::enqueue_chart_script($meses, $totais, $highlight_color);
    self::enqueue_course_chart_script($curso_labels, $curso_totals, $highlight_color);

    echo '</div>';
}

/**
 * Renderiza um bloco de ação rápida com ícone e texto lado a lado.
 */
private static function render_action_block($title, $url, $icon) {
    return '
    <a href="' . esc_url($url) . '" class="action-card">
        <span class="dashicons ' . esc_attr($icon) . '"></span>
        <span class="action-card-title">' . esc_html($title) . '</span>
    </a>';
}

/**
 * Adiciona o script para o gráfico de alunos registrados por mês.
 */
private static function enqueue_chart_script($labels, $data, $highlight_color) {
    echo '
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const ctx = document.getElementById("alunosPorMes").getContext("2d");
            new Chart(ctx, {
                type: "line",
                data: {
                    labels: ' . json_encode($labels) . ',
                    datasets: [{
                        label: "Alunos Registrados",
                        data: ' . json_encode($data) . ',
                        borderColor: "' . $highlight_color . '",
                        backgroundColor: "' . $highlight_color . '20",
                        borderWidth: 2,
                        tension: 0.4,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: { 
                            title: { display: true, text: "Meses" },
                            grid: { display: false }
                        },
                        y: { 
                            title: { display: true, text: "Alunos" },
                            beginAtZero: true,
                            grid: { color: "#f3f4f6" }
                        }
                    }
                }
            });
        });
    </script>';
}

/**
 * Adiciona o script para o gráfico de Matrículas por Curso.
 */
private static function enqueue_course_chart_script($labels, $data, $highlight_color) {
    echo '
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const ctx = document.getElementById("matriculasPorCurso").getContext("2d");
            new Chart(ctx, {
                type: "bar",
                data: {
                    labels: ' . json_encode($labels) . ',
                    datasets: [{
                        label: "Matrículas por Curso",
                        data: ' . json_encode($data) . ',
                        backgroundColor: "' . $highlight_color . '20",
                        borderColor: "' . $highlight_color . '",
                        borderWidth: 1,
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: { 
                            title: { display: true, text: "Cursos" },
                            grid: { display: false }
                        },
                        y: { 
                            beginAtZero: true, 
                            title: { display: true, text: "Matrículas" },
                            grid: { color: "#f3f4f6" }
                        }
                    }
                }
            });
        });
    </script>';
}

    /**

     * Renderiza o dashboard para Professores.

     */

    public static function dashboard_professor() {

        global $wpdb;

        $current_user = wp_get_current_user();

        $professor_id = $current_user->ID;



        $total_turmas = $wpdb->get_var(

            $wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}turmas WHERE professor_id = %d", $professor_id)

        );



        echo '<div class="wrap" style="padding: 0px !important;">';

        echo '<h1>' . __('Dashboard do Professor', 'tutor-ead') . '</h1>';

        echo '<ul>';

        echo '<li><strong>Total de Turmas:</strong> ' . intval($total_turmas) . '</li>';

        echo '</ul>';



        echo '<h2 style="margin-top: 10px;">' . __('Atividades Recentes', 'tutor-ead') . '</h2>';

        $atividades = $wpdb->get_results(

            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}atividades WHERE professor_id = %d", $professor_id)

        );



        if ($atividades) {

            echo '<ul>';

            foreach ($atividades as $atividade) {

                echo '<li>' . esc_html($atividade->titulo) . '</li>';

            }

            echo '</ul>';

        } else {

            echo '<p>' . __('Nenhuma atividade encontrada.', 'tutor-ead') . '</p>';

        }

        echo '</div>';

    }



    /**

     * Renderiza o dashboard para Alunos.

     */

    public static function dashboard_aluno() {

        global $wpdb;

        $current_user = wp_get_current_user();

        $aluno_id = $current_user->ID;



        echo '<div class="wrap" style="padding: 0px !important;">';

        echo '<h1>' . __('Dashboard do Aluno', 'tutor-ead') . '</h1>';

        echo '<h2 style="margin-top: 10px;">' . __('Meus Cursos', 'tutor-ead') . '</h2>';



        $cursos = $wpdb->get_results(

            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}matriculas WHERE user_id = %d", $aluno_id)

        );



        if ($cursos) {

            echo '<ul>';

            foreach ($cursos as $curso) {

                $curso_titulo = $wpdb->get_var(

                    $wpdb->prepare("SELECT post_title FROM {$wpdb->prefix}posts WHERE ID = %d", $curso->course_id)

                );

                echo '<li>' . esc_html($curso_titulo) . '</li>';

            }

            echo '</ul>';

        } else {

            echo '<p>' . __('Você ainda não está matriculado em nenhum curso.', 'tutor-ead') . '</p>';

        }

        echo '</div>';

    }

}

