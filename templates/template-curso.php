<?php
/**
 * Template Name: Visualização do Curso (Roteador)
 * 
 * Este template atua como um roteador para carregar a visualização de curso correta.
 * PRIORIDADE: Se um 'lesson_id' estiver na URL, ele sempre carrega a visualização da aula.
 * Caso contrário, ele carrega a visualização padrão do curso (Níveis ou Expandido).
 */

global $wpdb;

// 1. Obter os IDs da URL.
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;
$lesson_id = isset($_GET['lesson_id']) ? intval($_GET['lesson_id']) : 0;

if ($course_id > 0) {
    // 2. Roteamento inteligente:
    // Se um lesson_id estiver presente, a intenção é visualizar uma aula.
    // O template 'expanded' é o responsável por carregar o player da aula.
    if ($lesson_id > 0) {
        $template_path = __DIR__ . '/template-view-expanded.php';
    } else {
        // Se não houver lesson_id, use a lógica padrão baseada na configuração do curso.
        $view_mode = get_option('tutoread_view_mode_' . $course_id, 'expanded');
        if ($view_mode === 'levels') {
            $template_path = __DIR__ . '/template-view-levels.php';
        } else {
            $template_path = __DIR__ . '/template-view-expanded.php';
        }
    }

    // 3. Incluir o template escolhido.
    if (file_exists($template_path)) {
        include $template_path;
    } else {
        // Fallback caso um dos templates não seja encontrado.
        wp_die('O template de visualização do curso não foi encontrado.');
    }

} else {
    // Se nenhum ID de curso for fornecido, exibe uma mensagem de erro.
    get_header();
    wp_die('<h2>Curso não especificado</h2><p>Nenhum ID de curso foi fornecido na URL.</p>', 'Erro', ['response' => 404]);
    get_footer();
}
