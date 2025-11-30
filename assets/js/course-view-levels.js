document.addEventListener('DOMContentLoaded', function () {
    const levelsContainer = document.querySelector('.course-overview-container .module-list');

    if (!levelsContainer) {
        return;
    }

    levelsContainer.addEventListener('click', function (e) {
        const target = e.target.closest('.level-item-title');

        if (!target) {
            return;
        }

        const moduleId = target.dataset.moduleId;
        const unitId = target.dataset.unitId;
        const contentContainer = target.nextElementSibling;

        if (!contentContainer) {
            return;
        }

        // Toggle visibility
        const isVisible = contentContainer.style.display === 'block';
        contentContainer.style.display = isVisible ? 'none' : 'block';
        target.classList.toggle('is-open', !isVisible);

        // Load content only if it's the first time opening
        if (!isVisible && contentContainer.innerHTML.trim() === '') {
            contentContainer.innerHTML = '<p>Carregando...</p>';

            const formData = new FormData();
            formData.append('action', 'tutor_ead_get_level_content');
            formData.append('nonce', tutor_ead_levels_data.nonce);
            if (moduleId) {
                formData.append('module_id', moduleId);
            }
            if (unitId) {
                formData.append('unit_id', unitId);
            }

            fetch(tutor_ead_levels_data.ajax_url, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    contentContainer.innerHTML = data.data.html;
                } else {
                    contentContainer.innerHTML = '<p>' + data.data.message + '</p>';
                }
            })
            .catch(error => {
                console.error('Erro no AJAX:', error);
                contentContainer.innerHTML = '<p>Ocorreu um erro ao carregar o conteúdo.</p>';
            });
        }
    });
});
