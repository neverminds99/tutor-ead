jQuery(document).ready(function($) {
    // Usa os dados passados pelo PHP via wp_localize_script
    const ajaxurl = tutoread_dashboard_data.ajax_url;
    const nonce = tutoread_dashboard_data.nonce;

    // --- ABA: MEUS CURSOS ---
    function loadProfessorCourses() {
        $('#professor-courses-table-body').html('<tr><td colspan="3">Carregando...</td></tr>');
        $.post(ajaxurl, { action: 'tutoread_get_professor_courses', nonce: nonce })
            .done(function(response) {
                if (response.success) {
                    const courses = response.data;
                    let rows = '';
                    if (courses.length) {
                        courses.forEach(course => {
                            rows += `
                                <tr data-id="${course.id}">
                                    <td>${course.title}</td>
                                    <td>${course.student_count}</td>
                                    <td><button class="button-secondary view-students-for-course">Ver Alunos</button></td>
                                </tr>
                            `;
                        });
                    } else {
                        rows = '<tr><td colspan="3">Você ainda não foi associado a nenhum curso.</td></tr>';
                    }
                    $('#professor-courses-table-body').html(rows);
                } else {
                    $('#professor-courses-table-body').html('<tr><td colspan="3">Erro ao carregar os cursos.</td></tr>');
                }
            });
    }

    // --- ABA: MEUS ALUNOS ---
    function loadProfessorStudents() {
        $('#professor-students-table-body').html('<tr><td colspan="3">Carregando...</td></tr>');
        $.post(ajaxurl, { action: 'tutoread_get_professor_students', nonce: nonce })
            .done(function(response) {
                if (response.success) {
                    const students = response.data;
                    let rows = '';
                    if (students.length) {
                        students.forEach(student => {
                            rows += `
                                <tr data-id="${student.id}">
                                    <td>${student.name}</td>
                                    <td>${student.email}</td>
                                    <td>${student.courses_list}</td>
                                </tr>
                            `;
                        });
                    } else {
                        rows = '<tr><td colspan="3">Nenhum aluno encontrado em seus cursos.</td></tr>';
                    }
                    $('#professor-students-table-body').html(rows);
                } else {
                    $('#professor-students-table-body').html('<tr><td colspan="3">Erro ao carregar os alunos.</td></tr>');
                }
            });
    }

    // --- ABA: BOLETIM ---
    function loadBoletimHistory() {
        $('#boletim-history-table-body').html('<tr><td colspan="5">Carregando...</td></tr>');
        $.post(ajaxurl, { action: 'tutoread_get_boletim_history', nonce: nonce })
            .done(function(response) {
                if (response.success) {
                    const history = response.data;
                    let rows = '';
                    if (history.length) {
                        history.forEach(item => {
                            rows += `
                                <tr>
                                    <td>${item.student_name}</td>
                                    <td>${item.course_title}</td>
                                    <td>${item.atividade_title}</td>
                                    <td>${item.nota}</td>
                                    <td>${item.data_formatada}</td>
                                </tr>
                            `;
                        });
                    } else {
                        rows = '<tr><td colspan="5">Nenhuma nota lançada ainda.</td></tr>';
                    }
                    $('#boletim-history-table-body').html(rows);
                } else {
                    $('#boletim-history-table-body').html('<tr><td colspan="5">Erro ao carregar o histórico.</td></tr>');
                }
            });
    }

    function populateBoletimCourses() {
        $.post(ajaxurl, { action: 'tutoread_get_professor_courses', nonce: nonce })
            .done(function(response) {
                if (response.success) {
                    const courses = response.data;
                    const select = $('#boletim-course');
                    select.html('<option value="">Selecione um curso...</option>');
                    if (courses.length) {
                        courses.forEach(course => {
                            select.append(`<option value="${course.id}">${course.title}</option>`);
                        });
                    }
                }
            });
    }

    $('#boletim-course').on('change', function() {
        const courseId = $(this).val();
        const studentSelect = $('#boletim-student');
        const activitySelect = $('#boletim-activity');

        studentSelect.prop('disabled', true).html('<option value="">Carregando...</option>');
        activitySelect.prop('disabled', true).html('<option value="">Carregando...</option>');

        if (!courseId) {
            studentSelect.html('<option value="">Selecione um curso primeiro...</option>');
            activitySelect.html('<option value="">Selecione um curso primeiro...</option>');
            return;
        }

        // Carregar alunos
        $.post(ajaxurl, { action: 'tutoread_get_students_for_course_boletim', nonce: nonce, course_id: courseId })
            .done(function(response) {
                studentSelect.html('<option value="">Selecione um aluno...</option>');
                if (response.success && response.data.length) {
                    response.data.forEach(student => {
                        studentSelect.append(`<option value="${student.id}">${student.display_name}</option>`);
                    });
                    studentSelect.prop('disabled', false);
                } else {
                     studentSelect.html('<option value="">Nenhum aluno no curso</option>');
                }
            });

        // Carregar atividades
        $.post(ajaxurl, { action: 'tutoread_get_activities_for_course_boletim', nonce: nonce, course_id: courseId })
            .done(function(response) {
                activitySelect.html('<option value="">Selecione uma atividade...</option>');
                if (response.success && response.data.length) {
                    response.data.forEach(activity => {
                        activitySelect.append(`<option value="${activity.id}">${activity.titulo}</option>`);
                    });
                    activitySelect.prop('disabled', false);
                } else {
                    activitySelect.html('<option value="">Nenhuma atividade no curso</option>');
                }
            });
    });
    
    $('#boletim-form').on('submit', function(e) {
        e.preventDefault();
        const formData = $(this).serialize() + '&action=tutoread_save_boletim_entry&nonce=' + nonce;
        
        $.post(ajaxurl, formData).done(function(response) {
            if (response.success) {
                alert(response.data.message);
                $('#boletim-form')[0].reset();
                $('#boletim-student').prop('disabled', true).html('<option value="">Selecione um curso primeiro...</option>');
                $('#boletim-activity').prop('disabled', true).html('<option value="">Selecione um curso primeiro...</option>');
                loadBoletimHistory(); // Atualiza a tabela
            } else {
                alert('Erro: ' + response.data.message);
            }
        });
    });


    // --- CONTROLE GERAL DAS ABAS ---
    $('.tab-link').on('click', function() {
        const tabId = $(this).data('tab');
        $('.tab-link').removeClass('active');
        $(this).addClass('active');
        $('.tab-content').removeClass('active');
        $('#' + tabId).addClass('active');

        // Carrega os dados da aba clicada
        if (tabId === 'meus-cursos') {
            loadProfessorCourses();
        } else if (tabId === 'meus-alunos') {
            loadProfessorStudents();
        } else if (tabId === 'boletim') {
            populateBoletimCourses();
            loadBoletimHistory();
        }
    });

    // Ação do botão "Ver Alunos"
    $('#professor-courses-table-body').on('click', '.view-students-for-course', function() {
        $('.tab-link[data-tab="meus-alunos"]').click();
    });

    // Carregar dados da primeira aba na inicialização
    loadProfessorCourses();
});