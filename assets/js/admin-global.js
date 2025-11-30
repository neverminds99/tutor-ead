jQuery(document).ready(function($) {
    console.log('Tutor EAD Admin Script Loaded');

    // Verifica se o objeto TutorEAD_Ajax está disponível
    if (typeof TutorEAD_Ajax === 'undefined') {
        console.error('TutorEAD_Ajax object is not defined. AJAX calls will fail.');
        return;
    } else {
        console.log('TutorEAD_Ajax object found:', TutorEAD_Ajax);
    }

    const $migrationButton = $('#migrate-phone-numbers');
    const $migrationTooltip = $('#migration-tooltip');

    // Função para verificar e exibir/ocultar o botão de migração
    function checkAndDisplayMigrationButton() {
        if ($migrationButton.length && $migrationTooltip.length) {
            $.post(TutorEAD_Ajax.ajax_url, {
                action: 'tutoread_check_unmigrated_data',
                nonce: TutorEAD_Ajax.nonce
            })
            .done(function(response) {
                if (response.success && response.data.has_unmigrated_data) {
                    $migrationButton.show();
                    // Posiciona o tooltip abaixo do botão
                    const buttonOffset = $migrationButton.offset();
                    const buttonWidth = $migrationButton.outerWidth();
                    const tooltipWidth = $migrationTooltip.outerWidth();
                    const tooltipHeight = $migrationTooltip.outerHeight();

                    $migrationTooltip.css({
                        'top': buttonOffset.top - tooltipHeight - 10, // 10px de espaçamento acima do botão
                        'left': buttonOffset.left + (buttonWidth / 2) - (tooltipWidth / 2)
                    });

                    // Mostra o tooltip ao carregar a página
                    $migrationTooltip.addClass('show');

                    // Oculta o tooltip após 5 segundos
                    setTimeout(() => {
                        $migrationTooltip.removeClass('show');
                    }, 5000);

                    // Oculta o tooltip ao clicar no botão
                    $migrationButton.on('click', function() {
                        $migrationTooltip.removeClass('show');
                    });

                } else {
                    $migrationButton.hide();
                    $migrationTooltip.hide();
                }
            })
            .fail(function() {
                console.error('Erro ao verificar dados não migrados.');
                $migrationButton.hide();
                $migrationTooltip.hide();
            });
        }
    }

    // Chama a função ao carregar a página
    checkAndDisplayMigrationButton();

    // Botão de migração de dados do aluno
    $migrationButton.on('click', function(e) {
        e.preventDefault();
        const $button = $(this);

        if (confirm('Tem certeza de que deseja migrar os dados dos alunos (nome completo e celular) da tabela antiga do WordPress para a nova tabela do TutorEAD? Esta ação é recomendada para garantir a consistência dos dados e o uso de funcionalidades futuras.')) {
            $button.text('Migrando...').prop('disabled', true);

            $.post(TutorEAD_Ajax.ajax_url, { 
                action: 'tutoread_migrate_phone_numbers', 
                nonce: TutorEAD_Ajax.nonce 
            })
            .done(function(response) {
                // Log para debug, pode ser removido em produção
                console.log('Resposta completa recebida:', response);

                $button.text('Migrar Dados').prop('disabled', false);

                if (response.success && response.data) {
                    let $logContainer = $('#migration-log-container');
                    if (!$logContainer.length) {
                        $logContainer = $('<div id="migration-log-container" style="margin-top: 20px; padding: 15px; border: 1px solid #e5e7eb; border-radius: 8px; background-color: #f9fafb; font-family: monospace; white-space: pre-wrap; max-height: 300px; overflow-y: auto;"></div>');
                        // ALTERAÇÃO: Inserir o log de forma mais robusta, depois do cabeçalho do card.
                        $migrationButton.closest('.card-header').after($logContainer);
                    }

                    $logContainer.empty();

                    $logContainer.append($('<strong>').text(response.data.message));
                    $logContainer.append('<hr style="margin: 10px 0;">');

                    if (response.data.log && response.data.log.length > 0) {
                        response.data.log.forEach(function(logEntry) {
                            $logContainer.append($('<div>').text(logEntry));
                        });
                    } else {
                        if (response.data.message && !response.data.message.startsWith('0')) {
                             $logContainer.append($('<div>').text('Migração concluída, mas sem detalhes de log para exibir.'));
                        } else {
                             $logContainer.append($('<div>').text('Nenhum dado precisava ser migrado.'));
                        }
                    }

                    $button.fadeOut(); // Esconde o botão com uma animação suave

                } else {
                    alert('Erro: ' + (response.data ? response.data.message : 'Ocorreu um erro desconhecido.'));
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX request failed:', textStatus, errorThrown);
                alert('Ocorreu um erro de comunicação com o servidor. Verifique o console para mais detalhes.');
                $button.text('Migrar Dados').prop('disabled', false);
            });
        }
    });

    // Adiciona o logo a todas as páginas do admin do Tutor EAD
    var tutorEADPages = [
        'tutor-ead-dashboard',
        'tutor-ead-courses',
        'tutor-ead-view-course',
        'tutor-ead-course-builder',
        'tutor-ead-import-export',
        'tutor-ead-students',
        'tutor-ead-enrollment-list',
        'tutor-ead-edit-user',
        'tutor-ead-teachers',
        'tutor-ead-edit-teacher',
        'tutor-ead-boletim',
        'tutor-ead-create-boletim',
        'tutor-ead-edit-boletim',
        'tutor-ead-atividades',
        'tutor-ead-add-atividade-externa',
        'tutor-ead-edit-atividade-padrao',
        'tutor-ead-edit-atividade-externa',
        'tutor-ead-delete-atividade',
        'tutor-ead-delete-associacao',
        'tutor-ead-add-atividade-sem-url',
        'tutor-ead-add-atividade',
        'tutor-ead-edit-atividade',
        'tutor-ead-associar-atividade',
        'tutor-ead-activation',
        'tutor-ead-settings',
        'tutor-ead-central-settings',
        'tutor-ead-alertas',
        'tutor-ead-temp-login'
    ];

    var currentPage = new URLSearchParams(window.location.search).get('page');

    if (tutorEADPages.includes(currentPage)) {
        // Verifica se a variável do logo existe
        if (typeof tutorEadAdmin !== 'undefined' && tutorEadAdmin.pluginUrl) {
            var logoHtml = '<div class="tutor-ead-admin-logo"><img src="' + tutorEadAdmin.pluginUrl + 'img/tutureadlogo.png" alt="Tutor EAD Logo"></div>';
            $('#wpbody-content .wrap').append(logoHtml);
        } else {
            console.warn('tutorEadAdmin object or pluginUrl not defined. Logo will not be added.');
        }
    }
});