<?php
/*
Plugin Name: User ID Verifivation
Plugin URI: https://www.iranicode.com
Description:  احراز هویت با کارت شناسایی به ساده ترین روش ممکن 
Author: IraniCode Team
Version: 1.0.0
Author URI: https://www.iranicode.com
*/
if (!defined('ABSPATH')) {
    exit; // دسترسی مستقیم به فایل ممنوع
}

// ایجاد پوشه امن برای ذخیره عکس‌ها
define('UIV_UPLOAD_DIR', WP_CONTENT_DIR . '/uploads/user-id-verification/');

if (!file_exists(UIV_UPLOAD_DIR)) {
    wp_mkdir_p(UIV_UPLOAD_DIR);
}

// افزودن شورت‌کد برای فرم آپلود عکس
function uiv_upload_form_shortcode() {
    if (!is_user_logged_in()) {
        return '<p>برای آپلود عکس کارت شناسایی، باید وارد حساب کاربری خود شوید.</p>';
    }

    $user_id = get_current_user_id();
    $verification_status = get_user_meta($user_id, 'user_verification_status', true);
    $status_message = '';
    $image_url = get_user_meta($user_id, 'user_identification', true);

    // نمایش وضعیت احراز هویت
    if ($verification_status === 'verified') {
        $status_message = '<p style="color: green;">احراز هویت شما تأیید شده است.</p>';
        if ($image_url) {
            $status_message .= '<p>عکس کارت شناسایی تأیید شده: <a href="' . esc_url($image_url) . '" target="_blank">مشاهده عکس</a></p>';
        }
        return $status_message; // اگر تأیید شده باشد، فرم آپلود نمایش داده نمی‌شود.
    } elseif ($verification_status === 'rejected') {
        $status_message = '<p style="color: red;">احراز هویت شما رد شده است. لطفاً عکس جدیدی آپلود کنید.</p>';
    } else {
        $status_message = '<p style="color: orange;">وضعیت احراز هویت شما: در انتظار بررسی.</p>';
    }

    ob_start();
    ?>
    <form method="post" enctype="multipart/form-data">
        <p>
            <label for="user_identification">عکس کارت شناسایی (فرمت‌های مجاز: JPG, JPEG, PNG, GIF)<span class="required">*</span></label>
            <input type="file" name="user_identification" id="user_identification" accept=".jpg,.jpeg,.png,.gif" required>
        </p>
        <p>
            <input type="submit" name="submit_id_verification" value="آپلود عکس">
        </p>
    </form>
    <?php
    echo $status_message;

    // پردازش فرم آپلود
    if (isset($_POST['submit_id_verification'])) {
        if (!empty($_FILES['user_identification']['name'])) {
            $file = $_FILES['user_identification'];
            $allowed_types = array('image/jpeg', 'image/png', 'image/gif');
            $file_type = wp_check_filetype($file['name']);

            // بررسی فرمت فایل
            if (!in_array($file_type['type'], $allowed_types)) {
                echo '<p style="color: red;">فقط فایل‌های تصویری با فرمت JPG, JPEG, PNG یا GIF مجاز هستند.</p>';
            } else {
                $user = get_userdata($user_id);
                $email = sanitize_file_name($user->user_email); // ایمیل کاربر
                $file_name = sanitize_file_name($email . '-' . basename($file['name'])); // نام فایل

                // مسیر ذخیره‌سازی فایل
                $upload_dir = WP_CONTENT_DIR . '/uploads/user-id-verification/';
                if (!file_exists($upload_dir)) {
                    wp_mkdir_p($upload_dir); // ایجاد پوشه اگر وجود نداشته باشد
                }

                // ذخیره‌سازی فایل
                $file_path = $upload_dir . $file_name;
                if (move_uploaded_file($file['tmp_name'], $file_path)) {
                    // تولید URL فایل
                    $file_url = content_url('/uploads/user-id-verification/' . $file_name);

                    // ذخیره URL فایل در متادیتای کاربر
                    update_user_meta($user_id, 'user_identification', $file_url);
                    update_user_meta($user_id, 'user_verification_status', 'pending'); // وضعیت پیش‌فرض: در انتظار بررسی

                    echo '<p style="color: green;">عکس کارت شناسایی با موفقیت آپلود شد و در انتظار بررسی است.</p>';
                } else {
                    echo '<p style="color: red;">خطا در آپلود فایل. لطفاً دوباره تلاش کنید.</p>';
                }
            }
        } else {
            echo '<p style="color: red;">لطفاً یک فایل انتخاب کنید.</p>';
        }
    }

    return ob_get_clean();
}
add_shortcode('id_verification_form', 'uiv_upload_form_shortcode');

