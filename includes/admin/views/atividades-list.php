<?php
defined('ABSPATH') || exit;

if (isset($_GET['success']) && $_GET['success'] == 1) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Atividade criada com sucesso!', 'tutor-ead') . '</p></div>';
    });
}

// Pega a cor de destaque das opções
$highlight_color = get_option('tutor_ead_highlight_color', '#0073aa');
?>

<style>
   :root {
       --tutor-ead-primary: <?php echo esc_attr($highlight_color); ?>;
       --tutor-ead-primary-light: #e0f2fe;
       --tutor-ead-text-dark: #1f2937;
       --tutor-ead-text-medium: #4b5563;
       --tutor-ead-text-light: #6b7280;
       --tutor-ead-border: #e5e7eb;
       --tutor-ead-bg-light: #f9fafb;
       --tutor-ead-bg-dark: #f3f4f6;
   }

   .tutor-activities-wrap {
       font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
       background: var(--tutor-ead-bg-dark);
       margin: -20px;
       padding: 32px;
       min-height: 100vh;
   }
   .tutor-activities-wrap * { box-sizing: border-box; }

   .activities-header {
       display: flex;
       justify-content: space-between;
       align-items: center;
       margin-bottom: 32px;
   }
   .activities-title {
       font-size: 32px;
       font-weight: 700;
       color: var(--tutor-ead-text-dark);
       margin: 0 0 8px 0;
   }
   .activities-subtitle {
       color: var(--tutor-ead-text-medium);
       font-size: 16px;
       margin: 0;
   }

   .tutor-card {
        background: #ffffff;
        border: 1px solid var(--tutor-ead-border);
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        margin-bottom: 24px;
    }
   .card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
   .card-title {
        font-size: 20px;
        font-weight: 600;
        color: var(--tutor-ead-text-dark);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }
   .card-title .dashicons {
        font-size: 24px;
        color: var(--tutor-ead-primary);
    }

   .btn-primary {
       background: var(--tutor-ead-primary);
       color: #ffffff;
       border: none;
       padding: 10px 20px;
       border-radius: 8px;
       font-weight: 600;
       font-size: 14px;
       cursor: pointer;
       transition: all 0.2s ease;
       display: inline-flex;
       align-items: center;
       gap: 8px;
       text-decoration: none;
       box-shadow: 0 2px 4px rgba(0,0,0,0.1);
   }
   .btn-primary:hover {
       background: var(--tutor-ead-primary);
       filter: brightness(1.1);
       transform: translateY(-1px);
       box-shadow: 0 4px 8px rgba(0,0,0,0.15);
       color: #ffffff;
   }
   .btn-primary .dashicons { font-size: 18px; }

   .wp-list-table {
       border: 1px solid var(--tutor-ead-border);
       border-radius: 8px;
       overflow: hidden; /* Garante que as bordas arredondadas funcionem */
   }
   .wp-list-table thead th {
       background: var(--tutor-ead-bg-light);
       color: var(--tutor-ead-text-medium);
       font-weight: 600;
       border-bottom: 1px solid var(--tutor-ead-border);
       padding: 12px 16px;
   }
   .wp-list-table tbody tr {
       background: #ffffff;
   }
   .wp-list-table tbody tr:nth-child(odd) {
       background: var(--tutor-ead-bg-light);
   }
   .wp-list-table tbody tr:hover {
       background: #f0f4f8; /* Um hover mais suave */
   }
   .wp-list-table td {
       padding: 12px 16px;
       vertical-align: middle;
       border-top: 1px solid var(--tutor-ead-border);
   }
   .wp-list-table td:first-child { border-left: none; }
   .wp-list-table td:last-child { border-right: none; }

   /* Estilo para o link da URL externa */
   .activity-url-display {
       display: inline-flex;
       align-items: center;
       gap: 8px;
   }
   .activity-url-display span {
       max-width: 250px;
       white-space: nowrap;
       overflow: hidden;
       text-overflow: ellipsis;
       color: var(--tutor-ead-text-medium);
   }

   /* Botão de Copiar Discreto */
   .copy-btn {
       background: none;
       border: none;
       color: var(--tutor-ead-text-light);
       padding: 4px;
       border-radius: 4px;
       cursor: pointer;
       transition: all 0.2s ease;
       opacity: 0.7;
   }
   .copy-btn:hover {
       color: var(--tutor-ead-primary);
       background: var(--tutor-ead-primary-light);
       opacity: 1;
   }
   .copy-btn .dashicons {
       font-size: 16px;
       line-height: 1;
   }

   /* Estilos para os links de ação (Editar, Associar, Excluir) */
   .wp-list-table td a {
       color: var(--tutor-ead-primary);
       text-decoration: none;
       transition: color 0.2s ease;
   }
   .wp-list-table td a:hover {
       color: var(--tutor-ead-text-dark);
   }
   .wp-list-table td a.delete-link {
       color: #dc2626; /* Cor vermelha para exclusão */
   }
   .wp-list-table td a.delete-link:hover {
       color: #b91c1c;
   }

   /* Botão Copiar Tudo (Externas) */
   #copy-all-btn {
       background: var(--tutor-ead-bg-light);
       border: 1px solid var(--tutor-ead-border);
       color: var(--tutor-ead-text-medium);
       padding: 8px 16px;
       border-radius: 8px;
       font-weight: 600;
       font-size: 13px;
       cursor: pointer;
       transition: all 0.2s ease;
       display: inline-flex;
       align-items: center;
       gap: 8px;
       box-shadow: 0 1px 2px rgba(0,0,0,0.05);
   }
   #copy-all-btn:hover {
       background: #eef2f6;
       border-color: #d1d5db;
       transform: translateY(-1px);
       box-shadow: 0 2px 4px rgba(0,0,0,0.08);
   }
   #copy-all-btn .dashicons { font-size: 16px; }
