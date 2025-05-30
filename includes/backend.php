<?php
if (!defined('ABSPATH')) exit;

add_action('admin_menu', 'wsn_admin_menu');
function wsn_admin_menu() {
    add_menu_page('Stock Notifier', 'Stock Notifier', 'manage_options', 'wsn_panel', 'wsn_admin_page', 'dashicons-email', 56);
    add_submenu_page('wsn_panel', 'Suscripciones', 'Suscripciones', 'manage_options', 'wsn_subscribers', 'wsn_subscribers_page');
}

function wsn_admin_page() {
    // Guardar configuración general
    if (isset($_POST['wsn_enabled'])) {
        update_option('wsn_enabled', $_POST['wsn_enabled']);
        echo '<div class="updated"><p>Configuración guardada.</p></div>';
    }
    // Guardar textos personalizados
    if (isset($_POST['wsn_texts_nonce']) && wp_verify_nonce($_POST['wsn_texts_nonce'], 'wsn_save_texts')) {
        update_option('wsn_frontend_text_title', sanitize_text_field($_POST['wsn_frontend_text_title']));
        update_option('wsn_frontend_text_placeholder', sanitize_text_field($_POST['wsn_frontend_text_placeholder']));
        update_option('wsn_frontend_text_button', sanitize_text_field($_POST['wsn_frontend_text_button']));
        update_option('wsn_frontend_text_success', sanitize_text_field($_POST['wsn_frontend_text_success']));
        update_option('wsn_frontend_text_error', sanitize_text_field($_POST['wsn_frontend_text_error']));
        echo '<div class="updated"><p>Textos guardados.</p></div>';
    }

    $enabled = wsn_is_enabled();
    // Obtener textos actuales o valores por defecto
    $text_title = get_option('wsn_frontend_text_title', 'Do you want to be notified when this product is back in stock?');
    $text_placeholder = get_option('wsn_frontend_text_placeholder', 'Enter your email');
    $text_button = get_option('wsn_frontend_text_button', 'Notify me');
    $text_success = get_option('wsn_frontend_text_success', 'We will notify you when the product is available!');
    $text_error = get_option('wsn_frontend_text_error', 'An error occurred. Please try again.');

    echo '<div class="wrap"><h1>Stock Notifier Settings</h1>';
    echo '<h2 class="nav-tab-wrapper">
        <a href="#wsn-tab-ajustes" class="nav-tab nav-tab-active" onclick="wsnShowTab(event, \'wsn-tab-ajustes\')">General Settings</a>
        <a href="#wsn-tab-textos" class="nav-tab" onclick="wsnShowTab(event, \'wsn-tab-textos\')">Form Texts</a>
        <a href="#wsn-tab-suscripciones" class="nav-tab" onclick="wsnShowTab(event, \'wsn-tab-suscripciones\')">Subscriptions</a>
    </h2>';
    echo '<div id="wsn-tab-ajustes" class="wsn-tab-content" style="display:block;">';
    echo '<form method="post">
            <table class="form-table">
                <tr><th scope="row">Enable notification system</th>
                    <td><select name="wsn_enabled">
                        <option value="yes"' . selected($enabled, true, false) . '>Yes</option>
                        <option value="no"' . selected($enabled, false, false) . '>No</option>
                    </select></td>
                </tr>
            </table>
            <p><input type="submit" class="button-primary" value="Save changes"></p>
        </form>';
    echo '</div>';
    echo '<div id="wsn-tab-textos" class="wsn-tab-content" style="display:none;">';
    echo '<form method="post">
            ' . wp_nonce_field('wsn_save_texts', 'wsn_texts_nonce', true, false) . '
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="wsn_frontend_text_title">Title</label></th>
                    <td><input type="text" name="wsn_frontend_text_title" id="wsn_frontend_text_title" value="' . esc_attr($text_title) . '" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="wsn_frontend_text_placeholder">Email placeholder</label></th>
                    <td><input type="text" name="wsn_frontend_text_placeholder" id="wsn_frontend_text_placeholder" value="' . esc_attr($text_placeholder) . '" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="wsn_frontend_text_button">Button text</label></th>
                    <td><input type="text" name="wsn_frontend_text_button" id="wsn_frontend_text_button" value="' . esc_attr($text_button) . '" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="wsn_frontend_text_success">Success message</label></th>
                    <td><input type="text" name="wsn_frontend_text_success" id="wsn_frontend_text_success" value="' . esc_attr($text_success) . '" class="regular-text"></td>
                </tr>
                <tr>
                    <th scope="row"><label for="wsn_frontend_text_error">Error message</label></th>
                    <td><input type="text" name="wsn_frontend_text_error" id="wsn_frontend_text_error" value="' . esc_attr($text_error) . '" class="regular-text"></td>
                </tr>
            </table>
            <p><input type="submit" class="button-primary" value="Save texts"></p>
        </form>';
    echo '</div>';
    // Nueva pestaña de suscripciones
    echo '<div id="wsn-tab-suscripciones" class="wsn-tab-content" style="display:none;">';
    // Procesar eliminación de suscripciones desde esta pestaña
    if (isset($_POST['wsn_clear_emails']) && isset($_POST['product_id'])) {
        delete_post_meta(intval($_POST['product_id']), '_wsn_emails');
        delete_post_meta(intval($_POST['product_id']), '_wsn_timestamps');
        delete_post_meta(intval($_POST['product_id']), '_wsn_sent');
        echo '<div class="updated"><p>Subscriptions deleted for the selected product.</p></div>';
    }
    $args = ['post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish'];
    $products = get_posts($args);
    echo '<form method="post" action="">';
    echo '<p><input type="submit" name="wsn_export_csv" class="button-secondary" value="Export all to CSV"></p>';
    echo '</form>';
    echo '<table class="widefat"><thead><tr><th>Product</th><th>Emails</th><th>Subscription date</th><th>Status</th><th>Actions</th></tr></thead><tbody>';
    foreach ($products as $product) {
        $emails = get_post_meta($product->ID, '_wsn_emails', true);
        $timestamps = get_post_meta($product->ID, '_wsn_timestamps', true);
        $sent = get_post_meta($product->ID, '_wsn_sent', true);
        $sent = is_array($sent) ? $sent : [];
        if ($emails && is_array($emails) && count($emails) > 0) {
            echo '<tr><td><a href="' . get_edit_post_link($product->ID) . '">' . esc_html($product->post_title) . '</a></td><td>';
            foreach ($emails as $email) {
                echo esc_html($email) . '<br>';
            }
            echo '</td><td>';
            foreach ($emails as $email) {
                echo esc_html(isset($timestamps[$email]) ? $timestamps[$email] : '-') . '<br>';
            }
            echo '</td><td>';
            foreach ($emails as $email) {
                if (isset($sent[$email])) {
                    echo '<span style="color:green;">Sent (' . esc_html($sent[$email]) . ')</span><br>';
                } else {
                    echo '<span style="color:orange;">Pending</span><br>';
                }
            }
            echo '</td><td>';
            echo '<form method="post" action="">';
            echo '<input type="hidden" name="product_id" value="' . $product->ID . '">';
            echo '<input type="submit" name="wsn_clear_emails" class="button" value="Delete">';
            echo '</form>';
            echo '</td></tr>';
        }
    }
    echo '</tbody></table>';
    echo '</div>';
    echo '<script>
    function wsnShowTab(e, tabId) {
        e.preventDefault();
        var tabs = document.querySelectorAll(".wsn-tab-content");
        var navs = document.querySelectorAll(".nav-tab");
        tabs.forEach(function(tab) { tab.style.display = "none"; });
        navs.forEach(function(nav) { nav.classList.remove("nav-tab-active"); });
        document.getElementById(tabId).style.display = "block";
        e.target.classList.add("nav-tab-active");
    }
    </script>';
    echo '<div style="margin-top:40px;text-align:center;color:#888;font-size:14px;">'
        . '<strong>Stock Notifier</strong> &mdash; made with <span style="color:#e25555;">&#10084;&#65039;</span> by <a href="https://github.com/voodoopablo" target="_blank" style="color:#888;text-decoration:underline;">voodoopablo</a>'
        . '</div>';
    echo '</div>';
}

