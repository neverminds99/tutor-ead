<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        (function() {
            try {
                const userPref = localStorage.getItem('tutoread_dark_mode');
                const systemPref = window.matchMedia('(prefers-color-scheme: dark)').matches;
                if (userPref === 'true' || (userPref === null && systemPref)) {
                    document.body.classList.add('dark-mode');
                }
            } catch (e) {
                // Silently ignore errors
            }
        })();
    </script>
    <?php wp_head(); ?>
</head>
<body <?php body_class('tutoread-dashboard'); ?>>
<div id="tutoread-dashboard-wrapper">
    <header class="dashboard-header">
        <div class="logo">
            <!-- Pode ser o logo do site ou um logo customizado do plugin -->
            <h1>Painel TutorEAD</h1>
        </div>
        <nav class="dashboard-nav">
            <button id="dark-mode-toggle" class="dark-mode-button" title="Alternar modo escuro">🌙</button>
            <a href="<?php echo wp_logout_url(home_url()); ?>">Sair</a>
        </nav>
    </header>
    <main id="dashboard-content" class="dashboard-main">