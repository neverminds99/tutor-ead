<?php
/**
 * Import‑Export de Cursos – Tutor EAD
 *
 * • Tela administrativa de Importar / Exportar
 * • Importa JSON (upload de arquivo ou textarea) – UPSERT
 * • Exporta um curso em JSON completo
 *
 * @package TutorEAD\Admin
 */

namespace TutorEAD\Admin;

defined( 'ABSPATH' ) || exit;

class ImportExportCourses {

	/* ------------------------------------------------------------------ *
	 * Init
	 * ------------------------------------------------------------------ */
	public static function init() {
		add_action( 'admin_post_export_course', [ __CLASS__, 'handle_export_course' ] );
		add_action( 'wp_ajax_preview_course_import', [ __CLASS__, 'handle_preview_import' ] );
		add_action( 'wp_ajax_get_course_json_for_editing', [ __CLASS__, 'handle_get_course_json_for_editing' ] );

		// Garante que arquivos .json sejam aceitos no uploader do WP
		add_filter( 'upload_mimes', function ( $mimes ) {
			$mimes['json'] = 'application/json';
			return $mimes;
		} );
	}

	/**
	 * AJAX handler para a pré-visualização da importação.
	 */
	public static function handle_preview_import() {
		check_ajax_referer( 'tutor_ead_import_preview_nonce', 'nonce' );

		$json_raw = isset( $_POST['import_json'] ) ? wp_unslash( $_POST['import_json'] ) : '';
		if ( empty( $json_raw ) ) {
			wp_send_json_error( [ 'message' => 'Nenhum dado JSON recebido.' ] );
		}

		$data = json_decode( $json_raw, true );
		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error( [ 'message' => 'JSON inválido: ' . json_last_error_msg() ] );
		}

		if ( ! isset( $data['courses'] ) || ! is_array( $data['courses'] ) ) {
			wp_send_json_error( [ 'message' => 'Estrutura JSON inválida. A chave "courses" não foi encontrada ou não é um array.' ] );
		}

		list($courses, $warnings) = self::analyze_import_data( $data['courses'] );

