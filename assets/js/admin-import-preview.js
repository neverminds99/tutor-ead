document.addEventListener('DOMContentLoaded', function () {
    if (typeof TutorEAD_Import === 'undefined') {
        console.error('TutorEAD_Import object not found. The preview script will not run.');
        return;
    }

    const previewBtn = document.getElementById('tutor-preview-btn');
    const fileInput = document.getElementById('import_file');
    const textarea = document.querySelector('textarea[name="import_json"]');
    const previewWrapper = document.getElementById('import-preview-wrapper');
    const previewContent = document.getElementById('import-preview-content');

    if (!previewBtn || !previewWrapper || !previewContent) {
        return;
    }

    previewBtn.addEventListener('click', function () {
        let jsonRaw = textarea.value.trim();

        if (jsonRaw) {
            generatePreview(jsonRaw);
        } else if (fileInput.files && fileInput.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                generatePreview(e.target.result);
            };
            reader.readAsText(fileInput.files[0]);
        } else {
            alert('Por favor, cole um JSON ou selecione um arquivo para pré-visualizar.');
        }
    });

    function generatePreview(jsonString) {
        previewWrapper.classList.add('is-visible');
        previewContent.innerHTML = '<p>Analisando JSON...</p>';
        const previewBtn = document.getElementById('tutor-preview-btn');
        
        // Garante que o botão de importação de uma pré-visualização anterior seja removido.
        document.getElementById('preview-submit-container').innerHTML = '';
        // Garante que o botão de pré-visualizar esteja visível no início.
        if (previewBtn) {
            previewBtn.style.display = 'inline-block';
        }

        const params = new URLSearchParams();
        params.append('action', 'preview_course_import');
        params.append('nonce', TutorEAD_Import.nonce);
        params.append('import_json', jsonString);

        fetch(TutorEAD_Import.ajax_url, {
            method: 'POST',
            body: params
        })
        .then(response => response.json())
        .then(response => {
            if (!response.success) {
                previewContent.innerHTML = `<div class="notice notice-error"><p>${response.data.message}</p></div>`;
                return;
            }

            let warningsHtml = '';
            const warnings = response.data.warnings;
            const previewHtml = buildHtml(response.data.courses, 'course');

            if (warnings && warnings.length > 0) {
                // AVISOS ENCONTRADOS: Bloqueia a importação
                warningsHtml = '<div class="notice notice-error"><p><strong>Importação Bloqueada.</strong> Corrija os seguintes problemas no seu JSON e tente pré-visualizar novamente:</p><ul style="list-style: disc; margin-left: 20px;">';
                warnings.forEach(warning => {
                    warningsHtml += `<li>${warning}</li>`;
                });
                warningsHtml += '</ul></div>';
                
                previewContent.innerHTML = warningsHtml + previewHtml;
            } else {
                				// SEM AVISOS: Permite a importação
                				const previewSubmitContainer = document.getElementById('preview-submit-container');
                				previewSubmitContainer.innerHTML = ''; // Limpa o conteúdo anterior
                
                				const importButton = document.createElement('button');
                				importButton.type = 'button'; // Alterado para button para evitar submissão automática
                				importButton.className = 'button button-primary';
                				importButton.textContent = 'Importar Cursos';
                												importButton.addEventListener('click', function() {
                													const form = document.getElementById('tutor-ead-import-form');
                													HTMLFormElement.prototype.submit.call(form);
                												});                				previewSubmitContainer.appendChild(importButton);
                
                				if (previewBtn) {
                					previewBtn.style.display = 'none';
                				}
                				
                				previewContent.innerHTML = previewHtml;
                			}        })
        .catch(error => {
            previewContent.innerHTML = `<div class="notice notice-error"><p>Ocorreu um erro na requisição: ${error.message}</p></div>`;
        });
    }

    function buildHtml(items, type) {
        if (!items || items.length === 0) {
            return '';
        }

        let html = `<ul class="preview-list preview-list-${type}">`;

        const status_map = {
            'new': 'Novo',
            'updated': 'Atualizado',
            'unchanged': 'Inalterado'
        };

        items.forEach(item => {
            const title = item.title || 'Item sem título';
            const status = item.status || 'unknown';
            const badge = `<span class="badge status-${status}">${status_map[status] || status}</span>`;
            const icon = `<span class="item-icon icon-${type}"></span>`;

            html += `<li>${icon}<strong>${title}</strong> ${badge}`;

            let childrenHtml = '';
            if (item.modules && item.modules.length > 0) {
                childrenHtml += buildHtml(item.modules, 'module');
            }
            if (item.units && item.units.length > 0) {
                childrenHtml += buildHtml(item.units, 'unit');
            }
            if (item.lessons && item.lessons.length > 0) {
                childrenHtml += buildHtml(item.lessons, 'lesson');
            }

            if (childrenHtml) {
                html += `<ul>${childrenHtml}</ul>`;
            }

            html += '</li>';
        });

        html += '</ul>';
        return html;
    }
});
