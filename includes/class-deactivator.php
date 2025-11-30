<?php
namespace TutorEAD;

defined('ABSPATH') || exit;

// Análise do arquivo: /includes/class-deactivator.php

// Esta classe gerencia a desativação do plugin Tutor EAD.

// **Principais funções:**
// - `deactivate()`: Executa ações ao desativar o plugin.
//   - Remove tabelas do banco de dados (`Database::drop_tables()`).
//   - Exclui os papéis e permissões de usuários (`RoleManager::remove_roles_and_capabilities()`).

// **Resumo:**
// O arquivo garante que, ao desativar o plugin, as configurações sejam limpas corretamente, removendo dados do banco e permissões associadas.


class Deactivator {
    public static function deactivate() {
        Database::drop_tables();
        RoleManager::remove_roles_and_capabilities();
    }
}