</style>

<div class="wrap tutor-activities-wrap">
   <div class="activities-header">
       <div>
            <h1 class="activities-title"><?php esc_html_e('Gerenciar Atividades', 'tutor-ead'); ?></h1>
            <p class="activities-subtitle"><?php esc_html_e('Gerencie todas as atividades do sistema', 'tutor-ead'); ?></p>
       </div>
       <a href="<?php echo admin_url('admin.php?page=tutor-ead-add-new-activity'); ?>" class="btn-primary">
           <span class="dashicons dashicons-plus-alt"></span>
           <?php esc_html_e('Nova Atividade', 'tutor-ead'); ?>
       </a>
   </div>
   
   <div class="tutor-card">
        <div class="card-header">
            <h2 class="card-title">
                <span class="dashicons dashicons-list-view"></span>
                <?php esc_html_e('Todas as Atividades', 'tutor-ead'); ?>
            </h2>
            <button id="copy-all-btn">
                <span class="dashicons dashicons-clipboard"></span>
                <?php esc_html_e('Copiar Tudo (Externas)', 'tutor-ead'); ?>
            </button>
        </div>
        <table class="wp-list-table widefat striped">
            <thead>
                <tr>
                    <th><?php _e('ID', 'tutor-ead'); ?></th>
                    <th><?php _e('Título', 'tutor-ead'); ?></th>
                    <th><?php _e('Tipo', 'tutor-ead'); ?></th>
                    <th><?php _e('URL', 'tutor-ead'); ?></th>
                    <th><?php _e('Data de Criação', 'tutor-ead'); ?></th>
                    <th><?php _e('Ações', 'tutor-ead'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( !empty($atividades) ) : foreach ( $atividades as $atividade ) : 
                    $tipo = '';
                    if ($atividade->is_externa == 0) {
                        $tipo = __('Padrão', 'tutor-ead');
                    } elseif ($atividade->is_externa == 1 && !empty($atividade->link_externo)) {
                        $tipo = __('Externa', 'tutor-ead');
                    } else {
                        $tipo = __('Presencial', 'tutor-ead');
                    }
                ?>
                    <tr>
                        <td><?php echo esc_html($atividade->id); ?></td>
                        <td><?php echo esc_html($atividade->titulo); ?></td>
                        <td><?php echo esc_html($tipo); ?></td>
                        <td>
                            <?php if ($atividade->is_externa == 1 && !empty($atividade->link_externo)) : ?>
                                <div class="activity-url-display">
                                    <span id="activity-url-<?php echo esc_attr($atividade->id); ?>"><?php echo esc_url($atividade->link_externo); ?></span>
                                    <button class="copy-btn" onclick="copyToClipboard('activity-url-<?php echo esc_attr($atividade->id); ?>', this)" title="<?php esc_attr_e('Copiar Link', 'tutor-ead'); ?>">
                                        <span class="dashicons dashicons-admin-page"></span>
                                    </button>
                                </div>
                            <?php else : ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?php echo esc_html($atividade->data_criacao); ?></td>
                        <td>
                            <?php
                            $edit_page_slug = 'tutor-ead-edit-atividade-padrao'; // Default
                            if ($atividade->is_externa == 1 && !empty($atividade->link_externo)) {
                                $edit_page_slug = 'tutor-ead-edit-atividade-externa'; // External
                            }

                            $edit_url = admin_url('admin.php?page=' . $edit_page_slug . '&id=' . $atividade->id);
                            $delete_url = admin_url('admin.php?page=tutor-ead-delete-atividade&activity_id=' . $atividade->id);
                            $associate_url = admin_url('admin.php?page=tutor-ead-associate-activity-select-course&activity_id=' . $atividade->id);
                            ?>
                            <a href="<?php echo esc_url($edit_url); ?>"><?php _e('Editar', 'tutor-ead'); ?></a> |
                            <a href="<?php echo esc_url($associate_url); ?>"><?php _e('Associar', 'tutor-ead'); ?></a> |
                            <a href="<?php echo esc_url($delete_url); ?>" onclick="return confirm('<?php _e('Tem certeza que deseja excluir esta atividade? Esta ação não pode ser desfeita.', 'tutor-ead'); ?>')" class="delete-link"><?php _e('Excluir', 'tutor-ead'); ?></a>
                        </td>
                    </tr>
                <?php endforeach; else : ?>
                    <tr><td colspan="6"><?php _e('Nenhuma atividade encontrada.', 'tutor-ead'); ?></td></tr>
                <?php endif; ?>
            </tbody>
        </table>
<script>
function copyToClipboard(elementId, buttonElement) {
    const tempInput = document.createElement('input');
    const textToCopy = document.getElementById(elementId).innerText;
    tempInput.value = textToCopy;
    document.body.appendChild(tempInput);
    tempInput.select();
    document.execCommand('copy');
    document.body.removeChild(tempInput);

    const originalContent = buttonElement.innerHTML;
    const originalTitle = buttonElement.title;
    buttonElement.innerHTML = '<span class="dashicons dashicons-yes"></span>';
    buttonElement.title = 'Copiado!';

    setTimeout(function() {
        buttonElement.innerHTML = originalContent;
        buttonElement.title = originalTitle;
    }, 2000);
}

document.addEventListener('DOMContentLoaded', function() {
    const copyAllBtn = document.getElementById('copy-all-btn');
    if (copyAllBtn) {
        copyAllBtn.addEventListener('click', function() {
            const activities = <?php echo json_encode($atividades); ?>;
            let textToCopy = '';

            activities.forEach(function(activity) {
                if (activity.is_externa == 1 && activity.link_externo) {
                    textToCopy += activity.titulo + ' - ' + activity.link_externo + '\n';
                }
            });

            if (textToCopy) {
                navigator.clipboard.writeText(textToCopy).then(function() {
                    const originalText = copyAllBtn.innerHTML;
                    copyAllBtn.innerHTML = '<span class="dashicons dashicons-yes"></span> Copiado com sucesso!';
                    copyAllBtn.disabled = true;
                    setTimeout(function() {
                        copyAllBtn.innerHTML = originalText;
                        copyAllBtn.disabled = false;
                    }, 2500);
                }, function(err) {
                    alert('Erro ao copiar as atividades.');
                });
            } else {
                alert('Nenhuma atividade externa com URL encontrada para copiar.');
            }
        });
    }
});
</script>