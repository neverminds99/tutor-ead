# Documentação TutorEAD: Gerenciamento de Cursos e Conteúdo

Este documento descreve a arquitetura para a criação e estruturação de cursos, que é o coração do TutorEAD. O sistema utiliza tabelas personalizadas e uma interface de construtor de cursos avançada (Course Builder) para gerenciar o conteúdo.

---

## 1. Cursos e Atividades (Entidades de Banco de Dados)

Ao contrário de muitos plugins que usam Custom Post Types (CPTs), o TutorEAD gerencia cursos e atividades diretamente em **tabelas personalizadas** no banco de dados, o que oferece mais performance e flexibilidade.

### `class-course-manager.php`
- **Responsabilidade:** Gerencia a entidade "Curso".
- **Tabela Principal:** `wp_tutoread_courses`.
- **Funcionalidades:** Lida com a lógica de negócio para criar, editar e excluir cursos, interagindo diretamente com a tabela `wp_tutoread_courses`.

### `class-activity-manager.php`
- **Responsabilidade:** Gerencia as "Atividades" avaliativas, como quizzes.
- **Tabelas Principais:** `wp_atividades`, `wp_perguntas`, `wp_alternativas`.
- **Funcionalidades:**
    -   Permite a criação de diferentes tipos de atividades: **Padrão** (quiz montado no plugin), **Externa** (link para conteúdo externo) e **Presencial** (sem conteúdo online).
    -   Um formulário unificado (`atividade-form-unificada.php`) lida com a criação inicial, e editores específicos são usados para cada tipo.
    -   Para atividades padrão (quizzes), o `admin/js/tutor-ead.js` permite adicionar perguntas e alternativas dinamicamente ao formulário.
    
### `class-activity-association-manager.php`
- **Responsabilidade:** Gerencia a associação de atividades aos cursos.
- **Tabela Principal:** `wp_tutoread_course_activities`.
- **Funcionalidade:** Permite definir em que parte de um curso (módulo ou aula) uma atividade específica deve aparecer.

---

## 2. O Construtor de Cursos (Course Builder)

Esta é a principal ferramenta para estruturar o conteúdo. É uma aplicação de página única (SPA - Single Page Application) complexa, construída com JavaScript e alimentada por uma API REST interna.

### Arquivos Principais:
-   **Backend:** `includes/admin/class-course-builder-manager.php`
-   **Frontend:** `assets/js/admin-course-builder.js`
-   **Estilos:** `assets/css/admin-course-builder.css`
-   **Template Canvas:** `templates/course-builder-canvas.php` (página em branco que carrega a aplicação JS).

### Arquitetura e Hierarquia de Conteúdo:
O Course Builder implementa uma hierarquia de três níveis, permitindo uma organização de conteúdo granular:
1.  **Módulo:** O nível mais alto, representa um grande tópico do curso.
2.  **Unidade:** Um sub-nível dentro de um módulo. Permite agrupar aulas relacionadas.
3.  **Aula:** O conteúdo final, que pode conter texto e vídeo.

Tecnicamente, tanto **Módulos** quanto **Unidades** são armazenados na mesma tabela `wp_tutoread_modules`, diferenciados pelo campo `parent_id`. Uma Unidade é um módulo cujo `parent_id` aponta para outro módulo. As **Aulas** são armazenadas em `wp_tutoread_lessons`.

### Interface e Interatividade:
-   **Drag-and-Drop:** Usa a biblioteca **SortableJS** para permitir que o administrador arraste e solte módulos, unidades e aulas para reordená-los.
-   **Visualização "Drill-down":** O usuário começa visualizando a lista de módulos. Ao clicar em um módulo, ele "entra" e visualiza as unidades daquele módulo. Ao clicar em uma unidade, ele visualiza as aulas. Um breadcrumb no topo indica a localização atual.
-   **Edição Inline e Modais:** A maioria das edições (título, descrição) pode ser feita diretamente na interface. Configurações mais complexas, como as do curso, são editadas em um modal.
-   **Salvemanto Automático de Rascunho (Auto-Save):** As alterações são salvas automaticamente como um rascunho em `wp_usermeta`, prevenindo a perda de trabalho. O usuário é notificado da existência de um rascunho e pode optar por carregá-lo ou descartá-lo. O botão principal "Salvar" publica o rascunho para a versão final.
-   **Comunicação via API REST:** Toda a comunicação (carregar dados, salvar, deletar) é feita via chamadas assíncronas para a API REST interna do plugin.

---

## 3. Integração com IA (Google Gemini)

O Course Builder possui uma integração direta com a API do Google Gemini, oferecendo um assistente de IA para a criação de cursos.

### Funcionalidades:
-   **Sugestão Inicial:** Ao abrir o builder, a IA analisa a estrutura atual do curso e, após um delay, pode oferecer uma sugestão de melhoria (ex: "Adicionar um módulo de conclusão").
-   **Chat com IA:** Um painel lateral abre uma interface de chat. O administrador pode enviar um comando em linguagem natural (ex: "Crie um módulo sobre JavaScript com aulas sobre variáveis, funções e loops").
-   **Processamento e Resposta:**
    1.  O JavaScript envia o comando do usuário e o **contexto do curso atual** (toda a estrutura em JSON) para a API REST interna (`/ai-edit`).
    2.  O backend (PHP) formata um prompt detalhado para a API do Gemini, instruindo-a a retornar uma estrutura JSON válida.
    3.  A IA processa o pedido e retorna o JSON do curso modificado.
    4.  O frontend recebe a resposta, exibe uma explicação da IA e uma **pré-visualização das diferenças** (diff) entre a estrutura antiga e a nova.
    5.  O administrador pode **aprovar** (aplicando as alterações à interface) ou **rejeitar** a sugestão.

### Funcionamento Técnico:
-   **API Key:** Requer uma chave de API do Google AI, que deve ser inserida na página de configurações do TutorEAD.
-   **API REST:** A comunicação é intermediada pelos endpoints `/get-initial-suggestion` e `/ai-edit` em `api/api.php`.
-   **JavaScript:** A lógica para enviar prompts, receber respostas, gerar a visualização das diferenças e aplicar as alterações está toda contida em `assets/js/admin-course-builder.js`.
