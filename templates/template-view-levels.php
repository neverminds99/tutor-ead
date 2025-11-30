<?php
/**
 * Template Name: Visualização do Curso - Modo Níveis (Multi-página) V3 - Corrigido
 */

// Bloco de segurança e dados iniciais
global $wpdb;
$user_id   = get_current_user_id();
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$module_id = isset($_GET['module_id']) ? intval($_GET['module_id']) : 0;
$unit_id   = isset($_GET['unit_id']) ? intval($_GET['unit_id']) : 0;

// Pega a cor de destaque para usar no CSS
$highlight_color = get_option('tutor_ead_highlight_color', '#0073aa');

// Busca dados do curso
$course = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tutoread_courses WHERE id = %d", $course_id), ARRAY_A);

// Validações
if (!is_user_logged_in()) { wp_redirect(home_url('/login')); exit; }
$is_enrolled = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}matriculas WHERE user_id = %d AND course_id = %d", $user_id, $course_id));
if (!$is_enrolled && $course_id > 0 && !current_user_can('manage_options')) { wp_die('Acesso Negado', 'Você não está matriculado neste curso.', ['response' => 403]); }
if (!$course) { wp_die('Curso não encontrado.'); }

$base_url = get_permalink(get_page_by_path('visualizar-curso'));

// =========================================================================
// CABEÇALHO COMUM
// =========================================================================
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?> >
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo esc_html($course['title']); ?> - Tutor EAD</title>
    <?php wp_head(); ?>