// افزودن صفحه مدیریت برای مشاهده عکس‌ها و تأیید/رد احراز هویت
function uiv_add_admin_page() {
    add_menu_page(
        'احراز هویت کاربران',
        'احراز هویت',
        'manage_options',
        'user-id-verification',
        'uiv_admin_page_content',
        'dashicons-id',
        6
    );
}
add_action('admin_menu', 'uiv_add_admin_page');

// محتوای صفحه مدیریت
function uiv_admin_page_content() {
    if (!current_user_can('manage_options')) {
        return;
    }

    // پردازش تأیید یا رد احراز هویت
    if (isset($_GET['action']) && isset($_GET['user_id'])) {
        $user_id = intval($_GET['user_id']);
        if ($_GET['action'] === 'verify' && wp_verify_nonce($_GET['_wpnonce'], 'verify_user_' . $user_id)) {
            update_user_meta($user_id, 'user_verification_status', 'verified');
            echo '<div class="notice notice-success"><p>احراز هویت کاربر تأیید شد.</p></div>';
        } elseif ($_GET['action'] === 'reject' && wp_verify_nonce($_GET['_wpnonce'], 'reject_user_' . $user_id)) {
            update_user_meta($user_id, 'user_verification_status', 'rejected');
            echo '<div class="notice notice-error"><p>احراز هویت کاربر رد شد.</p></div>';
        }
    }

    // نمایش لیست کاربران و عکس‌های آپلود شده
    $users = get_users(array(
        'meta_key' => 'user_identification',
        'meta_compare' => 'EXISTS',
    ));

    echo '<div class="wrap">';
    echo '<h1>احراز هویت کاربران</h1>';
    echo '<table class="wp-list-table widefat fixed striped">';
    echo '<thead>
            <tr>
                <th>نام کاربری</th>
                <th>ایمیل</th>
                <th>عکس کارت شناسایی</th>
                <th>وضعیت</th>
                <th>عملیات</th>
            </tr>
          </thead>
          <tbody>';

    foreach ($users as $user) {
        $user_id = $user->ID;
        $verification_status = get_user_meta($user_id, 'user_verification_status', true);
        $status_label = '';
        if ($verification_status === 'verified') {
            $status_label = '<span style="color: green;">تأیید شده</span>';
        } elseif ($verification_status === 'rejected') {
            $status_label = '<span style="color: red;">رد شده</span>';
        } else {
            $status_label = '<span style="color: orange;">در انتظار بررسی</span>';
        }

        $image_url = get_user_meta($user_id, 'user_identification', true);
        echo '<tr>
                <td>' . esc_html($user->user_login) . '</td>
                <td>' . esc_html($user->user_email) . '</td>
                <td><a href="' . esc_url($image_url) . '" target="_blank">مشاهده عکس</a></td>
                <td>' . $status_label . '</td>
                <td>
                    <a href="' . wp_nonce_url(admin_url('admin.php?page=user-id-verification&action=verify&user_id=' . $user_id), 'verify_user_' . $user_id) . '">تأیید</a> | 
                    <a href="' . wp_nonce_url(admin_url('admin.php?page=user-id-verification&action=reject&user_id=' . $user_id), 'reject_user_' . $user_id) . '">رد</a>
                </td>
              </tr>';
    }

    echo '</tbody></table></div>';
}