<?php
/**
 * Tutor EAD – Enrollment Manager
 */

namespace TutorEAD\Admin;

defined( 'ABSPATH' ) || exit;

class EnrollmentManager {

	/*======================================================================
	 * LISTAGEM DE MATRÍCULAS
	 *====================================================================*/
	public static function enrollment_list_page() {
		global $wpdb;

		/* remover matrícula via GET */
		self::remove_enrollment();

		/* adicionar matrícula via formulário */
		if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['add_enrollment'] ) ) {
			$user_ids    = isset( $_POST['user_ids'] )   ? array_map( 'intval', $_POST['user_ids'] )   : [];
			$course_ids  = isset( $_POST['course_ids'] ) ? array_map( 'intval', $_POST['course_ids'] ) : [];
			$added_count = 0;

			foreach ( $user_ids as $user_id ) {
				foreach ( $course_ids as $course_id ) {
					$exists = $wpdb->get_var(
						$wpdb->prepare(
							"SELECT COUNT(*) FROM {$wpdb->prefix}matriculas WHERE user_id = %d AND course_id = %d",
							$user_id,
							$course_id
						)
					);
					if ( ! $exists ) {
						$wpdb->insert(
							"{$wpdb->prefix}matriculas",
							[
								'user_id'        => $user_id,
								'course_id'      => $course_id,
								'role'           => 'aluno',
								'data_matricula' => current_time( 'mysql' ),
							]
						);
						$added_count ++;
					}
				}
			}

			if ( $added_count > 0 ) {
				echo '<div class="notice notice-success is-dismissible"><p>' .
				     __( 'Matrículas adicionadas com sucesso!', 'tutor-ead' ) .
				     '</p></div>';
			} else {
				echo '<div class="notice notice-warning is-dismissible"><p>' .
				     __( 'Nenhuma nova matrícula foi adicionada. Verifique se os alunos já estão matriculados.', 'tutor-ead' ) .
				     '</p></div>';
			}
		}

		/* consulta das matrículas */
		$enrollments = $wpdb->get_results(
			"SELECT m.id, m.user_id, u.display_name, u.user_email, m.course_id,
			        c.title AS course_title, m.data_matricula
			   FROM {$wpdb->prefix}matriculas AS m
			   INNER JOIN {$wpdb->prefix}users AS u ON m.user_id = u.ID
			   INNER JOIN {$wpdb->prefix}tutoread_courses AS c ON m.course_id = c.id
		   ORDER BY m.data_matricula DESC"
		);

		echo '<div class="wrap">';
		echo '<h1>' . __( 'Lista de Matrículas', 'tutor-ead' ) . '</h1>';

		/* tabela */
		if ( $enrollments ) {
			echo '<table class="widefat fixed striped" id="enrollments-table">';
			echo '<thead><tr>';
			echo '<th>' . __( 'ID', 'tutor-ead' ) . '</th>';
			echo '<th>' . __( 'Aluno', 'tutor-ead' ) . '</th>';
			echo '<th>' . __( 'E‑mail', 'tutor-ead' ) . '</th>';
			echo '<th>' . __( 'Curso', 'tutor-ead' ) . '</th>';
			echo '<th>' . __( 'Data da Matrícula', 'tutor-ead' ) . '</th>';
			echo '<th>' . __( 'Ações', 'tutor-ead' ) . '</th>';
			echo '</tr></thead><tbody>';

			foreach ( $enrollments as $i => $enrollment ) {
				$class = $i > 2 ? ' class="enrollment-row hidden"' : '';
				echo "<tr{$class}>";
				echo '<td>' . esc_html( $enrollment->id ) . '</td>';
				echo '<td>' . esc_html( $enrollment->display_name ) . '</td>';
				echo '<td>' . esc_html( $enrollment->user_email ) . '</td>';
				echo '<td>' . esc_html( $enrollment->course_title ) . '</td>';
				echo '<td>' . esc_html( $enrollment->data_matricula ) . '</td>';
				echo '<td><a href="' .
				     admin_url( 'admin.php?page=tutor-ead-enrollment-list&remove_id=' . esc_attr( $enrollment->id ) ) .
				     '" class="button button-secondary">' . __( 'Remover', 'tutor-ead' ) . '</a></td>';
				echo '</tr>';
			}

			echo '</tbody></table>';

			if ( count( $enrollments ) > 3 ) {
				echo '<p><button id="toggle-enrollments" class="button">' .
				     __( 'Exibir tabela completa', 'tutor-ead' ) .
				     '</button></p>';
				echo '<style>
					.hidden{display:none;transition:all .3s ease}
					.fade-slide{opacity:0;max-height:0;overflow:hidden;transition:all .3s ease}
					.fade-slide.visible{opacity:1;max-height:200px}
				</style>';
				echo '<script>
					document.addEventListener("DOMContentLoaded",function(){
						const toggleBtn=document.getElementById("toggle-enrollments");
						const hiddenRows=document.querySelectorAll("#enrollments-table .enrollment-row");
						let expanded=false;
						if(toggleBtn){
							toggleBtn.addEventListener("click",function(){
								expanded=!expanded;
								hiddenRows.forEach(row=>{
									if(expanded){row.classList.remove("hidden");row.classList.add("fade-slide","visible");}
									else{row.classList.add("hidden");row.classList.remove("fade-slide","visible");}
								});
								toggleBtn.textContent=expanded?"Ocultar tabela":"Exibir tabela completa";
							});
						}
					});
				</script>';
			}
		} else {
			echo '<p>' . __( 'Nenhuma matrícula encontrada.', 'tutor-ead' ) . '</p>';
		}

		/* botão para abrir formulário */
		echo '<button id="add-enrollment-btn" type="button" class="button button-primary" style="margin-top:20px;">' .
		     __( 'Adicionar Nova Matrícula', 'tutor-ead' ) .
		     '</button>';

		/* formulário oculto */
		self::add_enrollment_form();

		/* script toggle do formulário */
		echo '<script>
			document.addEventListener("DOMContentLoaded",function(){
				const btn=document.getElementById("add-enrollment-btn");
				const form=document.getElementById("add-enrollment-form");
				if(!btn||!form)return;
				btn.addEventListener("click",()=>{
					const hidden=getComputedStyle(form).display==="none";
					form.style.display=hidden?"block":"none";
				});
			});
		</script>';

		/* seção JSON */
		self::render_json_enrollment_section();
		echo '</div>';
	}

	/*======================================================================
	 * FORMULÁRIO DE NOVA MATRÍCULA
	 *====================================================================*/
	public static function add_enrollment_form() {
		global $wpdb;

		echo '<div id="add-enrollment-form" style="display:none;margin-top:20px;background:#fff;padding:20px;border-radius:5px;box-shadow:0 0 10px rgba(0,0,0,.1);">';
		echo '<h2>' . __( 'Nova Matrícula', 'tutor-ead' ) . '</h2>';
		echo '<form method="POST">';

		echo '<h3>' . __( 'Selecione os Alunos', 'tutor-ead' ) . '</h3>';
		echo '<div style="max-height:300px;overflow-y:auto;border:1px solid #ddd;padding:10px;">';
		$students = get_users( [ 'role' => 'tutor_aluno' ] );
		foreach ( $students as $student ) {
			echo '<label style="display:inline-block;padding:5px 10px;margin:5px;border:1px solid #ccc;border-radius:3px;">';
			echo '<input type="checkbox" name="user_ids[]" value="' . esc_attr( $student->ID ) .
			     '" style="margin-right:5px;">' . esc_html( $student->display_name );
			echo '</label>';
		}
		echo '</div>';

		echo '<h3>' . __( 'Selecione os Cursos', 'tutor-ead' ) . '</h3>';
		echo '<div style="max-height:300px;overflow-y:auto;border:1px solid #ddd;padding:10px;">';
		$courses = $wpdb->get_results( "SELECT id,title FROM {$wpdb->prefix}tutoread_courses" );
		foreach ( $courses as $course ) {
			echo '<label style="display:inline-block;padding:5px 10px;margin:5px;border:1px solid #ccc;border-radius:3px;">';
			echo '<input type="checkbox" name="course_ids[]" value="' . esc_attr( $course->id ) .
			     '" style="margin-right:5px;">' . esc_html( $course->title );
			echo '</label>';
		}
		echo '</div>';

		echo '<p><input type="submit" name="add_enrollment" class="button button-primary" value="' .
		     __( 'Salvar Matrícula', 'tutor-ead' ) .
		     '"></p>';
		echo '</form></div>';
	}

	/*======================================================================
	 * MATRÍCULA VIA JSON – SEÇÃO
	 *====================================================================*/
	public static function render_json_enrollment_section() {
		echo '<h2 style="margin-top:40px;">Matrícula via JSON</h2>';
		echo '<pre>{
  "emails": ["email1@gmail.com", "email2@gmail.com"],
  "course_id": 12
}</pre>';
		echo '<textarea id="bulk-enroll-json" rows="6" style="width:100%;"></textarea>';
		echo '<p><button class="button" id="preview-enroll-btn">Pré‑visualizar JSON</button></p>';
		echo '<div id="preview-results" style="margin-top:20px;"></div>';
	}

	/*======================================================================
	 * REMOVER MATRÍCULA
	 *====================================================================*/
	public static function remove_enrollment() {
		global $wpdb;
		if ( isset( $_GET['remove_id'] ) ) {
			$wpdb->delete(
				"{$wpdb->prefix}matriculas",
				[ 'id' => intval( $_GET['remove_id'] ) ],
				[ '%d' ]
			);
			echo '<div class="notice notice-success is-dismissible"><p>' .
			     __( 'Matrícula removida com sucesso!', 'tutor-ead' ) .
			     '</p></div>';
		}
	}

	/*======================================================================
	 * AJAX: PRÉ‑VISUALIZAR JSON
	 *====================================================================*/
	public static function ajax_preview_bulk_enrollments() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Sem permissão', 403 );
		}
		check_ajax_referer( 'preview_bulk_enrollments', 'nonce' );

		$json_text = wp_unslash( $_POST['bulk_enroll_json'] ?? '' );
		$data      = json_decode( trim( $json_text ), true );

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			wp_send_json_error( [ 'message' => 'JSON inválido: ' . json_last_error_msg() ], 400 );
		}

		if ( ! isset( $data['emails'], $data['course_id'] ) || ! is_array( $data['emails'] ) ) {
			wp_send_json_error(
				[ 'message' => 'Formato inválido. \"emails\" e \"course_id\" são obrigatórios.' ],
				400
			);
		}

		global $wpdb;
		$course_identifier = $data['course_id'];

		$course = is_numeric( $course_identifier )
			? $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}tutoread_courses WHERE id = %d",
				intval( $course_identifier )
			) )
			: $wpdb->get_row( $wpdb->prepare(
				"SELECT * FROM {$wpdb->prefix}tutoread_courses WHERE title = %s",
				$course_identifier
			) );

		$course_info = $course
			? [ 'id' => intval( $course->id ), 'title' => $course->title ]
			: [ 'id' => null, 'title' => 'Curso não encontrado' ];

		$results = [];

		foreach ( $data['emails'] as $email ) {
			$email   = sanitize_email( $email );
			$user_id = email_exists( $email );
			$item    = [ 'email' => $email, 'status' => '', 'user_id' => null ];

			if ( ! $user_id ) {
				$item['status'] = 'not_found';
			} elseif ( $course ) {
				$exists = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COUNT(*) FROM {$wpdb->prefix}matriculas WHERE user_id=%d AND course_id=%d",
						$user_id,
						$course_info['id']
					)
				);
				$item['status']  = $exists ? 'already_enrolled' : 'ok';
				$item['user_id'] = $exists ? null : $user_id;
			} else {
				$item['status'] = 'course_not_found';
			}
			$results[] = $item;
		}

		wp_send_json_success( [ 'course' => $course_info, 'users' => $results ] );
	}

	/*======================================================================
	 * AJAX: CONFIRMAR MATRÍCULAS JSON
	 *====================================================================*/
	public static function ajax_confirm_bulk_enrollments() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Sem permissão', 403 );
		}

		$user_ids  = array_map( 'intval', $_POST['user_ids'] ?? [] );
		$course_id = intval( $_POST['course_id'] ?? 0 );
		$added     = 0;

		global $wpdb;

		foreach ( $user_ids as $user_id ) {
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$wpdb->prefix}matriculas WHERE user_id=%d AND course_id=%d",
					$user_id,
					$course_id
				)
			);
			if ( ! $exists ) {
				$wpdb->insert(
					"{$wpdb->prefix}matriculas",
					[
						'user_id'        => $user_id,
						'course_id'      => $course_id,
						'role'           => 'aluno',
						'data_matricula' => current_time( 'mysql' ),
					]
				);
				$added ++;
			}
		}

		wp_send_json_success( [ 'message' => "$added matrícula(s) adicionada(s)." ] );
	}

	/*======================================================================
	 * ENQUEUE SCRIPTS
	 *====================================================================*/
	public static function enqueue_scripts( $hook ) {
		/* executa só na página de matrículas */
		if ( $hook !== 'tutor-ead_page_tutor-ead-enrollment-list' ) {
			return;
		}

		/* caminho do arquivo principal do plugin */
		$plugin_main_file = dirname( __DIR__, 2 ) . '/plugin-tutor-eap.php';

		wp_enqueue_script(
			'tutor-ead-json-preview',
			plugins_url( 'assets/js/json-enrollment-preview.js', $plugin_main_file ),
			[ 'jquery' ],
			'1.0',
			true
		);

		wp_localize_script(
			'tutor-ead-json-preview',
			'TutorEAD_Enrollment',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'preview_bulk_enrollments' ),
			]
		);
	}
}

/* hooks */
add_action( 'admin_enqueue_scripts', [ 'TutorEAD\\Admin\\EnrollmentManager', 'enqueue_scripts' ] );
add_action( 'wp_ajax_preview_bulk_enrollments',  [ EnrollmentManager::class, 'ajax_preview_bulk_enrollments' ] );
add_action( 'wp_ajax_confirm_bulk_enrollments', [ EnrollmentManager::class, 'ajax_confirm_bulk_enrollments' ] );
