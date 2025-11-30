<?php
defined('ABSPATH') || exit;

if (get_transient('tutor_ead_ad_submission_lock')) {
    delete_transient('tutor_ead_ad_submission_lock');
}

$highlight_color = get_option('tutor_ead_highlight_color', '#0073aa');

$edit_mode = false;
$ad_to_edit = null;
if (isset($_GET['edit_id'])) {
    $edit_mode = true;
    $ad_to_edit = \TutorEAD\Admin\AdvertisementManager::get_advertisement(intval($_GET['edit_id']));
}
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
        padding-bottom: 16px;
        border-bottom: 1px solid var(--tutor-ead-border);
    }
   .card-title {
        font-size: 20px;
        font-weight: 600;
        color: var(--tutor-ead-text-dark);
        margin: 0;
    }

    .form-field {
        margin-bottom: 16px;
    }

    .form-field label {
        display: block;
        font-weight: 600;
        margin-bottom: 8px;
        color: var(--tutor-ead-text-dark);
    }

    .form-field input[type="file"],
    .form-field input[type="url"],
    .form-field input[type="number"],
    .form-field select {
        width: 100%;
        padding: 10px;
        border: 1px solid var(--tutor-ead-border);
        border-radius: 8px;
    }

    .form-field p {
        font-size: 13px;
        color: var(--tutor-ead-text-light);
        margin-top: 8px;
    }

   .wp-list-table {
       border: 1px solid var(--tutor-ead-border);
       border-radius: 8px;
       overflow: hidden;
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
   .wp-list-table td {
       padding: 12px 16px;
       vertical-align: middle;
       border-top: 1px solid var(--tutor-ead-border);
   }
   .wp-list-table td a.delete-link {
       color: #dc2626;
   }

    #image-preview-container {
        margin-top: 10px;
    }

    #image-preview {
        max-width: 200px;
        height: auto;
        border: 1px solid var(--tutor-ead-border);
        border-radius: 8px;
        padding: 5px;
    }

    .cropper-modal {
        display: none;
        position: fixed;
        z-index: 99999;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.8); /* Increased opacity */
        opacity: 1; /* Explicitly set opacity */
    }

    .cropper-modal-content {
        background-color: #fefefe;
        margin: 5% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 80%;
        max-width: 800px;
        border-radius: 12px;
        opacity: 1;
    }

    .cropper-modal-content img {
        max-width: 100%;
    }

    .location-selector {
        display: flex;
        gap: 16px;
    }

    .location-option {
        border: 2px solid var(--tutor-ead-border);
        border-radius: 8px;
        padding: 16px;
        cursor: pointer;
        text-align: center;
        transition: all 0.2s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }

    .location-option.selected {
        border-color: var(--tutor-ead-primary);
        box-shadow: 0 0 0 3px var(--tutor-ead-primary-light);
    }

    .location-option img {
        width: 64px;
        height: 64px;
        margin-bottom: 8px;
    }

    .location-option span {
        font-weight: 600;
        color: var(--tutor-ead-text-medium);
    }
</style>

