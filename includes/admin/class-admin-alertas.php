<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;
$table_name = $wpdb->prefix . 'tutoread_alertas';

// Pega a cor de destaque das opções
$highlight_color = get_option('tutor_ead_highlight_color', '#0073aa');

/*───────────────────────────────────────────────────────────
 1) EXCLUIR AVISO/ALERTA
───────────────────────────────────────────────────────────*/
if ( isset( $_GET['delete'] ) ) {
    $delete_id = intval( $_GET['delete'] );
    $wpdb->delete( $table_name, [ 'id' => $delete_id ] );
    echo "<script>location.href='?page=tutor-ead-alertas';</script>";
    exit;
}

/*───────────────────────────────────────────────────────────
 2) INSERIR NOVO AVISO/ALERTA
───────────────────────────────────────────────────────────*/
if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['salvar_alerta'] ) ) {

    $mensagem       = sanitize_text_field( $_POST['alerta_mensagem'] );
    $tipo           = sanitize_text_field( $_POST['alerta_tipo'] );          // alerta | aviso
    $local_exibicao = sanitize_text_field( $_POST['local_exibicao'] );       // dashboard | aula
    $limite         = isset( $_POST['limite_exibicoes'] )
                        ? max( 1, intval( $_POST['limite_exibicoes'] ) )
                        : null;                                              // só para alerta

    $selected_users   = isset( $_POST['user_ids'] )   ? array_map( 'intval', $_POST['user_ids'] )   : [];
    $selected_courses = isset( $_POST['course_ids'] ) ? array_map( 'intval', $_POST['course_ids'] ) : [];

    $user_ids_str   = implode( ',', $selected_users );
    $course_ids_str = implode( ',', $selected_courses );

    /*──────── Regras exclusivas de AVISO (barra) ────────*/
    if ( $tipo === 'aviso' ) {

        $is_geral = empty( $selected_users ) && empty( $selected_courses );

        // Um único aviso geral por local
        if ( $is_geral ) {
            $count = $wpdb->get_var( $wpdb->prepare(
                "SELECT COUNT(*) FROM $table_name
                 WHERE tipo='aviso'
                   AND local_exibicao=%s
                   AND (user_id='' OR user_id IS NULL)
                   AND (course_id='' OR course_id IS NULL)",
                $local_exibicao
            ) );
            if ( $count ) {
                echo "<div class='tutor-notification-error'>
                          <span class='dashicons dashicons-no'></span>
                          <p>Já existe um aviso geral para <b>$local_exibicao</b>.</p>
                      </div>";
                // Não retorna, deixa continuar para mostrar o formulário
            }
        }

        // Máximo 1 aviso específico por aluno
        if ( ! empty( $selected_users ) ) {
            $has_error = false;
            foreach ( $selected_users as $uid ) {
                $existe = $wpdb->get_var( $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_name
                     WHERE tipo='aviso' AND FIND_IN_SET(%d,user_id)", $uid
                ) );
                if ( $existe ) {
                    echo "<div class='tutor-notification-error'>
                              <span class='dashicons dashicons-no'></span>
                              <p>Usuário ID $uid já possui um aviso ativo.</p>
                          </div>";
                    $has_error = true;
                }
            }
            if ($has_error) {
                // Não retorna, deixa continuar para mostrar o formulário
            }
        }
    }

    /*──────── Validação exclusiva de ALERTA (popup) ──────*/
    if ( $tipo === 'alerta' && $limite === null ) {
        echo "<div class='tutor-notification-error'>
                  <span class='dashicons dashicons-no'></span>
                  <p>Defina o limite de exibições para o alerta.</p>
              </div>";
        // Não retorna, deixa continuar para mostrar o formulário
    } else if ($tipo === 'alerta' || ($tipo === 'aviso' && !isset($count) && !isset($has_error))) {
        /*──────── Inserir registro ────────*/
        $wpdb->insert( $table_name, [
            'mensagem'         => $mensagem,
            'tipo'             => $tipo,
            'local_exibicao'   => $local_exibicao,
            'user_id'          => $user_ids_str,
            'course_id'        => $course_ids_str,
            'limite_exibicoes' => ( $tipo === 'alerta' ? $limite : null )
        ] );
        echo "<script>location.href='?page=tutor-ead-alertas';</script>";
        exit;
    }
}

/*───────────────────────────────────────────────────────────
 3) LISTAGEM
───────────────────────────────────────────────────────────*/
$alertas = $wpdb->get_results( "SELECT * FROM $table_name ORDER BY id DESC" );
?>

