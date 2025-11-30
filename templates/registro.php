<?php
/**
 * Template Name: Registro
 */

 // Análise do arquivo: /templates/registro.php

// Este arquivo é um template do WordPress chamado "Registro".
// Define a estrutura da página de registro de usuários para o Tutor EAD.
// Exibe um título "Registro de Usuário" dentro de um contêiner principal.
// Utiliza o shortcode `[tutor_ead_register]` para carregar dinamicamente o formulário de registro.
// O layout é simples e depende do shortcode para exibir as funcionalidades de cadastro de usuários.


?>

<div class="container">
    <h1>Registro de Usuário</h1>
    <div>
        <?php echo do_shortcode('[tutor_ead_register]'); ?>
    </div>
</div>


