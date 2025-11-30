<?php
require_once('../../../wp-load.php');

if (!isset($_GET['ad_id'])) {
    wp_die('Invalid ad ID.');
}

$ad_id = intval($_GET['ad_id']);

global $wpdb;
$ads_table = $wpdb->prefix . 'tutoread_advertisements';
$stats_table = $wpdb->prefix . 'tutoread_advertisement_stats';

$ad = $wpdb->get_row($wpdb->prepare("SELECT * FROM $ads_table WHERE id = %d", $ad_id));

if (!$ad) {
    wp_die('Advertisement not found.');
}

$stats = $wpdb->get_row($wpdb->prepare("SELECT * FROM $stats_table WHERE ad_id = %d", $ad_id));

if ($stats) {
    $wpdb->update(
        $stats_table,
        ['clicks' => $stats->clicks + 1],
        ['ad_id' => $ad_id]
    );
} else {
    $wpdb->insert(
        $stats_table,
        ['ad_id' => $ad_id, 'clicks' => 1]
    );
}

wp_redirect($ad->link_url);
exit;
