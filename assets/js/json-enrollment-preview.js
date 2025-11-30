document.addEventListener('DOMContentLoaded', function () {
    console.log("DOM content loaded.");

    const previewBtn = document.getElementById('preview-enroll-btn');
    const textarea = document.getElementById('bulk-enroll-json');
    const resultsContainer = document.getElementById('preview-results');

    if (!previewBtn) {
        console.error("Elemento com id 'preview-enroll-btn' não encontrado.");
        return;
    }
    if (!textarea) {
        console.error("Elemento com id 'bulk-enroll-json' não encontrado.");
        return;
    }
    if (!resultsContainer) {
        console.error("Elemento com id 'preview-results' não encontrado.");
        return;
    }

    // Verifica se a variável localizada está definida.
    if (!window.TutorEAD_Enrollment) {
        console.error("Variável localized 'TutorEAD_Enrollment' não está definida no window.");
        return;
    } else {
        console.log("Variável localized TutorEAD_Enrollment encontrada:", window.TutorEAD_Enrollment);
    }

    previewBtn.addEventListener('click', function () {
        console.log("Botão 'Pré-visualizar JSON' clicado.");
        const jsonText = textarea.value.trim();
        console.log("Texto JSON:", jsonText);
        
        if (!jsonText) {
            alert('Por favor, insira um JSON válido.');
            return;
        }

        resultsContainer.innerHTML = '<p>Carregando...</p>';

        // Envio da requisição para pré-visualização
        console.log("Enviando requisição AJAX para pré-visualização...");
        fetch(window.TutorEAD_Enrollment.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                action: 'preview_bulk_enrollments',
                bulk_enroll_json: jsonText,
                _ajax_nonce: window.TutorEAD_Enrollment.nonce
            })
        })
        .then(response => {
            console.log("Resposta recebida para pré-visualização.");
            return response.json();
        })
        .then(data => {
            console.log("Dados recebidos:", data);
            resultsContainer.innerHTML = '';

            if (!data.success) {
                const msg = data?.data?.message || 'Erro inesperado.';
                resultsContainer.innerHTML = `<div class="notice notice-error"><p>${msg}</p></div>`;
                console.error("Erro na pré-visualização:", msg);
                return;
            }

            // Exibe as informações do curso no preview
            if (data.data.course) {
                const courseInfo = document.createElement('div');
                courseInfo.style.marginBottom = '20px';
                courseInfo.innerHTML = `<strong>Curso:</strong> ${data.data.course.title}` +
                                         (data.data.course.id ? ` (ID: ${data.data.course.id})` : '');
                resultsContainer.appendChild(courseInfo);
                console.log("Informações do curso exibidas:", data.data.course);
            } else {
                console.warn("Dados do curso não foram retornados.");
            }

            const validUsers = [];
            let courseId;
            try {
                const parsed = JSON.parse(jsonText);
                courseId = parsed.course_id;
                console.log("Course ID extraído do JSON:", courseId);
            } catch (error) {
                resultsContainer.innerHTML = `<div class="notice notice-error"><p>JSON inválido.</p></div>`;
                console.error("Erro ao converter JSON:", error);
                return;
            }

            // Processa os resultados dos usuários (chave 'users')
            data.data.users.forEach(item => {
                const row = document.createElement('div');
                row.style.marginBottom = '10px';
                let statusText = '';

                if (item.status === 'not_found') {
                    statusText = '<span style="color:red;">Usuário não encontrado</span>';
                } else if (item.status === 'already_enrolled') {
                    statusText = '<span style="color:orange;">Já está matriculado</span>';
                } else if (item.status === 'course_not_found') {
                    statusText = '<span style="color:red;">Não é possível matricular: curso não encontrado</span>';
                } else if (item.status === 'ok') {
                    statusText = '<span style="color:green;">Pronto para matrícula</span>';
                    validUsers.push(item.user_id);
                } else {
                    statusText = '<span style="color:gray;">Status desconhecido</span>';
                }

                row.innerHTML = `<strong>${item.email}</strong>: ${statusText}`;
                resultsContainer.appendChild(row);
            });

            console.log("Usuários válidos para matrícula:", validUsers);

            if (validUsers.length > 0) {
                const confirmBtn = document.createElement('button');
                confirmBtn.textContent = 'Confirmar Matrícula';
                confirmBtn.className = 'button button-primary';
                confirmBtn.style.marginTop = '15px';

                confirmBtn.addEventListener('click', () => {
                    confirmBtn.disabled = true;
                    confirmBtn.textContent = 'Processando...';

                    // Cria os parâmetros para enviar os dados dos IDs de usuário em formato array.
                    const params = new URLSearchParams();
                    params.append('action', 'confirm_bulk_enrollments');
                    validUsers.forEach(user => params.append('user_ids[]', user));
                    params.append('course_id', courseId);
                    params.append('_ajax_nonce', window.TutorEAD_Enrollment.nonce);

                    console.log("Enviando requisição AJAX para confirmação com os parâmetros:", params.toString());
                    fetch(window.TutorEAD_Enrollment.ajax_url, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: params
                    })
                    .then(res => {
                        console.log("Resposta recebida para confirmação.");
                        return res.json();
                    })
                    .then(res => {
                        console.log("Dados da confirmação:", res);
                        if (res.success) {
                            resultsContainer.innerHTML += `<div class="notice notice-success"><p>${res.data.message}</p></div>`;
                            confirmBtn.remove();
                        } else {
                            resultsContainer.innerHTML += `<div class="notice notice-error"><p>Erro: ${res.data?.message || 'Desconhecido'}</p></div>`;
                        }
                    })
                    .catch(err => {
                        resultsContainer.innerHTML += `<div class="notice notice-error"><p>Erro na requisição: ${err.message}</p></div>`;
                        console.error("Erro durante a requisição de confirmação:", err);
                    });
                });

                resultsContainer.appendChild(confirmBtn);
            }
        })
        .catch(err => {
            resultsContainer.innerHTML = `<div class="notice notice-error"><p>Erro na requisição: ${err.message}</p></div>`;
            console.error("Erro durante a requisição de pré-visualização:", err);
        });
    });
});
