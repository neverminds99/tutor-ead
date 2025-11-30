# Documentação TutorEAD: Experiência do Usuário (Frontend)

Este documento descreve como o TutorEAD constrói a experiência do usuário no frontend, incluindo os dashboards, a visualização de cursos e outras funcionalidades interativas.

---

## 1. Templates e Shortcodes

A interface do TutorEAD no frontend é construída principalmente através de **Templates de Página** do WordPress, que são ativados por **Shortcodes**.

### `includes/class-shortcodes.php`
Esta classe registra os shortcodes que renderizam os painéis e formulários do plugin.

-   **`[tutor_ead_dashboard_aluno]`**: Carrega o template do dashboard do aluno.
-   **`[tutor_ead_dashboard_professor]`**: Carrega o template do dashboard do professor.
-   **`[tutor_ead_dashboard_admin]`**: Carrega o template do dashboard do administrador.
-   **`[tutor_ead_register]`**: Exibe o formulário de registro. O processamento do formulário é feito via `admin-post.php`, não no próprio template.


### `includes/class-page-manager.php`
Durante a ativação, esta classe cria automaticamente as páginas do WordPress necessárias e atribui a elas o template de página correto, que por sua vez contém os shortcodes ou a lógica de exibição.

---

## 2. Dashboards do Frontend

Os dashboards são aplicações ricas e interativas, controladas por JavaScript, que se comunicam com o backend via AJAX.

### Dashboard do Aluno (`templates/dashboard-aluno.php`)
Este é o principal hub para o estudante.
-   **Layout:** Apresenta um layout moderno com uma barra lateral de navegação (expansível ao passar o mouse), um cabeçalho com menu de usuário e uma área de conteúdo principal.
-   **Funcionalidades:**
    -   **Listagem de Cursos:** Exibe "cards" para cada curso em que o aluno está matriculado, com imagem, título e uma barra de progresso.
    -   **Cálculo de Progresso:** O progresso de cada curso é calculado dinamicamente, contando o número de aulas concluídas em relação ao total de aulas do curso.
    -   **Alertas e Avisos:** Exibe notificações e pop-ups configurados no painel de administração (`tutoread_alertas`).
    -   **Bloqueio por Termo de Uso:** Se houver um termo de uso pendente, o acesso ao dashboard é bloqueado por um modal que exige o aceite do aluno para continuar.
    -   **Integração com Publicidade:** Exibe anúncios em locais designados, como o topo da página e a barra lateral.
    -   **Tour Guiado:** Utiliza a biblioteca **Shepherd.js** para oferecer um tour interativo da interface para novos usuários.

### Dashboard do Professor (`templates/dashboard-professor.php`)
Este painel permite que os professores gerenciem seus cursos e alunos.
-   **Estrutura:** Organizado em abas: "Meus Cursos", "Meus Alunos" e "Boletim".
-   **Funcionalidades:**
    -   **Meus Cursos:** Lista os cursos que o professor leciona.
    -   **Meus Alunos:** Lista os alunos matriculados nos cursos do professor.
    -   **Boletim:** Uma ferramenta completa para o professor **lançar notas**. Possui dropdowns que se atualizam dinamicamente: ao selecionar um curso, os campos "Aluno" e "Atividade" são populados com os dados relevantes daquele curso via AJAX.

### Dashboard do Administrador (`templates/dashboard-administrador.php`)
Oferece uma interface de gerenciamento completa no frontend.
-   **Estrutura:** Organizado em abas: "Gerenciar Alunos", "Gerenciar Cursos" e "Gerenciar Professores".
-   **Funcionalidades:**
    -   **CRUD Completo:** Permite criar, listar, editar e excluir alunos, cursos e professores diretamente do frontend, através de tabelas dinâmicas e modais.
    -   **Busca em Tempo Real:** Cada tabela possui um campo de busca que filtra os resultados instantaneamente.

---

## 3. Visualização de Curso (`templates/template-curso.php`)

Esta é a "sala de aula" virtual.
-   **Roteador de Views:** O `template-curso.php` atua como um roteador. Com base nas configurações do curso e nos parâmetros da URL, ele decide qual layout carregar:
    -   `template-view-levels.php`: Uma visualização de curso organizada por níveis ou etapas.
    -   `template-view-expanded.php`: Uma visualização mais detalhada, que também é usada para exibir o player de uma aula específica.
-   **Conteúdo da Aula:** Os templates de visualização são responsáveis por exibir o conteúdo da aula (texto e vídeo) e o botão "Marcar como Concluído".

---

## 4. Funcionalidades de Experiência do Usuário (UX)



### Modo de Personificação (Impersonation)
-   **Propósito:** Permite que um administrador navegue no site como se fosse um aluno, para fins de suporte e teste.
-   **Funcionamento:**
    1.  No painel, o admin clica em "Ver como aluno". Um token de personificação é gerado e salvo como um `transient`.
    2.  O admin é redirecionado para o dashboard do aluno com o token na URL.
    3.  O plugin detecta o token, valida-o e armazena o ID do admin e o ID do aluno na `$_SESSION`.
    4.  Enquanto a sessão estiver ativa, o `wp_set_current_user()` é usado para definir o usuário atual como o aluno.
    5.  Um banner de aviso é exibido no topo de todas as páginas, com um link para sair do modo de personificação, que limpa a sessão e restaura o login do admin.

### Modo Escuro (Dark Mode) (`assets/js/dark-mode.js`)
-   **Propósito:** Oferece conforto visual, permitindo alternar entre um tema claro e um escuro.
-   **Funcionamento:** Um botão no dashboard alterna uma classe `.dark-mode` no `<body>` da página. A preferência do usuário é salva no `localStorage` do navegador, para que o tema escolhido persista entre as visitas.
-   **Estilos:** Os arquivos CSS (`dashboard-frontend.css`, `admin-course-builder.css`) contêm regras específicas para a classe `.dark-mode` para alterar as cores de fundo, texto e bordas.
