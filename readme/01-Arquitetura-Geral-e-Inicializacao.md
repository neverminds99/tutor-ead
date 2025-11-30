# Documentação TutorEAD: Arquitetura Geral e Inicialização

Este documento descreve a arquitetura central do plugin TutorEAD, seu processo de inicialização, e a estrutura do banco de dados.

---

## 1. Arquivo Principal (`plugin-tutor-eap.php`)

O `plugin-tutor-eap.php` é o ponto de entrada do plugin. Ele adota uma arquitetura orientada a objetos, utilizando o padrão **Singleton** para garantir que apenas uma instância da classe `Plugin` seja executada.

### Responsabilidades Principais:
- **Definição de Constantes:** Define constantes globais como `TUTOR_EAD_URL`, `TUTOR_EAD_PATH`, `TUTOR_EAD_VERSION`, etc., que são usadas em todo o plugin.
- **Ciclo de Vida:** Registra os hooks de ativação (`register_activation_hook`) e desativação (`register_deactivation_hook`), delegando as ações para os métodos `activate()` e `deactivate()` da classe `Plugin`.
- **Carregamento de Dependências (`load_dependencies`)**: Ao contrário de um simples "loader", este método carrega um conjunto específico de arquivos cruciais na inicialização, incluindo `includes-loader.php`, `ajax-handlers.php`, `api/api.php` e outros.
- **Inicialização (`init`)**: Conecta os principais hooks do WordPress a seus respectivos gerenciadores e classes, efetivamente "ligando" o plugin.
- **Verificação de Versão do Banco de Dados (`update_db_check`)**: Compara a versão atual do banco de dados salva em `wp_options` com a constante `Database::VERSION`. Se a versão for mais antiga, ele chama `Database::create_tables()` para atualizar o esquema de forma segura e exibe um aviso no painel de administração.
- **Redirecionamento de Login (`tutoread_login_redirect`)**: Implementa uma lógica de redirecionamento granular para cada papel de usuário do plugin (`tutor_admin`, `tutor_professor`, etc.) após o login.
- **Gerenciamento de Personificação (Impersonation)**: Contém a lógica para permitir que um administrador navegue no site como se fosse um aluno, usando `$_SESSION` para gerenciar o estado da personificação.

---

## 2. Carregador de Arquivos (`includes/includes-loader.php`)

Este arquivo tem uma única responsabilidade: carregar a maioria das classes do plugin usando `require_once`.

- **Estrutura:** É um arquivo puramente procedural, não uma classe.
- **Função:** Simplesmente inclui todos os arquivos de classe das pastas `includes/` e `includes/admin/`, garantindo que elas estejam disponíveis para serem instanciadas e utilizadas pelo plugin. A instanciação e o registro de hooks ocorrem dentro de outras partes do código (como a classe `Plugin` principal ou as próprias classes), não neste arquivo.

---

## 3. Ciclo de Vida (Ativação e Desativação)

### `includes/class-activator.php`
- **Classe:** `TutorEAD\Activator`
- **Método:** `activate()`
- **Ações na Ativação:**
    1.  **`Database::create_tables()`**: Cria todas as 21 tabelas personalizadas do plugin.
    2.  **`RoleManager::add_roles_and_capabilities()`**: Cria os papéis de usuário (`tutor_admin`, `tutor_professor`, `tutor_course_editor`, `tutor_aluno`) e suas permissões.
    3.  **`PageManager::create_pages()`**: Cria as páginas do frontend necessárias para o plugin funcionar.
    4.  **Gera Opções Iniciais:** Cria um `tutoread_jwt_secret` e um `tutoread_instance_api_key` (usado para o Real-time Tracker) se eles não existirem.
    5.  **Redirecionamento para o Wizard:** Define um `transient` (`tutor_ead_activation_redirect`) para redirecionar o administrador para o assistente de configuração na primeira vez.

### `includes/class-deactivator.php`
- **Classe:** `TutorEAD\Deactivator`
- **Método:** `deactivate()`
- **Ações na Desativação:**
    1.  **`Database::drop_tables()`**: **REMOVE TODAS AS TABELAS** personalizadas do plugin, resultando na **perda de todos os dados** (cursos, alunos, matrículas, notas, etc.).
    2.  **`RoleManager::remove_roles_and_capabilities()`**: Remove os papéis de usuário customizados criados pelo plugin.
    
> **AVISO IMPORTANTE:** A desativação deste plugin é uma ação **destrutiva**. Todo o conteúdo criado (cursos, matrículas, progresso) será permanentemente apagado do banco de dados.

---

## 4. Estrutura do Banco de Dados (`includes/class-database.php`)

A classe `TutorEAD\Database` é um **gerenciador de esquema**, não uma camada de abstração de dados (CRUD). Sua única função é criar e manter a estrutura das tabelas.

- **Tabelas Criadas (21 no total):**
    1.  `tutoread_courses`: Cursos.
    2.  `tutoread_alertas`: Avisos e alertas para o dashboard.
    3.  `tutoread_alertas_views`: Rastreia visualizações de alertas.
    4.  `tutoread_modules`: Módulos e Unidades (usando `parent_id`).
    5.  `tutoread_lessons`: Aulas.
    6.  `tutoread_lesson_blocks`: Blocos de conteúdo para o novo editor de aulas.
    7.  `matriculas`: Matrículas de usuários em cursos.
    8.  `atividades`: Atividades avaliativas (quizzes).
    9.  `tutoread_course_activities_presenciais`: Associa atividades presenciais a cursos.
    10. `boletins`: Notas e feedbacks.
    11. `progresso_aulas`: Progresso de conclusão das aulas.
    12. `tutoread_comments`: Comentários nas aulas.
    13. `perguntas`: Perguntas dos quizzes.
    14. `alternativas`: Alternativas das perguntas.
    15. `respostas`: Respostas dos alunos aos quizzes.
    16. `tutoread_course_activities`: Associa atividades EAD a cursos/módulos/aulas.
    17. `temp_login_tokens`: Tokens para o login temporário.
    18. `tutoread_user_info`: Dados de perfil customizados dos usuários (CPF, telefone, etc.).
    19. `tutoread_user_info_meta`: Metadados adicionais para o perfil.
    20. `tutoread_student_activity_log`: Logs de atividade dos alunos.
    21. `tutoread_advertisements`: Anúncios do sistema de publicidade.
    22. `tutoread_advertisement_stats`: Estatísticas de cliques/views dos anúncios.
- **Técnica:** Utiliza a função `dbDelta()` do WordPress para criar e atualizar as tabelas de forma segura, sem duplicar ou apagar dados existentes durante uma atualização.
- **Manutenção:** A classe contém funções `ensure_column()` e `ensure_column_is_nullable()` para aplicar correções pontuais no esquema do banco de dados em atualizações futuras.
