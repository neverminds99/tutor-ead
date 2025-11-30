# Documentação TutorEAD: Gerenciamento de Usuários e Permissões

Este documento detalha como o TutorEAD gerencia seus diferentes tipos de usuários, desde a definição de seus papéis e permissões até as ferramentas avançadas para a administração de alunos.

---

## 1. Gerenciador de Papéis (`includes/class-role-manager.php`)

A classe `TutorEAD\RoleManager` é a base do sistema de permissões do plugin. Ela define os diferentes "atores" do sistema e o que cada um pode fazer.

### Papéis de Usuário Criados:
-   **`tutor_admin` (Administrador EAD):** Acesso total a todas as funcionalidades do TutorEAD, incluindo configurações.
-   **`tutor_professor` (Professor):** Pode gerenciar seus cursos, ver alunos e lançar notas, mas não tem acesso às configurações administrativas globais.
-   **`tutor_course_editor` (Editor de Curso):** Um papel limitado, focado apenas na criação e edição do conteúdo dos cursos, sem acesso ao gerenciamento de alunos ou notas.
-   **`tutor_aluno` (Aluno):** O estudante, com acesso restrito apenas ao consumo dos cursos em que está matriculado e ao seu próprio painel.

### Capacidades (Permissões) Personalizadas:
O `RoleManager` cria um conjunto de permissões granulares para controlar o acesso a cada funcionalidade. As principais são:
- `manage_courses`: Gerenciar cursos.
- `view_students`: Visualizar alunos.
- `grade_students`: Avaliar alunos (lançar notas).
- `access_dashboard`: Acessar os painéis do frontend.
- `view_boletim`: Visualizar boletins.
- `manage_atividades`: Gerenciar atividades/quizzes.
- `manage_tutor_settings`: Acessar a página de configurações do plugin.

### Funcionamento Técnico:
- **Criação e Atualização:** O método `add_roles_and_capabilities()` usa a função `add_role()` do WordPress para criar os papéis e `add_cap()` para atribuir as permissões.
- **Versionamento:** A classe utiliza um sistema de versionamento (`const ROLES_VERSION`) para verificar se os papéis e permissões estão atualizados, garantindo que novas capacidades sejam adicionadas aos papéis existentes em atualizações do plugin, sem precisar de reativação.
- **Remoção:** O método `remove_roles_and_capabilities()` é chamado na desativação do plugin para remover os papéis criados.

---

## 2. Gerenciador de Alunos (`includes/admin/class-student-manager.php`)

Esta classe é responsável pela avançada página de **"Gerenciar Alunos"** no painel administrativo, que é muito mais do que uma simples lista de usuários.

### Principais Funcionalidades da Página:

- **Dashboard de Estatísticas:** O topo da página exibe um conjunto de cards com métricas importantes, como:
    - Total de Alunos e Novos Alunos (nos últimos 30 dias).
    - Alunos Ativos (hoje, última semana, último mês), baseado em logs de atividade.
    - Taxa de Engajamento (% de alunos que iniciaram aulas).
    - Progresso Médio Geral.

- **Tabela de Alunos Interativa:**
    - A lista de alunos é carregada dinamicamente via AJAX.
    - **Busca em Tempo Real:** Um campo de busca filtra a lista de alunos instantaneamente.
    - **Ordenação Dinâmica:** As colunas "Aluno" e "Último Acesso" podem ser clicadas para ordenar a tabela.
    - **Ações Rápidas por Aluno:** Um menu de ações para cada aluno permite:
        - **Ver como Aluno:** Uma funcionalidade de *personificação* que abre o dashboard do aluno em uma nova aba, permitindo que o admin veja a interface exatamente como o aluno a vê.
        - **Editar:** Abre o perfil detalhado do aluno.
        - **Resetar Senha:** Altera a senha do aluno para um valor padrão.
        - **Link de Login:** Gera um link de acesso temporário (funcionalidade do `TempLoginManager`).

- **Criação de Alunos (Individual e em Massa):**
    - **Adicionar Novo Aluno:** Abre um modal com um formulário completo para registrar um novo aluno, incluindo nome, e-mail, celular, senha e a associação direta a cursos.
    - **Adicionar Múltiplos Alunos (via JSON):** Uma ferramenta poderosa que permite colar um JSON com uma lista de usuários. O sistema oferece uma **pré-visualização** que valida os dados, indicando quais usuários são novos, quais serão atualizados e quais contêm erros, antes de confirmar a criação.

- **Ações em Massa:**
    - O administrador pode selecionar múltiplos alunos na tabela e aplicar uma das seguintes ações:
        1.  Excluir Alunos.
        2.  Resetar Senha.
        3.  Matricular em Cursos (abre uma segunda etapa para selecionar os cursos desejados).

### Funcionamento Técnico:
- **Página Própria:** A funcionalidade é renderizada em uma página de administração customizada (`tutor-ead-students`), e não na página padrão de "Usuários" do WordPress.
- **Armazenamento de Dados:** Os dados de perfil adicionais (como CPF, telefone, etc.) são salvos na tabela personalizada `wp_tutoread_user_info`.
- **Interatividade via AJAX e JavaScript:** Toda a página é controlada por JavaScript (`assets/js/admin-global.js` e `assets/js/bulk-preview.js`), que faz chamadas AJAX para buscar listas, validar dados e executar ações sem recarregar a página.
- **Log de Atividades:** A coluna "Último Acesso" é alimentada pela tabela `wp_tutoread_student_activity_log`, que registra as atividades dos alunos (como logins).
