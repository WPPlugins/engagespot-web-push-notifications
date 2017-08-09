<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}
/**
 * adding admin end css/js
 */
wp_enqueue_style('engagespot_admin_css', plugin_dir_url(dirname(__FILE__)) . '/assets/css/engagespot-admin.css');
?>

<?php
$msg = '';
if (isset($_POST['engagespot_save'])) {
    if (current_user_can('administrator')) {
        if (isset($_REQUEST['engagespot_custom_nonce']) && wp_verify_nonce($_REQUEST['engagespot_custom_nonce'], 'engagespot_config_nonce')) {
            
            update_option('engagespot_site_key', isset($_POST['engagespot_site_key']) ? sanitize_text_field(stripslashes($_POST['engagespot_site_key'])) : '');
            update_option('engagespot_api_key', isset($_POST['engagespot_api_key']) ? sanitize_text_field(stripslashes($_POST['engagespot_api_key'])) : '');
            update_option('engagespot_status', isset($_POST['engagespot_status']) ? sanitize_text_field(stripslashes($_POST['engagespot_status'])) : '');
            $msg = "<div class='success'>Settings saved successfully</div>";
        }
    }
}
?>


<h2><?= __('Engagespot', 'customtracker') ?></h2>
<?= $msg ?>
<div class="cpp_wrapper metabox-holder postbox" style="padding-top: 0px;">
    <h2 class="hndle ui-sortable-handle"><span><?= __('Engagespot API Settings', 'customtracker') ?></span></h2>
    <div class="inside">
        <form name="engagespot_form" action="" method="post">
            <table class="engagespot_wrapper">
                <tbody>
                    <tr>
                        <td colspan="2">
                            Enter your Engagespot Site_Key and API_KEY. You will find it on your Engagespot Dashboard. Don't have an Engagespot account? <a href="https://app.engagespot.co/register">Get one for free now.</a>
                        </td>
                    </tr>
                    
                    <tr>
                        <th>
                            <?= __('Site Key', 'customtracker') ?>
                        </th>
                        <td>
                            <input type="text" required="" name="engagespot_site_key" value="<?= esc_attr(get_option('engagespot_site_key', '')) ?>">
                        </td>
                    </tr>
                     <tr>
                        <th>
                            <?= __('API Key', 'customtracker') ?>
                        </th>
                        <td>
                            <input type="text" required="" name="engagespot_api_key" value="<?= esc_attr(get_option('engagespot_api_key', '')) ?>">
                        </td>
                    </tr>
                    <tr>
                        <th>
                            &nbsp;
                        </th>
                        <td>
                            <input type="checkbox" name="engagespot_status" value="1" <?= (esc_attr(get_option('engagespot_status', '1')) == '1' ? 'checked' : '') ?>> Track Events
                        </td>
                    </tr>
                    <tr>
                        <th>&nbsp;</th>
                        <td>
                            <input type="hidden" name="engagespot_custom_nonce" value="<?php echo wp_create_nonce('engagespot_config_nonce'); ?>">
                            <input type="submit" name="engagespot_save" value="Save" class="button button-primary button-large">
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
    </div>
</div>