		wp_send_json_success( [
			'courses'  => $courses,
			'warnings' => $warnings,
		] );
	}

	/**
	 * Analisa os dados do JSON para gerar a pré-visualização.
	 */
	private static function analyze_import_data( $courses ) {
		$warnings = [];
		$preview  = [];

		foreach ( $courses as $course ) {
			$course_id   = $course['course_id'] ?? $course['id'] ?? null;
			$course_info = self::ajax_check_item_status( 'tutoread_courses', $course_id );
			$course_info['title'] = $course['title'] ?? 'Curso sem título';

			// Analisa módulos e unidades de forma recursiva
			if ( ! empty( $course['modules'] ) ) {
				list($modules, $mod_warnings) = self::analyze_recursive_modules( $course['modules'], $course_id, $course_info['status'], $course_info['status'] );
				$course_info['modules'] = $modules;
				$warnings = array_merge($warnings, $mod_warnings);
			}

			$preview[] = $course_info;
		}

		return [ $preview, $warnings ];
	}

	/**
	 * Helper recursivo para analisar módulos e unidades.
	 */
	private static function analyze_recursive_modules( $modules, $course_id, $course_status, $parent_status = 'new', $parent_id = 0 ) {
		$preview = [];
		$warnings = [];

		foreach($modules as $module) {
			$module_id   = $module['module_id'] ?? $module['id'] ?? null;
			$module_info = self::ajax_check_item_status( 'tutoread_modules', $module_id, 'course_id', $course_id );
			$module_info['title'] = $module['title'] ?? 'Módulo/Unidade sem título';

			// Validações
			if ( ($course_status === 'new' || $parent_status === 'new') && $module_id ) {
				$warnings[] = "O item '{$module_info['title']}' tem um ID ($module_id), mas pertence a um curso/módulo novo. Será criado como novo, ignorando o ID.";
			}

			// Analisa unidades filhas
			if ( ! empty( $module['units'] ) ) {
				list($units, $unit_warnings) = self::analyze_recursive_modules( $module['units'], $course_id, $course_status, $module_info['status'], $module_id );
				$module_info['units'] = $units;
				$warnings = array_merge($warnings, $unit_warnings);
			}

			// Analisa aulas
			if ( ! empty( $module['lessons'] ) ) {
				foreach ( $module['lessons'] as $lesson ) {
					$lesson_id   = $lesson['lesson_id'] ?? $lesson['id'] ?? null;
					$lesson_info = self::ajax_check_item_status( 'tutoread_lessons', $lesson_id, 'module_id', $module_id );
					$lesson_info['title'] = $lesson['title'] ?? 'Aula sem título';

					if ( $module_info['status'] === 'new' && $lesson_id ) {
						$warnings[] = "A aula '{$lesson_info['title']}' tem um ID ($lesson_id), mas pertence a um item novo. A aula será criada como nova, ignorando o ID.";
					}

					$module_info['lessons'][] = $lesson_info;
				}
			}
			$preview[] = $module_info;
		}

		return [$preview, $warnings];
	}


	/**
	 * Helper para verificar o status de um item no DB.
	 */
	private static function ajax_check_item_status( $table, $id, $parent_key = null, $parent_id = null ) {
		global $wpdb;
		$full_table_name = $wpdb->prefix . $table;

		if ( ! $id ) {
			return [ 'status' => 'new' ];
		}

		$query = $wpdb->prepare( "SELECT id FROM $full_table_name WHERE id = %d", $id );

		// Se um pai for especificado, garante que o item pertence a ele.
		if ( $parent_key && $parent_id ) {
			// Se o pai for novo, o filho também será.
			if ( ! $parent_id ) {
				return [ 'status' => 'new' ];
			}
			$query .= $wpdb->prepare( " AND $parent_key = %d", $parent_id );
		}

		$exists = $wpdb->get_var( $query );

		return [ 'status' => $exists ? 'updated' : 'new' ];
	}


	/* ------------------------------------------------------------------ *
	 * Página “Importar / Exportar”
	 * ------------------------------------------------------------------ */
	public static function render_page() {

		// INÍCIO DO CABEÇALHO INJETADO DIRETAMENTE
        $header_style = 'padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); width: 100%; margin-bottom: 24px;';
        $dashboard_url = admin_url('admin.php?page=tutor-ead-dashboard');

        echo "<div style='" . esc_attr($header_style) . "'>"; // A barra branca
        echo "<div>"; // Container do lado esquerdo
        echo "<div><a href='" . esc_url($dashboard_url) . "'><img src='" . TUTOR_EAD_LOGO_URL . "' style='width: 100px; height: auto;' alt='Tutor EAD Logo'></a></div>"; // Logo
        
        $highlight_color = get_option('tutor_ead_highlight_color', '#0073aa');
        $courses_url = admin_url('admin.php?page=tutor-ead-courses');
        $button_style = 'background-color: ' . esc_attr($highlight_color) . '; color: #fff; width: 40px; height: 40px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; margin-top: 15px;';
        $icon_style = 'font-size: 20px; line-height: 1;';
        echo "<a href='" . esc_url($courses_url) . "' style='" . esc_attr($button_style) . "' title='Voltar para Cursos'>";
        echo "<span class='dashicons dashicons-arrow-left-alt' style='" . esc_attr($icon_style) . "'></span>";
        echo "</a>";
        
        echo "</div>"; // Fim do container do lado esquerdo
        echo "</div>";
        // FIM DO CABEÇALHO

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Acesso negado.', 'tutor-ead' ) );
		}

		global $wpdb;
		$modules_updated  = $modules_inserted = [];
		$lessons_updated  = $lessons_inserted = [];

		/* -------- IMPORTAÇÃO -------- */
		if ( isset( $_POST['_wpnonce'] ) && wp_verify_nonce( $_POST['_wpnonce'], 'tutor_ead_import_course' ) ) {

			$json_raw = null;

			// 1) Upload de arquivo .json
			if (
				! empty( $_FILES['import_file']['tmp_name'] ) &&
				is_uploaded_file( $_FILES['import_file']['tmp_name'] ) &&
				$_FILES['import_file']['error'] === UPLOAD_ERR_OK
			) {
				$filename = $_FILES['import_file']['name'];
				$ext      = strtolower( pathinfo( $filename, PATHINFO_EXTENSION ) );
				$mime     = wp_check_filetype( $filename )['type'] ?? '';

				$allowed_mimes = [
					'application/json',
					'text/plain',
					'application/octet-stream', // Alguns servidores retornam isso
					'',                         // Quando WP não identifica
				];

				if ( $ext === 'json' || in_array( $mime, $allowed_mimes, true ) ) {
					$json_raw = file_get_contents( $_FILES['import_file']['tmp_name'] );
				} else {
					echo '<div class="error"><p>' . esc_html__( 'Arquivo inválido. Selecione um .json.', 'tutor-ead' ) . '</p></div>';
				}
			}
			// 2) Conteúdo colado na textarea
			elseif ( ! empty( $_POST['import_json'] ) ) {
				$json_raw = wp_unslash( $_POST['import_json'] );
			}

			// Processa o JSON
			if ( $json_raw ) {
				$data = json_decode( $json_raw, true );

				if ( json_last_error() !== JSON_ERROR_NONE ) {
					echo '<div class="error"><p>' . esc_html( json_last_error_msg() ) . '</p></div>';
				} elseif ( ! isset( $data['courses'] ) || ! is_array( $data['courses'] ) ) {
					echo '<div class="error"><p>' . esc_html__( 'Estrutura JSON inválida.', 'tutor-ead' ) . '</p></div>';
				} else {
					$imported = 0;
					foreach ( $data['courses'] as $course ) {
						$course_id = self::upsert_course( $course );

						// Processa módulos, unidades e aulas de forma recursiva
						if ( ! empty( $course['modules'] ) ) {
							self::process_course_items( $course_id, $course['modules'] );
						}
						$imported++;
					}

					echo '<div class="updated"><p>' . esc_html( $imported ) . ' ' . __( 'curso(s) importado(s) com sucesso!', 'tutor-ead' ) . '</p></div>';
					// AINDA: reativar os logs de forma a refletir a recursividade
					// self::print_logs( $modules_updated, $modules_inserted, $lessons_updated, $lessons_inserted );
				}
			}
		}

		/* -------- INTERFACE -------- */
		require plugin_dir_path( __FILE__ ) . 'views/import-export-page.php';
	}

	/**
	 * Processa recursivamente os itens do curso (módulos, unidades, aulas).
	 */
	private static function process_course_items( $course_id, $modules, $parent_id = 0 ) {
		foreach ( $modules as $module_data ) {
			// Insere/atualiza o módulo/unidade atual
			$module_result = self::upsert_module( $course_id, $module_data, $parent_id );
			$new_module_id = $module_result['id'];

			// Processa as unidades filhas (recursão)
			if ( ! empty( $module_data['units'] ) ) {
				self::process_course_items( $course_id, $module_data['units'], $new_module_id );
			}

			// Processa as aulas deste módulo/unidade
			if ( ! empty( $module_data['lessons'] ) ) {
				foreach ( $module_data['lessons'] as $lesson_data ) {
					self::upsert_lesson( $new_module_id, $lesson_data );
				}
			}
		}
	}


	/** Exemplo de JSON (botão “?”) */
	public static function get_json_example() {
		return <<<JSON
{
  "courses": [
    {
      "course_id": 1,
      "title": "Nome do Curso",
      "description": "Descrição do curso",
      "capa_img": "https://exemplo.com/curso.jpg",
      "modules": [
        {
          "module_id": 10,
          "title": "Nome do Módulo Principal",
          "description": "Descrição do módulo",
          "units": [
            {
              "module_id": 11,
              "title": "Nome da Unidade (Sub-módulo)",
              "description": "Descrição da unidade",
              "lessons": [
                {
                  "lesson_id": 101,
                  "title": "Aula dentro da Unidade",
                  "content": "Conteúdo da aula..."
                }
              ]
            }
          ],
          "lessons": [
            {
              "lesson_id": 100,
              "title": "Aula dentro do Módulo Principal",
              "content": "Conteúdo da aula...",
              "video_url": "https://youtube.com/exemplo"
            }
          ]
        }
      ]
    }
  ]
}
JSON;
	}

	/* ------------------------------------------------------------------ *
	 * UPSERT helpers
	 * ------------------------------------------------------------------ */

	private static function upsert_course( $course ) {
		global $wpdb;

		$cid = $course['course_id'] ?? $course['id'] ?? null;
		$existing = $cid ? $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}tutoread_courses WHERE id = %d",
				$cid
			)
		) : null;

		if ( $existing ) {
			$wpdb->update(
				"{$wpdb->prefix}tutoread_courses",
				[
					'title'       => sanitize_text_field( $course['title'] ),
					'description' => sanitize_textarea_field( $course['description'] ),
					'capa_img'    => esc_url_raw( $course['capa_img'] ),
				],
				[ 'id' => $existing ]
			);
			return (int) $existing;
		}

		$wpdb->insert(
			"{$wpdb->prefix}tutoread_courses",
			[
				'title'       => sanitize_text_field( $course['title'] ),
				'description' => sanitize_textarea_field( $course['description'] ),
				'capa_img'    => esc_url_raw( $course['capa_img'] ),
			]
		);
		return (int) $wpdb->insert_id;
	}

	private static function upsert_module( $course_id, $module, $parent_id = 0 ) {
		global $wpdb;

		$mid = $module['module_id'] ?? $module['id'] ?? null;
		$existing = $mid ? $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}tutoread_modules WHERE id = %d AND course_id = %d",
				$mid,
				$course_id
			)
		) : null;

		$data = [
			'title'       => sanitize_text_field( $module['title'] ),
			'description' => sanitize_textarea_field( $module['description'] ),
			'capa_img'    => esc_url_raw( $module['capa_img'] ?? '' ),
			'parent_id'   => (int) $parent_id,
		];

		if ( $existing ) {
			$wpdb->update(
				"{$wpdb->prefix}tutoread_modules",
				$data,
				[ 'id' => $existing ]
			);
			return [
				'id'      => (int) $existing,
				'type'    => 'module',
				'action'  => 'updated',
				'message' => "Módulo/Unidade atualizado(a): '{$data['title']}' (ID $existing)",
			];
		}

		$data['course_id'] = $course_id;
		$wpdb->insert( "{$wpdb->prefix}tutoread_modules", $data );
		$new_id = (int) $wpdb->insert_id;

		return [
			'id'      => $new_id,
			'type'    => 'module',
			'action'  => 'inserted',
			'message' => "Módulo/Unidade inserido(a): '{$data['title']}' (Novo ID $new_id)",
		];
	}

	private static function upsert_lesson( $module_id, $lesson ) {
		global $wpdb;

		$lid = $lesson['lesson_id'] ?? $lesson['id'] ?? null;
		$existing = $lid ? $wpdb->get_var(
			$wpdb->prepare(
				"SELECT id FROM {$wpdb->prefix}tutoread_lessons WHERE id = %d AND module_id = %d",
				$lid,
				$module_id
			)
		) : null;

		if ( $existing ) {
			$wpdb->update(
				"{$wpdb->prefix}tutoread_lessons",
				[
					'title'       => sanitize_text_field( $lesson['title'] ),
					'description' => sanitize_textarea_field( $lesson['description'] ),
					'content'     => wp_kses_post( $lesson['content'] ),
					'video_url'   => esc_url_raw( $lesson['video_url'] ),
					'capa_img'    => esc_url_raw( $lesson['capa_img'] ),
				],
				[ 'id' => $existing ]
			);
			return [
				'id'      => (int) $existing,
				'type'    => 'lesson',
				'action'  => 'updated',
				'message' => "Aula atualizada: '{$lesson['title']}' (ID $existing)",
			];
		}

		$wpdb->insert(
			"{$wpdb->prefix}tutoread_lessons",
			[
				'module_id'   => $module_id,
				'title'       => sanitize_text_field( $lesson['title'] ),
				'description' => sanitize_textarea_field( $lesson['description'] ),
				'content'     => wp_kses_post( $lesson['content'] ),
				'video_url'   => esc_url_raw( $lesson['video_url'] ),
				'capa_img'    => esc_url_raw( $lesson['capa_img'] ),
			]
		);
		$new_id = (int) $wpdb->insert_id;

		return [
			'id'      => $new_id,
			'type'    => 'lesson',
			'action'  => 'inserted',
			'message' => "Aula inserida: '{$lesson['title']}' (Novo ID $new_id)",
		];
	}

	private static function build_module_tree(array &$elements, $parentId = 0) {
		$branch = [];
		global $wpdb;

		foreach ($elements as &$element) {
			if ($element['parent_id'] == $parentId) {
				$children = self::build_module_tree($elements, $element['id']);
				if ($children) {
					// Se são "unidades" (filhos de um módulo)
					$element['units'] = $children;
				}

				// Pega as aulas para o módulo/unidade atual
				$element['lessons'] = $wpdb->get_results(
					$wpdb->prepare(
						"SELECT id as lesson_id, title, description, content, video_url, capa_img FROM {$wpdb->prefix}tutoread_lessons WHERE module_id = %d ORDER BY lesson_order ASC",
						$element['id']
					),
					ARRAY_A
				);

				$branch[] = $element;
				unset($element);
			}
		}
		return $branch;
	}


	/* ------------------------------------------------------------------ *
	 * Exporta curso
	 * ------------------------------------------------------------------ */
	public static function handle_export_course() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Acesso negado.', 'tutor-ead' ) );
		}

		$course_id = isset( $_GET['course_id'] ) ? intval( $_GET['course_id'] ) : 0;
		if ( ! $course_id ) {
			wp_die( __( 'Curso não informado.', 'tutor-ead' ) );
		}

		global $wpdb;

		$course = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id as course_id, title, description, capa_img FROM {$wpdb->prefix}tutoread_courses WHERE id = %d",
				$course_id
			),
			ARRAY_A
		);
		if ( ! $course ) {
			wp_die( __( 'Curso não encontrado.', 'tutor-ead' ) );
		}

		// 1. Pega todos os módulos e unidades do curso de uma vez
		$all_modules = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, parent_id, title, description, capa_img FROM {$wpdb->prefix}tutoread_modules WHERE course_id = %d ORDER BY module_order ASC",
				$course_id
			),
			ARRAY_A
		);

		// 2. Monta a árvore hierárquica
		$course['modules'] = self::build_module_tree($all_modules);

		$output = [ 'courses' => [ $course ] ];

		$slug = sanitize_title( $course['title'] );
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $slug . '-' . $course_id . '.json"' );
		echo wp_json_encode( $output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );
		exit;
	}

	/**
	 * AJAX handler para buscar o JSON de um curso para edição direta.
	 */
	public static function handle_get_course_json_for_editing() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( [ 'message' => 'Acesso negado.' ] );
		}

		check_ajax_referer( 'tutor_ead_get_json_nonce', 'nonce' );

		$course_id = isset( $_POST['course_id'] ) ? intval( $_POST['course_id'] ) : 0;
		if ( ! $course_id ) {
			wp_send_json_error( [ 'message' => 'Curso não informado.' ] );
		}

		global $wpdb;

		$course = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id as course_id, title, description, capa_img FROM {$wpdb->prefix}tutoread_courses WHERE id = %d",
				$course_id
			),
			ARRAY_A
		);

		if ( ! $course ) {
			wp_send_json_error( [ 'message' => 'Curso não encontrado.' ] );
		}

		// 1. Pega todos os módulos e unidades do curso de uma vez
		$all_modules = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, parent_id, title, description, capa_img FROM {$wpdb->prefix}tutoread_modules WHERE course_id = %d ORDER BY module_order ASC",
				$course_id
			),
			ARRAY_A
		);

		// 2. Monta a árvore hierárquica
		$course['modules'] = self::build_module_tree( $all_modules );

		$output = [ 'courses' => [ $course ] ];

		wp_send_json_success( $output );
	}
}

/* Init */
ImportExportCourses::init();
