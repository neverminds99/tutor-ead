// ============================
// tutor-ead.js - Scripts do Tutor EAD
// ============================
// Caminho: assets/js/tutor-ead.js
// Este script adiciona funcionalidades dinâmicas ao Tutor EAD, 
// permitindo a inserção de perguntas e alternativas de forma dinâmica
// em formulários de avaliações, além de integrar novas funcionalidades 
// no painel do aluno.
//
// ============================
// Funcionalidades já existentes:
// ============================
// - Exibe uma mensagem no console indicando que os scripts do Tutor EAD foram carregados.
// - Adicionar perguntas dinamicamente:
//   - Implementa um sistema para criar perguntas dinamicamente em formulários.
//   - Cada pergunta contém um título, um enunciado e opções de resposta múltipla.
//   - As alternativas são geradas automaticamente com um botão de rádio para selecionar a resposta correta.
//   - O número de perguntas é controlado por um índice, garantindo que cada nova pergunta receba um identificador único.
//   - Ao clicar no botão "Adicionar Pergunta", um novo bloco de pergunta é gerado e adicionado ao contêiner.

// ============================
// Nova funcionalidade adicionada:
// ============================
// - Criar Página de Negócio no Painel do Aluno:
//   - Adiciona um listener de clique para o botão com o ID "criar-pagina-negocio".
//   - Ao clicar, dispara uma requisição AJAX para o handler 'create_aluno_negocio_page'.
//   - Caso a página seja criada com sucesso, exibe uma mensagem e redireciona o usuário para a nova página.
//   - Em caso de erro, exibe uma mensagem de aviso.
//   - A URL para a requisição AJAX é obtida a partir da variável "tutorEAD.ajax_url" (ou da variável global "ajaxurl").
// 
// O script mantém todas as funcionalidades originais e adiciona a nova lógica sem interferir no funcionamento existente.

jQuery(document).ready(function ($) {
    console.log('Tutor EAD scripts carregados.');

    // ============================
    // Adicionar perguntas dinamicamente
    // ============================
    let perguntaIndex = 0;

    $('#adicionar-pergunta').on('click', function () {
        perguntaIndex++;
        const perguntaHTML = `
        <div class="pergunta-block" style="margin-bottom: 20px; border: 1px solid #ccc; padding: 10px;">
            <h3>Pergunta ${perguntaIndex}</h3>
            <label>Título da Pergunta:</label>
            <input type="text" name="perguntas[${perguntaIndex}][titulo]" required style="width: 100%; margin-bottom: 10px;">
            <label>Enunciado:</label>
            <textarea name="perguntas[${perguntaIndex}][enunciado]" rows="3" required style="width: 100%; margin-bottom: 10px;"></textarea>
            <h4>Alternativas</h4>
            <div class="alternativas-container">
                ${[1, 2, 3, 4].map((i) => `
                    <label>Alternativa ${i}:</label>
                    <input type="text" name="perguntas[${perguntaIndex}][alternativas][${i}][texto]" required style="width: 90%; margin-right: 5px;">
                    <label><input type="radio" name="perguntas[${perguntaIndex}][correta]" value="${i}" required> Correta</label>
                    <br>
                `).join('')}
            </div>
        </div>`;
        $('#perguntas-container').append(perguntaHTML);
    });

    // ============================
    // Criar Página de Negócio no Painel do Aluno
    // ============================
    // - Ao clicar no botão "Criar minha página de negócio", este bloco realiza:
    //   - Prevenção do comportamento padrão do clique.
    //   - Desabilitação temporária do botão para evitar múltiplos cliques.
    //   - Envio de uma requisição AJAX para o handler 'create_aluno_negocio_page'.
    //   - Em caso de sucesso, exibe uma mensagem e redireciona para a nova página de negócio.
    //   - Em caso de falha, exibe uma mensagem de erro.
    $('#criar-pagina-negocio').on('click', function (e) {
        e.preventDefault();
        var $btn = $(this);
        $btn.prop('disabled', true);
        $.ajax({
            // Utilize a variável "tutorEAD.ajax_url" se estiver definida via wp_localize_script ou a variável global "ajaxurl"
            url: typeof tutorEAD !== 'undefined' && tutorEAD.ajax_url ? tutorEAD.ajax_url : ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'create_aluno_negocio_page'
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message);
                    window.location.href = response.data.page_url;
                } else {
                    alert(response.data.message || 'Erro ao criar a página de negócio.');
                }
            },
            error: function(xhr, status, error) {
                alert('Erro na requisição AJAX: ' + error);
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
});
