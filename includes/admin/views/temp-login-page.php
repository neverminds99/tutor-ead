<?php
/**
 * View – Logins Temporários
 *
 * Variáveis disponíveis (vindas de TemporaryLogin::render_page()):
 *   $notice_link        – string (link recém‑gerado ou vazio)
 *   $selected_student   – int    (pré‑seleção do aluno)
 *   $students           – array  (WP_User objects)
 *   $tokens             – array  (resultados da query)
 */

defined( 'ABSPATH' ) || exit;

// Pega a cor de destaque das opções
$highlight_color = get_option('tutor_ead_highlight_color', '#0073aa');
?>

<style>
    .tutor-temp-login-wrap {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        background: #f3f4f6;
        margin: -20px;
        padding: 32px;
        min-height: 100vh;
    }
    
    .tutor-temp-login-wrap * {
        box-sizing: border-box;
    }
    
    .temp-login-header {
        margin-bottom: 32px;
    }
    
    .temp-login-title {
        font-size: 32px;
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 8px 0;
    }
    
    .temp-login-subtitle {
        color: #6b7280;
        font-size: 16px;
        margin: 0;
    }
    
    .tutor-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05);
        margin-bottom: 24px;
    }
    
    .card-title {
        font-size: 20px;
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 20px 0;
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
    .card-title .dashicons {
        font-size: 24px;
        color: <?php echo $highlight_color; ?>;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        display: block;
        font-weight: 600;
        color: #374151;
        font-size: 14px;
        margin-bottom: 8px;
    }
    
    .form-control {
        width: 100%;
        padding: 10px 12px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        font-size: 14px;
        transition: all 0.2s ease;
        background: #ffffff;
    }
    
    .form-control:focus {
        outline: none;
        border-color: <?php echo $highlight_color; ?>;
        box-shadow: 0 0 0 3px <?php echo $highlight_color; ?>20;
    }
    
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    
    .btn-primary {
        background: <?php echo $highlight_color; ?>;
        color: #ffffff;
        border: none;
        padding: 12px 24px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    
    .btn-primary:hover {
        background: <?php echo $highlight_color; ?>e6;
        transform: translateY(-1px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }
    
    .btn-primary .dashicons {
        font-size: 18px;
    }
    
    .success-box {
        background: #d1fae5;
        border: 1px solid #a7f3d0;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 24px;
        display: flex;
        align-items: flex-start;
        gap: 16px;
    }
    
    .success-box .dashicons {
        color: #059669;
        font-size: 24px;
        flex-shrink: 0;
    }
    
    .success-content {
        flex: 1;
    }
    
    .success-title {
        font-weight: 600;
        color: #059669;
        margin: 0 0 8px 0;
        font-size: 16px;
    }
    
    .generated-link {
        background: #ffffff;
        border: 1px solid #a7f3d0;
        border-radius: 6px;
        padding: 12px 16px;
        font-family: 'Courier New', monospace;
        font-size: 14px;
        color: #047857;
        word-break: break-all;
        margin: 8px 0;
    }
    
    .copy-button {
        background: #ffffff;
        color: #059669;
        border: 1px solid #a7f3d0;
        padding: 8px 16px;
        border-radius: 6px;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s ease;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .copy-button:hover {
        background: #d1fae5;
    }
    
    .data-table {
        width: 100%;
        border-collapse: collapse;
        margin: 0;
    }
    
    .data-table thead {
        background: #f9fafb;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .data-table th {
        text-align: left;
        padding: 12px 16px;
        font-weight: 600;
        color: #374151;
        font-size: 14px;
    }
    
    .data-table tbody tr {
        border-bottom: 1px solid #f3f4f6;
        transition: all 0.1s ease;
    }
    
    .data-table tbody tr:hover {
        background: #f9fafb;
    }
    
    .data-table td {
        padding: 16px;
        color: #1f2937;
        font-size: 14px;
        vertical-align: top;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .status-active {
        background: #d1fae5;
        color: #059669;
    }
    
    .status-used {
        background: #dbeafe;
        color: #3730a3;
    }
    
    .status-expired {
        background: #f3f4f6;
        color: #6b7280;
    }
    
    .link-cell {
        font-family: 'Courier New', monospace;
        font-size: 13px;
    }
    
    .link-cell a {
        color: <?php echo $highlight_color; ?>;
        text-decoration: none;
        word-break: break-all;
    }
    
    .link-cell a:hover {
        text-decoration: underline;
    }
    
    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: #6b7280;
    }
    
    .empty-state .dashicons {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.3;
    }
    
    .empty-state-title {
        font-size: 18px;
        font-weight: 600;
        color: #1f2937;
        margin-bottom: 8px;
    }
    
    .empty-state-text {
        font-size: 14px;
        color: #6b7280;
    }
    
    .help-text {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 20px;
        padding: 12px;
        background: #f9fafb;
        border-radius: 8px;
        font-size: 14px;
        color: #6b7280;
    }
    
    .help-text .dashicons {
        color: <?php echo $highlight_color; ?>;
        font-size: 20px;
    }
    
    @media (max-width: 768px) {
        .tutor-temp-login-wrap {
            padding: 16px;
        }
        
        .form-grid {
            grid-template-columns: 1fr;
        }
        
        .data-table {
            display: block;
            overflow-x: auto;
        }
    }
</style>

<div class="wrap tutor-temp-login-wrap">
    <!-- Header -->
    <div class="temp-login-header">
        <h1 class="temp-login-title"><?php esc_html_e( 'Links de Login Temporário', 'tutor-ead' ); ?></h1>
        <p class="temp-login-subtitle"><?php esc_html_e( 'Crie links de acesso rápido para os alunos acessarem o dashboard', 'tutor-ead' ); ?></p>
    </div>
    
    <?php if ( $notice_link ) : ?>
        <!-- Success Message -->
        <div class="success-box">
            <span class="dashicons dashicons-yes"></span>
            <div class="success-content">
                <h3 class="success-title"><?php esc_html_e( 'Link temporário criado com sucesso!', 'tutor-ead' ); ?></h3>
                <div class="generated-link"><?php echo esc_html( $notice_link ); ?></div>
                <button type="button" class="copy-button" onclick="copyToClipboard('<?php echo esc_js( $notice_link ); ?>')">
                    <span class="dashicons dashicons-clipboard"></span>
                    <?php esc_html_e( 'Copiar Link', 'tutor-ead' ); ?>
                </button>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Generate New Link -->
    <div class="tutor-card">
        <h2 class="card-title">
            <span class="dashicons dashicons-admin-network"></span>
            <?php esc_html_e( 'Gerar Novo Link', 'tutor-ead' ); ?>
        </h2>
        
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
            <input type="hidden" name="action" value="tutor_ead_generate_temp_login">
            <?php wp_nonce_field( 'temp_login_nonce_action', 'temp_login_nonce_field' ); ?>
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="student_id" class="form-label"><?php esc_html_e( 'Selecione o Aluno', 'tutor-ead' ); ?></label>
                    <select name="student_id" id="student_id" class="form-control" required>
                        <option value=""><?php esc_html_e( 'Escolha um aluno...', 'tutor-ead' ); ?></option>
                        <?php foreach ( $students as $student ) : ?>
                            <option value="<?php echo esc_attr( $student->ID ); ?>" <?php selected( $student->ID, $selected_student ); ?>>
                                <?php echo esc_html( $student->display_name . ' (' . $student->user_email . ')' ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="duration" class="form-label"><?php esc_html_e( 'Tempo de Validade', 'tutor-ead' ); ?></label>
                    <select name="duration" id="duration" class="form-control" required>
                        <option value="15"><?php esc_html_e( '15 minutos', 'tutor-ead' ); ?></option>
                        <option value="30" selected><?php esc_html_e( '30 minutos', 'tutor-ead' ); ?></option>
                        <option value="60"><?php esc_html_e( '1 hora', 'tutor-ead' ); ?></option>
                        <option value="120"><?php esc_html_e( '2 horas', 'tutor-ead' ); ?></option>
                        <option value="1440"><?php esc_html_e( '24 horas', 'tutor-ead' ); ?></option>
                    </select>
                </div>
            </div>
            
            <div class="help-text">
                <span class="dashicons dashicons-info"></span>
                <span><?php esc_html_e( 'O link permitirá acesso direto ao dashboard do aluno sem necessidade de senha.', 'tutor-ead' ); ?></span>
            </div>
            
            <p style="margin-top: 24px;">
                <button type="submit" name="generate_temp_login" class="btn-primary">
                    <span class="dashicons dashicons-admin-links"></span>
                    <?php esc_html_e( 'Gerar Link', 'tutor-ead' ); ?>
                </button>
            </p>
        </form>
    </div>
    
    <!-- Existing Links -->
    <div class="tutor-card">
        <h2 class="card-title">
            <span class="dashicons dashicons-backup"></span>
            <?php esc_html_e( 'Links Criados', 'tutor-ead' ); ?>
        </h2>
        
        <?php if ( $tokens && count($tokens) > 0 ) : ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Aluno', 'tutor-ead' ); ?></th>
                        <th><?php esc_html_e( 'Link', 'tutor-ead' ); ?></th>
                        <th><?php esc_html_e( 'Validade', 'tutor-ead' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'tutor-ead' ); ?></th>
                        <th><?php esc_html_e( 'Usado em', 'tutor-ead' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $now = current_time( 'timestamp' );
                    foreach ( $tokens as $token ) :
                        $expired = $now > strtotime( $token->expiration );
                        $user_info = get_userdata( $token->user_id );
                        $link = add_query_arg(
                            [
                                'temp_login_token' => $token->token,
                                'user_id'          => $token->user_id,
                            ],
                            site_url()
                        );
                        
                        // Determine status
                        if ( $token->status === 'used' ) {
                            $status_class = 'status-used';
                            $status_text = __( 'Usado', 'tutor-ead' );
                        } elseif ( $expired ) {
                            $status_class = 'status-expired';
                            $status_text = __( 'Expirado', 'tutor-ead' );
                        } else {
                            $status_class = 'status-active';
                            $status_text = __( 'Ativo', 'tutor-ead' );
                        }
                        ?>
                        <tr>
                            <td>
                                <?php if ( $user_info ) : ?>
                                    <strong><?php echo esc_html( $user_info->display_name ); ?></strong><br>
                                    <span style="color: #6b7280; font-size: 13px;"><?php echo esc_html( $user_info->user_email ); ?></span>
                                <?php else : ?>
                                    <span style="color: #6b7280;"><?php esc_html_e( 'Usuário não encontrado', 'tutor-ead' ); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="link-cell">
                                <a href="<?php echo esc_url( $link ); ?>" target="_blank"><?php echo esc_html( substr($token->token, 0, 20) . '...' ); ?></a>
                            </td>
                            <td>
                                <?php
                                $expiration_date = date_i18n( get_option('date_format') . ' ' . get_option('time_format'), strtotime($token->expiration) );
                                echo esc_html( $expiration_date );
                                ?>
                            </td>
                            <td>
                                <span class="status-badge <?php echo esc_attr( $status_class ); ?>">
                                    <?php echo esc_html( $status_text ); ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                if ( $token->login_at ) {
                                    $login_date = date_i18n( get_option('date_format') . ' ' . get_option('time_format'), strtotime($token->login_at) );
                                    echo esc_html( $login_date );
                                } else {
                                    echo '<span style="color: #6b7280;">—</span>';
                                }
                                ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="empty-state">
                <span class="dashicons dashicons-admin-links"></span>
                <h3 class="empty-state-title"><?php esc_html_e( 'Nenhum link criado ainda', 'tutor-ead' ); ?></h3>
                <p class="empty-state-text"><?php esc_html_e( 'Crie links temporários para permitir acesso rápido aos alunos.', 'tutor-ead' ); ?></p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function copyToClipboard(text) {
    // Create temporary input element
    const temp = document.createElement('input');
    temp.value = text;
    document.body.appendChild(temp);
    temp.select();
    document.execCommand('copy');
    document.body.removeChild(temp);
    
    // Update button text temporarily
    const button = event.target.closest('.copy-button');
    const originalText = button.innerHTML;
    button.innerHTML = '<span class="dashicons dashicons-yes"></span> Copiado!';
    setTimeout(() => {
        button.innerHTML = originalText;
    }, 2000);
}
</script>