function wsn_subscribers_page() {
    echo '<div class="wrap"><h1>Emails pendientes de notificación</h1>';

    if (isset($_POST['wsn_export_csv'])) {
        wsn_export_to_csv();
        return;
    }
    if (isset($_POST['wsn_clear_emails']) && isset($_POST['product_id'])) {
        delete_post_meta(intval($_POST['product_id']), '_wsn_emails');
        delete_post_meta(intval($_POST['product_id']), '_wsn_timestamps');
        delete_post_meta(intval($_POST['product_id']), '_wsn_sent'); // Borrar también el meta de enviados
        echo '<div class="updated"><p>Subscriptions deleted for the selected product.</p></div>';
    }

    $args = ['post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish'];
    $products = get_posts($args);
    echo '<form method="post"><p><input type="submit" name="wsn_export_csv" class="button-secondary" value="Exportar todo a CSV"></p></form>';
    echo '<table class="widefat"><thead><tr><th>Producto</th><th>Emails</th><th>Fecha de suscripción</th><th>Acciones</th></tr></thead><tbody>';
    foreach ($products as $product) {
        $emails = get_post_meta($product->ID, '_wsn_emails', true);
        $timestamps = get_post_meta($product->ID, '_wsn_timestamps', true);
        if ($emails && is_array($emails) && count($emails) > 0) {
            echo '<tr><td><a href="' . get_edit_post_link($product->ID) . '">' . esc_html($product->post_title) . '</a></td><td>';
            foreach ($emails as $email) {
                echo esc_html($email) . '<br>';
            }
            echo '</td><td>';
            foreach ($emails as $email) {
                echo esc_html(isset($timestamps[$email]) ? $timestamps[$email] : '-') . '<br>';
            }
            echo '</td><td><form method="post" style="display:inline;"><input type="hidden" name="product_id" value="' . $product->ID . '"><input type="submit" name="wsn_clear_emails" class="button" value="Eliminar"></form></td></tr>';
        }
    }
    echo '</tbody></table>';
    echo '<div style="margin-top:40px;text-align:center;color:#888;font-size:14px;">'
        . '<strong>Stock Notifier</strong> &mdash; made with <span style="color:#e25555;">&#10084;&#65039;</span> by <a href="https://github.com/voodoopablo" target="_blank" style="color:#888;text-decoration:underline;">voodoopablo</a>'
        . '</div>';
    echo '</div>';
}

function wsn_export_to_csv() {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=stock_notifier_emails.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Producto', 'Email', 'Fecha de suscripción']);

    $args = ['post_type' => 'product', 'posts_per_page' => -1, 'post_status' => 'publish'];
    $products = get_posts($args);
    foreach ($products as $product) {
        $emails = get_post_meta($product->ID, '_wsn_emails', true);
        $timestamps = get_post_meta($product->ID, '_wsn_timestamps', true);
        if ($emails && is_array($emails)) {
            foreach ($emails as $email) {
                $date = isset($timestamps[$email]) ? $timestamps[$email] : '';
                fputcsv($output, [$product->post_title, $email, $date]);
            }
        }
    }
    fclose($output);
    exit;
}

add_filter('plugin_action_links_' . plugin_basename(dirname(__DIR__) . '/../woo-stock-notifier.php'), 'wsn_settings_link');
function wsn_settings_link($links) {
    $settings_link = '<a href="' . admin_url('admin.php?page=wsn_panel') . '">Settings</a>';
    array_unshift($links, $settings_link);
    return $links;
}
