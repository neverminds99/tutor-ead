
jQuery(function ($) {

    /*--------------------------------------------------------------*/
    /* Função de debug (somente para console)                       */
    /*--------------------------------------------------------------*/
    function logDebug(msg) {
        if (window.console && console.log) {
            console.log('[TutorEAD] ' + msg);
        }
    }

    /*--------------------------------------------------------------*/
    /* Carrega atividades e alunos ao trocar curso, reaplicando     */
    /* seleção anterior em modo de edição                           */
    /*--------------------------------------------------------------*/
    function loadDependents() {
        const courseID = $('#course_select').val();
        const courseText = $('#course_select option:selected').text().trim();

        // Atualiza título do curso
        $('#course_title').val(courseText);

        // Seleções prévias (edição)
        const selectedAtividade = $('#atividade_select').data('selected');
        const selectedAluno     = $('#aluno_select').data('selected');

        // Indica carregamento
        $('#atividade_select').html('<option value="">Carregando…</option>');
        $('#atividade_title').val('');
        $('#aluno_select').html('<option value="">Carregando…</option>');

        if (!courseID) return;

        // AJAX: carregar atividades
        $.post(tutorEadAjax.ajaxurl, {
            action:      'tutor_ead_get_atividades_for_course',
            course_id:   courseID,
            _ajax_nonce: tutorEadAjax.nonce
        }, function (resp) {
            $('#atividade_select').html(resp);
            if (selectedAtividade) {
                $('#atividade_select').val(selectedAtividade);
            }
            $('#atividade_select').trigger('change');
        }).fail(function () {
            logDebug('Erro ao carregar atividades');
            $('#atividade_select').html('<option value="">Erro ao carregar</option>');
        });

        // AJAX: carregar alunos
        $.post(tutorEadAjax.ajaxurl, {
            action:      'tutor_ead_get_alunos_for_course',
            course_id:   courseID,
            _ajax_nonce: tutorEadAjax.nonce
        }, function (resp) {
            $('#aluno_select').html(resp);
            if (selectedAluno) {
                $('#aluno_select').val(selectedAluno);
            }
        }).fail(function () {
            logDebug('Erro ao carregar alunos');
            $('#aluno_select').html('<option value="">Erro ao carregar</option>');
        });
    }

    // Dispara AJAX ao alterar o select de curso
    $(document).on('change', '#course_select', loadDependents);

    // Atualiza o campo de título ao alterar atividade
    $(document).on('change', '#atividade_select', function () {
        const txt = $('#atividade_select option:selected').text().trim();
        $('#atividade_title').val(txt);
    });

    // No carregamento inicial, se for edição, dispara o loadDependents()
    if ($('#course_select').val()) {
        loadDependents();
    }

    /*--------------------------------------------------------------*/
    /* Processamento da Inserção em Massa via JSON                 */
    /*--------------------------------------------------------------*/

    // Pré-visualizar JSON
    $(document).on('click', '#preview_json', function (e) {
        e.preventDefault();
        const jsonData = $('#json_input').val();

        $.post(tutorEadAjax.ajaxurl, {
            action:      'tutor_ead_previsualizar_notas',
            json_data:   jsonData,
            _ajax_nonce: tutorEadAjax.nonce
        }, function (response) {
            if (response.success) {
                let previewHtml = '<div class="json-preview-container">';
                let allValid = true;
                $.each(response.data, function (index, item) {
                    const errorClass = item.user_id ? 'json-preview-block' : 'json-preview-block json-error';
                    previewHtml += '<div class="' + errorClass + '">';
                    previewHtml += '<strong>Email:</strong> '     + item.email            + '<br>';
                    previewHtml += '<strong>ID:</strong> '        + (item.user_id || 'Não encontrado') + '<br>';
                    previewHtml += '<strong>Curso:</strong> '     + item.course_title     + '<br>';
                    previewHtml += '<strong>Atividade:</strong> '+ item.atividade_title   + '<br>';
                    previewHtml += '<strong>Nota:</strong> '      + item.nota             + '<br>';
                    previewHtml += '<strong>Status:</strong> '    + (item.user_id ? 'Válido' : 'Erro: usuário não encontrado');
                    previewHtml += '</div>';
                    if (!item.user_id) allValid = false;
                });
                previewHtml += '</div>';
                $('#json_preview').html(previewHtml);

                if (allValid) {
                    $('#continue_insertion').prop('disabled', false);
                    $('#try_again').hide();
                } else {
                    $('#continue_insertion').prop('disabled', true);
                    $('#try_again').show();
                }
            } else {
                alert(response.data || 'Erro ao processar JSON');
            }
        }).fail(function () {
            alert('Erro na requisição de pré-visualização.');
        });
    });

    // Inserção definitiva
    $(document).on('click', '#continue_insertion', function (e) {
        e.preventDefault();
        const jsonData = $('#json_input').val();

        $.post(tutorEadAjax.ajaxurl, {
            action:      'tutor_ead_inserir_notas',
            json_data:   jsonData,
            _ajax_nonce: tutorEadAjax.nonce
        }, function (response) {
            if (response.success) {
                alert('Inserção realizada com sucesso!');
                // opcional: recarregar a lista ou limpar o form
            } else {
                alert(response.data || 'Erro ao inserir notas.');
            }
        }).fail(function () {
            alert('Falha na requisição de inserção.');
        });
    });

    // Botão "Tentar novamente"
    $(document).on('click', '#try_again', function (e) {
        e.preventDefault();
        $('#json_preview').empty();
        $('#continue_insertion').prop('disabled', true);
        $(this).hide();
    });
});

