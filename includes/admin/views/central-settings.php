<?php
/**
 * Arquivo: wp-content/plugins/tutoread/includes/admin/views/central-settings.php
 * Descrição: Exibe o formulário HTML para:
 *  - configurar “Identificador deste site”;
 *  - exibir “JWT Secret” como readonly (já gerado pelo hook de ativação).
 *
 * Variáveis disponíveis (vindas de render_settings_page()):
 *  - $identifier (string)
 *  - $jwt_secret (string)
 *  - $reg_status (string) → 'success' | 'exists' | 'error' | ''
 */

?>
<div class="wrap">
  <h1><?php esc_html_e( 'Configurações – Central de Identificadores', 'tutoread' ); ?></h1>
  <p><?php esc_html_e( 'Seu site já possui um JWT Secret gerado automaticamente na ativação do plugin. Apenas confirme seu identificador e envie o pedido de registro.', 'tutoread' ); ?></p>

  <?php if ( $reg_status === 'success' ) : ?>
    <div class="notice notice-success is-dismissible">
      <p><?php esc_html_e( 'Pedido de registro enviado com sucesso. Aguarde aprovação na Central.', 'tutoread' ); ?></p>
    </div>
  <?php elseif ( $reg_status === 'exists' ) : ?>
    <div class="notice notice-warning is-dismissible">
      <p><?php esc_html_e( 'Já existe um pedido com este identificador na Central.', 'tutoread' ); ?></p>
    </div>
  <?php elseif ( $reg_status === 'error' ) : ?>
    <div class="notice notice-error is-dismissible">
      <p><?php esc_html_e( 'Falha ao enviar o pedido. Entre em contato com o suporte.', 'tutoread' ); ?></p>
    </div>
  <?php endif; ?>

  <form method="post" action="">
    <?php wp_nonce_field( 'tutoread_central_save', 'tutoread_central_nonce' ); ?>

    <table class="form-table" role="presentation">
      <tbody>
        <!-- Campo “Identificador deste site” -->
        <tr>
          <th scope="row">
            <label for="identifier"><?php esc_html_e( 'Identificador deste site:', 'tutoread' ); ?></label>
          </th>
          <td>
            <input name="identifier" type="text" id="identifier"
                   <input name="identifier" type="text" id="identifier" value="<?php echo esc_attr( $identifier ); ?>" class="regular-text" placeholder="<?php esc_attr_e('Ex: minhaempresa_ead', 'tutoread'); ?>" required>
            <p class="description">
              <?php esc_html_e( 'String única (ex: segi, empresaXYZ). Apenas letras, números, “_” e “-”.', 'tutoread' ); ?>
            </p>
          </td>
        </tr>

        <!-- Campo “JWT Secret” (readonly) -->
        <tr>
          <th scope="row">
            <label for="jwt_secret"><?php esc_html_e( 'JWT Secret (único):', 'tutoread' ); ?></label>
          </th>
          <td>
            <input type="text" id="jwt_secret"
                   value="<?php echo esc_attr( $jwt_secret ); ?>" class="regular-text" placeholder="<?php esc_attr_e('Gerado automaticamente', 'tutoread'); ?>" readonly>
            <button type="button" class="button copy-jwt-secret">
              <?php esc_html_e( 'Copiar', 'tutoread' ); ?>
            </button>
            <p class="description">
              <?php esc_html_e( 'Chave secreta gerada automaticamente na ativação. Use-a no App Android para validar tokens.', 'tutoread' ); ?>
            </p>
          </td>
        </tr>
      </tbody>
    </table>

    <?php submit_button( __( 'Salvar Identificador', 'tutoread' ), 'primary', 'save_settings' ); ?>
  </form>

  <?php
  // Se já houver “Identifier” preenchido e JWT Secret existente, exibir o botão “Enviar Pedido”
  if ( ! empty( $identifier ) && ! empty( $jwt_secret ) ) :
  ?>
    <hr>
    <h2><?php esc_html_e( 'Enviar Pedido de Registro à Central', 'tutoread' ); ?></h2>
    <p><?php esc_html_e( 'Clique no botão abaixo para solicitar o registro do seu site na Central de Identificadores. Aguarde aprovação.', 'tutoread' ); ?></p>

    <form method="post" action="">
      <?php wp_nonce_field( 'tutoread_central_request', 'tutoread_central_nonce' ); ?>
      <input type="hidden" name="submit_request" value="1">
      <?php submit_button( __( 'Enviar Pedido de Registro', 'tutoread' ), 'secondary', 'submit_request' ); ?>
    </form>
  <?php endif; ?>

</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  var btn = document.querySelector('.copy-jwt-secret');
  if (btn) {
    btn.addEventListener('click', function(){
      var input = document.getElementById('jwt_secret');
      input.select();
      document.execCommand('copy');
      btn.textContent = '<?php echo esc_js(__('Copiado!', 'tutoread')); ?>';
      setTimeout(function(){
        btn.textContent = '<?php echo esc_js(__('Copiar', 'tutoread')); ?>';
      }, 2000);
    });
  }
});
</script>
