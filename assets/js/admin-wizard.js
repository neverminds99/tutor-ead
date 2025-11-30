jQuery(document).ready(function($) {
    // Inicializa o Color Picker
    $('.wp-color-picker').wpColorPicker();

    // Lógica para o Media Uploader
    var mediaUploader;

    $('#upload_logo_button').on('click', function(e) {
        e.preventDefault();
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }
        mediaUploader = wp.media.frames.file_frame = wp.media({
            title: 'Escolha o Logotipo',
            button: {
                text: 'Usar esta imagem'
            },
            multiple: false
        });

        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#tutor_ead_course_logo').val(attachment.url);
            $('#logo-preview').html('<img src="' + attachment.url + '" style="max-width: 200px; max-height: 100px;"/>');
        });

        mediaUploader.open();
    });
});
