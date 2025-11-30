<?php
/**
 * Partial: Paginação da listagem
 *
 * Variáveis esperadas:
 * $current_page (int)  – página atual
 * $total_pages (int)   – quantas páginas existem
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( $total_pages > 1 ) : ?>
    <div class="tablenav"><div class="tablenav-pages">
        <?php echo paginate_links( [
            'base'      => add_query_arg( 'paged', '%#%' ),
            'total'     => $total_pages,
            'current'   => $current_page,
            'prev_text' => '«',
            'next_text' => '»',
        ] ); ?>
    </div></div>
<?php endif; ?>