<!-- Estilos Modernos -->
<style>
    .tutor-alertas-wrap {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
        background: #f3f4f6;
        margin: -20px;
        padding: 32px;
        min-height: 100vh;
    }
    
    .tutor-alertas-wrap * {
        box-sizing: border-box;
    }
    
    .alertas-header {
        margin-bottom: 32px;
    }
    
    .alertas-title {
        font-size: 32px;
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 8px 0;
    }
    
    .alertas-subtitle {
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
    
    .form-grid {
        display: grid;
        gap: 20px;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    
    .form-group label {
        font-weight: 600;
        color: #374151;
        font-size: 14px;
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
    
    select.form-control {
        cursor: pointer;
    }
    
    select[multiple].form-control {
        height: 120px;
    }
    
    .form-row {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .form-actions {
        margin-top: 24px;
        display: flex;
        gap: 12px;
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
    
    .btn-danger {
        background: #ef4444;
        color: #ffffff;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 500;
        font-size: 13px;
        cursor: pointer;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .btn-danger:hover {
        background: #dc2626;
        text-decoration: none;
        transform: translateY(-1px);
    }
    
    .btn-danger .dashicons {
        font-size: 16px;
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
    
    .badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .badge-alerta {
        background: #fee2e2;
        color: #dc2626;
    }
    
    .badge-aviso {
        background: #fef3c7;
        color: #d97706;
    }
    
    .badge-dashboard {
        background: #e0e7ff;
        color: #4f46e5;
    }
    
    .badge-aula {
        background: #d1fae5;
        color: #059669;
    }
    
    .tag {
        display: inline-block;
        padding: 2px 8px;
        background: #f3f4f6;
        color: #6b7280;
        border-radius: 4px;
        font-size: 12px;
        margin-right: 4px;
    }
    
    .empty-state {
        text-align: center;
        padding: 48px;
        color: #6b7280;
        font-size: 16px;
    }
    
    .empty-state .dashicons {
        font-size: 48px;
        margin-bottom: 16px;
        opacity: 0.3;
    }
    
    .rules-box {
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 20px;
        margin-top: 24px;
    }
    
    .rules-box h4 {
        font-size: 16px;
        font-weight: 600;
        color: #1f2937;
        margin: 0 0 12px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .rules-box .dashicons {
        color: <?php echo $highlight_color; ?>;
        font-size: 20px;
    }
    
    .rules-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }
    
    .rule-section {
        padding: 16px;
        background: #ffffff;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
    }
    
    .rule-section h5 {
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin: 0 0 8px 0;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    
    .rule-section ul {
        margin: 0;
        padding-left: 20px;
        color: #6b7280;
        font-size: 13px;
    }
    
    .rule-section li {
        margin-bottom: 4px;
    }
    
    .tutor-notification-error {
        background: #fee2e2;
        border: 1px solid #fecaca;
        border-radius: 8px;
        padding: 16px;
        margin-bottom: 16px;
        display: flex;
        align-items: center;
        gap: 12px;
        color: #dc2626;
    }
    
    .tutor-notification-error .dashicons {
        font-size: 24px;
        flex-shrink: 0;
    }
    
    .tutor-notification-error p {
        margin: 0;
        font-size: 14px;
    }
</style>

<div class="tutor-alertas-wrap">
    <!-- Header -->
    <div class="alertas-header">
        <h1 class="alertas-title">Gerenciar Alertas e Avisos</h1>
        <p class="alertas-subtitle">Configure alertas pop-up e avisos em barra para alunos e cursos</p>
    </div>

    <!-- Formulário de Novo Alerta/Aviso -->
    <div class="tutor-card">
        <h2 class="card-title">
            <span class="dashicons dashicons-plus-alt"></span>
            Novo Alerta/Aviso
        </h2>
        <form method="post">
            <div class="form-grid">
                <div class="form-group">
                    <label for="alerta_mensagem">Mensagem</label>
                    <input type="text" 
                           id="alerta_mensagem" 
                           name="alerta_mensagem" 
                           class="form-control" 
                           placeholder="Digite a mensagem do alerta ou aviso"
                           required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="alerta_tipo">Tipo</label>
                        <select id="alerta_tipo" name="alerta_tipo" class="form-control">
                            <option value="alerta">🔴 Alerta (Popup obrigatório)</option>
                            <option value="aviso">🟡 Aviso (Barra superior)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="local_exibicao">Local de Exibição</label>
                        <select id="local_exibicao" name="local_exibicao" class="form-control">
                            <option value="dashboard">📌 Dashboard</option>
                            <option value="aula">🎥 Aula</option>
                        </select>
                    </div>
                    
                    <div class="form-group" id="wrap_limite">
                        <label for="limite_exibicoes">Limite de Exibições</label>
                        <input type="number" 
                               min="1" 
                               id="limite_exibicoes" 
                               name="limite_exibicoes" 
                               class="form-control" 
                               value="1"
                               placeholder="Número de vezes">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="user_ids">Alunos (opcional)</label>
                        <select id="user_ids" name="user_ids[]" class="form-control" multiple>
                            <?php
                            foreach ( get_users( ['role'=>'tutor_aluno'] ) as $u ) {
                                echo '<option value="'.$u->ID.'">'.
                                     esc_html( $u->display_name ).' ('.$u->user_email.')</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="course_ids">Cursos (opcional)</label>
                        <select id="course_ids" name="course_ids[]" class="form-control" multiple>
                            <?php
                            foreach ( $wpdb->get_results("SELECT id,title FROM {$wpdb->prefix}tutoread_courses") as $c ) {
                                echo '<option value="'.$c->id.'">'.esc_html($c->title).'</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn-primary" name="salvar_alerta">
                    <span class="dashicons dashicons-saved"></span>
                    Salvar Alerta/Aviso
                </button>
            </div>
        </form>
    </div>

    <!-- Tabela de Registros -->
    <div class="tutor-card">
        <h2 class="card-title">
            <span class="dashicons dashicons-list-view"></span>
            Alertas e Avisos Ativos
        </h2>
        
        <?php if ( !empty($alertas) && is_array($alertas) ) : ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:25%">Mensagem</th>
                        <th style="width:10%">Tipo</th>
                        <th style="width:10%">Local</th>
                        <th style="width:15%">Alunos</th>
                        <th style="width:15%">Cursos</th>
                        <th style="width:10%">Limite</th>
                        <th style="width:15%">Ações</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ( $alertas as $a ) : ?>
                    <tr>
                        <td><?php echo esc_html( $a->mensagem ); ?></td>
                        <td>
                            <?php if ($a->tipo === 'alerta') : ?>
                                <span class="badge badge-alerta">
                                    <span style="color: #dc2626;">●</span> Alerta
                                </span>
                            <?php else : ?>
                                <span class="badge badge-aviso">
                                    <span style="color: #d97706;">●</span> Aviso
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($a->local_exibicao === 'dashboard') : ?>
                                <span class="badge badge-dashboard">
                                    <span class="dashicons dashicons-dashboard" style="font-size: 12px;"></span> Dashboard
                                </span>
                            <?php else : ?>
                                <span class="badge badge-aula">
                                    <span class="dashicons dashicons-format-video" style="font-size: 12px;"></span> Aula
                                </span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($a->user_id) : ?>
                                <?php 
                                $user_ids = explode(',', $a->user_id);
                                foreach ($user_ids as $uid) : ?>
                                    <span class="tag">#<?php echo $uid; ?></span>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <em style="color: #6b7280;">Todos</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($a->course_id) : ?>
                                <?php 
                                $course_ids = explode(',', $a->course_id);
                                foreach ($course_ids as $cid) : ?>
                                    <span class="tag">#<?php echo $cid; ?></span>
                                <?php endforeach; ?>
                            <?php else : ?>
                                <em style="color: #6b7280;">—</em>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($a->tipo === 'alerta') : ?>
                                <strong style="color: #1f2937;"><?php echo intval($a->limite_exibicoes); ?>x</strong>
                            <?php else : ?>
                                <span style="color: #6b7280;">—</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a class="btn-danger" 
                               href="?page=tutor-ead-alertas&delete=<?php echo $a->id;?>"
                               onclick="return confirm('Tem certeza que deseja excluir este item?');">
                               <span class="dashicons dashicons-trash"></span>
                               Excluir
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        <?php else : ?>
            <div class="empty-state">
                <span class="dashicons dashicons-info-outline"></span>
                <p>Nenhum alerta ou aviso cadastrado ainda.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Regras e Instruções -->
    <div class="tutor-card">
        <div class="rules-box">
            <h4>
                <span class="dashicons dashicons-info"></span>
                Regras e Instruções
            </h4>
            <div class="rules-grid">
                <div class="rule-section">
                    <h5>
                        <span style="color: #d97706;">●</span> Avisos (Barra Superior)
                    </h5>
                    <ul>
                        <li>Máximo 1 aviso geral por local</li>
                        <li>Máximo 1 aviso específico por aluno</li>
                        <li>Exibidos como barra no topo da página</li>
                        <li>Podem ser fechados pelo usuário</li>
                    </ul>
                </div>
                <div class="rule-section">
                    <h5>
                        <span style="color: #dc2626;">●</span> Alertas (Pop-up)
                    </h5>
                    <ul>
                        <li>Defina o limite de exibições</li>
                        <li>Obrigatório confirmar leitura</li>
                        <li>Não aparece após atingir o limite</li>
                        <li>Rastreia visualizações por usuário</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Toggle campo Limite conforme tipo
document.addEventListener('DOMContentLoaded', () => {
    const tipo = document.getElementById('alerta_tipo');
    const wrap = document.getElementById('wrap_limite');
    
    const sync = () => { 
        if (tipo.value !== 'alerta') {
            wrap.style.display = 'none';
        } else {
            wrap.style.display = 'block';
        }
    };
    
    tipo.addEventListener('change', sync);
    sync();
});
</script>