<?php
/**
 * View – Importar/Exportar Cursos
 *
 * Recebe o contexto de TutorEAD\Admin\ImportExportCourses::render_page()
 * (variáveis como $wpdb já estão disponíveis).
 *
 * Observação: mantenha‑se atento a escaping (esc_html, esc_attr, esc_url) mesmo na view.
 */

defined( 'ABSPATH' ) || exit;

// Cor de destaque definida nas opções do plugin
$highlight_color = get_option( 'tutor_ead_highlight_color', '#0073aa' );
?>

	<style>
		/* Estilos do CodeMirror */

		.CodeMirror {
			border: 1px solid #e5e7eb;
			border-radius: 8px;
			height: auto;
			min-height: 300px;
			font-family: monospace;
			font-size: 14px;
		}

		/* Tema de Cores Personalizado */
		.cm-tutoread-course { color: #5ea3d8; font-weight: bold; }
		.cm-tutoread-module { color: #4caf50; font-weight: bold; }
		.cm-tutoread-unit   { color: #9c27b0; font-weight: bold; }
		.cm-tutoread-lesson { color: #ff9800; font-weight: bold; }
		.cm-tutoread-text   { color: #795548; }

		/* Barra de Ações Fixa */
		.import-actions-footer {
			position: sticky;
			bottom: 0;
			background-color: rgba(255, 255, 255, 0.9);
			backdrop-filter: blur(5px);
			padding: 16px 24px;
			margin: 20px -24px -24px -24px; /* Compensa o padding do card pai */
			border-top: 1px solid #e5e7eb;
			border-bottom-left-radius: 12px;
			border-bottom-right-radius: 12px;
			display: flex;
			justify-content: flex-end;
			align-items: center;
			gap: 12px;
			z-index: 10;
		}


	.overlay-content .button {
		padding: 6px 12px !important;
		font-size: 13px !important;
		font-weight: 600 !important;
		border-radius: 5px !important;
		text-decoration: none;
		display: inline-flex;
		align-items: center;
		gap: 5px;
	}
	.overlay-content .button.download-json-btn {
		background-color: <?php echo $highlight_color; ?>;
		border-color: transparent;
		color: #fff;
	}
	.overlay-content .button.download-json-btn .dashicons {
		color: #fff;
	}
	.overlay-content .button.edit-json-btn {
		background-color: #fff;
		border-color: #ccc;
		color: #333;
	}
	.overlay-content .button.edit-json-btn .dashicons {
		color: #333;
	}
	.edit-json-btn:hover {
		background: #dcdcde !important;
	}
	/* ——— Layout geral ——— */
    .tutor-import-export-wrap{
        font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif;
        background:#f3f4f6;
        padding:32px;
        min-height:100vh;
        max-width: 1600px;
        margin: 40px auto 0 auto; /* Margem superior, centralizado horizontalmente */
        position: relative; /* Para criar contexto de empilhamento */
        z-index: 0; /* Para garantir que fique abaixo de cabeçalhos com z-index maior */
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        align-items: flex-start;
    }
	.tutor-import-export-wrap *{box-sizing:border-box}

	/* ——— Cabeçalho ——— */
	.import-export-header{display:flex;align-items:center;gap:12px;margin-bottom:32px}
	.import-export-title{font-size:32px;font-weight:600;color:#1f2937;margin:0}
	#json-example-btn{background:#fff;color:#374151;border:1px solid #e5e7eb;width:36px;height:36px;padding:6px 12px;border-radius:6px;font-size:14px;font-weight:500;cursor:pointer;transition:.2s;display:inline-flex;align-items:center;justify-content:center}
	#json-example-btn:hover{background:#f9fafb;border-color:#d1d5db}

	/* ——— Bloco do exemplo JSON (toggle) ——— */
	#json-example{display:none;background:#f9fafb;border:1px solid #e5e7eb;border-radius:8px;padding:20px;margin-bottom:32px}
	#json-example pre{margin:0;font:13px/1.4 "Courier New",monospace;color:#1f2937;white-space:pre-wrap;word-break:break-all}

	/* ——— Cartões reutilizados ——— */
	.tutor-ie-card{background:#fff;border:1px solid #e5e7eb;border-radius:12px;padding:24px;box-shadow:0 1px 3px rgba(0,0,0,.05);margin-bottom:24px}
	.ie-card-title{display:flex;align-items:center;gap:12px;font-size:20px;font-weight:600;color:#1f2937;margin:0 0 20px}
	.ie-card-title .dashicons{font-size:24px;color:<?php echo $highlight_color; ?>}

	/* ——— Form elements ——— */
	.form-group{margin-bottom:20px}
	.form-group label{display:block;font-weight:600;color:#374151;font-size:14px;margin-bottom:8px}
	.tutor-import-export-wrap textarea{width:100%;min-height:300px;padding:12px;font:14px "Courier New",monospace;background:#fff;border:1px solid #e5e7eb;border-radius:8px;resize:vertical;transition:.2s}
	.tutor-import-export-wrap textarea:focus{outline:none;border-color:<?php echo $highlight_color; ?>;box-shadow:0 0 0 3px <?php echo $highlight_color; ?>20}
	.tutor-import-export-wrap .button-primary{background:<?php echo $highlight_color; ?>;color:#fff;border:none;padding:12px 24px;border-radius:8px;font-weight:600;font-size:14px;cursor:pointer;display:inline-flex;align-items:center;gap:8px;transition:.2s}
	.tutor-import-export-wrap .button-primary:hover{background:<?php echo $highlight_color; ?>e6;transform:translateY(-1px);box-shadow:0 4px 6px -1px rgba(0,0,0,.1),0 2px 4px -1px rgba(0,0,0,.06)}

	.divider{height:1px;background:#e5e7eb;margin:32px 0}

	/* ——— Upload JSON ——— */
	.upload-section {
		text-align: center;
		padding: 40px;
		border: 2px dashed #e5e7eb;
		border-radius: 12px;
		background: #fafbfc;
		transition: all 0.3s ease;
		position: relative;
		margin-bottom: 20px;
	}
	
	.upload-section.drag-over {
		border-color: <?php echo $highlight_color; ?>;
		background: <?php echo $highlight_color; ?>05;
	}
	
	.upload-icon {
		width: 64px;
		height: 64px;
		background: <?php echo $highlight_color; ?>10;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		margin: 0 auto 20px;
	}
	
	.upload-icon .dashicons {
		font-size: 32px;
		color: <?php echo $highlight_color; ?>;
	}
	
	.upload-title {
		font-size: 18px;
		font-weight: 600;
		color: #1f2937;
		margin: 0 0 8px 0;
	}
	
	.upload-description {
		font-size: 14px;
		color: #6b7280;
		margin: 0 0 20px 0;
	}
	
	.file-input-wrapper {
		position: relative;
		display: inline-block;
	}
	
	.file-input-wrapper input[type="file"] {
		position: absolute;
		opacity: 0;
		width: 100%;
		height: 100%;
		cursor: pointer;
	}
	
	.upload-button {
		background: <?php echo $highlight_color; ?>;
		color: #fff;
		border: none;
		padding: 12px 24px;
		border-radius: 8px;
		font-weight: 600;
		font-size: 14px;
		cursor: pointer;
		display: inline-flex;
		align-items: center;
		gap: 8px;
		transition: .2s;
	}
	
	.upload-button:hover {
		background: <?php echo $highlight_color; ?>e6;transform:translateY(-1px);box-shadow:0 4px 6px -1px rgba(0,0,0,.1),0 2px 4px -1px rgba(0,0,0,.06)}
	
	.toggle-mode {
		display: inline-block;
		margin-top: 20px;
		color: #6b7280;
		font-size: 13px;
		text-decoration: none;
		transition: color 0.2s;
	}
	
	.toggle-mode:hover {
		color: <?php echo $highlight_color; ?>;
		text-decoration: underline;
	}
	
	.file-name {
		margin-top: 16px;
		padding: 12px;
		background: #f3f4f6;
		border-radius: 8px;
		font-size: 14px;
		color: #1f2937;
		display: none;
	}
	
	.file-name .dashicons {
		color: #10b981;
		margin-right: 8px;
		vertical-align: middle;
	}
	
	.file-name-text {
		vertical-align: middle;
	}
	
	#text-mode {
		display: none;
	}

    /* Estilos para o Preview */
    #import-preview-content ul {
        list-style-type: none;
        padding-left: 25px;
    }
    #import-preview-content li {
        padding: 5px 0;
        font-size: 14px;
    }
    .preview-list-course > li { font-size: 16px; }
    .preview-list-module > li { font-weight: bold; }
    .preview-list-unit > li { font-weight: normal; }
    .preview-list-lesson > li { font-weight: normal; font-style: italic; }

    .badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: bold;
        text-transform: uppercase;
    }
    .status-new {
        background-color: #d4edda; color: #155724;
    }
    .status-updated {
        background-color: #fff3cd; color: #856404;
    }
    .status-unchanged {
        background-color: #e9ecef; color: #495057;
    }

    .item-icon::before {
        font-family: dashicons;
        margin-right: 8px;
        color: #555;
    }
    .icon-course::before { content: '\f509'; }
    .icon-module::before { content: '\f111'; }
    .icon-unit::before { content: '\f109'; }
    .icon-lesson::before { content: '\f480'; }

    /* Layout da Página com Grid */
    .tutor-import-export-wrap {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        align-items: flex-start;
    }
    .tutor-import-export-wrap > * {
        grid-column: 1 / -1; /* Filhos diretos ocupam a largura toda por padrão */
    }
    .tutor-import-export-wrap > .tutor-ie-card {
        grid-column: auto; /* Reseta para os cards ocuparem uma coluna cada */
    }
    	#import-preview-wrapper {
            grid-column: auto;
            visibility: hidden; 
    		position: sticky;
    		top: 48px; /* Deslocamento para a barra de admin do WP */
        }    #import-preview-wrapper.is-visible { 
        visibility: visible; 
    }

	/* ——— Grid de cursos melhorado ——— */
	.courses-grid {
		display: grid;
		grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
		gap: 24px;
		margin-top: 24px;
	}
	
	.course-card {
		position: relative;
		display: block;
		overflow: hidden;
		border-radius: 12px;
		background: #ffffff;
		box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
		transition: all 0.3s ease;
		text-decoration: none;
		height: 100%;
	}
	
	.course-card:hover {
		transform: translateY(-4px);
		box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
	}
	
	.course-image-wrapper {
		position: relative;
		height: 180px;
		overflow: hidden;
		background: #f3f4f6;
	}
	
	.course-card img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		transition: transform 0.3s ease;
	}
	
	.course-card:hover img {
		transform: scale(1.05);
	}
	
	.course-info {
		padding: 20px;
		position: relative;
		z-index: 1;
		background: #ffffff;
	}
	
	.course-title {
		font-size: 16px;
		font-weight: 600;
		color: #1f2937;
		margin: 0 0 8px 0;
		display: -webkit-box;
		-webkit-line-clamp: 2;
		-webkit-box-orient: vertical;
		overflow: hidden;
		line-height: 1.4;
	}
	
	.course-meta {
		display: flex;
		align-items: center;
		gap: 8px;
		font-size: 13px;
		color: #6b7280;
	}
	
	.course-meta .dashicons {
		font-size: 16px;
		color: <?php echo $highlight_color; ?>;
	}
	
	.card-overlay {
		position: absolute;
		inset: 0;
		background: linear-gradient(to bottom, transparent 0%, rgba(0, 0, 0, 0.85) 100%);
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		opacity: 0;
		transition: opacity 0.3s ease;
		z-index: 2;
	}
	
	.course-card:hover .card-overlay {
		opacity: 1;
	}
	
	.overlay-content {
		transform: translateY(20px);
		transition: transform 0.3s ease;
		text-align: center;
	}
	
	.course-card:hover .overlay-content {
		transform: translateY(0);
	}
	
	.card-overlay .download-icon {
		width: 56px;
		height: 56px;
		background: <?php echo $highlight_color; ?>;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		margin: 0 auto 12px;
		box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
	}
	
	.card-overlay .dashicons {
		font-size: 28px;
		color: #ffffff;
		margin: 0;
	}
	
	.card-overlay .download-text {
		font-size: 15px;
		font-weight: 600;
		color: #ffffff;
		letter-spacing: 0.5px;
	}
	
	.card-overlay .file-size {
		font-size: 13px;
		color: rgba(255, 255, 255, 0.8);
		margin-top: 4px;
	}
	
	.export-badge {
		position: absolute;
		top: 12px;
		right: 12px;
		background: <?php echo $highlight_color; ?>;
		color: #ffffff;
		padding: 4px 12px;
		border-radius: 20px;
		font-size: 12px;
		font-weight: 600;
		text-transform: uppercase;
		letter-spacing: 0.5px;
		z-index: 3;
	}

	@media(max-width: 768px){
		.tutor-import-export-wrap{padding:16px}
		.import-export-header{flex-direction:column;align-items:flex-start}
		.courses-grid {
			grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
			gap: 16px;
		}
		.upload-section {
			padding: 24px;
		}
	}
</style>

<div class="wrap tutor-import-export-wrap">
	<div class="import-export-header">
		<h1 class="import-export-title"><?php esc_html_e( 'Importar/Exportar Cursos', 'tutor-ead' ); ?></h1>
		<button id="json-example-btn" title="<?php esc_attr_e( 'Exibir exemplo de JSON', 'tutor-ead' ); ?>">?</button>
	</div>

	<!-- Exemplo JSON (toggle) -->
	<div id="json-example">
		<pre><?php echo esc_html( \TutorEAD\Admin\ImportExportCourses::get_json_example() ); ?></pre>
	</div>

	<!-- ===== IMPORTAÇÃO ===== -->
	<div class="tutor-ie-card">
		<h2 class="ie-card-title"><span class="dashicons dashicons-upload"></span><?php esc_html_e( 'Importar Cursos', 'tutor-ead' ); ?></h2>
		<form method="post" enctype="multipart/form-data" id="tutor-ead-import-form">
			<?php wp_nonce_field( 'tutor_ead_import_course' ); ?>
			<?php wp_nonce_field( 'tutor_ead_get_json_nonce', 'tutor_ead_get_json_nonce_field' ); ?>
			
			<!-- Modo Upload -->
			<div id="upload-mode">
				<div class="form-group">
					<label for="import_file"><?php esc_html_e( 'Enviar arquivo JSON', 'tutor-ead' ); ?></label>
					<div class="upload-section" id="drop-zone">
						<div class="upload-icon">
							<span class="dashicons dashicons-cloud-upload"></span>
						</div>
						<h3 class="upload-title"><?php esc_html_e( 'Arraste e solte seu arquivo JSON aqui', 'tutor-ead' ); ?></h3>
						<p class="upload-description"><?php esc_html_e( 'ou clique para selecionar o arquivo', 'tutor-ead' ); ?></p>
						<div class="file-input-wrapper">
							<input type="file" id="import_file" name="import_file" accept="application/json,text/plain">
							<button type="button" class="upload-button">
								<span class="dashicons dashicons-upload"></span>
								<?php esc_html_e( 'Selecionar Arquivo', 'tutor-ead' ); ?>
							</button>
						</div>
						<div id="file-name" class="file-name">
							<span class="dashicons dashicons-yes"></span>
							<span id="file-name-text"></span>
						</div>
					</div>
				</div>
				<a href="#" class="toggle-mode" data-mode="text">
					<?php esc_html_e( 'Colar JSON em texto', 'tutor-ead' ); ?>
				</a>
			</div>
			
			<!-- Modo Texto (oculto por padrão) -->
			<div id="text-mode">
				<div class="form-group">
					<textarea id="tutor-ead-json-textarea" name="import_json" rows="10" placeholder="<?php esc_attr_e( 'Cole aqui o JSON com os cursos…', 'tutor-ead' ); ?>"></textarea>
				</div>
			</div>
			
			<div class="import-actions-footer">
				<a href="#" class="toggle-mode" data-mode="upload" style="margin-right: auto;"><?php esc_html_e( 'Fazer upload de arquivo', 'tutor-ead' ); ?></a>

				<button type="button" id="tutor-preview-btn" class="button" style="margin-right: 10px;"><?php _e('Pré-visualizar', 'tutor-ead'); ?></button>
				<div id="submit-button-wrapper" style="display: none;"><?php submit_button( __('Importar Cursos', 'tutor-ead') ); ?></div>
			</div>
        </form>
    </div>

    <div class="tutor-ie-card" id="import-preview-wrapper">
        <h2 class="ie-card-title"><span class="dashicons dashicons-visibility"></span><?php esc_html_e( 'Pré-visualização', 'tutor-ead' ); ?></h2>
        <div id="preview-submit-container" style="margin-bottom: 24px;"></div>
        <div id="import-preview-content"><p class="description"><?php _e('Clique em "Pré-visualizar" para ver a estrutura do curso aqui.', 'tutor-ead'); ?></p></div>
    </div>

	<div class="divider"></div>

	<!-- ===== EXPORTAÇÃO (cards) ===== -->
	<div class="tutor-ie-card">
		<h2 class="ie-card-title"><span class="dashicons dashicons-download"></span><?php esc_html_e( 'Exportar Curso', 'tutor-ead' ); ?></h2>

		<?php
		global $wpdb;
		$courses = $wpdb->get_results(
			"SELECT id, title, capa_img FROM {$wpdb->prefix}tutoread_courses",
			ARRAY_A
		);

		if ( ! $courses ) {
			echo '<p>' . esc_html__( 'Nenhum curso encontrado.', 'tutor-ead' ) . '</p>';
		} else {
			echo '<div class="courses-grid">';
			foreach ( $courses as $course ) {
				$img = $course['capa_img'] ?: plugin_dir_url( __FILE__ ) . '../../assets/img/sem-capa.jpg';
				$url = add_query_arg(
					[
						'action'    => 'export_course',
						'course_id' => (int) $course['id'],
					],
					admin_url( 'admin-post.php' )
				);
				?>
				<div class="course-card">
					<span class="export-badge">JSON</span>
					<div class="course-image-wrapper">
						<img src="<?php echo esc_url( $img ); ?>" alt="<?php echo esc_attr( $course['title'] ); ?>">
					</div>
					<div class="course-info">

						<h3 class="course-title"><?php echo esc_html( $course['title'] ); ?></h3>
						<div class="course-meta">
							<span class="dashicons dashicons-book"></span>
							<span><?php esc_html_e( 'Curso', 'tutor-ead' ); ?></span>
						</div>
					</div>
					<div class="card-overlay">
						<div class="overlay-content">
							<a href="<?php echo esc_url( $url ); ?>" class="button download-json-btn">
								<span class="dashicons dashicons-download"></span>
								<?php esc_html_e( 'Exportar', 'tutor-ead' ); ?>
							</a>
							<button type="button" class="button edit-json-btn" data-course-id="<?php echo (int) $course['id']; ?>">
								<span class="dashicons dashicons-edit"></span>
								<?php esc_html_e( 'Editar JSON', 'tutor-ead' ); ?>
							</button>
						</div>
					</div>
				</div>
				<?php
			}
			echo '</div>';
		}
		?>
	</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // --- Variáveis Globais do Script ---
    let jsonEditor = null;
    const textArea = document.getElementById('tutor-ead-json-textarea');
    const uploadModeDiv = document.getElementById('upload-mode');
    const textModeDiv = document.getElementById('text-mode');

    // --- Inicialização do CodeMirror ---
    if (typeof CodeMirror !== 'undefined') {
        // Definição do modo de sobreposição para realce personalizado
        CodeMirror.defineMode("tutoread-json", function(config) {
            return CodeMirror.overlayMode(CodeMirror.getMode(config, {name: "javascript", json: true}), {
                token: function(stream) {
                    if (stream.match(/\"(course_id|capa_img)\"/)) return "tutoread-course";
                    if (stream.match(/\"(module_id)\"/)) return "tutoread-module";
                    if (stream.match(/\"(title|description|content|video_url)\"/)) return "tutoread-text";
                    if (stream.match(/\"(lesson_id)\"/)) return "tutoread-lesson";
                    
                    while (stream.next() != null && !stream.match(/\"/, false)) {}
                    return null;
                }
            });
        });

        // Inicializa o editor a partir da textarea
        jsonEditor = CodeMirror.fromTextArea(textArea, {
            mode: "tutoread-json",
            theme: "material",
            lineNumbers: true,
            lineWrapping: true,
        });

        // O editor começa escondido, pois o modo de texto começa escondido
        jsonEditor.getWrapperElement().style.display = 'none';
    }

    // --- Lógica para alternar entre Upload e Modo Texto ---
    document.querySelectorAll('.toggle-mode').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const mode = this.getAttribute('data-mode');
            
            if (mode === 'text') {
                uploadModeDiv.style.display = 'none';
                textModeDiv.style.display = 'block';
                if (jsonEditor) {
                    jsonEditor.getWrapperElement().style.display = 'block';
                    // CRUCIAL: Atualiza o editor após ele se tornar visível
                    setTimeout(() => {
                        jsonEditor.refresh();
                        jsonEditor.setSize(null, 300); // Força a altura
                    }, 1);
                }
            } else {
                uploadModeDiv.style.display = 'block';
                textModeDiv.style.display = 'none';
                if (jsonEditor) {
                    jsonEditor.getWrapperElement().style.display = 'none';
                }
            }
        });
    });

    // --- Lógica para o botão "Editar JSON" ---
    document.addEventListener('click', function(e) {
        const editBtn = e.target.closest('.edit-json-btn');
        if (!editBtn) return;

        e.preventDefault();
        e.stopPropagation();

        const courseId = editBtn.dataset.courseId;
        const originalHTML = editBtn.innerHTML;
        editBtn.innerHTML = '<span class="dashicons dashicons-update-alt spin"></span> Carregando...';
        editBtn.disabled = true;

        const nonce = document.getElementById('tutor_ead_get_json_nonce_field').value;
        const formData = new FormData();
        formData.append('action', 'get_course_json_for_editing');
        formData.append('course_id', courseId);
        formData.append('nonce', nonce);

        fetch(ajaxurl, { method: 'POST', body: formData })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                uploadModeDiv.style.display = 'none';
                textModeDiv.style.display = 'block';
                
                if (jsonEditor) {
                    jsonEditor.getWrapperElement().style.display = 'block';
                    jsonEditor.setValue(JSON.stringify(data.data, null, 2));
                    // CRUCIAL: Atualiza o editor após preencher e garantir que está visível
                    setTimeout(() => {
                        jsonEditor.refresh();
                        jsonEditor.setSize(null, 300); // Força a altura
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        jsonEditor.focus();
                    }, 1);
                }
            } else {
                alert('Erro ao buscar JSON: ' + data.data.message);
            }
        })
        .catch(error => {
            console.error('Erro na requisição AJAX:', error);
            alert('Ocorreu um erro de comunicação. Tente novamente.');
        })
        .finally(() => {
            editBtn.innerHTML = originalHTML;
            editBtn.disabled = false;
        });
    });

    // --- Lógica para o botão "Pré-visualizar" ---
    const previewBtn = document.getElementById('tutor-preview-btn');
    if(previewBtn) {
        previewBtn.addEventListener('click', function() {
            if (jsonEditor) {
                // Garante que a textarea original tenha o conteúdo mais recente do editor
                jsonEditor.save();
            }
        });
    }

    // --- Lógica para o botão de exemplo de JSON ---
    const exampleBtn = document.getElementById('json-example-btn');
    if (exampleBtn) {
        exampleBtn.addEventListener('click', function() {
            const ex = document.getElementById('json-example');
            ex.style.display = (ex.style.display === 'none' || ex.style.display === '') ? 'block' : 'none';
        });
    }

    // --- Lógica para Drag & Drop ---
    const fileInput = document.getElementById('import_file');
	const dropZone = document.getElementById('drop-zone');
	const fileNameDiv = document.getElementById('file-name');
	const fileNameText = document.getElementById('file-name-text');
	
	if (fileInput && dropZone && fileNameDiv && fileNameText) {
        dropZone.addEventListener('click', () => fileInput.click());
        fileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) displayFileName(this.files[0].name);
        });

        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropZone.addEventListener(eventName, preventDefaults, false);
            document.body.addEventListener(eventName, preventDefaults, false);
        });
        ['dragenter', 'dragover'].forEach(eventName => dropZone.addEventListener(eventName, () => dropZone.classList.add('drag-over'), false));
        ['dragleave', 'drop'].forEach(eventName => dropZone.addEventListener(eventName, () => dropZone.classList.remove('drag-over'), false));
        
        dropZone.addEventListener('drop', function(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files && files[0]) {
                fileInput.files = files;
                displayFileName(files[0].name);
            }
        }, false);
    }

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    function displayFileName(name) {
        fileNameText.textContent = name;
        fileNameDiv.style.display = 'block';
    }
});
</script>
