<?php
namespace TutorEAD;

defined('ABSPATH') || exit;

// Análise do arquivo: /includes/class-activator.php

// Esta classe gerencia a ativação do plugin Tutor EAD.

// **Principais funções:**
// - `activate()`: Executa ações essenciais na ativação do plugin:
//   - Cria as tabelas do banco de dados (`Database::create_tables()`).
//   - Adiciona papéis e permissões (`RoleManager::add_roles_and_capabilities()`).
//   - Cria as páginas padrão (`PageManager::create_pages()`).

// **Resumo:**
// O arquivo assegura que, ao ativar o plugin, a estrutura de banco, permissões e páginas sejam corretamente configuradas.


class Activator {
    public static function activate() {
        Database::create_tables();
        RoleManager::add_roles_and_capabilities();
        PageManager::create_pages();
    }
}
