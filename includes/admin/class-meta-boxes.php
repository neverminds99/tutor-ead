<?php

namespace TutorEAD\Admin;

defined('ABSPATH') || exit;

// Análise do arquivo: class-meta-boxes.php

// **Objetivo:** 
// Gerenciar Meta Boxes para os Custom Post Types (CPTs) do Tutor EAD, permitindo associações entre cursos, módulos e aulas.

// **Principais funcionalidades:**
// - Adicionar e salvar metadados personalizados para cursos, módulos e aulas.
// - Criar campos no editor do WordPress para associar:
//   - Módulos a cursos.
//   - Aulas a módulos.
//   - Professores a cursos.
//   - Definir um limite de alunos por curso.
// - Manter dados organizados no banco de dados usando `post_meta`.

// **Resumo:**
// A `MetaBoxes` facilita o vínculo entre os diferentes elementos da estrutura educacional do Tutor EAD, permitindo uma gestão eficiente dentro do painel administrativo do WordPress.


class MetaBoxes {

    public function __construct() {
        add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
        add_action('save_post', [$this, 'save_meta_boxes']);
    }

    /**
     * Método que será chamado no hook "add_meta_boxes".
     * Chama os métodos estáticos responsáveis por registrar as meta boxes.
     */
    public function register_meta_boxes() {
        // Registra as meta boxes específicas para os CPTs
        self::registrar_meta_boxes();
        self::add_custom_meta_boxes();
    }

    /**
     * Método que será chamado no hook "save_post".
     * Chama o método estático responsável por salvar os campos personalizados.
     *
     * @param int $post_id ID do post que está sendo salvo.
     */
    public function save_meta_boxes($post_id) {
        self::save_custom_meta_boxes($post_id);
    }

    /**
     * Registra as meta boxes para diferentes Custom Post Types.
     */
    public static function registrar_meta_boxes() {
        add_action('add_meta_boxes_modulo', function() {
            add_meta_box('curso_para_modulo', __('Associar ao Curso', 'tutor-ead'), 'adicionar_meta_box_curso_para_modulo', 'modulo', 'side');
        });
        add_action('save_post_modulo', 'salvar_meta_box_curso_para_modulo');

        add_action('add_meta_boxes_aula', function() {
            add_meta_box('modulo_para_aula', __('Associar à um Módulo', 'tutor-ead'), 'adicionar_meta_box_modulo_para_aula', 'aula', 'side', 'default');
        });
        add_action('save_post_aula', 'salvar_meta_box_modulo_para_aula');
    }

    // Função para exibir a metabox no CPT 'modulo'
    public function adicionar_meta_box_curso_para_modulo($post) {
        // Obter todos os cursos para seleção
        $cursos = get_posts(['post_type' => 'curso', 'numberposts' => -1]);
        $curso_atual = get_post_meta($post->ID, '_curso_pai', true);
        echo '<label for="curso_pai">' . __('Curso Pai', 'tutor-ead') . '</label>';
        echo '<select name="curso_pai" id="curso_pai">';
        echo '<option value="">' . __('Selecione um curso', 'tutor-ead') . '</option>';
        foreach ($cursos as $curso) {
            $selected = ($curso_atual == $curso->ID) ? 'selected' : '';
            echo '<option value="' . esc_attr($curso->ID) . '" ' . $selected . '>' . esc_html($curso->post_title) . '</option>';
        }
        echo '</select>';
    }

    public function salvar_meta_box_curso_para_modulo($post_id) {
        if (isset($_POST['curso_pai'])) {
            update_post_meta($post_id, '_curso_pai', intval($_POST['curso_pai']));
        }
    }

    // Função para exibir a metabox no CPT 'aula'
    public function adicionar_meta_box_modulo_para_aula($post) {
        // Obter todos os módulos para seleção
        $modulos = get_posts([
            'post_type'   => 'modulo',
            'numberposts' => -1
        ]);
        // Obter o módulo atualmente associado à aula, se houver
        $modulo_atual = get_post_meta($post->ID, '_modulo_pai', true);

        echo '<label for="modulo_pai">' . __('Módulo Pai', 'tutor-ead') . '</label>';
        echo '<select name="modulo_pai" id="modulo_pai" style="width:100%;">';
        echo '<option value="">' . __('Selecione um módulo', 'tutor-ead') . '</option>';
        foreach ($modulos as $modulo) {
            $selected = ($modulo_atual == $modulo->ID) ? 'selected' : '';
            echo '<option value="' . esc_attr($modulo->ID) . '" ' . $selected . '>' . esc_html($modulo->post_title) . '</option>';
        }
        echo '</select>';
    }

    // Função para salvar a seleção do módulo associado à aula
    public function salvar_meta_box_modulo_para_aula($post_id) {
        // Verificar se o campo foi enviado e salvar o valor
        if (isset($_POST['modulo_pai'])) {
            update_post_meta($post_id, '_modulo_pai', intval($_POST['modulo_pai']));
        }
    }

    public static function add_custom_meta_boxes() {
        add_meta_box(
            'professor_associado',
            __('Professor Associado', 'tutor-ead'),
            [__CLASS__, 'render_professor_associado_field'],
            'curso',
            'side', // Em qual parte da tela do editor o campo será exibido
            'default'
        );

        add_meta_box(
            'max_students',
            __('Número Máximo de Alunos', 'tutor-ead'),
            [__CLASS__, 'render_max_students_field'],
            'curso',
            'side',
            'default'
        );
    }

    public static function render_professor_associado_field($post) {
        // Obter o professor associado
        $professor_id = get_post_meta($post->ID, '_professor_associado', true);
        $professores = get_users(['role' => 'tutor_professor']);

        echo '<select name="professor_associado" id="professor_associado">';
        echo '<option value="">' . __('Selecione um Professor', 'tutor-ead') . '</option>';

        foreach ($professores as $professor) {
            $selected = selected($professor_id, $professor->ID, false);
            echo '<option value="' . esc_attr($professor->ID) . '" ' . $selected . '>' . esc_html($professor->user_login) . '</option>';
        }

        echo '</select>';
    }

    public static function render_max_students_field($post) {
        // Obter o número máximo de alunos
        $max_students = get_post_meta($post->ID, '_max_students', true);
        echo '<input type="number" name="max_students" id="max_students" value="' . esc_attr($max_students) . '" min="0" />';
    }

    public static function save_custom_meta_boxes($post_id) {
        // Verificar se a chave 'professor_associado' foi definida e salvar
        if (isset($_POST['professor_associado'])) {
            update_post_meta($post_id, '_professor_associado', intval($_POST['professor_associado']));
        }

        // Verificar se a chave 'max_students' foi definida e salvar
        if (isset($_POST['max_students'])) {
            update_post_meta($post_id, '_max_students', intval($_POST['max_students']));
        }
    }
}
