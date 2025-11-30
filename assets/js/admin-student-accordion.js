document.addEventListener('DOMContentLoaded', function() {
    const accordion = document.querySelector('.tutor-accordion-wrap');
    if (accordion) {
        const trigger = accordion.querySelector('.tutor-accordion-trigger');
        if (trigger) {
            trigger.addEventListener('click', function() {
                accordion.classList.toggle('is-open');
            });
        }
    }
});
