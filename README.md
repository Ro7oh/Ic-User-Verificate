# User ID Verification - WordPress Plugin

![WordPress Plugin](https://img.shields.io/badge/WordPress-Plugin-blue.svg)
![Language](https://img.shields.io/badge/Language-Persian/Farsi-purple.svg)
![Made For](https://img.shields.io/badge/Made_for-Iranian_Users-green.svg)

A WordPress plugin designed specifically for Iranian websites to handle national ID card verification through image uploads. (ساخته شده برای وبسایت های ایرانی)

## 📌 Features
- **ID Card Photo Upload**: Users can upload scanned copies of their national ID cards (کارت ملی)
- **Admin Verification Panel**: Admins can approve/reject verification requests (تایید/رد احراز هویت)
- **User Status Notification**: Real-time verification status updates for users
- **Secure File Handling**: Files stored in protected directory
- **Persian Language Support**: Fully Persian interface (زبان فارسی)
- **Shortcode Integration**: Easy implementation using `[id_verification_form]`

## 🚀 Installation
1. Download the plugin ZIP file
2. Upload to WordPress through `Plugins > Add New > Upload Plugin`
3. Activate the plugin
4. Create a new page and add shortcode: `[id_verification_form]`
5. Set proper permissions for `/wp-content/uploads/user-id-verification/` directory

```apache
# Add to .htaccess if needed
<FilesMatch "\.(jpg|jpeg|png|gif)$">
    Order Allow,Deny
    Allow from all
</FilesMatch>
