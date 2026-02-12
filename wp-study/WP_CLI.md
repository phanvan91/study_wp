# WP-CLI - Hướng Dẫn Sử Dụng Chi Tiết

## Mục lục

1. [Giới thiệu WP-CLI](#1-gioi-thieu-wp-cli)
2. [Các lệnh cơ bản](#2-cac-lenh-co-ban)
3. [wp scaffold - Tạo plugin/theme skeleton](#3-wp-scaffold---tao-plugintheme-skeleton)
4. [wp search-replace - Thay đổi domain](#4-wp-search-replace---thay-doi-domain)
5. [wp db export/import - Backup database](#5-wp-db-exportimport---backup-database)
6. [wp cron - Quản lý cron jobs](#6-wp-cron---quan-ly-cron-jobs)
7. [wp media - Quản lý media](#7-wp-media---quan-ly-media)
8. [wp rewrite - Quản lý rewrite rules](#8-wp-rewrite---quan-ly-rewrite-rules)
9. [wp transient - Quản lý transients](#9-wp-transient---quan-ly-transients)
10. [Tạo Custom WP-CLI Command](#10-tao-custom-wp-cli-command)
11. [wp eval và wp eval-file](#11-wp-eval-va-wp-eval-file)
12. [wp shell](#12-wp-shell)
13. [Automation scripts với WP-CLI](#13-automation-scripts-voi-wp-cli)

---

## 1. Giới thiệu WP-CLI

### WP-CLI là gì?

WP-CLI (WordPress Command Line Interface) là công cụ dòng lệnh chính thức để quản lý WordPress. Thay vì thao tác trên giao diện web, bạn có thể thực hiện hầu hết mọi tác vụ qua terminal: cài đặt plugin, cập nhật core, quản lý user, backup database, và nhiều việc khác.

### Lợi ích của WP-CLI

- Nhanh hơn nhiều so với thao tác trên giao diện web
- Có thể tự động hóa (automation) bằng script
- Quản lý nhiều site cùng lúc
- Hữu ích cho môi trường không có giao diện (server headless)
- Debug và troubleshoot dễ dàng hơn

### Cài đặt WP-CLI

```bash
# Tải file phar
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar

# Kiểm tra hoạt động
php wp-cli.phar --info

# Chuyển thành lệnh toàn cục
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp

# Kiểm tra phiên bản
wp --version
# WP-CLI 2.x.x
```

### Cài đặt trên các môi trường

```bash
# macOS với Homebrew
brew install wp-cli

# Ubuntu/Debian
sudo apt-get install wp-cli

# Composer
composer global require wp-cli/wp-cli-bundle

# Docker
docker run --rm -v $(pwd):/var/www/html wordpress:cli wp --info
```

### Cấu hình WP-CLI

```bash
# Tạo file cấu hình toàn cục
# ~/.wp-cli/config.yml

# Tạo file cấu hình cho project
# Đặt file wp-cli.yml hoặc wp-cli.local.yml trong thư mục WordPress
```

```yaml
# wp-cli.yml - File cấu hình cho project
path: /var/www/html/wordpress
url: https://example.com
user: admin
color: true
debug: false
quiet: false

# Cấu hình cho từng lệnh cụ thể
core update:
  locale: vi

plugin install:
  activate: true

# Alias cho các môi trường
@staging:
  ssh: user@staging.example.com/var/www/staging
  url: https://staging.example.com

@production:
  ssh: user@prod.example.com/var/www/production
  url: https://example.com
```

### Sử dụng alias

```bash
# Chạy lệnh trên môi trường staging
wp @staging plugin list

# Chạy lệnh trên môi trường production
wp @production db export

# Chạy trên tất cả alias
wp @all plugin update --all
```

---

## 2. Các lệnh cơ bản

### wp core - Quản lý WordPress Core

```bash
# --- CÀI ĐẶT ---

# Tải WordPress mới nhất
wp core download

# Tải phiên bản cụ thể
wp core download --version=6.4.2

# Tải phiên bản tiếng Việt
wp core download --locale=vi

# Tạo file wp-config.php
wp config create --dbname=mydb --dbuser=root --dbpass=password --dbhost=localhost

# Cài đặt WordPress
wp core install \
  --url="http://localhost/mysite" \
  --title="My WordPress Site" \
  --admin_user="admin" \
  --admin_password="securepassword123" \
  --admin_email="admin@example.com"

# Cài đặt multisite
wp core multisite-install \
  --url="http://localhost/multisite" \
  --title="My Network" \
  --admin_user="admin" \
  --admin_password="securepassword123" \
  --admin_email="admin@example.com"

# --- CẬP NHẬT ---

# Kiểm tra phiên bản hiện tại
wp core version

# Kiểm tra bản cập nhật
wp core check-update

# Cập nhật lên phiên bản mới nhất
wp core update

# Cập nhật lên phiên bản cụ thể
wp core update --version=6.4.2

# Cập nhật database sau khi cập nhật core
wp core update-db

# --- THÔNG TIN ---

# Kiểm tra toàn bộ thông tin
wp core version --extra
```

### wp plugin - Quản lý Plugins

```bash
# --- DANH SÁCH ---

# Xem tất cả plugin
wp plugin list

# Chỉ xem plugin đang active
wp plugin list --status=active

# Chỉ xem plugin cần update
wp plugin list --update=available

# Xuất danh sách ra CSV
wp plugin list --format=csv > plugins.csv

# Xuất ra JSON
wp plugin list --format=json

# --- CÀI ĐẶT ---

# Cài đặt plugin từ WordPress.org
wp plugin install woocommerce

# Cài đặt và kích hoạt ngay
wp plugin install woocommerce --activate

# Cài đặt phiên bản cụ thể
wp plugin install woocommerce --version=8.0.0

# Cài đặt từ file zip
wp plugin install /path/to/plugin.zip

# Cài đặt từ URL
wp plugin install https://example.com/plugin.zip

# Cài đặt nhiều plugin cùng lúc
wp plugin install woocommerce contact-form-7 yoast-seo --activate

# --- KÍCH HOẠT / VÔ HIỆU HÓA ---

# Kích hoạt plugin
wp plugin activate woocommerce

# Kích hoạt tất cả plugin
wp plugin activate --all

# Vô hiệu hóa plugin
wp plugin deactivate woocommerce

# Vô hiệu hóa tất cả plugin (hữu ích khi debug)
wp plugin deactivate --all

# --- CẬP NHẬT ---

# Cập nhật một plugin
wp plugin update woocommerce

# Cập nhật tất cả plugin
wp plugin update --all

# Cập nhật plugin lên phiên bản cụ thể (rollback)
wp plugin update woocommerce --version=8.0.0

# --- XÓA ---

# Xóa plugin (phải deactivate trước)
wp plugin deactivate woocommerce && wp plugin delete woocommerce

# Xóa và uninstall
wp plugin uninstall woocommerce

# --- THÔNG TIN ---

# Xem thông tin chi tiết plugin
wp plugin get woocommerce

# Tìm kiếm plugin trên WordPress.org
wp plugin search "contact form"

# Kiểm tra trạng thái
wp plugin is-active woocommerce
echo $?  # 0 = active, 1 = inactive

# Xem đường dẫn plugin
wp plugin path woocommerce
```

### wp theme - Quản lý Themes

```bash
# Xem danh sách theme
wp theme list

# Cài đặt theme
wp theme install flavor

# Cài đặt và kích hoạt
wp theme install flavor --activate

# Kích hoạt theme
wp theme activate flavor

# Cập nhật theme
wp theme update flavor
wp theme update --all

# Xóa theme
wp theme delete flavor

# Kiểm tra theme đang active
wp theme status

# Lấy thông tin theme
wp theme get flavor

# Tạo child theme
wp scaffold child-theme flavor-child --parent_theme=flavor --activate
```

### wp post - Quản lý Bài viết

```bash
# --- DANH SÁCH ---

# Xem danh sách bài viết
wp post list

# Lọc theo post type
wp post list --post_type=product

# Lọc theo trạng thái
wp post list --post_status=draft

# Lọc theo tác giả
wp post list --author=1

# Giới hạn số lượng và định dạng
wp post list --post_type=post --posts_per_page=5 --format=table

# Lấy chỉ ID
wp post list --post_type=post --field=ID

# --- TẠO ---

# Tạo bài viết mới
wp post create --post_type=post --post_title="Bai Viet Moi" --post_status=publish

# Tạo từ file
wp post create ./content.txt --post_title="Bai Viet Tu File" --post_status=draft

# Tạo với nhiều tham số
wp post create \
  --post_type=product \
  --post_title="San Pham Moi" \
  --post_content="Mo ta san pham" \
  --post_status=publish \
  --post_author=1 \
  --meta_input='{"_product_price":"500000","_product_sku":"SP001"}'

# Tạo nhiều bài viết nhanh (testing)
for i in $(seq 1 10); do
  wp post create --post_type=post --post_title="Bai viet test $i" --post_status=publish
done

# --- SỬA ---

# Sửa tiêu đề
wp post update 123 --post_title="Tieu De Moi"

# Sửa trạng thái
wp post update 123 --post_status=draft

# Sửa nội dung từ file
wp post update 123 ./new-content.txt

# --- XÓA ---

# Chuyển vào thùng rác
wp post delete 123

# Xóa vĩnh viễn
wp post delete 123 --force

# Xóa tất cả bài viết nháp
wp post delete $(wp post list --post_status=draft --field=ID) --force

# Xóa tất cả bài viết của post type
wp post delete $(wp post list --post_type=product --field=ID) --force

# --- META ---

# Xem meta
wp post meta list 123

# Lấy giá trị meta
wp post meta get 123 _product_price

# Thêm/Cập nhật meta
wp post meta update 123 _product_price 500000

# Xóa meta
wp post meta delete 123 _product_price

# --- KHÁC ---

# Tạo nội dung test (lorem ipsum)
wp post generate --count=20 --post_type=post --post_status=publish

# Lấy URL của bài viết
wp post url 123
```

### wp user - Quản lý Người dùng

```bash
# Xem danh sách user
wp user list

# Xem theo role
wp user list --role=editor

# Tạo user mới
wp user create john john@example.com --role=author --user_pass=password123

# Tạo admin
wp user create newadmin admin@example.com --role=administrator --user_pass=StrongPass123!

# Cập nhật user
wp user update 1 --user_email=new@example.com

# Đổi mật khẩu
wp user update 1 --user_pass=NewPassword123!

# Xóa user
wp user delete 2

# Xóa user và chuyển bài viết cho user khác
wp user delete 2 --reassign=1

# Thêm/xóa role
wp user add-role 1 editor
wp user remove-role 1 editor

# Thêm/xóa capability
wp user add-cap 1 manage_options
wp user remove-cap 1 manage_options

# Xem thông tin user
wp user get 1

# Lấy user meta
wp user meta list 1
wp user meta get 1 nickname

# Đăng nhập tự động (tạo URL đăng nhập)
# Cần plugin "wp-cli-login-command"
wp login create 1 --launch
```

### wp option - Quản lý Options

```bash
# Lấy giá trị option
wp option get blogname
wp option get siteurl
wp option get home
wp option get permalink_structure

# Lấy giá trị option dạng JSON
wp option get active_plugins --format=json

# Cập nhật option
wp option update blogname "Ten Website Moi"
wp option update blogdescription "Mo ta moi"
wp option update permalink_structure "/%postname%/"

# Thêm option mới
wp option add my_custom_option "gia tri" --autoload=yes

# Xóa option
wp option delete my_custom_option

# Tìm kiếm option
wp option list --search="*woocommerce*"

# Xem tất cả options autoloaded
wp option list --autoload=on

# Cập nhật autoload
wp option update my_option "value" --autoload=no

# Lấy danh sách autoloaded options và kích thước
wp option list --autoload=on --format=csv | awk -F',' '{print length($2), $1}' | sort -rn | head -20
```

### wp db - Quản lý Database

```bash
# Kiểm tra kết nối database
wp db check

# Mở MySQL CLI
wp db cli

# Chạy SQL query
wp db query "SELECT * FROM wp_options WHERE option_name = 'siteurl'"

# Xem kích thước database
wp db size
wp db size --tables  # Chi tiết từng bảng

# Xem cấu trúc bảng
wp db columns wp_posts

# Tối ưu database (OPTIMIZE TABLE)
wp db optimize

# Sửa chữa database (REPAIR TABLE)
wp db repair

# Xem prefix
wp db prefix

# Danh sách bảng
wp db tables

# Tìm kiếm trong database
wp db search "old-domain.com"

# Export/Import (xem phần 5)
```

---

## 3. wp scaffold - Tạo plugin/theme skeleton

### Tạo Plugin

```bash
# Tạo plugin cơ bản
wp scaffold plugin my-custom-plugin

# Tạo với các tham số
wp scaffold plugin my-custom-plugin \
  --plugin_name="My Custom Plugin" \
  --plugin_description="Mo ta plugin" \
  --plugin_author="Tac Gia" \
  --plugin_author_uri="https://example.com" \
  --plugin_uri="https://example.com/plugin"

# Kết quả tạo ra:
# wp-content/plugins/my-custom-plugin/
#   |-- my-custom-plugin.php      (File chính)
#   |-- readme.txt                (Mô tả plugin)
#   |-- .editorconfig
#   |-- .phpcs.xml.dist
#   |-- Gruntfile.js
#   |-- package.json
#   |-- phpunit.xml.dist
#   |-- tests/
#   |     |-- bootstrap.php
#   |     |-- test-sample.php
```

### Tạo Theme

```bash
# Tạo theme cơ bản
wp scaffold _s my-theme --theme_name="My Theme"

# _s (Underscores) là starter theme của Automattic
# Kết quả:
# wp-content/themes/my-theme/
#   |-- style.css
#   |-- functions.php
#   |-- header.php
#   |-- footer.php
#   |-- sidebar.php
#   |-- index.php
#   |-- single.php
#   |-- page.php
#   |-- archive.php
#   |-- search.php
#   |-- 404.php
#   |-- ...
```

### Tạo Child Theme

```bash
wp scaffold child-theme flavor-child \
  --parent_theme=flavor \
  --theme_name="Flavor Child" \
  --author="Dev Team" \
  --activate

# Kết quả:
# wp-content/themes/flavor-child/
#   |-- style.css
#   |-- functions.php
```

### Tạo Post Type và Taxonomy

```bash
# Tạo code đăng ký post type
wp scaffold post-type product \
  --label="San Pham" \
  --textdomain="my-plugin" \
  --plugin=my-custom-plugin

# Tạo code đăng ký taxonomy
wp scaffold taxonomy product_category \
  --post_types=product \
  --label="Danh Muc San Pham" \
  --textdomain="my-plugin" \
  --plugin=my-custom-plugin
```

### Tạo PHPUnit Tests

```bash
# Tạo test cho plugin
wp scaffold plugin-tests my-custom-plugin

# Kết quả:
# tests/
#   |-- bootstrap.php
#   |-- test-sample.php
# phpunit.xml.dist
# bin/
#   |-- install-wp-tests.sh

# Chạy cài đặt test environment
cd wp-content/plugins/my-custom-plugin
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

### Tạo Block

```bash
# Tạo Gutenberg block
wp scaffold block my-block --title="My Custom Block" --plugin=my-custom-plugin

# Kết quả:
# wp-content/plugins/my-custom-plugin/
#   |-- blocks/
#   |     |-- my-block/
#   |           |-- index.js
#   |           |-- editor.css
#   |           |-- style.css
```

---

## 4. wp search-replace - Thay đổi domain

### Cú pháp cơ bản

```bash
wp search-replace <old-string> <new-string> [table...] [--dry-run] [--precise] [--all-tables]
```

### Thay đổi domain (Migration)

```bash
# LUÔN chạy --dry-run trước để kiểm tra
wp search-replace 'http://old-domain.com' 'https://new-domain.com' --dry-run

# Kết quả:
# +------------------+-----------------------+--------------+------+
# | Table            | Column                | Replacements | Type |
# +------------------+-----------------------+--------------+------+
# | wp_options       | option_value          | 15           | SQL  |
# | wp_posts         | post_content          | 123          | SQL  |
# | wp_posts         | guid                  | 456          | SQL  |
# | wp_postmeta      | meta_value            | 78           | SQL  |
# +------------------+-----------------------+--------------+------+

# Chạy thật sự
wp search-replace 'http://old-domain.com' 'https://new-domain.com'

# Thay đổi cả trong toàn bộ các bảng (bao gồm bảng của plugin)
wp search-replace 'http://old-domain.com' 'https://new-domain.com' --all-tables

# Bỏ qua cột guid (nên làm khi migration)
wp search-replace 'http://old-domain.com' 'https://new-domain.com' --skip-columns=guid

# Chỉ thay đổi trong bảng cụ thể
wp search-replace 'http://old-domain.com' 'https://new-domain.com' wp_options wp_posts wp_postmeta
```

### Chuyển từ HTTP sang HTTPS

```bash
# Thay đổi URL
wp search-replace 'http://example.com' 'https://example.com' --all-tables --dry-run
wp search-replace 'http://example.com' 'https://example.com' --all-tables

# Cập nhật siteurl và home
wp option update siteurl 'https://example.com'
wp option update home 'https://example.com'
```

### Thay đổi prefix trong nội dung

```bash
# Thay đổi đường dẫn upload
wp search-replace '/wp-content/uploads/2023/' '/wp-content/uploads/2024/' wp_posts

# Thay đổi email domain
wp search-replace '@old-company.com' '@new-company.com' --all-tables --dry-run
```

### Tùy chọn quan trọng

```bash
# --precise: Xử lý chính xác dữ liệu serialized (chậm hơn nhưng an toàn hơn)
wp search-replace 'old' 'new' --precise

# --regex: Sử dụng regular expression
wp search-replace 'http://(www\.)?old-domain\.com' 'https://new-domain.com' --regex

# --log: Ghi log thay đổi
wp search-replace 'old' 'new' --log=search-replace.log

# --export: Xuất kết quả ra file SQL thay vì thay đổi trực tiếp
wp search-replace 'old-domain.com' 'new-domain.com' --export=migration.sql

# --network: Chạy trên multisite
wp search-replace 'old-domain.com' 'new-domain.com' --network
```

### Quy trình Migration đầy đủ

```bash
#!/bin/bash
# Script migration từ local sang production

OLD_URL="http://localhost/mysite"
NEW_URL="https://www.mysite.com"

echo "=== Bắt đầu migration ==="

# 1. Export database
wp db export backup-before-migration.sql
echo "Đã backup database"

# 2. Dry run trước
echo "Kiểm tra thay đổi:"
wp search-replace "$OLD_URL" "$NEW_URL" --all-tables --dry-run

# 3. Thực hiện thay đổi
read -p "Tiếp tục? (y/n) " confirm
if [ "$confirm" = "y" ]; then
    wp search-replace "$OLD_URL" "$NEW_URL" --all-tables --skip-columns=guid
    echo "Đã thay đổi URL"

    # 4. Cập nhật options
    wp option update siteurl "$NEW_URL"
    wp option update home "$NEW_URL"

    # 5. Flush cache và rewrite
    wp cache flush
    wp rewrite flush

    echo "=== Migration hoàn tất ==="
else
    echo "Đã hủy"
fi
```

---

## 5. wp db export/import - Backup database

### Export (Backup)

```bash
# Export toàn bộ database
wp db export

# Export với tên file tự đặt
wp db export backup-2024-01-15.sql

# Export với nén gzip
wp db export - | gzip > backup-2024-01-15.sql.gz

# Export chỉ một số bảng
wp db export --tables=wp_posts,wp_postmeta,wp_options

# Loại trừ bảng
wp db export --exclude_tables=wp_comments,wp_commentmeta

# Export với tùy chọn mysqldump
wp db export --add-drop-table --single-transaction

# Export với thời gian trong tên file
wp db export "backup-$(date +%Y%m%d-%H%M%S).sql"
```

### Import

```bash
# Import từ file SQL
wp db import backup.sql

# Import từ file nén
gunzip < backup.sql.gz | wp db import -

# Import và skip errors
wp db import backup.sql --skip-optimization
```

### Reset Database

```bash
# Xóa toàn bộ database và tạo lại
wp db reset --yes

# Sau khi reset, cần cài đặt lại WordPress
wp core install \
  --url="http://localhost/mysite" \
  --title="My Site" \
  --admin_user="admin" \
  --admin_password="password" \
  --admin_email="admin@example.com"
```

### Script Backup tự động

```bash
#!/bin/bash
# backup-daily.sh - Script backup hàng ngày

# Cấu hình
WP_PATH="/var/www/html/wordpress"
BACKUP_DIR="/var/backups/wordpress"
KEEP_DAYS=30
DATE=$(date +%Y%m%d-%H%M%S)

# Tạo thư mục backup nếu chưa có
mkdir -p "$BACKUP_DIR"

# Backup database
wp db export "$BACKUP_DIR/db-$DATE.sql" --path="$WP_PATH"

# Nén file
gzip "$BACKUP_DIR/db-$DATE.sql"

# Backup uploads
tar -czf "$BACKUP_DIR/uploads-$DATE.tar.gz" -C "$WP_PATH/wp-content" uploads

echo "Backup hoàn tất: $DATE"

# Xóa backup cũ hơn KEEP_DAYS ngày
find "$BACKUP_DIR" -name "db-*.sql.gz" -mtime +$KEEP_DAYS -delete
find "$BACKUP_DIR" -name "uploads-*.tar.gz" -mtime +$KEEP_DAYS -delete

echo "Đã xóa backup cũ hơn $KEEP_DAYS ngày"
```

```bash
# Thêm vào crontab để chạy hàng ngày lúc 2h sáng
# crontab -e
# 0 2 * * * /path/to/backup-daily.sh >> /var/log/wp-backup.log 2>&1
```

---

## 6. wp cron - Quản lý cron jobs

### Xem cron events

```bash
# Xem tất cả cron events
wp cron event list

# Kết quả:
# +---------------------------+---------------------+-----------------------+------------+
# | hook                      | next_run_gmt        | next_run_relative     | recurrence |
# +---------------------------+---------------------+-----------------------+------------+
# | wp_version_check          | 2024-01-15 10:00:00 | 2 hours 30 minutes    | twicedaily |
# | wp_update_plugins         | 2024-01-15 10:00:00 | 2 hours 30 minutes    | twicedaily |
# | wp_scheduled_delete       | 2024-01-15 12:00:00 | 4 hours 30 minutes    | daily      |
# +---------------------------+---------------------+-----------------------+------------+

# Xem chi tiết
wp cron event list --format=json
```

### Chạy cron events

```bash
# Chạy tất cả cron events đã đến hạn
wp cron event run --due-now

# Chạy một event cụ thể
wp cron event run wp_version_check

# Chạy tất cả events (kể cả chưa đến hạn)
wp cron event run --all
```

### Quản lý cron schedules

```bash
# Xem các schedule đã đăng ký
wp cron schedule list

# Kết quả:
# +------------+----------+-------------------+
# | name       | interval | display           |
# +------------+----------+-------------------+
# | hourly     | 3600     | Once Hourly       |
# | twicedaily | 43200    | Twice Daily       |
# | daily      | 86400    | Once Daily        |
# | weekly     | 604800   | Once Weekly       |
# +------------+----------+-------------------+
```

### Xóa và thêm cron events

```bash
# Xóa một event
wp cron event delete wp_version_check

# Tạo event mới
wp cron event schedule my_custom_hook now hourly

# Kiểm tra WP-Cron có hoạt động không
wp cron test
```

### Debug Cron

```bash
# Kiểm tra DISABLE_WP_CRON
wp config get DISABLE_WP_CRON

# Bật/Tắt WP-Cron
wp config set DISABLE_WP_CRON true --raw
wp config set DISABLE_WP_CRON false --raw

# Khi DISABLE_WP_CRON = true, dùng system cron:
# crontab -e
# */5 * * * * cd /var/www/html && wp cron event run --due-now > /dev/null 2>&1

# Hoặc dùng wget/curl:
# */5 * * * * wget -q -O - https://example.com/wp-cron.php > /dev/null 2>&1
```

---

## 7. wp media - Quản lý media

### Regenerate thumbnails

```bash
# Tạo lại tất cả thumbnails (khi đổi theme hoặc thêm image size mới)
wp media regenerate

# Tạo lại cho hình cụ thể
wp media regenerate 123 456

# Chỉ tạo lại những size bị thiếu
wp media regenerate --only-missing

# Bỏ qua xác nhận
wp media regenerate --yes

# Chỉ tạo lại size cụ thể
wp media regenerate --image_size=thumbnail
```

### Import media

```bash
# Import từ URL
wp media import https://example.com/image.jpg

# Import từ file local
wp media import /path/to/image.jpg

# Import và gán cho bài viết
wp media import https://example.com/image.jpg --post_id=123

# Import và đặt làm featured image
wp media import https://example.com/image.jpg --post_id=123 --featured_image

# Import với tiêu đề tùy chỉnh
wp media import /path/to/image.jpg --title="Anh San Pham"

# Import nhiều file
wp media import /path/to/images/*.jpg
```

### Xem thông tin media

```bash
# Xem danh sách media
wp post list --post_type=attachment

# Lấy thông tin chi tiết
wp post get 123

# Lấy URL media
wp post list --post_type=attachment --field=guid

# Xem các image sizes đã đăng ký
wp media image-size
```

### Xóa media không sử dụng

```bash
# Tìm media không được gán với bài viết nào
wp post list --post_type=attachment --post_parent=0 --format=ids

# Xóa media không sử dụng
wp post delete $(wp post list --post_type=attachment --post_parent=0 --format=ids) --force
```

---

## 8. wp rewrite - Quản lý rewrite rules

### Xem và cập nhật rewrite rules

```bash
# Xem tất cả rewrite rules
wp rewrite list

# Xem chi tiết với match
wp rewrite list --match="san-pham/ao-thun"

# Kết quả:
# +------------------------------------------+-------------------------------------------+--------+
# | match                                    | query                                     | source |
# +------------------------------------------+-------------------------------------------+--------+
# | san-pham/([^/]+)/?$                      | index.php?product=$matches[1]             | post   |
# +------------------------------------------+-------------------------------------------+--------+

# Flush (làm mới) rewrite rules
wp rewrite flush

# Flush với hard (xóa .htaccess và tạo lại)
wp rewrite flush --hard

# Xem cấu trúc permalink hiện tại
wp option get permalink_structure

# Thay đổi cấu trúc permalink
wp rewrite structure '/%postname%/'
wp rewrite structure '/%category%/%postname%/'

# Thêm rewrite rule
# (Thường làm trong code PHP, nhưng có thể test bằng wp eval)
wp eval "add_rewrite_rule('custom-page/?$', 'index.php?pagename=my-page', 'top'); flush_rewrite_rules();"
```

---

## 9. wp transient - Quản lý transients

### Transients là gì?

Transients là cách lưu cache tạm thời trong database (hoặc object cache nếu có). Có thời gian hết hạn (expiration).

### Các lệnh cơ bản

```bash
# Lấy giá trị transient
wp transient get my_transient

# Đặt giá trị transient (hết hạn sau 3600 giây = 1 giờ)
wp transient set my_transient "gia tri" 3600

# Đặt transient không hết hạn
wp transient set my_transient "gia tri" 0

# Xóa transient cụ thể
wp transient delete my_transient

# Xóa tất cả transients
wp transient delete --all

# Xóa chỉ transients đã hết hạn
wp transient delete --expired

# Lấy loại transient (transient hoặc site-transient)
wp transient type my_transient

# --- MULTISITE ---

# Lấy site transient (cho multisite)
wp transient get my_transient --network

# Đặt site transient
wp transient set my_transient "value" 3600 --network

# Xóa site transient
wp transient delete my_transient --network
```

### Ví dụ thực tế

```bash
# Kiểm tra transient có tồn tại không
wp transient get featured_products
# Nếu trả về empty => chưa có hoặc đã hết hạn

# Đặt transient test
wp transient set test_cache '{"products":[1,2,3]}' 3600

# Xem tất cả transients trong database
wp db query "SELECT option_name, LENGTH(option_value) as size FROM wp_options WHERE option_name LIKE '_transient_%' ORDER BY size DESC LIMIT 20;"

# Xóa transients lớn
wp db query "DELETE FROM wp_options WHERE option_name LIKE '_transient_%' AND option_name NOT LIKE '_transient_timeout_%';"
```

---

## 10. Tạo Custom WP-CLI Command

### Command đơn giản

```php
<?php
/**
 * Plugin Name: My CLI Commands
 * Description: Custom WP-CLI commands
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Chỉ đăng ký khi chạy trong WP-CLI
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

/**
 * Quản lý sản phẩm từ command line.
 */
class My_Product_CLI_Command {

    /**
     * Xem danh sách sản phẩm.
     *
     * ## OPTIONS
     *
     * [--count=<number>]
     * : Số lượng sản phẩm hiển thị. Mặc định: 10
     *
     * [--status=<status>]
     * : Lọc theo trạng thái sản phẩm (in_stock, out_of_stock, on_sale)
     *
     * [--format=<format>]
     * : Định dạng output. Mặc định: table
     * ---
     * default: table
     * options:
     *   - table
     *   - csv
     *   - json
     *   - yaml
     * ---
     *
     * ## EXAMPLES
     *
     *     # Xem 5 sản phẩm đầu tiên
     *     wp product list --count=5
     *
     *     # Xem sản phẩm còn hàng, định dạng JSON
     *     wp product list --status=in_stock --format=json
     *
     * @when after_wp_load
     */
    public function list_products( $args, $assoc_args ) {
        $count  = isset( $assoc_args['count'] ) ? intval( $assoc_args['count'] ) : 10;
        $status = isset( $assoc_args['status'] ) ? $assoc_args['status'] : '';
        $format = isset( $assoc_args['format'] ) ? $assoc_args['format'] : 'table';

        $query_args = array(
            'post_type'      => 'product',
            'posts_per_page' => $count,
            'post_status'    => 'publish',
        );

        // Lọc theo trạng thái
        if ( $status ) {
            $query_args['meta_query'] = array(
                array(
                    'key'   => '_product_status',
                    'value' => $status,
                ),
            );
        }

        $products = new WP_Query( $query_args );
        $items    = array();

        if ( $products->have_posts() ) {
            while ( $products->have_posts() ) {
                $products->the_post();
                $items[] = array(
                    'ID'       => get_the_ID(),
                    'Title'    => get_the_title(),
                    'Price'    => get_post_meta( get_the_ID(), '_product_price', true ),
                    'SKU'      => get_post_meta( get_the_ID(), '_product_sku', true ),
                    'Status'   => get_post_meta( get_the_ID(), '_product_status', true ),
                    'Date'     => get_the_date( 'Y-m-d' ),
                );
            }
            wp_reset_postdata();
        }

        if ( empty( $items ) ) {
            WP_CLI::warning( 'Không tìm thấy sản phẩm nào.' );
            return;
        }

        // Hiển thị bằng WP_CLI\Utils\format_items
        WP_CLI\Utils\format_items( $format, $items, array( 'ID', 'Title', 'Price', 'SKU', 'Status', 'Date' ) );

        WP_CLI::success( sprintf( 'Tìm thấy %d sản phẩm.', count( $items ) ) );
    }

    /**
     * Tạo sản phẩm mới.
     *
     * ## OPTIONS
     *
     * <title>
     * : Tên sản phẩm
     *
     * --price=<price>
     * : Giá sản phẩm (VND)
     *
     * [--sku=<sku>]
     * : Mã sản phẩm
     *
     * [--category=<category>]
     * : Slug danh mục sản phẩm
     *
     * ## EXAMPLES
     *
     *     wp product create "Ao Thun Nam" --price=250000 --sku=ATN001 --category=thoi-trang
     *
     * @when after_wp_load
     */
    public function create( $args, $assoc_args ) {
        $title    = $args[0];
        $price    = $assoc_args['price'];
        $sku      = isset( $assoc_args['sku'] ) ? $assoc_args['sku'] : '';
        $category = isset( $assoc_args['category'] ) ? $assoc_args['category'] : '';

        // Tạo post
        $post_id = wp_insert_post( array(
            'post_type'   => 'product',
            'post_title'  => $title,
            'post_status' => 'publish',
        ) );

        if ( is_wp_error( $post_id ) ) {
            WP_CLI::error( 'Không thể tạo sản phẩm: ' . $post_id->get_error_message() );
        }

        // Lưu meta
        update_post_meta( $post_id, '_product_price', absint( $price ) );
        update_post_meta( $post_id, '_product_status', 'in_stock' );

        if ( $sku ) {
            update_post_meta( $post_id, '_product_sku', sanitize_text_field( $sku ) );
        }

        // Gán taxonomy
        if ( $category ) {
            wp_set_object_terms( $post_id, $category, 'product_category' );
        }

        WP_CLI::success( sprintf( 'Đã tạo sản phẩm "%s" (ID: %d)', $title, $post_id ) );
    }

    /**
     * Cập nhật giá sản phẩm.
     *
     * ## OPTIONS
     *
     * <id>
     * : ID sản phẩm
     *
     * <price>
     * : Giá mới (VND)
     *
     * ## EXAMPLES
     *
     *     wp product update-price 123 350000
     *
     * @when after_wp_load
     */
    public function update_price( $args, $assoc_args ) {
        $post_id = intval( $args[0] );
        $price   = intval( $args[1] );

        $post = get_post( $post_id );

        if ( ! $post || $post->post_type !== 'product' ) {
            WP_CLI::error( "Không tìm thấy sản phẩm với ID: $post_id" );
        }

        $old_price = get_post_meta( $post_id, '_product_price', true );
        update_post_meta( $post_id, '_product_price', $price );

        WP_CLI::success( sprintf(
            'Đã cập nhật giá sản phẩm "%s": %s -> %s VND',
            $post->post_title,
            number_format( $old_price ),
            number_format( $price )
        ) );
    }

    /**
     * Xóa sản phẩm.
     *
     * ## OPTIONS
     *
     * <id>...
     * : Một hoặc nhiều ID sản phẩm cần xóa
     *
     * [--force]
     * : Xóa vĩnh viễn (không qua thùng rác)
     *
     * ## EXAMPLES
     *
     *     # Xóa 1 sản phẩm
     *     wp product delete 123
     *
     *     # Xóa nhiều sản phẩm vĩnh viễn
     *     wp product delete 123 456 789 --force
     *
     * @when after_wp_load
     */
    public function delete( $args, $assoc_args ) {
        $force = isset( $assoc_args['force'] );

        foreach ( $args as $post_id ) {
            $post_id = intval( $post_id );
            $post    = get_post( $post_id );

            if ( ! $post || $post->post_type !== 'product' ) {
                WP_CLI::warning( "Không tìm thấy sản phẩm với ID: $post_id" );
                continue;
            }

            $title = $post->post_title;

            if ( $force ) {
                wp_delete_post( $post_id, true );
                WP_CLI::success( "Đã xóa vĩnh viễn: \"$title\" (ID: $post_id)" );
            } else {
                wp_trash_post( $post_id );
                WP_CLI::success( "Đã chuyển vào thùng rác: \"$title\" (ID: $post_id)" );
            }
        }
    }

    /**
     * Import sản phẩm từ file CSV.
     *
     * ## OPTIONS
     *
     * <file>
     * : Đường dẫn file CSV
     *
     * [--dry-run]
     * : Chỉ hiển thị, không thực sự import
     *
     * ## EXAMPLES
     *
     *     wp product import products.csv
     *     wp product import products.csv --dry-run
     *
     * @when after_wp_load
     */
    public function import( $args, $assoc_args ) {
        $file    = $args[0];
        $dry_run = isset( $assoc_args['dry-run'] );

        if ( ! file_exists( $file ) ) {
            WP_CLI::error( "File không tồn tại: $file" );
        }

        $handle = fopen( $file, 'r' );
        $header = fgetcsv( $handle ); // Dòng tiêu đề
        $count  = 0;

        // Progress bar
        $total = count( file( $file ) ) - 1; // Trừ dòng header
        $progress = WP_CLI\Utils\make_progress_bar( 'Đang import sản phẩm...', $total );

        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            $data = array_combine( $header, $row );

            if ( $dry_run ) {
                WP_CLI::log( sprintf( '[DRY RUN] Sẽ tạo: %s - Giá: %s', $data['title'], $data['price'] ) );
            } else {
                $post_id = wp_insert_post( array(
                    'post_type'   => 'product',
                    'post_title'  => $data['title'],
                    'post_content' => isset( $data['description'] ) ? $data['description'] : '',
                    'post_status' => 'publish',
                ) );

                if ( ! is_wp_error( $post_id ) ) {
                    update_post_meta( $post_id, '_product_price', absint( $data['price'] ) );
                    if ( isset( $data['sku'] ) ) {
                        update_post_meta( $post_id, '_product_sku', $data['sku'] );
                    }
                    $count++;
                }
            }

            $progress->tick();
        }

        $progress->finish();
        fclose( $handle );

        if ( $dry_run ) {
            WP_CLI::success( "Dry run hoàn tất. Sẽ import $total sản phẩm." );
        } else {
            WP_CLI::success( "Đã import $count/$total sản phẩm." );
        }
    }
}

// Đăng ký command
WP_CLI::add_command( 'product', 'My_Product_CLI_Command' );
```

### Sử dụng command

```bash
# Xem danh sách sub-commands
wp product --help

# Xem help của từng sub-command
wp product list --help
wp product create --help

# Sử dụng
wp product list --count=5 --format=table
wp product create "Ao Khoac" --price=500000 --sku=AK001
wp product update-price 123 600000
wp product delete 123 --force
wp product import products.csv --dry-run
```

---

## 11. wp eval và wp eval-file

### wp eval - Chạy code PHP trực tiếp

```bash
# Chạy 1 dòng code PHP
wp eval 'echo get_bloginfo("name");'

# Lấy thông tin
wp eval 'echo home_url();'
wp eval 'echo wp_get_theme()->get("Name");'

# Lấy số lượng bài viết
wp eval '$count = wp_count_posts("product"); echo "Published: " . $count->publish;'

# Kiểm tra function tồn tại
wp eval 'echo function_exists("wc_get_products") ? "WooCommerce active" : "WooCommerce inactive";'

# Chạy query
wp eval '
$users = get_users(array("role" => "administrator"));
foreach ($users as $user) {
    echo $user->user_login . " - " . $user->user_email . "\n";
}
'

# Xóa transients cũ
wp eval '
global $wpdb;
$count = $wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE \"_transient_timeout_%\"
     AND option_value < UNIX_TIMESTAMP()"
);
echo "Đã xóa $count transients hết hạn.";
'

# Cập nhật meta hàng loạt
wp eval '
$products = get_posts(array(
    "post_type" => "product",
    "posts_per_page" => -1,
    "fields" => "ids",
));
foreach ($products as $id) {
    $price = get_post_meta($id, "_product_price", true);
    if ($price) {
        $new_price = intval($price * 1.1); // Tăng 10%
        update_post_meta($id, "_product_price", $new_price);
    }
}
echo "Đã cập nhật " . count($products) . " sản phẩm.";
'
```

### wp eval-file - Chạy file PHP

```bash
# Chạy file PHP
wp eval-file maintenance.php

# Truyền tham số
wp eval-file process.php -- --type=product --limit=100
```

```php
<?php
// maintenance.php - File bảo trì
// Chạy: wp eval-file maintenance.php

echo "=== Bắt đầu bảo trì ===\n";

// 1. Xóa bài viết nháp cũ hơn 30 ngày
$old_drafts = get_posts( array(
    'post_type'      => 'any',
    'post_status'    => 'draft',
    'posts_per_page' => -1,
    'date_query'     => array(
        array(
            'before' => '30 days ago',
        ),
    ),
    'fields'         => 'ids',
) );

foreach ( $old_drafts as $id ) {
    wp_delete_post( $id, true );
}
echo "Xóa " . count( $old_drafts ) . " bài nháp cũ.\n";

// 2. Xóa revisions
global $wpdb;
$revisions = $wpdb->query(
    "DELETE FROM {$wpdb->posts} WHERE post_type = 'revision'"
);
echo "Xóa $revisions revisions.\n";

// 3. Xóa orphaned postmeta
$orphaned = $wpdb->query(
    "DELETE pm FROM {$wpdb->postmeta} pm
     LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
     WHERE p.ID IS NULL"
);
echo "Xóa $orphaned orphaned meta.\n";

// 4. Xóa transients hết hạn
$expired = $wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_timeout_%'
     AND option_value < UNIX_TIMESTAMP()"
);
echo "Xóa $expired expired transients.\n";

echo "=== Bảo trì hoàn tất ===\n";
```

---

## 12. wp shell

### WP Shell - Interactive PHP Shell

```bash
# Mở interactive shell
wp shell

# Trong shell, có thể chạy code PHP trực tiếp:
# wp> echo get_bloginfo('name');
# My WordPress Site
#
# wp> $posts = get_posts(['post_type' => 'product', 'numberposts' => 5]);
# wp> foreach ($posts as $p) echo $p->ID . ' - ' . $p->post_title . "\n";
# 123 - Ao Thun
# 124 - Quan Jean
# 125 - Giay The Thao
#
# wp> get_post_meta(123, '_product_price', true);
# => 250000
#
# wp> exit  // Thoát shell
```

### Ví dụ sử dụng wp shell

```bash
wp shell <<'PHP'
// Kiểm tra số lượng post theo từng post type
$post_types = get_post_types( array( 'public' => true ), 'names' );
foreach ( $post_types as $pt ) {
    $count = wp_count_posts( $pt );
    echo "$pt: {$count->publish} published\n";
}
PHP
```

---

## 13. Automation scripts với WP-CLI

### Script cài đặt WordPress tự động

```bash
#!/bin/bash
# setup-wordpress.sh - Cài đặt WordPress tự động

# Cấu hình
DB_NAME="mysite_db"
DB_USER="root"
DB_PASS="password"
DB_HOST="localhost"
SITE_URL="http://localhost/mysite"
SITE_TITLE="My WordPress Site"
ADMIN_USER="admin"
ADMIN_PASS="SecurePass123!"
ADMIN_EMAIL="admin@example.com"
WP_PATH="/var/www/html/mysite"

echo "=== Bắt đầu cài đặt WordPress ==="

# 1. Tạo thư mục
mkdir -p "$WP_PATH"
cd "$WP_PATH"

# 2. Tải WordPress
wp core download --locale=vi --path="$WP_PATH"
echo "[OK] Đã tải WordPress"

# 3. Tạo database
mysql -u"$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo "[OK] Đã tạo database"

# 4. Tạo wp-config.php
wp config create \
  --dbname="$DB_NAME" \
  --dbuser="$DB_USER" \
  --dbpass="$DB_PASS" \
  --dbhost="$DB_HOST" \
  --dbcharset="utf8mb4" \
  --path="$WP_PATH"

# Thêm cấu hình bổ sung
wp config set WP_DEBUG true --raw --path="$WP_PATH"
wp config set WP_DEBUG_LOG true --raw --path="$WP_PATH"
wp config set WP_DEBUG_DISPLAY false --raw --path="$WP_PATH"
wp config set DISALLOW_FILE_EDIT true --raw --path="$WP_PATH"
echo "[OK] Đã tạo wp-config.php"

# 5. Cài đặt WordPress
wp core install \
  --url="$SITE_URL" \
  --title="$SITE_TITLE" \
  --admin_user="$ADMIN_USER" \
  --admin_password="$ADMIN_PASS" \
  --admin_email="$ADMIN_EMAIL" \
  --path="$WP_PATH"
echo "[OK] Đã cài đặt WordPress"

# 6. Cấu hình cơ bản
wp option update permalink_structure '/%postname%/' --path="$WP_PATH"
wp option update blogdescription 'Mô tả website' --path="$WP_PATH"
wp option update timezone_string 'Asia/Ho_Chi_Minh' --path="$WP_PATH"
wp option update date_format 'd/m/Y' --path="$WP_PATH"
wp option update time_format 'H:i' --path="$WP_PATH"
wp option update WPLANG 'vi' --path="$WP_PATH"
echo "[OK] Đã cấu hình cơ bản"

# 7. Xóa nội dung mặc định
wp post delete 1 --force --path="$WP_PATH"  # Hello World
wp post delete 2 --force --path="$WP_PATH"  # Sample Page
wp comment delete 1 --force --path="$WP_PATH"  # Comment mặc định
echo "[OK] Đã xóa nội dung mặc định"

# 8. Cài đặt plugins
PLUGINS=(
    "query-monitor"
    "contact-form-7"
    "wordpress-seo"
    "wp-super-cache"
    "wordfence"
    "regenerate-thumbnails"
)

for plugin in "${PLUGINS[@]}"; do
    wp plugin install "$plugin" --activate --path="$WP_PATH"
    echo "[OK] Đã cài đặt plugin: $plugin"
done

# 9. Xóa plugins mặc định không cần
wp plugin delete hello --path="$WP_PATH"
wp plugin delete akismet --path="$WP_PATH"
echo "[OK] Đã xóa plugins không cần"

# 10. Cài đặt và kích hoạt theme
wp theme install flavor --activate --path="$WP_PATH"
echo "[OK] Đã cài đặt theme"

# 11. Xóa themes mặc định không cần
wp theme delete twentytwentytwo --path="$WP_PATH"
wp theme delete twentytwentythree --path="$WP_PATH"
echo "[OK] Đã xóa themes không cần"

# 12. Tạo các trang cơ bản
wp post create --post_type=page --post_title="Trang Chu" --post_status=publish --path="$WP_PATH"
wp post create --post_type=page --post_title="Gioi Thieu" --post_status=publish --path="$WP_PATH"
wp post create --post_type=page --post_title="Lien He" --post_status=publish --path="$WP_PATH"
wp post create --post_type=page --post_title="Blog" --post_status=publish --path="$WP_PATH"
echo "[OK] Đã tạo các trang cơ bản"

# 13. Cấu hình trang chủ và trang blog
FRONT_PAGE=$(wp post list --post_type=page --name="trang-chu" --field=ID --path="$WP_PATH")
BLOG_PAGE=$(wp post list --post_type=page --name="blog" --field=ID --path="$WP_PATH")
wp option update show_on_front 'page' --path="$WP_PATH"
wp option update page_on_front "$FRONT_PAGE" --path="$WP_PATH"
wp option update page_for_posts "$BLOG_PAGE" --path="$WP_PATH"
echo "[OK] Đã cấu hình trang chủ"

# 14. Tạo menu
wp menu create "Main Menu" --path="$WP_PATH"
wp menu item add-post main-menu "$FRONT_PAGE" --title="Trang Chu" --path="$WP_PATH"
ABOUT_PAGE=$(wp post list --post_type=page --name="gioi-thieu" --field=ID --path="$WP_PATH")
wp menu item add-post main-menu "$ABOUT_PAGE" --title="Gioi Thieu" --path="$WP_PATH"
CONTACT_PAGE=$(wp post list --post_type=page --name="lien-he" --field=ID --path="$WP_PATH")
wp menu item add-post main-menu "$CONTACT_PAGE" --title="Lien He" --path="$WP_PATH"
wp menu location assign main-menu primary --path="$WP_PATH"
echo "[OK] Đã tạo menu"

# 15. Flush rewrite
wp rewrite flush --hard --path="$WP_PATH"

echo ""
echo "========================================="
echo "Cài đặt hoàn tất!"
echo "URL: $SITE_URL"
echo "Admin: $SITE_URL/wp-admin"
echo "User: $ADMIN_USER"
echo "Pass: $ADMIN_PASS"
echo "========================================="
```

### Script cập nhật hàng loạt

```bash
#!/bin/bash
# update-all.sh - Cập nhật tất cả

WP_PATH="/var/www/html/wordpress"

echo "=== Bắt đầu cập nhật ==="

# Backup trước khi cập nhật
BACKUP_FILE="pre-update-$(date +%Y%m%d-%H%M%S).sql"
wp db export "$BACKUP_FILE" --path="$WP_PATH"
echo "[OK] Backup: $BACKUP_FILE"

# Cập nhật core
wp core update --path="$WP_PATH"
wp core update-db --path="$WP_PATH"
echo "[OK] Cập nhật WordPress core"

# Cập nhật tất cả plugins
wp plugin update --all --path="$WP_PATH"
echo "[OK] Cập nhật plugins"

# Cập nhật tất cả themes
wp theme update --all --path="$WP_PATH"
echo "[OK] Cập nhật themes"

# Cập nhật ngôn ngữ
wp language core update --path="$WP_PATH"
wp language plugin update --all --path="$WP_PATH"
wp language theme update --all --path="$WP_PATH"
echo "[OK] Cập nhật ngôn ngữ"

# Flush cache
wp cache flush --path="$WP_PATH"
wp rewrite flush --path="$WP_PATH"
echo "[OK] Flush cache"

echo "=== Cập nhật hoàn tất ==="
```

### Script tạo nội dung test

```bash
#!/bin/bash
# generate-test-content.sh - Tạo dữ liệu test

WP_PATH="/var/www/html/wordpress"

echo "=== Tạo dữ liệu test ==="

# Tạo categories
CATEGORIES=("Cong Nghe" "Doi Song" "Giai Tri" "Kinh Doanh" "The Thao")
for cat in "${CATEGORIES[@]}"; do
    wp term create category "$cat" --path="$WP_PATH"
done
echo "[OK] Tạo ${#CATEGORIES[@]} categories"

# Tạo bài viết
wp post generate --count=50 --post_type=post --post_status=publish --path="$WP_PATH"
echo "[OK] Tạo 50 bài viết"

# Tạo users
ROLES=("editor" "author" "contributor" "subscriber")
for i in $(seq 1 10); do
    ROLE=${ROLES[$((RANDOM % ${#ROLES[@]}))]}
    wp user create "user$i" "user$i@example.com" --role="$ROLE" --user_pass=Test123! --path="$WP_PATH"
done
echo "[OK] Tạo 10 users"

# Tạo comments
for post_id in $(wp post list --post_type=post --posts_per_page=20 --field=ID --path="$WP_PATH"); do
    for j in $(seq 1 3); do
        wp comment create --comment_post_ID="$post_id" \
          --comment_content="Day la comment test so $j cho bai viet $post_id" \
          --comment_author="User Test" \
          --comment_author_email="test@example.com" \
          --comment_approved=1 \
          --path="$WP_PATH"
    done
done
echo "[OK] Tạo comments"

echo "=== Tạo dữ liệu test hoàn tất ==="
```

### Script kiểm tra sức khỏe website

```bash
#!/bin/bash
# health-check.sh - Kiểm tra sức khỏe website

WP_PATH="/var/www/html/wordpress"

echo "========================================="
echo "  KIỂM TRA SỨC KHỎE WORDPRESS"
echo "  $(date '+%Y-%m-%d %H:%M:%S')"
echo "========================================="
echo ""

# 1. Thông tin cơ bản
echo "--- THÔNG TIN CƠ BẢN ---"
echo "WordPress Version: $(wp core version --path="$WP_PATH")"
echo "PHP Version: $(php -v | head -1)"
echo "MySQL Version: $(wp db cli --path="$WP_PATH" <<< 'SELECT VERSION();' 2>/dev/null | tail -1)"
echo "Site URL: $(wp option get siteurl --path="$WP_PATH")"
echo "Home URL: $(wp option get home --path="$WP_PATH")"
echo ""

# 2. Cập nhật
echo "--- CẬP NHẬT ---"
wp core check-update --path="$WP_PATH"
echo ""
echo "Plugins cần cập nhật:"
wp plugin list --update=available --path="$WP_PATH"
echo ""
echo "Themes cần cập nhật:"
wp theme list --update=available --path="$WP_PATH"
echo ""

# 3. Plugin status
echo "--- PLUGINS ---"
TOTAL_PLUGINS=$(wp plugin list --format=count --path="$WP_PATH")
ACTIVE_PLUGINS=$(wp plugin list --status=active --format=count --path="$WP_PATH")
INACTIVE_PLUGINS=$(wp plugin list --status=inactive --format=count --path="$WP_PATH")
echo "Tổng: $TOTAL_PLUGINS | Active: $ACTIVE_PLUGINS | Inactive: $INACTIVE_PLUGINS"
echo ""

# 4. Database
echo "--- DATABASE ---"
wp db size --tables --path="$WP_PATH"
echo ""

# 5. Autoloaded options
echo "--- AUTOLOADED OPTIONS (top 10 by size) ---"
wp db query "SELECT option_name, LENGTH(option_value) as size FROM $(wp db prefix --path="$WP_PATH")options WHERE autoload='yes' ORDER BY size DESC LIMIT 10;" --path="$WP_PATH"
echo ""

# 6. Cron
echo "--- CRON EVENTS ---"
CRON_COUNT=$(wp cron event list --format=count --path="$WP_PATH")
echo "Tổng cron events: $CRON_COUNT"
echo ""

# 7. Transients
echo "--- TRANSIENTS ---"
TRANSIENT_COUNT=$(wp db query "SELECT COUNT(*) FROM $(wp db prefix --path="$WP_PATH")options WHERE option_name LIKE '_transient_%';" --path="$WP_PATH" 2>/dev/null | tail -1)
echo "Tổng transients: $TRANSIENT_COUNT"
echo ""

# 8. Users
echo "--- USERS ---"
wp user list --fields=ID,user_login,user_email,roles --path="$WP_PATH"
echo ""

# 9. Disk usage
echo "--- DISK USAGE ---"
echo "WordPress: $(du -sh "$WP_PATH" 2>/dev/null | cut -f1)"
echo "Uploads:   $(du -sh "$WP_PATH/wp-content/uploads" 2>/dev/null | cut -f1)"
echo "Plugins:   $(du -sh "$WP_PATH/wp-content/plugins" 2>/dev/null | cut -f1)"
echo "Themes:    $(du -sh "$WP_PATH/wp-content/themes" 2>/dev/null | cut -f1)"
echo ""

echo "========================================="
echo "  Kiểm tra hoàn tất"
echo "========================================="
```

### Script quản lý nhiều site (Multisite Management)

```bash
#!/bin/bash
# multisite-update.sh - Cập nhật nhiều site cùng lúc

# Danh sách các site (dùng wp-cli aliases)
SITES=("@staging" "@production" "@dev")

# Hoặc danh sách đường dẫn
# SITE_PATHS=(
#     "/var/www/site1"
#     "/var/www/site2"
#     "/var/www/site3"
# )

for site in "${SITES[@]}"; do
    echo ""
    echo "=== Cập nhật: $site ==="

    # Backup trước
    wp "$site" db export "backup-$(date +%Y%m%d).sql"

    # Cập nhật
    wp "$site" core update
    wp "$site" core update-db
    wp "$site" plugin update --all
    wp "$site" theme update --all

    # Flush
    wp "$site" cache flush
    wp "$site" rewrite flush

    echo "=== Hoàn tất: $site ==="
done

echo ""
echo "Tất cả site đã được cập nhật!"
```

### Lệnh hữu ích khác

```bash
# --- CẤU HÌNH ---

# Xem tất cả constants trong wp-config.php
wp config list

# Thêm constant
wp config set WP_MEMORY_LIMIT '256M'
wp config set WP_MAX_MEMORY_LIMIT '512M'

# Bật/tắt debug
wp config set WP_DEBUG true --raw
wp config set WP_DEBUG false --raw

# --- MAINTENANCE MODE ---

# Bật maintenance mode
wp maintenance-mode activate

# Tắt maintenance mode
wp maintenance-mode deactivate

# Kiểm tra trạng thái
wp maintenance-mode status

# --- EXPORT/IMPORT NỘI DUNG ---

# Export nội dung (XML)
wp export --dir=/tmp/exports

# Export chỉ post type cụ thể
wp export --post_type=product --dir=/tmp/exports

# Import nội dung
wp import /tmp/exports/export.xml --authors=create

# --- WIDGET ---

# Xem danh sách widget areas
wp widget list sidebar-1

# Thêm widget
wp widget add text sidebar-1 --title="Liên Hệ" --text="SDT: 0123456789"

# --- SIDEBAR ---

# Xem sidebars
wp sidebar list

# --- SUPER ADMIN (Multisite) ---

wp super-admin list
wp super-admin add username
wp super-admin remove username

# --- PACKAGE MANAGEMENT ---

# Cài đặt WP-CLI package bổ sung
wp package install wp-cli/doctor-command
wp package install aaemnnosttv/wp-cli-login-command

# Xem packages đã cài
wp package list

# Chạy doctor check
wp doctor check --all
```
