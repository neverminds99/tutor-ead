# Documentação TutorEAD: Ferramentas Administrativas

Este documento descreve as diversas ferramentas administrativas disponíveis no painel do TutorEAD, que permitem gerenciar matrículas, notas, publicidade, e realizar outras tarefas de configuração e suporte.

---

## 1. Gerenciador de Matrículas (`includes/admin/class-enrollment-manager.php`)

Esta ferramenta oferece uma interface centralizada para gerenciar a associação de alunos a cursos.

-   **Interface:** Possui uma página dedicada ("Matrículas") no painel de administração, e não é apenas uma subseção em outras páginas.
-   **Funcionalidades:**
    -   **Listagem Completa:** Exibe uma tabela com todas as matrículas existentes no sistema, mostrando aluno, curso e data da matrícula.
    -   **Matrícula Individual/Múltipla:** Um formulário permite ao administrador selecionar múltiplos alunos e múltiplos cursos e criar todas as associações de uma só vez.
    -   **Matrícula em Massa via JSON:** Uma funcionalidade avançada que permite colar um JSON contendo uma lista de e-mails de alunos e um `course_id`.
        -   **Pré-visualização:** Antes de confirmar, o sistema valida o JSON, verifica se os e-mails existem no WordPress e se os alunos já estão matriculados, exibindo um resumo claro.
        -   **Confirmação:** Após a pré-visualização, um botão "Confirmar Matrícula" executa a inserção em massa no banco de dados.
    -   **Remoção de Matrícula:** Permite remover matrículas individualmente.
-   **Tecnologia:** A interface de matrícula em massa via JSON é controlada pelo script `assets/js/json-enrollment-preview.js`, que faz chamadas AJAX para pré-visualizar e confirmar os dados.

---

## 2. Gerenciador de Boletins/Relatórios (`includes/admin/class-report-manager.php`)

Esta ferramenta é, na prática, um sistema **CRUD (Create, Read, Update, Delete)** para o lançamento de notas, mais do que apenas um gerador de relatórios.

-   **Interface:** Uma página dedicada ("Boletins") no painel de administração.
-   **Funcionalidades:**
    -   **Listagem e Filtragem:** Exibe uma tabela com todas as notas lançadas. A tabela pode ser filtrada por curso, atividade, aluno e intervalo de datas. A filtragem é feita via parâmetros na URL (GET), recarregando a página com os resultados filtrados.
    -   **Criação de Nota:** Um formulário permite ao administrador lançar uma nova nota, selecionando o curso, aluno, atividade, nota e um feedback opcional.
    -   **Edição e Exclusão:** Permite editar ou excluir entradas de notas existentes.
-   **Tecnologia:** A interatividade do formulário de criação/edição (como popular o dropdown de atividades após selecionar um curso) é controlada pelo `assets/js/boletim.js`.

---

## 3. Sistema de Publicidade (`includes/admin/class-advertisement-manager.php`)

O plugin possui um sistema nativo para gerenciar e exibir publicidade em locais específicos da plataforma.

-   **Interface:** Uma página dedicada ("Publicidade") para criar, editar e listar todos os anúncios.
-   **Funcionalidades:**
    -   **Criação de Anúncios:** Permite criar anúncios com uma imagem (que pode ser recortada com `Cropper.js`), um link de destino, um local de exibição (ex: `dashboard_aluno_sidebar`) e uma "chance de exibição" (um peso para randomização).
    -   **Rastreamento de Performance:** O sistema rastreia automaticamente **visualizações** e **cliques** para cada anúncio, salvando os dados na tabela `wp_tutoread_advertisement_stats`. As visualizações são contadas quando o anúncio se torna visível na tela (usando `IntersectionObserver` em JavaScript), e os cliques são registrados via AJAX antes de redirecionar o usuário.
-   **Exibição:** A função `get_active_advertisement_for_location()` é usada nos templates do frontend para buscar e exibir um anúncio válido para um determinado local.

---

## 4. Gerador de Login Temporário (`includes/admin/class-temp-login.php`)

Uma ferramenta de suporte para criar links de acesso único e temporário para a conta de um aluno.

-   **Interface:** Uma página dedicada ("Login Temporário") no painel de administração.
-   **Funcionamento:**
    1.  O administrador seleciona um aluno e uma duração de validade (em minutos).
    2.  O sistema gera um link com um token seguro e o armazena na tabela `wp_temp_login_tokens`.
    3.  Ao acessar o link, o sistema valida o token e, se for válido e não estiver expirado, autentica o usuário programaticamente usando `wp_set_auth_cookie()`.
    4.  O token é invalidado após o primeiro uso.
-   **Caso de Uso:** Ideal para equipes de suporte que precisam investigar um problema na conta de um aluno sem pedir suas credenciais.

---

## 5. Assistente de Configuração (`includes/admin/class-setup-wizard.php`)

Um guia passo a passo para facilitar a configuração inicial do plugin.

-   **Interface:** Uma página de wizard que é exibida para o administrador após a primeira ativação do plugin.
-   **Funcionamento:**
    1.  O assistente guia o usuário por etapas para configurar a identidade visual da plataforma.
    2.  **Passos:** Inclui a definição do nome da plataforma, upload do logo e seleção de uma cor de destaque.
    3.  **Armazenamento:** As configurações são salvas na tabela `wp_options` usando `update_option()`.
-   **Tecnologia:** A interatividade, como o upload de mídia e a seleção de cores, é controlada pelo `assets/js/admin-wizard.js`, que integra o Media Uploader e o Color Picker nativos do WordPress.
