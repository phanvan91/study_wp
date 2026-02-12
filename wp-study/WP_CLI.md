# WP-CLI - Huong Dan Su Dung Chi Tiet

## Muc luc

1. [Gioi thieu WP-CLI](#1-gioi-thieu-wp-cli)
2. [Cac lenh co ban](#2-cac-lenh-co-ban)
3. [wp scaffold - Tao plugin/theme skeleton](#3-wp-scaffold---tao-plugintheme-skeleton)
4. [wp search-replace - Thay doi domain](#4-wp-search-replace---thay-doi-domain)
5. [wp db export/import - Backup database](#5-wp-db-exportimport---backup-database)
6. [wp cron - Quan ly cron jobs](#6-wp-cron---quan-ly-cron-jobs)
7. [wp media - Quan ly media](#7-wp-media---quan-ly-media)
8. [wp rewrite - Quan ly rewrite rules](#8-wp-rewrite---quan-ly-rewrite-rules)
9. [wp transient - Quan ly transients](#9-wp-transient---quan-ly-transients)
10. [Tao Custom WP-CLI Command](#10-tao-custom-wp-cli-command)
11. [wp eval va wp eval-file](#11-wp-eval-va-wp-eval-file)
12. [wp shell](#12-wp-shell)
13. [Automation scripts voi WP-CLI](#13-automation-scripts-voi-wp-cli)

---

## 1. Gioi thieu WP-CLI

### WP-CLI la gi?

WP-CLI (WordPress Command Line Interface) la cong cu dong lenh chinh thuc de quan ly WordPress. Thay vi thao tac tren giao dien web, ban co the thuc hien hau het moi tac vu qua terminal: cai dat plugin, cap nhat core, quan ly user, backup database, va nhieu viec khac.

### Loi ich cua WP-CLI

- Nhanh hon nhieu so voi thao tac tren giao dien web
- Co the tu dong hoa (automation) bang script
- Quan ly nhieu site cung luc
- Huu ich cho moi truong khong co giao dien (server headless)
- Debug va troubleshoot de dang hon

### Cai dat WP-CLI

```bash
# Tai file phar
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar

# Kiem tra hoat dong
php wp-cli.phar --info

# Chuyen thanh lenh toan cuc
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp

# Kiem tra phien ban
wp --version
# WP-CLI 2.x.x
```

### Cai dat tren cac moi truong

```bash
# macOS voi Homebrew
brew install wp-cli

# Ubuntu/Debian
sudo apt-get install wp-cli

# Composer
composer global require wp-cli/wp-cli-bundle

# Docker
docker run --rm -v $(pwd):/var/www/html wordpress:cli wp --info
```

### Cau hinh WP-CLI

```bash
# Tao file cau hinh toan cuc
# ~/.wp-cli/config.yml

# Tao file cau hinh cho project
# Dat file wp-cli.yml hoac wp-cli.local.yml trong thu muc WordPress
```

```yaml
# wp-cli.yml - File cau hinh cho project
path: /var/www/html/wordpress
url: https://example.com
user: admin
color: true
debug: false
quiet: false

# Cau hinh cho tung lenh cu the
core update:
  locale: vi

plugin install:
  activate: true

# Alias cho cac moi truong
@staging:
  ssh: user@staging.example.com/var/www/staging
  url: https://staging.example.com

@production:
  ssh: user@prod.example.com/var/www/production
  url: https://example.com
```

### Su dung alias

```bash
# Chay lenh tren moi truong staging
wp @staging plugin list

# Chay lenh tren moi truong production
wp @production db export

# Chay tren tat ca alias
wp @all plugin update --all
```

---

## 2. Cac lenh co ban

### wp core - Quan ly WordPress Core

```bash
# --- CAI DAT ---

# Tai WordPress moi nhat
wp core download

# Tai phien ban cu the
wp core download --version=6.4.2

# Tai phien ban tieng Viet
wp core download --locale=vi

# Tao file wp-config.php
wp config create --dbname=mydb --dbuser=root --dbpass=password --dbhost=localhost

# Cai dat WordPress
wp core install \
  --url="http://localhost/mysite" \
  --title="My WordPress Site" \
  --admin_user="admin" \
  --admin_password="securepassword123" \
  --admin_email="admin@example.com"

# Cai dat multisite
wp core multisite-install \
  --url="http://localhost/multisite" \
  --title="My Network" \
  --admin_user="admin" \
  --admin_password="securepassword123" \
  --admin_email="admin@example.com"

# --- CAP NHAT ---

# Kiem tra phien ban hien tai
wp core version

# Kiem tra ban cap nhat
wp core check-update

# Cap nhat len phien ban moi nhat
wp core update

# Cap nhat len phien ban cu the
wp core update --version=6.4.2

# Cap nhat database sau khi cap nhat core
wp core update-db

# --- THONG TIN ---

# Kiem tra toan bo thong tin
wp core version --extra
```

### wp plugin - Quan ly Plugins

```bash
# --- DANH SACH ---

# Xem tat ca plugin
wp plugin list

# Chi xem plugin dang active
wp plugin list --status=active

# Chi xem plugin can update
wp plugin list --update=available

# Xuat danh sach ra CSV
wp plugin list --format=csv > plugins.csv

# Xuat ra JSON
wp plugin list --format=json

# --- CAI DAT ---

# Cai dat plugin tu WordPress.org
wp plugin install woocommerce

# Cai dat va kich hoat ngay
wp plugin install woocommerce --activate

# Cai dat phien ban cu the
wp plugin install woocommerce --version=8.0.0

# Cai dat tu file zip
wp plugin install /path/to/plugin.zip

# Cai dat tu URL
wp plugin install https://example.com/plugin.zip

# Cai dat nhieu plugin cung luc
wp plugin install woocommerce contact-form-7 yoast-seo --activate

# --- KICH HOAT / VO HIEU HOA ---

# Kich hoat plugin
wp plugin activate woocommerce

# Kich hoat tat ca plugin
wp plugin activate --all

# Vo hieu hoa plugin
wp plugin deactivate woocommerce

# Vo hieu hoa tat ca plugin (huu ich khi debug)
wp plugin deactivate --all

# --- CAP NHAT ---

# Cap nhat mot plugin
wp plugin update woocommerce

# Cap nhat tat ca plugin
wp plugin update --all

# Cap nhat plugin len phien ban cu the (rollback)
wp plugin update woocommerce --version=8.0.0

# --- XOA ---

# Xoa plugin (phai deactivate truoc)
wp plugin deactivate woocommerce && wp plugin delete woocommerce

# Xoa va uninstall
wp plugin uninstall woocommerce

# --- THONG TIN ---

# Xem thong tin chi tiet plugin
wp plugin get woocommerce

# Tim kiem plugin tren WordPress.org
wp plugin search "contact form"

# Kiem tra trang thai
wp plugin is-active woocommerce
echo $?  # 0 = active, 1 = inactive

# Xem duong dan plugin
wp plugin path woocommerce
```

### wp theme - Quan ly Themes

```bash
# Xem danh sach theme
wp theme list

# Cai dat theme
wp theme install flavor

# Cai dat va kich hoat
wp theme install flavor --activate

# Kich hoat theme
wp theme activate flavor

# Cap nhat theme
wp theme update flavor
wp theme update --all

# Xoa theme
wp theme delete flavor

# Kiem tra theme dang active
wp theme status

# Lay thong tin theme
wp theme get flavor

# Tao child theme
wp scaffold child-theme flavor-child --parent_theme=flavor --activate
```

### wp post - Quan ly Bai viet

```bash
# --- DANH SACH ---

# Xem danh sach bai viet
wp post list

# Loc theo post type
wp post list --post_type=product

# Loc theo trang thai
wp post list --post_status=draft

# Loc theo tac gia
wp post list --author=1

# Gioi han so luong va dinh dang
wp post list --post_type=post --posts_per_page=5 --format=table

# Lay chi ID
wp post list --post_type=post --field=ID

# --- TAO ---

# Tao bai viet moi
wp post create --post_type=post --post_title="Bai Viet Moi" --post_status=publish

# Tao tu file
wp post create ./content.txt --post_title="Bai Viet Tu File" --post_status=draft

# Tao voi nhieu tham so
wp post create \
  --post_type=product \
  --post_title="San Pham Moi" \
  --post_content="Mo ta san pham" \
  --post_status=publish \
  --post_author=1 \
  --meta_input='{"_product_price":"500000","_product_sku":"SP001"}'

# Tao nhieu bai viet nhanh (testing)
for i in $(seq 1 10); do
  wp post create --post_type=post --post_title="Bai viet test $i" --post_status=publish
done

# --- SUA ---

# Sua tieu de
wp post update 123 --post_title="Tieu De Moi"

# Sua trang thai
wp post update 123 --post_status=draft

# Sua noi dung tu file
wp post update 123 ./new-content.txt

# --- XOA ---

# Chuyen vao thung rac
wp post delete 123

# Xoa vinh vien
wp post delete 123 --force

# Xoa tat ca bai viet nhap
wp post delete $(wp post list --post_status=draft --field=ID) --force

# Xoa tat ca bai viet cua post type
wp post delete $(wp post list --post_type=product --field=ID) --force

# --- META ---

# Xem meta
wp post meta list 123

# Lay gia tri meta
wp post meta get 123 _product_price

# Them/Cap nhat meta
wp post meta update 123 _product_price 500000

# Xoa meta
wp post meta delete 123 _product_price

# --- KHAC ---

# Tao noi dung test (lorem ipsum)
wp post generate --count=20 --post_type=post --post_status=publish

# Lay URL cua bai viet
wp post url 123
```

### wp user - Quan ly Nguoi dung

```bash
# Xem danh sach user
wp user list

# Xem theo role
wp user list --role=editor

# Tao user moi
wp user create john john@example.com --role=author --user_pass=password123

# Tao admin
wp user create newadmin admin@example.com --role=administrator --user_pass=StrongPass123!

# Cap nhat user
wp user update 1 --user_email=new@example.com

# Doi mat khau
wp user update 1 --user_pass=NewPassword123!

# Xoa user
wp user delete 2

# Xoa user va chuyen bai viet cho user khac
wp user delete 2 --reassign=1

# Them/xoa role
wp user add-role 1 editor
wp user remove-role 1 editor

# Them/xoa capability
wp user add-cap 1 manage_options
wp user remove-cap 1 manage_options

# Xem thong tin user
wp user get 1

# Lay user meta
wp user meta list 1
wp user meta get 1 nickname

# Dang nhap tu dong (tao URL dang nhap)
# Can plugin "wp-cli-login-command"
wp login create 1 --launch
```

### wp option - Quan ly Options

```bash
# Lay gia tri option
wp option get blogname
wp option get siteurl
wp option get home
wp option get permalink_structure

# Lay gia tri option dang JSON
wp option get active_plugins --format=json

# Cap nhat option
wp option update blogname "Ten Website Moi"
wp option update blogdescription "Mo ta moi"
wp option update permalink_structure "/%postname%/"

# Them option moi
wp option add my_custom_option "gia tri" --autoload=yes

# Xoa option
wp option delete my_custom_option

# Tim kiem option
wp option list --search="*woocommerce*"

# Xem tat ca options autoloaded
wp option list --autoload=on

# Cap nhat autoload
wp option update my_option "value" --autoload=no

# Lay danh sach autoloaded options va kich thuoc
wp option list --autoload=on --format=csv | awk -F',' '{print length($2), $1}' | sort -rn | head -20
```

### wp db - Quan ly Database

```bash
# Kiem tra ket noi database
wp db check

# Mo MySQL CLI
wp db cli

# Chay SQL query
wp db query "SELECT * FROM wp_options WHERE option_name = 'siteurl'"

# Xem kich thuoc database
wp db size
wp db size --tables  # Chi tiet tung bang

# Xem cau truc bang
wp db columns wp_posts

# Toi uu database (OPTIMIZE TABLE)
wp db optimize

# Sua chua database (REPAIR TABLE)
wp db repair

# Xem prefix
wp db prefix

# Danh sach bang
wp db tables

# Tim kiem trong database
wp db search "old-domain.com"

# Export/Import (xem phan 5)
```

---

## 3. wp scaffold - Tao plugin/theme skeleton

### Tao Plugin

```bash
# Tao plugin co ban
wp scaffold plugin my-custom-plugin

# Tao voi cac tham so
wp scaffold plugin my-custom-plugin \
  --plugin_name="My Custom Plugin" \
  --plugin_description="Mo ta plugin" \
  --plugin_author="Tac Gia" \
  --plugin_author_uri="https://example.com" \
  --plugin_uri="https://example.com/plugin"

# Ket qua tao ra:
# wp-content/plugins/my-custom-plugin/
#   |-- my-custom-plugin.php      (File chinh)
#   |-- readme.txt                (Mo ta plugin)
#   |-- .editorconfig
#   |-- .phpcs.xml.dist
#   |-- Gruntfile.js
#   |-- package.json
#   |-- phpunit.xml.dist
#   |-- tests/
#   |     |-- bootstrap.php
#   |     |-- test-sample.php
```

### Tao Theme

```bash
# Tao theme co ban
wp scaffold _s my-theme --theme_name="My Theme"

# _s (Underscores) la starter theme cua Automattic
# Ket qua:
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

### Tao Child Theme

```bash
wp scaffold child-theme flavor-child \
  --parent_theme=flavor \
  --theme_name="Flavor Child" \
  --author="Dev Team" \
  --activate

# Ket qua:
# wp-content/themes/flavor-child/
#   |-- style.css
#   |-- functions.php
```

### Tao Post Type va Taxonomy

```bash
# Tao code dang ky post type
wp scaffold post-type product \
  --label="San Pham" \
  --textdomain="my-plugin" \
  --plugin=my-custom-plugin

# Tao code dang ky taxonomy
wp scaffold taxonomy product_category \
  --post_types=product \
  --label="Danh Muc San Pham" \
  --textdomain="my-plugin" \
  --plugin=my-custom-plugin
```

### Tao PHPUnit Tests

```bash
# Tao test cho plugin
wp scaffold plugin-tests my-custom-plugin

# Ket qua:
# tests/
#   |-- bootstrap.php
#   |-- test-sample.php
# phpunit.xml.dist
# bin/
#   |-- install-wp-tests.sh

# Chay cai dat test environment
cd wp-content/plugins/my-custom-plugin
bash bin/install-wp-tests.sh wordpress_test root '' localhost latest
```

### Tao Block

```bash
# Tao Gutenberg block
wp scaffold block my-block --title="My Custom Block" --plugin=my-custom-plugin

# Ket qua:
# wp-content/plugins/my-custom-plugin/
#   |-- blocks/
#   |     |-- my-block/
#   |           |-- index.js
#   |           |-- editor.css
#   |           |-- style.css
```

---

## 4. wp search-replace - Thay doi domain

### Cu phap co ban

```bash
wp search-replace <old-string> <new-string> [table...] [--dry-run] [--precise] [--all-tables]
```

### Thay doi domain (Migration)

```bash
# LUON chay --dry-run truoc de kiem tra
wp search-replace 'http://old-domain.com' 'https://new-domain.com' --dry-run

# Ket qua:
# +------------------+-----------------------+--------------+------+
# | Table            | Column                | Replacements | Type |
# +------------------+-----------------------+--------------+------+
# | wp_options       | option_value          | 15           | SQL  |
# | wp_posts         | post_content          | 123          | SQL  |
# | wp_posts         | guid                  | 456          | SQL  |
# | wp_postmeta      | meta_value            | 78           | SQL  |
# +------------------+-----------------------+--------------+------+

# Chay that su
wp search-replace 'http://old-domain.com' 'https://new-domain.com'

# Thay doi ca trong toan bo cac bang (bao gom bang cua plugin)
wp search-replace 'http://old-domain.com' 'https://new-domain.com' --all-tables

# Bo qua cot guid (nen lam khi migration)
wp search-replace 'http://old-domain.com' 'https://new-domain.com' --skip-columns=guid

# Chi thay doi trong bang cu the
wp search-replace 'http://old-domain.com' 'https://new-domain.com' wp_options wp_posts wp_postmeta
```

### Chuyen tu HTTP sang HTTPS

```bash
# Thay doi URL
wp search-replace 'http://example.com' 'https://example.com' --all-tables --dry-run
wp search-replace 'http://example.com' 'https://example.com' --all-tables

# Cap nhat siteurl va home
wp option update siteurl 'https://example.com'
wp option update home 'https://example.com'
```

### Thay doi prefix trong noi dung

```bash
# Thay doi duong dan upload
wp search-replace '/wp-content/uploads/2023/' '/wp-content/uploads/2024/' wp_posts

# Thay doi email domain
wp search-replace '@old-company.com' '@new-company.com' --all-tables --dry-run
```

### Tuy chon quan trong

```bash
# --precise: Xu ly chinh xac du lieu serialized (cham hon nhung an toan hon)
wp search-replace 'old' 'new' --precise

# --regex: Su dung regular expression
wp search-replace 'http://(www\.)?old-domain\.com' 'https://new-domain.com' --regex

# --log: Ghi log thay doi
wp search-replace 'old' 'new' --log=search-replace.log

# --export: Xuat ket qua ra file SQL thay vi thay doi truc tiep
wp search-replace 'old-domain.com' 'new-domain.com' --export=migration.sql

# --network: Chay tren multisite
wp search-replace 'old-domain.com' 'new-domain.com' --network
```

### Quy trinh Migration day du

```bash
#!/bin/bash
# Script migration tu local sang production

OLD_URL="http://localhost/mysite"
NEW_URL="https://www.mysite.com"

echo "=== Bat dau migration ==="

# 1. Export database
wp db export backup-before-migration.sql
echo "Da backup database"

# 2. Dry run truoc
echo "Kiem tra thay doi:"
wp search-replace "$OLD_URL" "$NEW_URL" --all-tables --dry-run

# 3. Thuc hien thay doi
read -p "Tiep tuc? (y/n) " confirm
if [ "$confirm" = "y" ]; then
    wp search-replace "$OLD_URL" "$NEW_URL" --all-tables --skip-columns=guid
    echo "Da thay doi URL"

    # 4. Cap nhat options
    wp option update siteurl "$NEW_URL"
    wp option update home "$NEW_URL"

    # 5. Flush cache va rewrite
    wp cache flush
    wp rewrite flush

    echo "=== Migration hoan tat ==="
else
    echo "Da huy"
fi
```

---

## 5. wp db export/import - Backup database

### Export (Backup)

```bash
# Export toan bo database
wp db export

# Export voi ten file tu dat
wp db export backup-2024-01-15.sql

# Export voi nen gzip
wp db export - | gzip > backup-2024-01-15.sql.gz

# Export chi mot so bang
wp db export --tables=wp_posts,wp_postmeta,wp_options

# Loai tru bang
wp db export --exclude_tables=wp_comments,wp_commentmeta

# Export voi tuy chon mysqldump
wp db export --add-drop-table --single-transaction

# Export voi thoi gian trong ten file
wp db export "backup-$(date +%Y%m%d-%H%M%S).sql"
```

### Import

```bash
# Import tu file SQL
wp db import backup.sql

# Import tu file nen
gunzip < backup.sql.gz | wp db import -

# Import va skip errors
wp db import backup.sql --skip-optimization
```

### Reset Database

```bash
# Xoa toan bo database va tao lai
wp db reset --yes

# Sau khi reset, can cai dat lai WordPress
wp core install \
  --url="http://localhost/mysite" \
  --title="My Site" \
  --admin_user="admin" \
  --admin_password="password" \
  --admin_email="admin@example.com"
```

### Script Backup tu dong

```bash
#!/bin/bash
# backup-daily.sh - Script backup hang ngay

# Cau hinh
WP_PATH="/var/www/html/wordpress"
BACKUP_DIR="/var/backups/wordpress"
KEEP_DAYS=30
DATE=$(date +%Y%m%d-%H%M%S)

# Tao thu muc backup neu chua co
mkdir -p "$BACKUP_DIR"

# Backup database
wp db export "$BACKUP_DIR/db-$DATE.sql" --path="$WP_PATH"

# Nen file
gzip "$BACKUP_DIR/db-$DATE.sql"

# Backup uploads
tar -czf "$BACKUP_DIR/uploads-$DATE.tar.gz" -C "$WP_PATH/wp-content" uploads

echo "Backup hoan tat: $DATE"

# Xoa backup cu hon KEEP_DAYS ngay
find "$BACKUP_DIR" -name "db-*.sql.gz" -mtime +$KEEP_DAYS -delete
find "$BACKUP_DIR" -name "uploads-*.tar.gz" -mtime +$KEEP_DAYS -delete

echo "Da xoa backup cu hon $KEEP_DAYS ngay"
```

```bash
# Them vao crontab de chay hang ngay luc 2h sang
# crontab -e
# 0 2 * * * /path/to/backup-daily.sh >> /var/log/wp-backup.log 2>&1
```

---

## 6. wp cron - Quan ly cron jobs

### Xem cron events

```bash
# Xem tat ca cron events
wp cron event list

# Ket qua:
# +---------------------------+---------------------+-----------------------+------------+
# | hook                      | next_run_gmt        | next_run_relative     | recurrence |
# +---------------------------+---------------------+-----------------------+------------+
# | wp_version_check          | 2024-01-15 10:00:00 | 2 hours 30 minutes    | twicedaily |
# | wp_update_plugins         | 2024-01-15 10:00:00 | 2 hours 30 minutes    | twicedaily |
# | wp_scheduled_delete       | 2024-01-15 12:00:00 | 4 hours 30 minutes    | daily      |
# +---------------------------+---------------------+-----------------------+------------+

# Xem chi tiet
wp cron event list --format=json
```

### Chay cron events

```bash
# Chay tat ca cron events da den han
wp cron event run --due-now

# Chay mot event cu the
wp cron event run wp_version_check

# Chay tat ca events (ke ca chua den han)
wp cron event run --all
```

### Quan ly cron schedules

```bash
# Xem cac schedule da dang ky
wp cron schedule list

# Ket qua:
# +------------+----------+-------------------+
# | name       | interval | display           |
# +------------+----------+-------------------+
# | hourly     | 3600     | Once Hourly       |
# | twicedaily | 43200    | Twice Daily       |
# | daily      | 86400    | Once Daily        |
# | weekly     | 604800   | Once Weekly       |
# +------------+----------+-------------------+
```

### Xoa va them cron events

```bash
# Xoa mot event
wp cron event delete wp_version_check

# Tao event moi
wp cron event schedule my_custom_hook now hourly

# Kiem tra WP-Cron co hoat dong khong
wp cron test
```

### Debug Cron

```bash
# Kiem tra DISABLE_WP_CRON
wp config get DISABLE_WP_CRON

# Bat/Tat WP-Cron
wp config set DISABLE_WP_CRON true --raw
wp config set DISABLE_WP_CRON false --raw

# Khi DISABLE_WP_CRON = true, dung system cron:
# crontab -e
# */5 * * * * cd /var/www/html && wp cron event run --due-now > /dev/null 2>&1

# Hoac dung wget/curl:
# */5 * * * * wget -q -O - https://example.com/wp-cron.php > /dev/null 2>&1
```

---

## 7. wp media - Quan ly media

### Regenerate thumbnails

```bash
# Tao lai tat ca thumbnails (khi doi theme hoac them image size moi)
wp media regenerate

# Tao lai cho hinh cu the
wp media regenerate 123 456

# Chi tao lai nhung size bi thieu
wp media regenerate --only-missing

# Bo qua xac nhan
wp media regenerate --yes

# Chi tao lai size cu the
wp media regenerate --image_size=thumbnail
```

### Import media

```bash
# Import tu URL
wp media import https://example.com/image.jpg

# Import tu file local
wp media import /path/to/image.jpg

# Import va gan cho bai viet
wp media import https://example.com/image.jpg --post_id=123

# Import va dat lam featured image
wp media import https://example.com/image.jpg --post_id=123 --featured_image

# Import voi tieu de tuy chinh
wp media import /path/to/image.jpg --title="Anh San Pham"

# Import nhieu file
wp media import /path/to/images/*.jpg
```

### Xem thong tin media

```bash
# Xem danh sach media
wp post list --post_type=attachment

# Lay thong tin chi tiet
wp post get 123

# Lay URL media
wp post list --post_type=attachment --field=guid

# Xem cac image sizes da dang ky
wp media image-size
```

### Xoa media khong su dung

```bash
# Tim media khong duoc gan voi bai viet nao
wp post list --post_type=attachment --post_parent=0 --format=ids

# Xoa media khong su dung
wp post delete $(wp post list --post_type=attachment --post_parent=0 --format=ids) --force
```

---

## 8. wp rewrite - Quan ly rewrite rules

### Xem va cap nhat rewrite rules

```bash
# Xem tat ca rewrite rules
wp rewrite list

# Xem chi tiet voi match
wp rewrite list --match="san-pham/ao-thun"

# Ket qua:
# +------------------------------------------+-------------------------------------------+--------+
# | match                                    | query                                     | source |
# +------------------------------------------+-------------------------------------------+--------+
# | san-pham/([^/]+)/?$                      | index.php?product=$matches[1]             | post   |
# +------------------------------------------+-------------------------------------------+--------+

# Flush (lam moi) rewrite rules
wp rewrite flush

# Flush voi hard (xoa .htaccess va tao lai)
wp rewrite flush --hard

# Xem cau truc permalink hien tai
wp option get permalink_structure

# Thay doi cau truc permalink
wp rewrite structure '/%postname%/'
wp rewrite structure '/%category%/%postname%/'

# Them rewrite rule
# (Thuong lam trong code PHP, nhung co the test bang wp eval)
wp eval "add_rewrite_rule('custom-page/?$', 'index.php?pagename=my-page', 'top'); flush_rewrite_rules();"
```

---

## 9. wp transient - Quan ly transients

### Transients la gi?

Transients la cach luu cache tam thoi trong database (hoac object cache neu co). Co thoi gian het han (expiration).

### Cac lenh co ban

```bash
# Lay gia tri transient
wp transient get my_transient

# Dat gia tri transient (het han sau 3600 giay = 1 gio)
wp transient set my_transient "gia tri" 3600

# Dat transient khong het han
wp transient set my_transient "gia tri" 0

# Xoa transient cu the
wp transient delete my_transient

# Xoa tat ca transients
wp transient delete --all

# Xoa chi transients da het han
wp transient delete --expired

# Lay loai transient (transient hoac site-transient)
wp transient type my_transient

# --- MULTISITE ---

# Lay site transient (cho multisite)
wp transient get my_transient --network

# Dat site transient
wp transient set my_transient "value" 3600 --network

# Xoa site transient
wp transient delete my_transient --network
```

### Vi du thuc te

```bash
# Kiem tra transient co ton tai khong
wp transient get featured_products
# Neu tra ve empty => chua co hoac da het han

# Dat transient test
wp transient set test_cache '{"products":[1,2,3]}' 3600

# Xem tat ca transients trong database
wp db query "SELECT option_name, LENGTH(option_value) as size FROM wp_options WHERE option_name LIKE '_transient_%' ORDER BY size DESC LIMIT 20;"

# Xoa transients lon
wp db query "DELETE FROM wp_options WHERE option_name LIKE '_transient_%' AND option_name NOT LIKE '_transient_timeout_%';"
```

---

## 10. Tao Custom WP-CLI Command

### Command don gian

```php
<?php
/**
 * Plugin Name: My CLI Commands
 * Description: Custom WP-CLI commands
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Chi dang ky khi chay trong WP-CLI
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
    return;
}

/**
 * Quan ly san pham tu command line.
 */
class My_Product_CLI_Command {

    /**
     * Xem danh sach san pham.
     *
     * ## OPTIONS
     *
     * [--count=<number>]
     * : So luong san pham hien thi. Mac dinh: 10
     *
     * [--status=<status>]
     * : Loc theo trang thai san pham (in_stock, out_of_stock, on_sale)
     *
     * [--format=<format>]
     * : Dinh dang output. Mac dinh: table
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
     *     # Xem 5 san pham dau tien
     *     wp product list --count=5
     *
     *     # Xem san pham con hang, dinh dang JSON
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

        // Loc theo trang thai
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
            WP_CLI::warning( 'Khong tim thay san pham nao.' );
            return;
        }

        // Hien thi bang WP_CLI\Utils\format_items
        WP_CLI\Utils\format_items( $format, $items, array( 'ID', 'Title', 'Price', 'SKU', 'Status', 'Date' ) );

        WP_CLI::success( sprintf( 'Tim thay %d san pham.', count( $items ) ) );
    }

    /**
     * Tao san pham moi.
     *
     * ## OPTIONS
     *
     * <title>
     * : Ten san pham
     *
     * --price=<price>
     * : Gia san pham (VND)
     *
     * [--sku=<sku>]
     * : Ma san pham
     *
     * [--category=<category>]
     * : Slug danh muc san pham
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

        // Tao post
        $post_id = wp_insert_post( array(
            'post_type'   => 'product',
            'post_title'  => $title,
            'post_status' => 'publish',
        ) );

        if ( is_wp_error( $post_id ) ) {
            WP_CLI::error( 'Khong the tao san pham: ' . $post_id->get_error_message() );
        }

        // Luu meta
        update_post_meta( $post_id, '_product_price', absint( $price ) );
        update_post_meta( $post_id, '_product_status', 'in_stock' );

        if ( $sku ) {
            update_post_meta( $post_id, '_product_sku', sanitize_text_field( $sku ) );
        }

        // Gan taxonomy
        if ( $category ) {
            wp_set_object_terms( $post_id, $category, 'product_category' );
        }

        WP_CLI::success( sprintf( 'Da tao san pham "%s" (ID: %d)', $title, $post_id ) );
    }

    /**
     * Cap nhat gia san pham.
     *
     * ## OPTIONS
     *
     * <id>
     * : ID san pham
     *
     * <price>
     * : Gia moi (VND)
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
            WP_CLI::error( "Khong tim thay san pham voi ID: $post_id" );
        }

        $old_price = get_post_meta( $post_id, '_product_price', true );
        update_post_meta( $post_id, '_product_price', $price );

        WP_CLI::success( sprintf(
            'Da cap nhat gia san pham "%s": %s -> %s VND',
            $post->post_title,
            number_format( $old_price ),
            number_format( $price )
        ) );
    }

    /**
     * Xoa san pham.
     *
     * ## OPTIONS
     *
     * <id>...
     * : Mot hoac nhieu ID san pham can xoa
     *
     * [--force]
     * : Xoa vinh vien (khong qua thung rac)
     *
     * ## EXAMPLES
     *
     *     # Xoa 1 san pham
     *     wp product delete 123
     *
     *     # Xoa nhieu san pham vinh vien
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
                WP_CLI::warning( "Khong tim thay san pham voi ID: $post_id" );
                continue;
            }

            $title = $post->post_title;

            if ( $force ) {
                wp_delete_post( $post_id, true );
                WP_CLI::success( "Da xoa vinh vien: \"$title\" (ID: $post_id)" );
            } else {
                wp_trash_post( $post_id );
                WP_CLI::success( "Da chuyen vao thung rac: \"$title\" (ID: $post_id)" );
            }
        }
    }

    /**
     * Import san pham tu file CSV.
     *
     * ## OPTIONS
     *
     * <file>
     * : Duong dan file CSV
     *
     * [--dry-run]
     * : Chi hien thi, khong thuc su import
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
            WP_CLI::error( "File khong ton tai: $file" );
        }

        $handle = fopen( $file, 'r' );
        $header = fgetcsv( $handle ); // Dong tieu de
        $count  = 0;

        // Progress bar
        $total = count( file( $file ) ) - 1; // Tru dong header
        $progress = WP_CLI\Utils\make_progress_bar( 'Dang import san pham...', $total );

        while ( ( $row = fgetcsv( $handle ) ) !== false ) {
            $data = array_combine( $header, $row );

            if ( $dry_run ) {
                WP_CLI::log( sprintf( '[DRY RUN] Se tao: %s - Gia: %s', $data['title'], $data['price'] ) );
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
            WP_CLI::success( "Dry run hoan tat. Se import $total san pham." );
        } else {
            WP_CLI::success( "Da import $count/$total san pham." );
        }
    }
}

// Dang ky command
WP_CLI::add_command( 'product', 'My_Product_CLI_Command' );
```

### Su dung command

```bash
# Xem danh sach sub-commands
wp product --help

# Xem help cua tung sub-command
wp product list --help
wp product create --help

# Su dung
wp product list --count=5 --format=table
wp product create "Ao Khoac" --price=500000 --sku=AK001
wp product update-price 123 600000
wp product delete 123 --force
wp product import products.csv --dry-run
```

---

## 11. wp eval va wp eval-file

### wp eval - Chay code PHP truc tiep

```bash
# Chay 1 dong code PHP
wp eval 'echo get_bloginfo("name");'

# Lay thong tin
wp eval 'echo home_url();'
wp eval 'echo wp_get_theme()->get("Name");'

# Lay so luong bai viet
wp eval '$count = wp_count_posts("product"); echo "Published: " . $count->publish;'

# Kiem tra function ton tai
wp eval 'echo function_exists("wc_get_products") ? "WooCommerce active" : "WooCommerce inactive";'

# Chay query
wp eval '
$users = get_users(array("role" => "administrator"));
foreach ($users as $user) {
    echo $user->user_login . " - " . $user->user_email . "\n";
}
'

# Xoa transients cu
wp eval '
global $wpdb;
$count = $wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE \"_transient_timeout_%\"
     AND option_value < UNIX_TIMESTAMP()"
);
echo "Da xoa $count transients het han.";
'

# Cap nhat meta hang loat
wp eval '
$products = get_posts(array(
    "post_type" => "product",
    "posts_per_page" => -1,
    "fields" => "ids",
));
foreach ($products as $id) {
    $price = get_post_meta($id, "_product_price", true);
    if ($price) {
        $new_price = intval($price * 1.1); // Tang 10%
        update_post_meta($id, "_product_price", $new_price);
    }
}
echo "Da cap nhat " . count($products) . " san pham.";
'
```

### wp eval-file - Chay file PHP

```bash
# Chay file PHP
wp eval-file maintenance.php

# Truyen tham so
wp eval-file process.php -- --type=product --limit=100
```

```php
<?php
// maintenance.php - File bao tri
// Chay: wp eval-file maintenance.php

echo "=== Bat dau bao tri ===\n";

// 1. Xoa bai viet nhap cu hon 30 ngay
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
echo "Xoa " . count( $old_drafts ) . " bai nhap cu.\n";

// 2. Xoa revisions
global $wpdb;
$revisions = $wpdb->query(
    "DELETE FROM {$wpdb->posts} WHERE post_type = 'revision'"
);
echo "Xoa $revisions revisions.\n";

// 3. Xoa orphaned postmeta
$orphaned = $wpdb->query(
    "DELETE pm FROM {$wpdb->postmeta} pm
     LEFT JOIN {$wpdb->posts} p ON p.ID = pm.post_id
     WHERE p.ID IS NULL"
);
echo "Xoa $orphaned orphaned meta.\n";

// 4. Xoa transients het han
$expired = $wpdb->query(
    "DELETE FROM {$wpdb->options}
     WHERE option_name LIKE '_transient_timeout_%'
     AND option_value < UNIX_TIMESTAMP()"
);
echo "Xoa $expired expired transients.\n";

echo "=== Bao tri hoan tat ===\n";
```

---

## 12. wp shell

### WP Shell - Interactive PHP Shell

```bash
# Mo interactive shell
wp shell

# Trong shell, co the chay code PHP truc tiep:
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
# wp> exit  // Thoat shell
```

### Vi du su dung wp shell

```bash
wp shell <<'PHP'
// Kiem tra so luong post theo tung post type
$post_types = get_post_types( array( 'public' => true ), 'names' );
foreach ( $post_types as $pt ) {
    $count = wp_count_posts( $pt );
    echo "$pt: {$count->publish} published\n";
}
PHP
```

---

## 13. Automation scripts voi WP-CLI

### Script cai dat WordPress tu dong

```bash
#!/bin/bash
# setup-wordpress.sh - Cai dat WordPress tu dong

# Cau hinh
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

echo "=== Bat dau cai dat WordPress ==="

# 1. Tao thu muc
mkdir -p "$WP_PATH"
cd "$WP_PATH"

# 2. Tai WordPress
wp core download --locale=vi --path="$WP_PATH"
echo "[OK] Da tai WordPress"

# 3. Tao database
mysql -u"$DB_USER" -p"$DB_PASS" -e "CREATE DATABASE IF NOT EXISTS $DB_NAME CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
echo "[OK] Da tao database"

# 4. Tao wp-config.php
wp config create \
  --dbname="$DB_NAME" \
  --dbuser="$DB_USER" \
  --dbpass="$DB_PASS" \
  --dbhost="$DB_HOST" \
  --dbcharset="utf8mb4" \
  --path="$WP_PATH"

# Them cau hinh bo sung
wp config set WP_DEBUG true --raw --path="$WP_PATH"
wp config set WP_DEBUG_LOG true --raw --path="$WP_PATH"
wp config set WP_DEBUG_DISPLAY false --raw --path="$WP_PATH"
wp config set DISALLOW_FILE_EDIT true --raw --path="$WP_PATH"
echo "[OK] Da tao wp-config.php"

# 5. Cai dat WordPress
wp core install \
  --url="$SITE_URL" \
  --title="$SITE_TITLE" \
  --admin_user="$ADMIN_USER" \
  --admin_password="$ADMIN_PASS" \
  --admin_email="$ADMIN_EMAIL" \
  --path="$WP_PATH"
echo "[OK] Da cai dat WordPress"

# 6. Cau hinh co ban
wp option update permalink_structure '/%postname%/' --path="$WP_PATH"
wp option update blogdescription 'Mo ta website' --path="$WP_PATH"
wp option update timezone_string 'Asia/Ho_Chi_Minh' --path="$WP_PATH"
wp option update date_format 'd/m/Y' --path="$WP_PATH"
wp option update time_format 'H:i' --path="$WP_PATH"
wp option update WPLANG 'vi' --path="$WP_PATH"
echo "[OK] Da cau hinh co ban"

# 7. Xoa noi dung mac dinh
wp post delete 1 --force --path="$WP_PATH"  # Hello World
wp post delete 2 --force --path="$WP_PATH"  # Sample Page
wp comment delete 1 --force --path="$WP_PATH"  # Comment mac dinh
echo "[OK] Da xoa noi dung mac dinh"

# 8. Cai dat plugins
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
    echo "[OK] Da cai dat plugin: $plugin"
done

# 9. Xoa plugins mac dinh khong can
wp plugin delete hello --path="$WP_PATH"
wp plugin delete akismet --path="$WP_PATH"
echo "[OK] Da xoa plugins khong can"

# 10. Cai dat va kich hoat theme
wp theme install flavor --activate --path="$WP_PATH"
echo "[OK] Da cai dat theme"

# 11. Xoa themes mac dinh khong can
wp theme delete twentytwentytwo --path="$WP_PATH"
wp theme delete twentytwentythree --path="$WP_PATH"
echo "[OK] Da xoa themes khong can"

# 12. Tao cac trang co ban
wp post create --post_type=page --post_title="Trang Chu" --post_status=publish --path="$WP_PATH"
wp post create --post_type=page --post_title="Gioi Thieu" --post_status=publish --path="$WP_PATH"
wp post create --post_type=page --post_title="Lien He" --post_status=publish --path="$WP_PATH"
wp post create --post_type=page --post_title="Blog" --post_status=publish --path="$WP_PATH"
echo "[OK] Da tao cac trang co ban"

# 13. Cau hinh trang chu va trang blog
FRONT_PAGE=$(wp post list --post_type=page --name="trang-chu" --field=ID --path="$WP_PATH")
BLOG_PAGE=$(wp post list --post_type=page --name="blog" --field=ID --path="$WP_PATH")
wp option update show_on_front 'page' --path="$WP_PATH"
wp option update page_on_front "$FRONT_PAGE" --path="$WP_PATH"
wp option update page_for_posts "$BLOG_PAGE" --path="$WP_PATH"
echo "[OK] Da cau hinh trang chu"

# 14. Tao menu
wp menu create "Main Menu" --path="$WP_PATH"
wp menu item add-post main-menu "$FRONT_PAGE" --title="Trang Chu" --path="$WP_PATH"
ABOUT_PAGE=$(wp post list --post_type=page --name="gioi-thieu" --field=ID --path="$WP_PATH")
wp menu item add-post main-menu "$ABOUT_PAGE" --title="Gioi Thieu" --path="$WP_PATH"
CONTACT_PAGE=$(wp post list --post_type=page --name="lien-he" --field=ID --path="$WP_PATH")
wp menu item add-post main-menu "$CONTACT_PAGE" --title="Lien He" --path="$WP_PATH"
wp menu location assign main-menu primary --path="$WP_PATH"
echo "[OK] Da tao menu"

# 15. Flush rewrite
wp rewrite flush --hard --path="$WP_PATH"

echo ""
echo "========================================="
echo "Cai dat hoan tat!"
echo "URL: $SITE_URL"
echo "Admin: $SITE_URL/wp-admin"
echo "User: $ADMIN_USER"
echo "Pass: $ADMIN_PASS"
echo "========================================="
```

### Script cap nhat hang loat

```bash
#!/bin/bash
# update-all.sh - Cap nhat tat ca

WP_PATH="/var/www/html/wordpress"

echo "=== Bat dau cap nhat ==="

# Backup truoc khi cap nhat
BACKUP_FILE="pre-update-$(date +%Y%m%d-%H%M%S).sql"
wp db export "$BACKUP_FILE" --path="$WP_PATH"
echo "[OK] Backup: $BACKUP_FILE"

# Cap nhat core
wp core update --path="$WP_PATH"
wp core update-db --path="$WP_PATH"
echo "[OK] Cap nhat WordPress core"

# Cap nhat tat ca plugins
wp plugin update --all --path="$WP_PATH"
echo "[OK] Cap nhat plugins"

# Cap nhat tat ca themes
wp theme update --all --path="$WP_PATH"
echo "[OK] Cap nhat themes"

# Cap nhat ngon ngu
wp language core update --path="$WP_PATH"
wp language plugin update --all --path="$WP_PATH"
wp language theme update --all --path="$WP_PATH"
echo "[OK] Cap nhat ngon ngu"

# Flush cache
wp cache flush --path="$WP_PATH"
wp rewrite flush --path="$WP_PATH"
echo "[OK] Flush cache"

echo "=== Cap nhat hoan tat ==="
```

### Script tao noi dung test

```bash
#!/bin/bash
# generate-test-content.sh - Tao du lieu test

WP_PATH="/var/www/html/wordpress"

echo "=== Tao du lieu test ==="

# Tao categories
CATEGORIES=("Cong Nghe" "Doi Song" "Giai Tri" "Kinh Doanh" "The Thao")
for cat in "${CATEGORIES[@]}"; do
    wp term create category "$cat" --path="$WP_PATH"
done
echo "[OK] Tao ${#CATEGORIES[@]} categories"

# Tao bai viet
wp post generate --count=50 --post_type=post --post_status=publish --path="$WP_PATH"
echo "[OK] Tao 50 bai viet"

# Tao users
ROLES=("editor" "author" "contributor" "subscriber")
for i in $(seq 1 10); do
    ROLE=${ROLES[$((RANDOM % ${#ROLES[@]}))]}
    wp user create "user$i" "user$i@example.com" --role="$ROLE" --user_pass=Test123! --path="$WP_PATH"
done
echo "[OK] Tao 10 users"

# Tao comments
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
echo "[OK] Tao comments"

echo "=== Tao du lieu test hoan tat ==="
```

### Script kiem tra suc khoe website

```bash
#!/bin/bash
# health-check.sh - Kiem tra suc khoe website

WP_PATH="/var/www/html/wordpress"

echo "========================================="
echo "  KIEM TRA SUC KHOE WORDPRESS"
echo "  $(date '+%Y-%m-%d %H:%M:%S')"
echo "========================================="
echo ""

# 1. Thong tin co ban
echo "--- THONG TIN CO BAN ---"
echo "WordPress Version: $(wp core version --path="$WP_PATH")"
echo "PHP Version: $(php -v | head -1)"
echo "MySQL Version: $(wp db cli --path="$WP_PATH" <<< 'SELECT VERSION();' 2>/dev/null | tail -1)"
echo "Site URL: $(wp option get siteurl --path="$WP_PATH")"
echo "Home URL: $(wp option get home --path="$WP_PATH")"
echo ""

# 2. Cap nhat
echo "--- CAP NHAT ---"
wp core check-update --path="$WP_PATH"
echo ""
echo "Plugins can cap nhat:"
wp plugin list --update=available --path="$WP_PATH"
echo ""
echo "Themes can cap nhat:"
wp theme list --update=available --path="$WP_PATH"
echo ""

# 3. Plugin status
echo "--- PLUGINS ---"
TOTAL_PLUGINS=$(wp plugin list --format=count --path="$WP_PATH")
ACTIVE_PLUGINS=$(wp plugin list --status=active --format=count --path="$WP_PATH")
INACTIVE_PLUGINS=$(wp plugin list --status=inactive --format=count --path="$WP_PATH")
echo "Tong: $TOTAL_PLUGINS | Active: $ACTIVE_PLUGINS | Inactive: $INACTIVE_PLUGINS"
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
echo "Tong cron events: $CRON_COUNT"
echo ""

# 7. Transients
echo "--- TRANSIENTS ---"
TRANSIENT_COUNT=$(wp db query "SELECT COUNT(*) FROM $(wp db prefix --path="$WP_PATH")options WHERE option_name LIKE '_transient_%';" --path="$WP_PATH" 2>/dev/null | tail -1)
echo "Tong transients: $TRANSIENT_COUNT"
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
echo "  Kiem tra hoan tat"
echo "========================================="
```

### Script quan ly nhieu site (Multisite Management)

```bash
#!/bin/bash
# multisite-update.sh - Cap nhat nhieu site cung luc

# Danh sach cac site (dung wp-cli aliases)
SITES=("@staging" "@production" "@dev")

# Hoac danh sach duong dan
# SITE_PATHS=(
#     "/var/www/site1"
#     "/var/www/site2"
#     "/var/www/site3"
# )

for site in "${SITES[@]}"; do
    echo ""
    echo "=== Cap nhat: $site ==="

    # Backup truoc
    wp "$site" db export "backup-$(date +%Y%m%d).sql"

    # Cap nhat
    wp "$site" core update
    wp "$site" core update-db
    wp "$site" plugin update --all
    wp "$site" theme update --all

    # Flush
    wp "$site" cache flush
    wp "$site" rewrite flush

    echo "=== Hoan tat: $site ==="
done

echo ""
echo "Tat ca site da duoc cap nhat!"
```

### Lenh huu ich khac

```bash
# --- CAU HINH ---

# Xem tat ca constants trong wp-config.php
wp config list

# Them constant
wp config set WP_MEMORY_LIMIT '256M'
wp config set WP_MAX_MEMORY_LIMIT '512M'

# Bat/tat debug
wp config set WP_DEBUG true --raw
wp config set WP_DEBUG false --raw

# --- MAINTENANCE MODE ---

# Bat maintenance mode
wp maintenance-mode activate

# Tat maintenance mode
wp maintenance-mode deactivate

# Kiem tra trang thai
wp maintenance-mode status

# --- EXPORT/IMPORT NỘI DUNG ---

# Export noi dung (XML)
wp export --dir=/tmp/exports

# Export chi post type cu the
wp export --post_type=product --dir=/tmp/exports

# Import noi dung
wp import /tmp/exports/export.xml --authors=create

# --- WIDGET ---

# Xem danh sach widget areas
wp widget list sidebar-1

# Them widget
wp widget add text sidebar-1 --title="Lien He" --text="SDT: 0123456789"

# --- SIDEBAR ---

# Xem sidebars
wp sidebar list

# --- SUPER ADMIN (Multisite) ---

wp super-admin list
wp super-admin add username
wp super-admin remove username

# --- PACKAGE MANAGEMENT ---

# Cai dat WP-CLI package bo sung
wp package install wp-cli/doctor-command
wp package install aaemnnosttv/wp-cli-login-command

# Xem packages da cai
wp package list

# Chay doctor check
wp doctor check --all
```
