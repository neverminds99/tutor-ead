<?php
namespace TutorEAD;

// Análise do arquivo: /includes/includes-loader.php

// Este arquivo inicializa o plugin Tutor EAD, carregando todas as classes e funcionalidades essenciais.

// **Principais arquivos incluídos:**
// - **Banco de Dados:** Gerencia a criação e manutenção de tabelas (class-database.php).
// - **Ativação/Desativação:** Define ações ao ativar/desativar o plugin (class-activator.php, class-deactivator.php).
// - **Roles e Permissões:** Configura perfis de usuário e permissões (class-role-manager.php).
// - **Gerenciamento de Páginas:** Cria páginas essenciais automaticamente (class-page-manager.php).
// - **Shortcodes e AJAX:** Adiciona shortcodes e manipula requisições AJAX (class-shortcodes.php, ajax-handlers.php).
// - **Redirecionamento:** Direciona usuários ao dashboard correto (class-user-redirect.php).
// - **Recursos e Estilos:** Gerencia scripts e estilos do plugin (class-asset-manager.php).
// - **Funções Auxiliares:** Contém utilitários gerais (helpers.php).

// **Arquivos do painel administrativo:**
// - **Gerenciamento de Cursos e Atividades:** Criação, edição e construção interativa de cursos (class-course-manager.php, class-course-builder-manager.php, class-activity-manager.php).
// - **Dashboard e Relatórios:** Interface administrativa e geração de estatísticas (class-dashboard-manager.php, class-report-manager.php).
// - **Configurações e Licenciamento:** Personalização e ativação de licença (class-settings.php, class-license-manager.php).
// - **Gerenciamento de Usuários:** Professores e alunos (class-teacher-manager.php, class-student-manager.php).

// **Resumo:** 
// O arquivo garante que todas as funcionalidades do plugin sejam carregadas corretamente, organizando a estrutura de cursos, usuários e painéis administrativos de forma modular e expansível.


// Inclui os arquivos principais
require_once plugin_dir_path(__FILE__) . 'class-database.php';
require_once plugin_dir_path(__FILE__) . 'class-activator.php';
require_once plugin_dir_path(__FILE__) . 'class-deactivator.php';
require_once plugin_dir_path(__FILE__) . 'class-role-manager.php';
require_once plugin_dir_path(__FILE__) . 'class-page-manager.php';
require_once plugin_dir_path(__FILE__) . 'ajax-handlers.php';
require_once plugin_dir_path(__FILE__) . 'class-shortcodes.php';
require_once plugin_dir_path(__FILE__) . 'helpers.php';
require_once plugin_dir_path(__FILE__) . 'class-user-redirect.php';
require_once plugin_dir_path(__FILE__) . 'class-asset-manager.php';

// Inclui os arquivos do diretório 'admin'
require_once plugin_dir_path(__FILE__) . 'admin/class-activity-manager.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-admin-menus.php';

require_once plugin_dir_path(__FILE__) . 'admin/class-course-manager.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-license-manager.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-meta-boxes.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-dashboard-manager.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-report-manager.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-settings.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-teacher-manager.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-student-manager.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-setup-wizard.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-activity-association-manager.php';
