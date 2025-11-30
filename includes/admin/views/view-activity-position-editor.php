<?php
defined('ABSPATH') || exit;

/**
 * View para o Gerenciador de Atividades do Curso.
 *
 * @var object $course O objeto do curso.
 * @var array $modules_with_lessons Módulos com suas respectivas aulas.
 * @var array $positioned_activities Atividades já posicionadas.
 * @var array $unpositioned_activities Atividades ainda não posicionadas.
 */

$highlight_color = get_option('tutor_ead_highlight_color', '#0073aa');
$course_id = $course->id;
$position_nonce = wp_create_nonce('tutoread_update_activity_position_nonce');
$unposition_nonce = wp_create_nonce('tutoread_unposition_activity_nonce');

?>
<style>
    /* Layout Principal */
    .position-editor-wrap { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif; margin: -20px; background: #f3f4f6; min-height: 100vh; }
    .position-editor-container { display: flex; gap: 32px; padding: 32px; }
    .position-editor-sidebar { width: 280px; flex-shrink: 0; position: sticky; top: 50px; align-self: flex-start; }
    .position-editor-main { flex-grow: 1; min-width: 0; }

    /* Cabeçalho */
    .position-editor-header { margin-bottom: 24px; }
    .position-editor-title { font-size: 28px; font-weight: 600; color: #1f2937; margin: 0; }
    .position-editor-course { font-size: 18px; color: #4b5563; margin-top: 4px; }

    /* Container de Módulos na Sidebar */
    .modules-container { border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; margin-bottom: 16px; padding: 12px 16px; }
    .modules-container h2 { font-size: 16px; font-weight: 600; margin: 0 0 8px 0; }
    .module-nav-list.is-scrollable { max-height: 250px; overflow-y: auto; padding-right: 8px; }
    .module-nav-list a.active-module { color: var(--highlight-color, #0073aa); background: #e0f2fe; border-left-color: var(--highlight-color, #0073aa); }

    /* Imagem de Destaque do Curso na Sidebar */
    .course-sidebar-image {
        width: 100%;
        height: 120px; /* Altura fixa para consistência */
        object-fit: cover;
        border-radius: 8px;
        margin-bottom: 15px;
        border: 1px solid var(--tutor-ead-border);
    }
    .course-sidebar-image.placeholder {
        background-color: #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--tutor-ead-text-light);
        font-size: 13px;
        text-align: center;
        line-height: 1.4;
    }

    /* Accordion & Listas na Sidebar */
    .sidebar-accordion { border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; margin-bottom: 16px; }
    .accordion-header { padding: 12px 16px; font-weight: 600; cursor: pointer; display: flex; justify-content: space-between; align-items: center; }
    .accordion-header .dashicons { transition: transform 0.2s ease; }
    .accordion-content { display: none; border-top: 1px solid #e5e7eb; padding: 8px; max-height: 250px; overflow-y: auto; }
    .accordion-content.is-open { display: block; }
    .accordion-header.is-open .dashicons { transform: rotate(180deg); }

    .module-nav-list, .unpositioned-activity-list { list-style: none; padding: 0; margin: 0; }
    .module-nav-list a, .unpositioned-activity-list .activity-item { display: block; padding: 8px 12px; text-decoration: none; color: #334155; border-radius: 6px; font-weight: 500; transition: all 0.2s ease; border-left: 4px solid transparent; }
    .module-nav-list a:hover, .unpositioned-activity-list .activity-item:hover { background: #f3f4f6; }
    .unpositioned-activity-list .activity-item { cursor: pointer; }
    .unpositioned-activity-list .activity-item.is-selected { color: var(--highlight-color, #0073aa); background: #e0f2fe; border-left-color: var(--highlight-color, #0073aa); }
    .unpositioned-activity-list .empty-state { padding: 12px; color: #6b7280; }

    /* Lista de Módulos e Aulas */
    .module-list { list-style: none; padding: 0; margin: 0; }
    .module-item { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; margin-bottom: 20px; scroll-margin-top: 20px; }
    .module-header { padding: 16px; border-bottom: 1px solid #e5e7eb; font-size: 18px; font-weight: 600; color: #111827; }
    .lesson-list { list-style: none; padding: 12px; margin: 0; min-height: 20px; }

    /* Placeholder */
    .add-here-button { padding: 12px; margin: 4px 0; border: 2px dashed #d1d5db; border-radius: 8px; text-align: center; color: #9ca3af; font-size: 14px; font-weight: 500; transition: all 0.2s ease; cursor: pointer; }
    .add-here-button:hover { border-color: var(--highlight-color, #0073aa); background: #e0f2fe; color: var(--highlight-color, #0073aa); }
    .add-here-button.is-disabled { cursor: not-allowed; background: #f9fafb; border-color: #e5e7eb; color: #d1d5db; }

    /* Atividade Posicionada */
    .activity-card { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px; background: var(--highlight-color, #0073aa); color: #fff; border-radius: 8px; font-weight: 500; box-shadow: 0 2px 4px rgba(0,0,0,0.1); position: relative; transition: box-shadow 0.2s ease; margin: 4px 0; }
    .activity-card.is-loading { pointer-events: none; }
    .activity-card.is-loading .activity-spinner { display: block; }
    .activity-card.is-success { box-shadow: 0 0 0 3px #22c55e; }
    .remove-activity-btn { background: rgba(0,0,0,0.2); border: none; color: #fff; border-radius: 50%; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: background 0.2s ease; padding: 0; }
    .remove-activity-btn:hover { background: rgba(0,0,0,0.4); }
    .remove-activity-btn .dashicons { font-size: 18px; line-height: 1.3; }
    .activity-spinner { display: none; /* ... (estilos do spinner) ... */ }
</style>

<div class="wrap position-editor-wrap">
    <div class="position-editor-container">
        <!-- Coluna Lateral (Sidebar) -->
        <aside class="position-editor-sidebar">
            <div class="modules-container">
                <?php if (!empty($course->capa_img)) : ?>
                    <img src="<?php echo esc_url($course->capa_img); ?>" alt="<?php echo esc_attr($course->title); ?>" class="course-sidebar-image">
                <?php else : ?>
                    <div class="course-sidebar-image placeholder">
                        <?php esc_html_e('Sem Imagem', 'tutor-ead'); ?><br><?php echo esc_html($course->title); ?>
                    </div>
                <?php endif; ?>
                <h2><?php esc_html_e('Módulos do Curso', 'tutor-ead'); ?></h2>
                <ul class="module-nav-list <?php echo count($modules_with_lessons) > 6 ? 'is-scrollable' : ''; ?>">
                    <?php foreach ($modules_with_lessons as $module) : ?>
                        <li><a href="#module-item-<?php echo esc_attr($module['id']); ?>" data-module-target="#module-item-<?php echo esc_attr($module['id']); ?>"><?php echo esc_html($module['title']); ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <div class="sidebar-accordion">
                <div class="accordion-header is-open">
                    <span><?php esc_html_e('Atividades Não Posicionadas', 'tutor-ead'); ?></span>
                    <span class="dashicons dashicons-arrow-down"></span>
                </div>
                <div class="accordion-content is-open">
                    <ul id="unpositioned-list" class="unpositioned-activity-list">
                        <?php if (empty($unpositioned_activities)) : ?>
                            <li class="empty-state"><?php esc_html_e('Nenhuma atividade para posicionar.', 'tutor-ead'); ?></li>
                        <?php else : ?>
                            <?php foreach ($unpositioned_activities as $activity) : ?>
                                <li class="activity-item" data-activity-id="<?php echo esc_attr($activity->id); ?>" data-activity-title="<?php echo esc_attr($activity->title); ?>">
                                    <?php echo esc_html($activity->title); ?>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </aside>

        <!-- Conteúdo Principal -->
        <main class="position-editor-main">
            <div class="position-editor-header">
                <h1 class="position-editor-title"><?php esc_html_e('Gerenciar Atividades do Curso', 'tutor-ead'); ?></h1>
                <p class="position-editor-course"><?php echo esc_html($course->title); ?></p>
            </div>

            <ul class="module-list">
                <?php foreach ($modules_with_lessons as $module) : ?>
                    <li class="module-item" id="module-item-<?php echo esc_attr($module['id']); ?>">
                        <div class="module-header"><?php echo esc_html($module['title']); ?></div>
                        <ul class="lesson-list" data-module-id="<?php echo esc_attr($module['id']); ?>">
                            <li class="add-here-button-container">
                                <?php $first_lesson_id = !empty($module['lessons']) ? $module['lessons'][0]['id'] : 0; ?>
                                <div class="add-here-button" data-module-id="<?php echo esc_attr($module['id']); ?>" data-lesson-id="<?php echo esc_attr($first_lesson_id); ?>" data-position="antes">
                                    <?php esc_html_e('Adicionar aqui', 'tutor-ead'); ?>
                                </div>
                            </li>
                            <?php foreach ($module['lessons'] as $lesson) : ?>
                                <li class="lesson-item" data-lesson-id="<?php echo esc_attr($lesson['id']); ?>">
                                    <span><?php echo esc_html($lesson['title']); ?></span>
                                </li>
                                <li class="add-here-button-container">
                                    <div class="add-here-button" data-module-id="<?php echo esc_attr($module['id']); ?>" data-lesson-id="<?php echo esc_attr($lesson['id']); ?>" data-position="depois">
                                        <?php esc_html_e('Adicionar aqui', 'tutor-ead'); ?>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                <?php endforeach; ?>
            </ul>
        </main>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // --- STATE MANAGEMENT ---
    let selectedActivity = null;
    let positionedActivities = <?php echo json_encode($positioned_activities); ?>;

    // --- DOM ELEMENTS ---
    const unpositionedList = $('#unpositioned-list');
    const addButtons = $('.add-here-button');

    // --- FUNCTIONS ---
    function renderAllPositionedActivities() {
        console.log('renderAllPositionedActivities called.');
        console.log('Current positionedActivities:', positionedActivities);
        $('.activity-card-container').remove(); // Now correctly removes the <li> wrapper

        // Ordenar as atividades posicionadas para garantir a renderização correta
        positionedActivities.sort((a, b) => {
            if (a.module_id !== b.module_id) {
                return a.module_id - b.module_id;
            }
            if (a.lesson_id !== b.lesson_id) {
                // Tratar lesson_id=0 como o topo do módulo
                if (a.lesson_id === 0) return -1;
                if (b.lesson_id === 0) return 1;
                return a.lesson_id - b.lesson_id;
            }
            // 'antes' vem antes de 'depois'
            if (a.position === 'antes' && b.position === 'depois') return -1;
            if (a.position === 'depois' && b.position === 'antes') return 1;
            return 0;
        });

        positionedActivities.forEach(activity => {
            console.log('Processing activity:', activity);
            const cardHtml = createActivityCard(activity.id, activity.title);
            let target;
            if (activity.position === 'antes') {
                const selector = `.add-here-button[data-module-id=${activity.module_id}][data-lesson-id=${activity.lesson_id}][data-position='antes']`;
                console.log('Target selector (antes):', selector);
                const addButton = $(selector);
                console.log('addButton length:', addButton.length);
                target = addButton.parent();
                console.log('Target length (antes - parent):', target.length);
            } else { // activity.position === 'depois'
                const selector = `.lesson-item[data-lesson-id=${activity.lesson_id}]`;
                console.log('Target selector (depois - lesson-item):', selector);
                const lessonItem = $(selector);
                console.log('lessonItem length:', lessonItem.length);
                if (lessonItem.length) {
                    target = lessonItem.next('.add-here-button-container');
                    console.log('Target length (depois - next add-here-button-container):', target.length);
                } else {
                    console.warn(`Lesson item not found for activity ${activity.id} (lesson: ${activity.lesson_id})`);
                    target = $(); // Empty jQuery object
                }
            }
            
            if (target.length) {
                console.log('Target found for activity:', activity.id, target);
                target.before(cardHtml);
                console.log('Card inserted?', cardHtml.parent().length > 0 ? 'Yes' : 'No', cardHtml);
            } else {
                console.warn(`Target element not found for activity ${activity.id} (module: ${activity.module_id}, lesson: ${activity.lesson_id}, position: ${activity.position}). Card not rendered.`);
            }
        });
    }

    function createActivityCard(id, title) {
        return $(`
            <li class="activity-card-container">
                <div class="activity-card" data-activity-id="${id}" style="--highlight-color: <?php echo esc_attr($highlight_color); ?>;">
                    <span>${title}</span>
                    <button class="remove-activity-btn" title="Desposicionar"><span class="dashicons dashicons-no-alt"></span></button>
                </div>
            </li>
        `);
    }

    function updateAddButtonsState() {
        addButtons.toggleClass('is-disabled', selectedActivity === null);
        if (selectedActivity === null) {
            addButtons.text('<?php esc_html_e('Selecione uma atividade', 'tutor-ead'); ?>');
        } else {
            addButtons.text('<?php esc_html_e('Adicionar aqui', 'tutor-ead'); ?>');
        }
    }

    function initializeScrollSpy() {
        // --- LÓGICA DE ROLAGEM SUAVE ---
        $('.module-nav-list a').on('click', function(e) {
            e.preventDefault();
            const targetElement = $($(this).attr('href'));
            if (targetElement.length) {
                targetElement[0].scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });

        // --- LÓGICA DE SCROLL-SPY (DESTAQUE DO MÓDULO ATIVO) ---
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const moduleId = entry.target.id;
                    $('.module-nav-list a').each(function() {
                        const link = $(this);
                        const isActive = link.attr('href') === '#' + moduleId;
                        link.toggleClass('active-module', isActive);
                    });
                }
            });
        }, { rootMargin: '-40% 0px -60% 0px' }); // Ativa quando o módulo está no meio da tela

        $('.module-item').each(function() {
            observer.observe(this);
        });
    }

    // --- EVENT HANDLERS ---
    $('.accordion-header').on('click', function() {
        $(this).toggleClass('is-open').next('.accordion-content').slideToggle(200);
        $(this).find('.dashicons').toggleClass('dashicons-arrow-down dashicons-arrow-up');
    });

    unpositionedList.on('click', '.activity-item', function() {
        const clickedItem = $(this);
        const activityId = clickedItem.data('activity-id');

        if (selectedActivity && selectedActivity.id === activityId) {
            selectedActivity = null;
            clickedItem.removeClass('is-selected');
        } else {
            selectedActivity = { id: activityId, title: clickedItem.data('activity-title'), element: clickedItem };
            unpositionedList.find('.activity-item').removeClass('is-selected');
            clickedItem.addClass('is-selected');
        }
        updateAddButtonsState();
    });

    $('.module-list').on('click', '.add-here-button:not(.is-disabled)', function() {
        const button = $(this);
        $.post(ajaxurl, {
            action: 'tutoread_update_activity_position',
            nonce: '<?php echo $position_nonce; ?>',
            activity_id: selectedActivity.id,
            course_id: <?php echo $course_id; ?>,
            module_id: button.data('module-id'),
            lesson_id: button.data('lesson-id'),
            position: button.data('position')
        }).done(response => {
            if (response.success) {
                // Update state
                positionedActivities.push({ id: selectedActivity.id, title: selectedActivity.title, ...button.data() });
                selectedActivity.element.remove();
                selectedActivity = null;
                // Re-render
                renderAllPositionedActivities();
                updateAddButtonsState();
            }
        });
    });

    $('.module-list').on('click', '.remove-activity-btn', function() {
        const card = $(this).closest('.activity-card');
        const activityId = card.data('activity-id');
        
        $.post(ajaxurl, {
            action: 'tutoread_unposition_activity',
            nonce: '<?php echo $unposition_nonce; ?>',
            activity_id: activityId,
            course_id: <?php echo $course_id; ?>
        }).done(response => {
            if (response.success) {
                const activity = positionedActivities.find(a => a.id == activityId);
                // Update state
                positionedActivities = positionedActivities.filter(a => a.id != activityId);
                // Add back to unpositioned list
                const newItem = $(`<li class="activity-item" data-activity-id="${activity.id}" data-activity-title="${activity.title}">${activity.title}</li>`);
                unpositionedList.append(newItem);
                // Re-render
                card.remove();
            }
        });
    });

    // --- INITIALIZATION ---
    renderAllPositionedActivities();
    updateAddButtonsState();
    initializeScrollSpy();
});
</script>