<div class="wrap tutor-activities-wrap">
    <div class="activities-header">
        <div>
            <h1 class="activities-title"><?php echo esc_html(get_admin_page_title()); ?></h1>
            <p class="activities-subtitle">Adicione, edite e remova as publicidades do site.</p>
        </div>
    </div>

    <div id="col-container" class="wp-clearfix">
        <div id="col-left">
            <div class="col-wrap">
                <div class="tutor-card">
                    <div class="card-header">
                        <h2 class="card-title"><?php echo $edit_mode ? 'Editar Publicidade' : 'Adicionar Nova Publicidade'; ?></h2>
                    </div>
                    <form action="<?php echo esc_url(admin_url('admin-post.php')); ?>" method="post" id="add-advertisement-form">
                        <input type="hidden" name="action" value="<?php echo $edit_mode ? 'edit_advertisement' : 'add_advertisement'; ?>">
                        <?php if ($edit_mode) : ?>
                            <input type="hidden" name="ad_id" value="<?php echo esc_attr($ad_to_edit->id); ?>">
                        <?php endif; ?>
                        <input type="hidden" name="ad_image_base64" id="ad_image_base64">
                        <?php wp_nonce_field($edit_mode ? 'edit_advertisement_nonce' : 'add_advertisement_nonce', $edit_mode ? 'edit_advertisement_nonce' : 'add_advertisement_nonce'); ?>

                        <div class="form-field">
                            <label for="ad-target">Local de Exibição</label>
                            <div class="location-selector">
                                <div class="location-option" data-location="dashboard_aluno_sidebar">
                                    <img src="<?php echo TUTOR_EAD_URL . 'assets/icon/barlateraldashboard.svg'; ?>" alt="Dashboard Aluno Sidebar">
                                    <span>Dashboard Aluno</span>
                                </div>
                                <div class="location-option" data-location="dashboard_aluno_top_banner">
                                    <img src="<?php echo TUTOR_EAD_URL . 'assets/icon/bannersuperior.svg'; ?>" alt="Dashboard Aluno Top Banner">
                                    <span>Banner Superior</span>
                                </div>
                            </div>
                            <input type="hidden" name="ad_target" id="ad-target-input">
                            <p>Selecione onde a publicidade será exibida.</p>
                        </div>

                        <div class="form-field">
                            <label for="ad-image-input">Imagem da Publicidade</label>
                            <input type="file" name="ad_image_input" id="ad-image-input" accept="image/*" <?php echo $edit_mode ? '' : 'required'; ?> disabled title="Selecione o local de exibição primeiro">
                            <p>Selecione a imagem para a publicidade.</p>
                            <p id="ad-resolution-suggestion" style="font-size: 13px; color: var(--tutor-ead-text-medium); margin-top: 8px;"></p>
                            <div id="image-preview-container" style="<?php echo $edit_mode ? 'display:block;' : 'display:none;'; ?>">
                                <img id="image-preview" src="<?php echo $edit_mode ? esc_url($ad_to_edit->image_url) : '#'; ?>" alt="Pré-visualização da imagem">
                            </div>
                        </div>

                        <div class="form-field">
                            <label for="ad-chance">Chance de Exibição (%)</label>
                            <input type="number" name="ad_chance" id="ad-chance" min="0" max="100" value="<?php echo $edit_mode ? esc_attr($ad_to_edit->display_chance) : '100'; ?>" required>
                            <p>A chance percentual desta publicidade ser exibida.</p>
                        </div>

                        <?php submit_button($edit_mode ? 'Atualizar Publicidade' : 'Adicionar Publicidade', 'primary'); ?>
                    </form>
                </div>
            </div>
        </div>
        <div id="col-right">
            <div class="col-wrap">
                <div class="tutor-card">
                    <div class="card-header">
                        <h2 class="card-title">Publicidades Existentes</h2>
                    </div>
                    <?php
                    $advertisements = \TutorEAD\Admin\AdvertisementManager::get_advertisements();
                    ?>
                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th scope="col">Imagem</th>
                                <th scope="col">Link</th>
                                <th scope="col">Local</th>
                                <th scope="col">Chance</th>
                                <th scope="col">Views</th>
                                <th scope="col">Clicks</th>
                                <th scope="col">Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($advertisements)) : ?>
                                <?php foreach ($advertisements as $ad) : ?>
                                    <tr>
                                        <td><img src="<?php echo esc_url($ad->image_url); ?>" style="max-width: 100px; height: auto;"></td>
                                        <td><a href="<?php echo esc_url($ad->link_url); ?>" target="_blank"><?php echo esc_url($ad->link_url); ?></a></td>
                                        <td><?php echo esc_html($ad->target_location); ?></td>
                                        <td><?php echo esc_html($ad->display_chance); ?>%</td>
                                        <td><?php echo esc_html($ad->views ?? 0); ?></td>
                                        <td><?php echo esc_html($ad->clicks ?? 0); ?></td>
                                        <td>
                                            <a href="<?php echo esc_url(admin_url('admin.php?page=tutor-ead-advertisements&edit_id=' . $ad->id)); ?>">Editar</a> |
                                            <a href="<?php echo esc_url(admin_url('admin-post.php?action=delete_advertisement&ad_id=' . $ad->id . '&_wpnonce=' . wp_create_nonce('delete_advertisement_' . $ad->id))); ?>" onclick="return confirm('Tem certeza que deseja excluir esta publicidade?');" class="delete-link">Excluir</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <tr>
                                    <td colspan="7">Nenhuma publicidade encontrada.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="cropper-modal" class="cropper-modal">
    <div class="cropper-modal-content">
        <h2>Recortar Imagem</h2>
        <div>
            <img id="image-to-crop" src="">
        </div>
        <button id="crop-button" class="button button-primary">Recortar</button>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const imageInput = document.getElementById('ad-image-input');
        const modal = document.getElementById('cropper-modal');
        const imageToCrop = document.getElementById('image-to-crop');
        const cropButton = document.getElementById('crop-button');
        const imagePreview = document.getElementById('image-preview');
        const imagePreviewContainer = document.getElementById('image-preview-container');
        const hiddenBase64Input = document.getElementById('ad_image_base64');
        let cropper;

        const locationOptions = document.querySelectorAll('.location-option');
        const targetInput = document.getElementById('ad-target-input');
        const resolutionSuggestion = document.getElementById('ad-resolution-suggestion');
        let selectedLocations = [];

        function updateAspectRatio() {
            let aspectRatio = 1; // Default to 1:1
            if (selectedLocations.includes('dashboard_aluno_top_banner')) {
                aspectRatio = 5 / 2;
            }
            return aspectRatio;
        }

        function updateResolutionSuggestion() {
            if (selectedLocations.includes('dashboard_aluno_top_banner')) {
                resolutionSuggestion.textContent = 'Melhor resolução: 1920 x 384 px (formato 5:2)';
            } else if (selectedLocations.includes('dashboard_aluno_sidebar')) {
                resolutionSuggestion.textContent = 'Melhor resolução: 600 x 600 px (formato 1:1)';
            } else {
                resolutionSuggestion.textContent = '';
            }
        }

        imageInput.addEventListener('change', function (e) {
            const files = e.target.files;
            if (files && files.length > 0) {
                const reader = new FileReader();
                reader.onload = function (e) {
                    imageToCrop.src = e.target.result;
                    modal.style.display = 'block';
                    if (cropper) {
                        cropper.destroy();
                    }
                    cropper = new Cropper(imageToCrop, {
                        aspectRatio: updateAspectRatio(),
                        viewMode: 1,
                    });
                };
                reader.readAsDataURL(files[0]);
            }
        });

        cropButton.addEventListener('click', function () {
            const canvas = cropper.getCroppedCanvas();

            canvas.toBlob(function (blob) {
                const reader = new FileReader();
                reader.readAsDataURL(blob);
                reader.onloadend = function () {
                    const base64data = reader.result;
                    hiddenBase64Input.value = base64data;
                    imagePreview.src = base64data;
                    imagePreviewContainer.style.display = 'block';
                    modal.style.display = 'none';
                    cropper.destroy();
                };
            });
        });

        <?php if ($edit_mode && !empty($ad_to_edit->target_location)) : ?>
            selectedLocations = '<?php echo $ad_to_edit->target_location; ?>'.split(',');
            targetInput.value = selectedLocations.join(',');
            locationOptions.forEach(option => {
                if (selectedLocations.includes(option.dataset.location)) {
                    option.classList.add('selected');
                }
            });
            imageInput.disabled = false;
            updateResolutionSuggestion();
        <?php endif; ?>


        locationOptions.forEach(option => {
            option.addEventListener('click', function () {
                const location = this.dataset.location;
                if (this.classList.contains('selected')) {
                    this.classList.remove('selected');
                    selectedLocations = selectedLocations.filter(l => l !== location);
                } else {
                    this.classList.add('selected');
                    selectedLocations.push(location);
                }
                targetInput.value = selectedLocations.join(',');

                if (selectedLocations.length > 0) {
                    imageInput.disabled = false;
                } else {
                    imageInput.disabled = true;
                }
                updateResolutionSuggestion();
            });
        });
    });
</script>
