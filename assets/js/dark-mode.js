jQuery(document).ready(function($) {
    const darkModeToggle = $('#dark-mode-toggle');
    const bodyElement = $('body'); // Seleciona o elemento <body>
    const systemPref = window.matchMedia('(prefers-color-scheme: dark)');

    /**
     * Aplica o tema (claro ou escuro) e atualiza o ícone do botão.
     * @param {boolean} isDark - True para modo escuro, false para modo claro.
     */
    function applyTheme(isDark) {
        bodyElement.toggleClass('dark-mode', isDark);
        darkModeToggle.html(isDark ? '☀️' : '🌙');
    }

    // --- Event Listeners ---

    // 1. Clique no botão para alternar manualmente o tema
    darkModeToggle.on('click', function() {
        const isCurrentlyDark = bodyElement.hasClass('dark-mode');
        const newPreference = !isCurrentlyDark;
        // Salva a escolha do usuário para persistir entre as visitas
        localStorage.setItem('tutoread_dark_mode', newPreference);
        applyTheme(newPreference);
    });

    // 2. Mudança na preferência de tema do sistema operacional
    systemPref.addEventListener('change', e => {
        // Só altera o tema se o usuário não tiver uma preferência manual salva
        if (localStorage.getItem('tutoread_dark_mode') === null) {
            applyTheme(e.matches);
        }
    });

    // Define o estado inicial do botão com base no tema atual
    // A classe 'dark-mode' já foi aplicada pelo script no <head> se necessário
    applyTheme(bodyElement.hasClass('dark-mode'));
});
