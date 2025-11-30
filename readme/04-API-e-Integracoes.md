# Documentação TutorEAD: API REST e Integrações

Este documento descreve a API (Interface de Programação de Aplicativos) do TutorEAD, que permite a comunicação entre o plugin e sistemas externos (como aplicativos móveis ou o próprio frontend dinâmico do plugin), e detalha as integrações com serviços de terceiros.

---

## 1. Arquitetura da API REST

Diferente de uma abordagem legada com um arquivo `api.php` acessado diretamente, o TutorEAD utiliza a **API REST nativa do WordPress**, que é a maneira moderna, segura e padrão para criar APIs no WordPress.

-   **Arquivo Principal:** `api/api.php`
-   **Função:** Este arquivo não é um endpoint direto. Sua função é registrar todas as rotas (endpoints) personalizadas do plugin no WordPress usando a função `register_rest_route` dentro do hook `rest_api_init`.
-   **Namespace da API:** As rotas são organizadas sob o namespace `tutoread/v2`, `tutor-ead/v1`, e `tutoread-central/v1`, acessíveis através da URL `/wp-json/`. Por exemplo: `/wp-json/tutoread/v2/login`.

### Autenticação via JWT (JSON Web Tokens)
Para proteger os endpoints, o TutorEAD implementa um sistema de autenticação via JWT, usando a biblioteca `firebase/php-jwt`.

-   **Fluxo de Autenticação:**
    1.  **Login:** Um cliente externo envia um `POST` para o endpoint `/tutoread/v2/login` com nome de usuário e senha.
    2.  **Geração de Token:** Se as credenciais forem válidas, o backend usa `TutorEAD_JWT::generate_token()` para criar um token JWT. Este token contém o ID e o papel (role) do usuário e tem validade de 24 horas.
    3.  **Envio do Token:** O token é retornado ao cliente.
    4.  **Requisições Autenticadas:** Para acessar endpoints protegidos, o cliente deve enviar o token no cabeçalho da requisição: `Authorization: Bearer <token>`.
-   **Validação:** A função `tutoread_permission_validate_user` é usada como `permission_callback` na maioria dos endpoints. Ela extrai o token do cabeçalho, usa `TutorEAD_JWT::validate_token()` para verificar sua assinatura e validade, e confirma se o emissor (`iss`) do token corresponde ao site atual, prevenindo o uso de tokens de outros sites.
-   **Chave Secreta:** O segredo usado para assinar os tokens é gerado automaticamente e armazenado na tabela `wp_options` com a chave `tutoread_jwt_secret`.

### Principais Endpoints:
-   `/tutoread/v2/login`: Para autenticação e obtenção do token.
-   `/tutoread/v2/user/{id}`: Para obter e atualizar dados do perfil de um usuário.
-   `/tutoread/v2/student`: Para criar novos alunos.
-   `/tutoread/v2/cursos` e `/tutoread/v2/aluno/{id}/cursos`: Para listar cursos e gerenciar matrículas.
-   `/tutor-ead/v1/course-data/{id}`: Endpoint crucial para o Course Builder, que permite obter e salvar toda a estrutura de um curso.
-   `/tutor-ead/v1/ai-edit`: Endpoint que recebe os prompts do chat com IA, comunica-se com a API do Google Gemini e retorna a estrutura do curso modificada.

---

## 2. Integração com a Central TutorEAD

O plugin possui uma funcionalidade para se registrar em uma plataforma externa chamada "Central TutorEAD".

-   **Classe Responsável:** `TutorEAD_Central_Admin` (`includes/admin/class-tutoread-central-admin.php`).
-   **View:** `includes/admin/views/central-settings.php`.
-   **Propósito:** Permitir que o administrador do site vincule sua instalação do TutorEAD à plataforma central, provavelmente para licenciamento, suporte ou funcionalidades em nuvem.
-   **Funcionamento:**
    1.  O administrador acessa a página "TutorEAD Central" no painel.
    2.  Um `identificador` único para o site pode ser definido.
    3.  Ao clicar em "Enviar Pedido de Registro", o método `send_registration_request()` é chamado.
    4.  Ele monta um payload JSON contendo o `identificador`, a `site_url`, e o `jwt_secret` do site.
    5.  Este payload é enviado via `wp_remote_post` para o endpoint `https://tutoread.com.br/wp-json/tutoread-central/v1/register-request`.
    6.  A página então exibe o status da requisição (sucesso, já existe, ou erro).

---

## 3. Integração com a API do Google Gemini

Esta é uma das integrações mais avançadas do plugin, potencializando o Course Builder.

-   **Classe Responsável:** A lógica está principalmente nos callbacks da API REST em `api/api.php`, especificamente `tutor_ead_ai_edit_callback` e `tutor_ead_get_initial_suggestion_callback`.
-   **Configuração:** Requer uma chave de API do Google AI, que deve ser inserida na página de configurações do TutorEAD (`tutor_ead_gemini_api_key`).
-   **Funcionamento:**
    1.  O frontend (Course Builder) envia um prompt e o contexto do curso para a API REST interna.
    2.  O backend (PHP) formata um prompt detalhado para a API do Gemini, instruindo-a a retornar uma resposta em formato JSON.
    3.  É feita uma requisição `wp_remote_post` para o endpoint da API do Google: `https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent`.
    4.  A resposta da IA é processada, validada e retornada ao frontend para ser exibida ao usuário.
    5.  Esta integração é responsável por sugerir melhorias e gerar estruturas de curso automaticamente.
