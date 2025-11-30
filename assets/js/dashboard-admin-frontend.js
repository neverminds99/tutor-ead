jQuery(document).ready(function($) {
    // Usa os dados passados pelo PHP via wp_localize_script
    const ajaxurl = tutoread_dashboard_data.ajax_url;
    const nonce = tutoread_dashboard_data.nonce;

    // --- FUNÇÕES DE CARREGAMENTO DE DADOS ---

    function loadStudents(search = '') {
        $('#students-table-body').html('<tr><td colspan="4">Carregando...</td></tr>');
        $.post(ajaxurl, { action: 'tutoread_get_students', nonce: nonce, search: search })
            .done(handleTableResponse('#students-table-body', 4, buildStudentRow, 'Nenhum aluno encontrado.'));
    }

    function loadCourses(search = '') {
        $('#courses-table-body').html('<tr><td colspan="4">Carregando...</td></tr>');
        $.post(ajaxurl, { action: 'tutoread_get_courses', nonce: nonce, search: search })
            .done(handleTableResponse('#courses-table-body', 4, buildCourseRow, 'Nenhum curso encontrado.'));
    }

    function loadProfessores(search = '') {
        $('#professors-table-body').html('<tr><td colspan="4">Carregando...</td></tr>');
        $.post(ajaxurl, { action: 'tutoread_get_professores', nonce: nonce, search: search })
            .done(handleTableResponse('#professors-table-body', 4, buildProfessorRow, 'Nenhum professor encontrado.'));
    }

    // --- FUNÇÕES DE CONSTRUÇÃO DE LINHAS DA TABELA ---

    function buildStudentRow(student) {
        // Keep phone number visible
        const phoneNumberDisplayHtml = student.phone_number ? `<br><span>${student.phone_number}</span>` : '';

        let actionButtonsHtml = '<div class="student-actions" style="margin-top: 5px; display: flex; gap: 5px;">'; // Container for buttons, with flex for side-by-side and gap

        // Button to copy phone
        if (student.phone_number) {
            actionButtonsHtml += `<button class="button-small copy-phone-btn" data-phone-number="${student.phone_number}" style="font-size: 0.75em; padding: 3px 8px;">Copiar Celular</button>`;
        }

        // Button to copy login info
        const loginUrl = window.location.origin + '/login-tutor-ead'; // URL de login do site
        const loginMessage = `Entre no seu curso com email ${student.email} e senha padrão: aluno01 atraves do link ${loginUrl}`;
        actionButtonsHtml += `<button class="button-small copy-login-btn" data-clipboard-text="${encodeURIComponent(loginMessage)}" style="font-size: 0.75em; padding: 3px 8px;">Copiar Dados de Acesso</button>`;

        actionButtonsHtml += '</div>';

        return `
            <tr data-id="${student.id}">
                <td>${student.name}${phoneNumberDisplayHtml}${actionButtonsHtml}</td>
                <td>${student.email}</td>
                <td>${student.registered_date}</td>
                <td>
                    <button class="button-secondary edit-student">Editar</button>
                    <button class="button-secondary delete-student">Excluir</button>
                </td>
            </tr>
        `;
    }

    function buildCourseRow(course) {
        return `
            <tr data-id="${course.id}">
                <td>${course.title}</td>
                <td>${course.professor_name}</td>
                <td>${course.student_count}</td>
                <td>
                    <button class="button-secondary edit-course">Editar</button>
                    <button class="button-secondary delete-course">Excluir</button>
                </td>
            </tr>
        `;
    }

    function buildProfessorRow(professor) {
        return `
            <tr data-id="${professor.id}">
                <td>${professor.name}</td>
                <td>${professor.email}</td>
                <td>${professor.courses_list}</td>
                <td>
                    <button class="button-secondary manage-courses">Gerenciar Cursos</button>
                </td>
            </tr>
        `;
    }

    // --- CONTROLE DAS ABAS E CARREGAMENTO INICIAL ---

    function switchTab(tabId) {
        // Se a tab não existir, volta para a padrão 'alunos'
        if (!tabId || !$(`.tab-link[data-tab="${tabId}"]`).length) {
            tabId = 'alunos';
        }

        // Atualiza a classe 'active' nas abas e no conteúdo
        $('.tab-link').removeClass('active');
        $('.tab-content').removeClass('active');
        $(`.tab-link[data-tab="${tabId}"]`).addClass('active');
        $('#' + tabId).addClass('active');

        // Atualiza o hash na URL sem fazer a página pular
        if (history.pushState) {
            history.pushState(null, null, '#' + tabId);
        } else {
            window.location.hash = tabId;
        }

        // Carrega o conteúdo da aba selecionada
        if (tabId === 'alunos') loadStudents();
        else if (tabId === 'cursos') loadCourses();
        else if (tabId === 'professores') loadProfessores();
    }

    // Evento de clique para trocar de aba
    $('.tab-link').on('click', function(e) {
        e.preventDefault();
        const tabId = $(this).data('tab');
        switchTab(tabId);
    });

    // Carrega a aba inicial baseada no hash da URL
    function initializeTabs() {
        const hash = window.location.hash.replace('#', '');
        const currentPath = window.location.pathname;

        // Se estiver na página do editor de curso, carregue os cursos diretamente
        if (currentPath.includes('/dashboard-course-editor')) {
            loadCourses();
        } else {
            // Lógica existente para outros dashboards com abas
            switchTab(hash || 'alunos');
        }
    }

    initializeTabs(); // Carrega a aba correta na inicialização

    // --- BUSCA ---
    $('#search-students').on('keyup', debounce(function() { loadStudents($(this).val()); }, 300));
    $('#search-courses').on('keyup', debounce(function() { loadCourses($(this).val()); }, 300));
    $('#search-professors').on('keyup', debounce(function() { loadProfessores($(this).val()); }, 300));

    // --- MODAL E FORMULÁRIOS ---

    // Abrir modal para ADICIONAR
    $('#add-new-student').on('click', () => openModal('Adicionar Novo Aluno', studentForm()));
    $('#add-new-course').on('click', () => openModal('Adicionar Novo Curso', courseForm()));
    $('#add-new-professor').on('click', () => openModal('Adicionar Novo Professor', professorForm()));

    // Abrir modal para EDITAR
    $('#students-table-body').on('click', '.edit-student', function() {
        const id = $(this).closest('tr').data('id');
        $.post(ajaxurl, { action: 'tutoread_get_student_details', nonce: nonce, user_id: id })
            .done(response => {
                if (response.success) {
                    openModal('Editar Aluno', studentForm(response.data));
                }
            });
    });

    $('#courses-table-body').on('click', '.edit-course', function() {
        const id = $(this).closest('tr').data('id');
        $.post(ajaxurl, { action: 'tutoread_get_course_details', nonce: nonce, course_id: id })
            .done(response => {
                if (response.success) {
                    openModal('Editar Curso', courseForm(response.data));
                }
            });
    });
    
    // Abrir modal para GERENCIAR CURSOS DO PROFESSOR
    $('#professors-table-body').on('click', '.manage-courses', function() {
        const professorId = $(this).closest('tr').data('id');
        $.post(ajaxurl, { action: 'tutoread_get_professor_course_assignments', nonce: nonce, professor_id: professorId })
            .done(response => {
                if (response.success) {
                    openModal('Gerenciar Cursos do Professor', professorCoursesForm(professorId, response.data));
                }
            });
    });

    // Fechar modal
    $(document).on('click', '.close-modal-button, .modal-overlay', function(e) {
        if (e.target === this) {
            $('#entity-modal').hide();
        }
    });

    // Submissão do formulário do modal
    $(document).on('submit', '#modal-form', function(e) {
        e.preventDefault();
        const formData = $(this).serialize() + '&nonce=' + nonce;
        const action = $(this).find('input[name="action"]').val();
        
        $.post(ajaxurl, formData).done(function(response) {
            if (response.success) {
                alert(response.data.message);
                $('#entity-modal').hide();
                if (action.includes('student')) loadStudents();
                else if (action.includes('course')) loadCourses();
                else if (action.includes('professor')) loadProfessores();
            } else {
                alert('Erro: ' + (response.data ? response.data.message : 'Ocorreu um erro.'));
            }
        });
    });

    // Evento de clique delegado para os itens de curso no modal
    $(document).on('click', '.course-item', function() {
        $(this).toggleClass('selected');
        const checkbox = $(this).find('input[type="checkbox"]');
        checkbox.prop('checked', !checkbox.prop('checked'));
    });

    // Função para copiar texto para a área de transferência e exibir feedback no elemento
    function copyToClipboardWithFeedback(text, $element) {
        const el = document.createElement('textarea');
        el.value = text;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);

        const originalContent = $element.html();
        $element.html('Copiado!').addClass('copied');

        setTimeout(() => {
            $element.html(originalContent).removeClass('copied');
        }, 1500); // Mensagem visível por 1.5 segundos
    }

    // Eventos de clique para os botões de cópia
    $(document).on('click', '.copy-phone-btn', function(e) {
        e.stopPropagation();
        const textToCopy = $(this).data('phone-number');
        copyToClipboardWithFeedback(textToCopy, $(this));
    });

    $(document).on('click', '.copy-login-btn', function(e) {
        e.stopPropagation();
        const textToCopy = decodeURIComponent($(this).data('clipboard-text'));
        copyToClipboardWithFeedback(textToCopy, $(this));
    });

    // --- AÇÕES DE EXCLUSÃO ---
    $('#students-table-body').on('click', '.delete-student', function() {
        if (confirm('Tem certeza?')) {
            const id = $(this).closest('tr').data('id');
            $.post(ajaxurl, { action: 'tutoread_delete_student', nonce: nonce, user_id: id })
                .done(handleActionResponse(loadStudents));
        }
    });

    $('#courses-table-body').on('click', '.delete-course', function() {
        if (confirm('Tem certeza?')) {
            const id = $(this).closest('tr').data('id');
            $.post(ajaxurl, { action: 'tutoread_delete_course', nonce: nonce, course_id: id })
                .done(handleActionResponse(loadCourses));
        }
    });

    // --- FUNÇÕES GERADORAS DE FORMULÁRIO ---
    function studentForm(data = {}) {
        const isEdit = !!data.id;
        let coursesHtml = '';

        if (isEdit) {
            let courseItems = data.all_courses.map(course => `
                <div class="course-item ${data.enrolled_course_ids.includes(course.id) ? 'selected' : ''}" data-course-id="${course.id}">
                    <input type="checkbox" name="course_ids[]" value="${course.id}" ${data.enrolled_course_ids.includes(course.id) ? 'checked' : ''}>
                    <span>${course.title}</span>
                </div>
            `).join('');
            
            coursesHtml = `
                <h4>Cursos Matriculados</h4>
                <div class="courses-checkbox-list">${courseItems || 'Nenhum curso disponível.'}</div>
            `;
        }

        return `
            <input type="hidden" name="action" value="${isEdit ? 'tutoread_update_student' : 'tutoread_add_student'}">
            ${isEdit ? `<input type="hidden" name="user_id" value="${data.id}">` : ''}
            <p><label>Nome:</label><input type="text" name="name" value="${data.name || ''}" required></p>
            <p><label>E-mail:</label><input type="email" name="email" value="${data.email || ''}" required></p>
            <p><label>Senha:</label>
                <span class="password-wrapper" style="position: relative; display: inline-block; vertical-align: middle;">
                    <input type="text" name="password" value="aluno01" ${isEdit ? '' : 'required'} style="padding-right: 25px;">
                    <span class="clear-password" style="position: absolute; right: 8px; top: 50%; transform: translateY(-50%); cursor: pointer; font-size: 20px; line-height: 1; display: none;">&times;</span>
                </span>
            </p>
            <p><label>Celular:</label><input type="text" name="phone_number" value="${data.phone_number || ''}"></p>
            ${coursesHtml}
            <p><button type="submit" class="button-primary">${isEdit ? 'Atualizar' : 'Salvar'} Aluno</button></p>
        `;
    }

    function courseForm(data = {}) {
        const isEdit = !!data.id;
        return `
            <input type="hidden" name="action" value="${isEdit ? 'tutoread_update_course' : 'tutoread_add_course'}">
            ${isEdit ? `<input type="hidden" name="course_id" value="${data.id}">` : ''}
            <p><label>Título:</label><input type="text" name="title" value="${data.title || ''}" required></p>
            <p><label>Descrição:</label><textarea name="description" rows="4">${data.description || ''}</textarea></p>
            <p><button type="submit" class="button-primary">${isEdit ? 'Atualizar' : 'Salvar'} Curso</button></p>
        `;
    }
    
    function professorForm() {
        return `
            <input type="hidden" name="action" value="tutoread_add_professor">
            <p><label>Nome:</label><input type="text" name="name" required></p>
            <p><label>E-mail:</label><input type="email" name="email" required></p>
            <p><label>Senha:</label><input type="password" name="password" required></p>
            <p><button type="submit" class="button-primary">Salvar Professor</button></p>
        `;
    }

    function professorCoursesForm(professorId, data) {
        let courseItems = data.all_courses.map(course => `
            <div class="course-item ${data.assigned_ids.includes(course.id) ? 'selected' : ''}" data-course-id="${course.id}">
                <input type="checkbox" name="course_ids[]" value="${course.id}" ${data.assigned_ids.includes(course.id) ? 'checked' : ''}>
                <span>${course.title}</span>
            </div>
        `).join('');

        return `
            <input type="hidden" name="action" value="tutoread_update_professor_assignments">
            <input type="hidden" name="professor_id" value="${professorId}">
            <div class="courses-checkbox-list">${courseItems || 'Nenhum curso encontrado.'}</div>
            <p><button type="submit" class="button-primary">Salvar Associações</button></p>
        `;
    }

    // Handlers for the clear 'X' in password fields
    $(document).on('input', '.password-wrapper input', function() {
        const $input = $(this);
        const $clearBtn = $input.siblings('.clear-password');
        if ($input.val().length > 0) {
            $clearBtn.show();
        } else {
            $clearBtn.hide();
        }
    });

    $(document).on('click', '.password-wrapper .clear-password', function() {
        $(this).siblings('input').val('').trigger('input'); // trigger input to hide the 'X'
    });

    // --- FUNÇÕES UTILITÁRIAS ---
    function openModal(title, formHtml) {
        $('#modal-title').text(title);
        $('#modal-form').html(formHtml);
        $('#entity-modal').show();
        // Trigger check for all password fields in the new form
        $('#modal-form .password-wrapper input').trigger('input');
    }

    function handleTableResponse(tableBody, colspan, rowBuilder, emptyMessage) {
        return function(response) {
            if (response.success) {
                const items = response.data;
                let rows = '';
                if (items.length) {
                    items.forEach(item => rows += rowBuilder(item));
                } else {
                    rows = `<tr><td colspan="${colspan}">${emptyMessage}</td></tr>`;
                }
                $(tableBody).html(rows);
            } else {
                $(tableBody).html(`<tr><td colspan="${colspan}">Erro ao carregar dados.</td></tr>`);
            }
        };
    }
    
    function handleActionResponse(callback) {
        return function(response) {
            if (response.success) {
                alert(response.data.message);
                if (callback) callback();
            } else {
                alert('Erro: ' + (response.data ? response.data.message : 'Ocorreu um erro.'));
            }
        }
    }

    function debounce(func, wait) {
        let timeout;
        return function(...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }
});
