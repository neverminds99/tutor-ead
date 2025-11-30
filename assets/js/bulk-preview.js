jQuery(document).ready(function($){
    // Elementos do formulário de importação via JSON
    var $textarea = $('textarea[name="bulk_students_json"]');
    var $previewBtn = $('#json-preview-btn'); // Botão de pré-visualizar inserido no HTML
    var $previewArea = $('#bulk-preview-area');

    $previewBtn.on('click', function(){
        var jsonText = $textarea.val().trim();
        $previewArea.html('<p>Carregando pré‑visualização…</p>');

        $.post(TutorEAD_Ajax.ajax_url, {
            action: 'preview_bulk_students',
            _ajax_nonce: TutorEAD_Ajax.nonce,
            bulk_students_json: jsonText
        }).done(function(resp){
            if (!resp.success) {
                $previewArea.html('<div class="notice notice-error"><p>' + resp.data.message + '</p></div>');
                return;
            }
            var list = resp.data;
            var html = '<div class="bulk-preview-list">';
            list.forEach(function(item){
                // Se o status é "update" e o campo current existe, verifica se os dados são idênticos
                if(item.status === 'update' && item.current) {
                    if(item.new.username === item.current.username &&
                       item.new.email === item.current.email &&
                       item.new.nome === item.current.nome &&
                       item.new.celular === item.current.celular) {
                        item.status = 'same';
                    }
                }
                var cls = item.status === 'new' ? 'preview-new'
                        : item.status === 'update' ? 'preview-update'
                        : item.status === 'same' ? 'preview-same'
                        : 'preview-error';
                var statusText = '';
                if(item.status === 'new') {
                    statusText = 'NOVO';
                } else if(item.status === 'update') {
                    statusText = 'UPDATE';
                } else if(item.status === 'same') {
                    statusText = 'PERMANECE O MESMO';
                } else {
                    statusText = 'ERRO';
                }
                html += '<div class="bulk-preview-item ' + cls + '">';
                html += '<h4>Item #' + (item.index + 1) + ' – ' + statusText + '</h4>';
                if(item.status !== 'same' && item.current){
                    html += '<p><strong>Atual:</strong> username: ' + item.current.username + ', email: ' + item.current.email + ', nome: ' + item.current.nome + ', celular: ' + item.current.celular + '</p>';
                }
                html += '<p><strong>Novos dados:</strong> username: ' + item.new.username + ', email: ' + item.new.email + ', nome: ' + item.new.nome + ', celular: ' + item.new.celular + ', senha: ' + item.new.password + '</p>';
                if(item.errors.length){
                    html += '<ul class="errors">';
                    item.errors.forEach(function(e){
                        html += '<li>Erro: ' + e + '</li>';
                    });
                    html += '</ul>';
                }
                if(item.warnings.length){
                    html += '<ul class="warnings">';
                    item.warnings.forEach(function(w){
                        html += '<li>Aviso: ' + w + '</li>';
                    });
                    html += '</ul>';
                }
                html += '</div>';
            });
            html += '</div>';
            // Botão de confirmação exibido somente após o preview ser carregado
            html += '<p><button type="submit" name="bulk_add_students" class="button button-primary">Confirmar Importação</button></p>';
            $previewArea.html(html);
        }).fail(function(xhr){
            $previewArea.html('<div class="notice notice-error"><p>Erro AJAX: ' + xhr.statusText + '</p></div>');
        });
    });
});