</head>
<body <?php body_class('tutor-ead-page-body'); ?> >
    <header class="tutor-ead-header"> 
        <div class="logo">
            <?php
            $plugin_logo = get_option('tutor_ead_course_logo');
            if ($plugin_logo) { echo '<img src="' . esc_url($plugin_logo) . '" alt="Logo">'; } else { echo 'Tutor EAD'; }
            ?>
        </div>
        <div class="user-menu">
            <div class="user-icon">👤</div>
            <div class="user-dropdown">
                <a href="<?php echo esc_url(home_url('/dashboard-aluno')); ?>">Dashboard</a>
                <a href="<?php echo esc_url(wp_logout_url(home_url('/login-tutor-ead'))); ?>">Logout</a>
            </div>
        </div>
    </header>

    <main>
        <div class="course-overview-container">
            <div class="course-info-block">
                <?php if (!empty($course['capa_img'])) : ?>
                    <img src="<?php echo esc_url($course['capa_img']); ?>" alt="Capa do Curso" class="course-cover">
                <?php endif; ?>
                <div class="course-description">
                    <h1><?php echo esc_html($course['title']); ?></h1>
                    <?php 
                    // A descrição do curso agora é exibida em todas as páginas.
                    echo wpautop(esc_html($course['description'])); 
                    ?>
                </div>
            </div>

            <div class="module-list">
                <?php
                // =========================================================================
                // ROTEAMENTO DE CONTEÚDO (AGORA DENTRO DO LAYOUT CORRETO)
                // =========================================================================

                // CASO 3: EXIBIR AULAS (Dentro de uma unidade)
                if ($unit_id > 0) {
                    $unit = $wpdb->get_row($wpdb->prepare("SELECT title FROM {$wpdb->prefix}tutoread_modules WHERE id = %d", $unit_id), ARRAY_A);
                    echo '<h2>' . esc_html($unit['title']) . '</h2>';

                    // Botão Voltar
                    $back_link = add_query_arg(['course_id' => $course_id, 'module_id' => $module_id], $base_url);
                    echo '<a href="' . esc_url($back_link) . '" class="back-admin-btn">&larr; Voltar para as Unidades</a>';

                    $lessons = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tutoread_lessons WHERE module_id = %d ORDER BY lesson_order ASC", $unit_id), ARRAY_A);
                    echo '<div class="module-overview">';
                    if ($lessons) {
                        echo '<div class="lesson-cards-container">';
                        foreach ($lessons as $lesson) {
                            $lesson_url = add_query_arg(['course_id' => $course_id, 'lesson_id' => $lesson['id']], $base_url);
                                                            $thumb_url = TUTOR_EAD_IMG_URL . 'default-thumbnail.png';
                                                            if (!empty($lesson['video_url'])) {
                                                                if (preg_match('/\.pdf$/i', $lesson['video_url'])) {
                                                                    $thumb_url = TUTOR_EAD_IMG_URL . 'pdf.png';
                                                                } elseif (preg_match('/[\\?&]v=([^\\?&]+)/', $lesson['video_url'], $matches)) {
                                                                    $thumb_url = 'https://img.youtube.com/vi/' . $matches[1] . '/hqdefault.jpg';
                                                                }
                                                            }
                                                            echo '<a href="' . esc_url($lesson_url) . '" class="lesson-card">';
                                                            $pdf_class = (preg_match('/\.pdf$/i', $lesson['video_url'])) ? ' pdf-thumbnail' : '';
                                                            echo '    <div class="card-thumbnail' . $pdf_class . '" style="background-image: url(\'' . esc_url($thumb_url) . '\');"></div>';                            echo '    <div class="card-content"><span class="card-title">' . esc_html($lesson['title']) . '</span></div>';
                            echo '</a>';
                        }
                        echo '</div>';
                    } else { echo '<p>Nenhuma aula encontrada nesta unidade.</p>'; }
                    echo '</div>';
                }

                // CASO 2: EXIBIR UNIDADES OU AULAS (Dentro de um módulo)
                elseif ($module_id > 0) {
                    $module = $wpdb->get_row($wpdb->prepare("SELECT title FROM {$wpdb->prefix}tutoread_modules WHERE id = %d", $module_id), ARRAY_A);
                    echo '<h2>' . esc_html($module['title']) . '</h2>';

                    // Botão Voltar
                    $back_link = add_query_arg(['course_id' => $course_id], $base_url);
                    echo '<a href="' . esc_url($back_link) . '" class="back-admin-btn">&larr; Voltar para os Módulos</a>';

                    $units = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tutoread_modules WHERE parent_id = %d ORDER BY module_order ASC", $module_id), ARRAY_A);
                    if ($units) {
                        // Botões de alternância de visualização
                        echo '<div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 15px;">';
                        echo '<h3 style="margin:0;">Unidades</h3>';
                        echo '<div class="view-toggle">' . 
                             '<button id="view-toggle-list" class="active" title="Ver como lista"><span class="dashicons dashicons-list-view"></span></button>' . 
                             '<button id="view-toggle-blocks" title="Ver como blocos"><span class="dashicons dashicons-grid-view"></span></button>' . 
                             '</div>';
                        echo '</div>';

                        // Visualização em Lista (Padrão)
                        echo '<div id="unit-display-list">';
                        echo '<ul class="simple-unit-list">';
                        foreach ($units as $unit) {
                            $unit_url = add_query_arg(['course_id' => $course_id, 'module_id' => $module_id, 'unit_id' => $unit['id']], $base_url);
                            echo '<li><a href="' . esc_url($unit_url) . '">' . esc_html($unit['title']) . '</a></li>';
                        }
                        echo '</ul>';
                        echo '</div>';

                        // Visualização em Blocos (Oculta)
                        echo '<div id="unit-display-blocks" style="display:none;">';
                        echo '<div class="unit-cards-wrapper">';
                        foreach ($units as $unit) {
                            $unit_url = add_query_arg(['course_id' => $course_id, 'module_id' => $module_id, 'unit_id' => $unit['id']], $base_url);
                            $bg_image_url = !empty($unit['capa_img']) ? esc_url($unit['capa_img']) : esc_url($course['capa_img']);
                            $bg_style = $bg_image_url ? "background-image: url('{$bg_image_url}');" : "";
                            echo '<a href="' . esc_url($unit_url) . '" class="unit-card-square" style="' . $bg_style . '">';
                            echo '    <div class="unit-card-content">';
                            echo '        <h4>' . esc_html($unit['title']) . '</h4>';
                            echo '    </div>';
                            echo '</a>';
                        }
                        echo '</div>';
                        echo '</div>';

                    } else {
                        $lessons = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tutoread_lessons WHERE module_id = %d ORDER BY lesson_order ASC", $module_id), ARRAY_A);
                        echo '<div class="module-overview">';
                        if ($lessons) {
                            echo '<div class="lesson-cards-container">';
                            foreach ($lessons as $lesson) {
                                $lesson_url = add_query_arg(['course_id' => $course_id, 'lesson_id' => $lesson['id']], $base_url);
                                                            $thumb_url = TUTOR_EAD_IMG_URL . 'default-thumbnail.png';
                                                            if (!empty($lesson['video_url'])) {
                                                                if (preg_match('/\.pdf$/i', $lesson['video_url'])) {
                                                                    $thumb_url = TUTOR_EAD_IMG_URL . 'pdf.png';
                                                                } elseif (preg_match('/[\?&]v=([^\?&]+)/', $lesson['video_url'], $matches)) {
                                                                    $thumb_url = 'https://img.youtube.com/vi/' . $matches[1] . '/hqdefault.jpg';
                                                                }
                                                            }
                                                            echo '<a href="' . esc_url($lesson_url) . '" class="lesson-card">';
                                                            $pdf_class = (preg_match('/\.pdf$/i', $lesson['video_url'])) ? ' pdf-thumbnail' : '';
                                                            echo '    <div class="card-thumbnail' . $pdf_class . '" style="background-image: url(\'' . esc_url($thumb_url) . '\');"></div>';                                echo '    <div class="card-content"><span class="card-title">' . esc_html($lesson['title']) . '</span></div>';
                                echo '</a>';
                            }
                            echo '</div>';
                        } else { echo '<p>Nenhuma aula encontrada neste módulo.</p>'; }
                        echo '</div>';
                    }
                }

                // CASO 1: EXIBIR MÓDULOS (Página inicial do curso)
                else {
                    echo '<h2>Conteúdo do Curso</h2>';
                    echo '<div class="module-cards-wrapper">'; // Novo wrapper para os cards
                    $modules = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}tutoread_modules WHERE course_id = %d AND parent_id = 0 ORDER BY module_order ASC", $course_id), ARRAY_A);
                    if ($modules) {
                        foreach ($modules as $module) {
                            $module_url = add_query_arg(['course_id' => $course_id, 'module_id' => $module['id']], $base_url);
                            $bg_image_url = !empty($module['capa_img']) ? esc_url($module['capa_img']) : esc_url($course['capa_img']);
                            $bg_style = $bg_image_url ? "background-image: url('{$bg_image_url}');" : "";
                            echo '<a href="' . esc_url($module_url) . '" class="module-card-square" style="' . $bg_style . '">';
                            echo '    <div class="module-card-content">';
                            echo '        <h3>' . esc_html($module['title']) . '</h3>';
                            echo '    </div>';
                            echo '</a>';
                        }
                    } else { echo '<div class="module-overview"><p>Nenhum módulo foi adicionado a este curso ainda.</p></div>'; }
                    echo '</div>'; // Fim do wrapper
                }
                ?>
            </div>
        </div>
    </main>

    <style>
    .course-overview-container {
        display: flex;
        gap: 24px;
        margin: 0 auto;
        width: 100%;
        max-width: 1600px; /* Usando max-width para responsividade */
    }
    .course-info-block {
        flex: 0 0 400px; /* Base de 400px, não cresce, não encolhe */
    }
    .module-list {
        flex: 1 1 auto; /* Cresce e encolhe conforme necessário */
        background-color: #f9f9f9;
        min-width: 0; /* Evita que o flex item transborde */
    }

    /* Estilos para o botão Voltar */
    .back-admin-btn {
        display: inline-block;
        padding: 10px 20px;
        margin-top: 15px;
        margin-bottom: 20px;
        background-color: <?php echo esc_attr($highlight_color); ?>;
        color: #fff;
        text-decoration: none;
        border-radius: 20px; /* Bordas arredondadas */
        font-weight: 500;
        transition: filter 0.2s ease;
    }
    .back-admin-btn:hover {
        box-shadow: inset 0 0 100px 100px rgba(0, 0, 0, 0.1); /* Escurece no hover sem afetar o texto */
    }

    /* Estilos para a alternância de visualização */
    .view-toggle {
        display: inline-flex;
        border: 1px solid #ccc;
        border-radius: 6px;
        overflow: hidden;
    }
    .view-toggle button {
        background: #fff;
        border: none;
        padding: 8px 12px;
        cursor: pointer;
        color: #555;
        border-left: 1px solid #ccc;
    }
    .view-toggle button:first-child {
        border-left: none;
    }
    .view-toggle button.active {
        background: <?php echo esc_attr($highlight_color); ?>;
        color: #fff;
    }
    .view-toggle button:not(.active):hover {
        background: #f0f0f0;
    }

    /* Estilos para a lista simples de unidades */
    .simple-unit-list {
        list-style: none;
        padding: 0;
        margin: 0;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        overflow: hidden;
    }
    .simple-unit-list li {
        margin: 0;
    }
    .simple-unit-list li a {
        display: block;
        padding: 15px 20px;
        text-decoration: none;
        color: #333;
        background-color: #fff; /* Fundo branco */
        border-bottom: 1px solid #e5e7eb;
        transition: background-color 0.2s ease;
    }
    .simple-unit-list li:last-child a {
        border-bottom: none;
    }
    .simple-unit-list li a:hover {
        background-color: #f0f0f0;
    }

    /* Estilos para unidades como blocos quadrados */
    .unit-cards-wrapper {
        display: grid;
        grid-template-columns: repeat(3, 1fr); /* 3 colunas em desktop */
        gap: 20px;
    }
    @media screen and (max-width: 782px) {
        .unit-cards-wrapper {
            grid-template-columns: 1fr; /* 1 coluna em mobile */
        }
    }

    /* Estilos para os cards de Módulo e Unidade */
    .module-card-square, .unit-card-square {
        display: block;
        background-color: #333; /* Fallback background */
        background-size: cover;
        background-position: center;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        text-decoration: none;
        color: #fff; /* Cor do texto para branco */
        position: relative;
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .module-card-square:hover, .unit-card-square:hover {
        transform: translateY(-5px); /* Remove o scale do hover */
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    .module-card-square::before, .unit-card-square::before { /* :before para o aspect ratio */
        content: '';
        display: block;
        padding-top: 100%;
    }
    .module-card-square::after, .unit-card-square::after { /* Gradiente para legibilidade */
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 70%;
        background: linear-gradient(to top, rgba(0,0,0,0.8) 0%, rgba(0,0,0,0) 100%);
        backdrop-filter: blur(2px);
        z-index: 1;
    }
    .module-card-square .module-card-content, .unit-card-square .unit-card-content {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        display: flex; 
        flex-direction: column;
        justify-content: flex-end; /* Alinha o texto na parte de baixo */
        padding: 15px;
        text-align: left;
        z-index: 2; /* Garante que o texto fique sobre o gradiente */
    }
    .unit-card-square .unit-card-content h4, .module-card-square .module-card-content h3 {
        margin: 0; 
        line-height: 1.3;
        color: #fff;
        font-weight: bold;
    }
    .module-card-square .module-card-content h3 { font-size: 1.2em; }
    .unit-card-square .unit-card-content h4 { font-size: 1.1em; }

    /* Estilos para os módulos como blocos quadrados (wrapper) */
    .module-cards-wrapper {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); /* Responsive grid */
        gap: 20px;
        margin-top: 20px;
    }

    /* Estilos para o ícone de PDF nas miniaturas */
    .card-thumbnail.pdf-thumbnail {
        background-size: 60px 60px !important; /* Torna o ícone menor, altura automática */
        background-repeat: no-repeat !important; /* Não repete o ícone */
        background-position: center center !important; /* Centraliza o ícone */
        background-color: #e87769 !important; /* Nova cor de fundo */
    }

    /* Estilos para Mobile */
    @media screen and (max-width: 782px) {
        .course-overview-container {
            flex-direction: column; /* Empilha os itens */
            padding: 20px; /* Espaçamento das bordas da tela */
            gap: 20px; /* Espaçamento entre os blocos empilhados */
        }
        .course-info-block {
            flex-basis: auto; /* Reseta a base de largura */
            margin-bottom: 0; /* Remove margin-bottom, pois o gap já cuida do espaçamento */
        }
        main {
            padding: 0; /* Remove o padding do main, pois o container já tem */
        }
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const toggleListBtn = document.getElementById('view-toggle-list');
        const toggleBlocksBtn = document.getElementById('view-toggle-blocks');
        const listView = document.getElementById('unit-display-list');
        const blocksView = document.getElementById('unit-display-blocks');

        if (toggleListBtn && toggleBlocksBtn && listView && blocksView) {
            // Função para definir a visualização
            function setView(view) {
                if (view === 'blocks') {
                    toggleBlocksBtn.classList.add('active');
                    toggleListBtn.classList.remove('active');
                    blocksView.style.display = 'block';
                    listView.style.display = 'none';
                } else { // Padrão para lista
                    toggleListBtn.classList.add('active');
                    toggleBlocksBtn.classList.remove('active');
                    listView.style.display = 'block';
                    blocksView.style.display = 'none';
                }
            }

            // Ao carregar: Verifica o localStorage e define a visualização inicial
            const savedView = localStorage.getItem('unitViewMode');
            setView(savedView);

            // Manipulador de clique para o botão Lista
            toggleListBtn.addEventListener('click', function() {
                if (!this.classList.contains('active')) {
                    setView('list');
                    localStorage.setItem('unitViewMode', 'list');
                }
            });

            // Manipulador de clique para o botão Blocos
            toggleBlocksBtn.addEventListener('click', function() {
                if (!this.classList.contains('active')) {
                    setView('blocks');
                    localStorage.setItem('unitViewMode', 'blocks');
                }
            });
        }
    });
    </script>

    <script>
      // Script simples para o dropdown de usuário
      document.addEventListener('DOMContentLoaded', function(){
        var userIcon = document.querySelector('.user-icon');
        var userDropdown = document.querySelector('.user-dropdown');
        if (userIcon && userDropdown) {
            userIcon.addEventListener('click', function(e){
                e.stopPropagation();
                userDropdown.style.display = (userDropdown.style.display === 'block') ? 'none' : 'block';
            });
        }
        document.addEventListener('click', function(){
            if (userDropdown) {
                userDropdown.style.display = 'none';
            }
        });
      });
    </script>
    
    <?php wp_footer(); ?>
</body>
</html>