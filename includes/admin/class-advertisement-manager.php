<?php
namespace TutorEAD\Admin;

defined('ABSPATH') || exit;

class AdvertisementManager {
    public static function init() {
        add_action('admin_post_add_advertisement', [__CLASS__, 'handle_form_submission']);
        add_action('admin_post_edit_advertisement', [__CLASS__, 'handle_edit_advertisement']);
        add_action('admin_post_delete_advertisement', [__CLASS__, 'handle_delete_advertisement']);
        add_action('admin_enqueue_scripts', [__CLASS__, 'enqueue_scripts']);
        add_action('wp_ajax_track_ad_view', [__CLASS__, 'track_ad_view']);
        add_action('wp_ajax_nopriv_track_ad_view', [__CLASS__, 'track_ad_view']);
        add_action('wp_ajax_track_ad_click', [__CLASS__, 'track_ad_click']);
        add_action('wp_ajax_nopriv_track_ad_click', [__CLASS__, 'track_ad_click']);
    }

    public static function enqueue_scripts($hook) {
        if ($hook !== 'tutor-ead_page_tutor-ead-advertisements') {
            return;
        }
        wp_enqueue_style('cropper-css', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.css');
        wp_enqueue_script('cropper-js', 'https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.5.12/cropper.min.js', [], null, true);
    }

    public static function get_active_advertisement_for_location($location) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tutoread_advertisements';
        $ads = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM $table_name WHERE target_location = %s AND is_active = 1",
            $location
        ));

        if (empty($ads)) {
            return null;
        }

        $weighted_ads = [];
        foreach ($ads as $ad) {
            for ($i = 0; $i < $ad->display_chance; $i++) {
                $weighted_ads[] = $ad;
            }
        }

        if (empty($weighted_ads)) {
            return null;
        }

        $random_key = array_rand($weighted_ads);
        return $weighted_ads[$random_key];
    }

    public static function handle_delete_advertisement() {
        if (!isset($_GET['ad_id']) || !isset($_GET['_wpnonce'])) {
            wp_die('Invalid request.');
        }

        $ad_id = intval($_GET['ad_id']);

        if (!wp_verify_nonce($_GET['_wpnonce'], 'delete_advertisement_' . $ad_id)) {
            wp_die('Invalid nonce.');
        }

        if (!current_user_can('manage_tutor_settings')) {
            wp_die('You do not have permission to perform this action.');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'tutoread_advertisements';

        $wpdb->delete($table_name, ['id' => $ad_id]);

        wp_redirect(admin_url('admin.php?page=tutor-ead-advertisements'));
        exit;
    }

    public static function get_advertisement($id) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'tutoread_advertisements';
        return $wpdb->get_row($wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $id));
    }

    public static function get_advertisements() {
        global $wpdb;
        $ads_table = $wpdb->prefix . 'tutoread_advertisements';
        $stats_table = $wpdb->prefix . 'tutoread_advertisement_stats';
        return $wpdb->get_results("
            SELECT a.*, s.views, s.clicks
            FROM $ads_table a
            LEFT JOIN $stats_table s ON a.id = s.ad_id
            ORDER BY a.date_created DESC
        ");
    }

    public static function track_ad_click() {
        if (!isset($_POST['ad_id']) || !isset($_POST['nonce'])) {
            wp_send_json_error('Missing parameters.');
        }

        if (!wp_verify_nonce($_POST['nonce'], 'track_ad_click_nonce')) {
            wp_send_json_error('Invalid nonce.');
        }

        $ad_id = intval($_POST['ad_id']);

        global $wpdb;
        $ads_table = $wpdb->prefix . 'tutoread_advertisements';
        $stats_table = $wpdb->prefix . 'tutoread_advertisement_stats';

        $ad = $wpdb->get_row($wpdb->prepare("SELECT * FROM $ads_table WHERE id = %d", $ad_id));

        if (!$ad) {
            wp_send_json_error('Advertisement not found.');
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

        wp_send_json_success(['redirect_url' => $ad->link_url]);
    }

    public static function track_ad_view() {
        if (!isset($_POST['ad_id']) || !isset($_POST['nonce'])) {
            wp_send_json_error('Missing parameters.');
        }

        if (!wp_verify_nonce($_POST['nonce'], 'track_ad_view_nonce')) {
            wp_send_json_error('Invalid nonce.');
        }

        $ad_id = intval($_POST['ad_id']);

        global $wpdb;
        $stats_table = $wpdb->prefix . 'tutoread_advertisement_stats';

        $stats = $wpdb->get_row($wpdb->prepare("SELECT * FROM $stats_table WHERE ad_id = %d", $ad_id));

        if ($stats) {
            $wpdb->update(
                $stats_table,
                ['views' => $stats->views + 1],
                ['ad_id' => $ad_id]
            );
        } else {
            $wpdb->insert(
                $stats_table,
                ['ad_id' => $ad_id, 'views' => 1]
            );
        }

        wp_send_json_success();
    }

    public static function handle_edit_advertisement() {
        if (!isset($_POST['edit_advertisement_nonce']) || !wp_verify_nonce($_POST['edit_advertisement_nonce'], 'edit_advertisement_nonce')) {
            wp_die('Invalid nonce.');
        }

        if (!current_user_can('manage_tutor_settings')) {
            wp_die('You do not have permission to perform this action.');
        }

        $ad_id = intval($_POST['ad_id']);
        $data_to_update = [
            'link_url' => sanitize_text_field($_POST['ad_link']),
            'target_location' => sanitize_text_field($_POST['ad_target']),
            'display_chance' => intval($_POST['ad_chance']),
        ];

        if (isset($_POST['ad_image_base64']) && !empty($_POST['ad_image_base64'])) {
            $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $_POST['ad_image_base64']));
            $file_name = 'ad-' . time() . '.png';
            $upload = wp_upload_bits($file_name, null, $data);
            if (!$upload['error']) {
                $data_to_update['image_url'] = $upload['url'];
            } else {
                wp_die('File upload error: ' . $upload['error']);
            }
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'tutoread_advertisements';

        $wpdb->update(
            $table_name,
            $data_to_update,
            ['id' => $ad_id]
        );

        wp_redirect(admin_url('admin.php?page=tutor-ead-advertisements'));
        exit;
    }

    public static function handle_form_submission() {
        if (get_transient('tutor_ead_ad_submission_lock')) {
            wp_die('Aguarde um momento antes de enviar novamente.');
        }

        if (!isset($_POST['add_advertisement_nonce']) || !wp_verify_nonce($_POST['add_advertisement_nonce'], 'add_advertisement_nonce')) {
            wp_die('Invalid nonce.');
        }

        if (!current_user_can('manage_tutor_settings')) {
            wp_die('You do not have permission to perform this action.');
        }

        set_transient('tutor_ead_ad_submission_lock', true, 5); // Lock for 5 seconds

        $image_url = '';
        if (isset($_POST['ad_image_base64']) && !empty($_POST['ad_image_base64'])) {
            $data = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $_POST['ad_image_base64']));
            $file_name = 'ad-' . time() . '.png';
            $upload = wp_upload_bits($file_name, null, $data);
            if (!$upload['error']) {
                $image_url = $upload['url'];
            } else {
                wp_die('File upload error: ' . $upload['error']);
            }
        } else {
            wp_die('No image data received.');
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'tutoread_advertisements';

        $wpdb->insert(
            $table_name,
            [
                'image_url' => $image_url,
                'link_url' => sanitize_text_field($_POST['ad_link']),
                'target_location' => sanitize_text_field($_POST['ad_target']),
                'display_chance' => intval($_POST['ad_chance']),
            ]
        );

        wp_redirect(admin_url('admin.php?page=tutor-ead-advertisements'));
        exit;
    }
}

AdvertisementManager::init();
