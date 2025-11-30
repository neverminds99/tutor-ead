<?php
/**
 * Tutor EAD – Activity Manager (Refactored for Unification)
 */
namespace TutorEAD\Admin;

defined( 'ABSPATH' ) || exit;

class ActivityManager {

	private static function render_view( string $file, array $data = [] ): void {
		$template = __DIR__ . '/views/' . $file;
		if ( ! file_exists( $template ) ) {
			wp_die( sprintf( esc_html__( 'Template %1$s não encontrado.', 'tutor-ead' ), esc_html( $file ) ) );
		}
		extract( $data, EXTR_SKIP );
		require $template;
	}

	// RENDERERS (PAGES)
	public static function atividades_page(): void {
		global $wpdb;
		self::render_view(
			'atividades-list.php',
			[ 'atividades' => $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}atividades ORDER BY id DESC" ) ]
		);
	}

	public static function add_new_activity_page(): void {
		self::render_view( 'atividade-add-new.php' );
	}

	public static function unified_form_page(): void {
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			self::handle_unified_add();
		}
		self::render_view( 'atividade-form-unificada.php' );
	}

	public static function associate_activity_select_course_page(): void {
        self::render_view('view-associate-activity-course-selection.php');
    }

	// HANDLERS (FORM SUBMISSIONS)
	public static function handle_unified_add(): void {
		$type = $_POST['activity_type'] ?? 'padrao';
		switch ($type) {
			case 'externa':
				self::handle_add_externa();
				break;
			case 'presencial':
				self::handle_add_sem_url();
				break;
			case 'padrao':
			default:
				self::handle_add_padrao();
				break;
		}
	}

	    private static function handle_add_padrao(): void {
        global $wpdb;
        // Apenas insere a atividade principal com os dados do formulário inicial.
        $wpdb->insert(
            "{$wpdb->prefix}atividades",
            [
                'titulo'                   => sanitize_text_field( $_POST['titulo'] ),
                'descricao'                => sanitize_textarea_field( $_POST['descricao'] ),
                'nota_maxima'              => floatval( $_POST['nota_maxima'] ),
                'data_criacao'             => current_time( 'mysql' ),
                'is_externa'               => 0,
                'link_externo'             => '',
            ]
        );
        $atividade_id = $wpdb->insert_id;

        // Se a inserção foi bem-sucedida, redireciona para o editor de quiz.
        if ($atividade_id) {
            wp_redirect(admin_url('admin.php?page=tutor-ead-edit-atividade-padrao&id=' . $atividade_id));
            exit;
        }
        // Se falhar, pode adicionar uma mensagem de erro aqui.
    }

	    private static function handle_add_externa(): void {
        global $wpdb;
        $wpdb->insert( "{$wpdb->prefix}atividades", [
            'titulo'            => sanitize_text_field( $_POST['titulo'] ),
            'link_externo'      => esc_url_raw( $_POST['link_externo'] ),
            'data_criacao'      => current_time( 'mysql' ),
            'is_externa'        => 1,
            'dias_visualizacao' => intval( $_POST['dias_visualizacao'] ),
        ]);
        wp_redirect(admin_url('admin.php?page=tutor-ead-atividades&success=1'));
        exit;
    }

	    private static function handle_add_sem_url(): void {
        global $wpdb;
        $wpdb->insert( "{$wpdb->prefix}atividades", [
            'titulo'            => sanitize_text_field( $_POST['titulo'] ),
            'descricao'         => sanitize_textarea_field( $_POST['descricao'] ),
            'data_criacao'      => current_time( 'mysql' ),
            'is_externa'        => 1,
            'link_externo'      => '',
            'dias_visualizacao' => intval( $_POST['dias_visualizacao'] ),
        ]);
        wp_redirect(admin_url('admin.php?page=tutor-ead-atividades&success=1'));
        exit;
    }

	private static function show_success_notice( $message ) {
		add_action( 'admin_notices', function() use ( $message ) {
			printf( '<div class="notice notice-success"><p>%s</p></div>', esc_html( $message ) );
		});
	}

	// Métodos de edição e exclusão permanecem aqui (não foram alterados neste passo)
    // ... (código de edit e delete existente) ...

    public static function edit_atividade_padrao_page() {
        global $wpdb;
        $id = intval($_GET['id']);

        if ('POST' === $_SERVER['REQUEST_METHOD']) {
            self::handle_edit_padrao($id);
        }

        $atividade = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}atividades WHERE id = %d", $id));
        $perguntas = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}perguntas WHERE atividade_id = %d ORDER BY id ASC", $id));

        foreach ($perguntas as $pergunta) {
            $pergunta->alternativas = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}alternativas WHERE pergunta_id = %d ORDER BY id ASC", $pergunta->id));
        }

        // NOVA CONSULTA: Buscar todos os cursos
        $all_courses = $wpdb->get_results("SELECT id, title FROM {$wpdb->prefix}tutoread_courses ORDER BY title ASC");

        // NOVA CONSULTA: Buscar associações existentes para este quiz
        // Como é uma "Atividade Padrão", só nos preocupamos com a tabela principal de associação.
        $current_associations = $wpdb->get_results($wpdb->prepare(
            "SELECT course_id, module_id, lesson_id, position FROM {$wpdb->prefix}tutoread_course_activities WHERE activity_id = %d",
            $id
        ), OBJECT_K);

        self::render_view('atividade-edit-padrao.php', [
            'atividade'            => $atividade,
            'perguntas'            => $perguntas,
            'all_courses'          => $all_courses,          // <-- NOVO
            'current_associations' => $current_associations  // <-- NOVO
        ]);
    }

    public static function edit_atividade_externa_page() {
        global $wpdb;
        $id = isset($_GET['id']) ? intval($_GET['id']) : 0;

        if (!$id) {
            wp_die('ID da atividade inválido.');
        }

        if ('POST' === $_SERVER['REQUEST_METHOD'] && isset($_POST['save_external_activity'])) {
            self::handle_edit_externa($id);
            wp_redirect(admin_url('admin.php?page=tutor-ead-edit-atividade-externa&id=' . $id . '&success=1'));
            exit;
        }

        $atividade = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}atividades WHERE id = %d", $id));

        if (!$atividade) {
            wp_die('Atividade não encontrada.');
        }

        self::render_view('atividade-edit-externa.php', ['atividade' => $atividade]);
    }

    private static function handle_edit_externa($id) {
        global $wpdb;

        $wpdb->update(
            "{$wpdb->prefix}atividades",
            [
                'titulo'            => sanitize_text_field($_POST['titulo']),
                'link_externo'      => esc_url_raw($_POST['link_externo']),
                'dias_visualizacao' => intval($_POST['dias_visualizacao']),
            ],
            ['id' => $id]
        );
    }

    private static function handle_edit_padrao($id) {
        global $wpdb;

        // --- INÍCIO DA NOVA LÓGICA DE ASSOCIAÇÃO ---
        // 1. Pega os cursos selecionados no formulário. Se nada for enviado, será um array vazio.
        $submitted_courses = isset($_POST['associated_courses']) ? array_map('intval', $_POST['associated_courses']) : [];

        // 2. Pega as associações atuais do banco de dados.
        $existing_course_ids = $wpdb->get_col($wpdb->prepare(
            "SELECT course_id FROM {$wpdb->prefix}tutoread_course_activities WHERE activity_id = %d",
            $id
        ));

        // 3. Determina o que precisa ser adicionado e removido.
        $courses_to_add = array_diff($submitted_courses, $existing_course_ids);
        $courses_to_remove = array_diff($existing_course_ids, $submitted_courses);

        // 4. Remove as associações desmarcadas.
        if ( ! empty( $courses_to_remove ) ) {
            $wpdb->query($wpdb->prepare(
                "DELETE FROM {$wpdb->prefix}tutoread_course_activities WHERE activity_id = %d AND course_id IN (" . implode(',', $courses_to_remove) . ")",
                $id
            ));
        }

        // 5. Adiciona as novas associações.
        if ( ! empty( $courses_to_add ) ) {
            foreach ($courses_to_add as $course_id) {
                // CORREÇÃO: Encontrar o primeiro módulo do curso para evitar erro de 'module_id' nulo.
                $first_module_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}tutoread_modules WHERE course_id = %d ORDER BY module_order ASC LIMIT 1",
                    $course_id
                ));

                // Apenas insere se um módulo for encontrado.
                if ($first_module_id) {
                    $wpdb->insert("{$wpdb->prefix}tutoread_course_activities", [
                        'activity_id' => $id,
                        'course_id'   => $course_id,
                        'module_id'   => $first_module_id, // <-- USA O ID DO PRIMEIRO MÓDULO
                        'lesson_id'   => null,            // lesson_id pode ser nulo
                        'position'    => 'depois'
                    ]);
                }
            }
        }
        // --- FIM DA NOVA LÓGICA DE ASSOCIAÇÃO ---

        // 1. Atualizar a atividade principal
        $wpdb->update(
            "{$wpdb->prefix}atividades",
            [
                'titulo' => sanitize_text_field($_POST['titulo']),
                'descricao' => sanitize_textarea_field($_POST['descricao']),
                'nota_maxima' => floatval($_POST['nota_maxima'])
            ],
            ['id' => $id]
        );

        // 2. Apagar perguntas e alternativas antigas
        self::delete_activity_data($id);

        // 3. Inserir as novas perguntas e alternativas
        if (isset($_POST['perguntas']) && is_array($_POST['perguntas'])) {
            foreach ($_POST['perguntas'] as $p_data) {
                $wpdb->insert("{$wpdb->prefix}perguntas", [
                    'atividade_id' => $id,
                    'titulo' => sanitize_text_field($p_data['titulo'])
                ]);
                $pergunta_id = $wpdb->insert_id;

                if (isset($p_data['alternativas']) && is_array($p_data['alternativas'])) {
                    foreach ($p_data['alternativas'] as $a_idx => $a_data) {
                        $wpdb->insert("{$wpdb->prefix}alternativas", [
                            'pergunta_id' => $pergunta_id,
                            'texto' => sanitize_text_field($a_data['texto']),
                            'correta' => (isset($p_data['correta']) && intval($p_data['correta']) === $a_idx) ? 1 : 0
                        ]);
                    }
                }
            }
        }

        self::show_success_notice(__('Atividade atualizada com sucesso!', 'tutor-ead'));
    }

    private static function delete_activity_data($activity_id) {
        global $wpdb;
        $pergunta_ids = $wpdb->get_col($wpdb->prepare("SELECT id FROM {$wpdb->prefix}perguntas WHERE atividade_id = %d", $activity_id));
        if (!empty($pergunta_ids)) {
            $wpdb->query("DELETE FROM {$wpdb->prefix}alternativas WHERE pergunta_id IN (" . implode(',', $pergunta_ids) . ")");
        }
        $wpdb->delete("{$wpdb->prefix}perguntas", ['atividade_id' => $activity_id]);
    }
}
