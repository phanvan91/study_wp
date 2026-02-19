#!/usr/bin/env python3
"""
Translate English comments to Vietnamese in WordPress PHP files.
Only translates comments, NOT code or i18n strings.
"""
import sys

def translate_file(filepath, replacements):
    """Read file, apply replacements, write back."""
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    count = 0
    for old, new in replacements:
        if old in content:
            content = content.replace(old, new)
            count += 1

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

    print(f"Applied {count}/{len(replacements)} replacements in {filepath}")

def get_file1_replacements():
    """class-wp-customize-manager.php translations"""
    return [
        # Fix previous bad edit
        ("hay không. / Filters whether or not changeset branching is allowed.",
         "hay không."),
        # Block comment around line 787-810
        ("By default in core, when changeset branching is not allowed, changesets will operate",
         "Theo mặc định trong lõi, khi phân nhánh changeset không được phép, các changeset sẽ hoạt động"),
        ("linearly in that only one saved changeset will exist at a time (with a 'draft' or",
         "tuyến tính trong đó chỉ có một changeset đã lưu tồn tại tại một thời điểm (với trạng thái 'draft' hoặc"),
        ("'future' status). This makes the Customizer operate in a way that is similar to going to",
         "'future'). Điều này làm cho Trình tùy biến hoạt động theo cách tương tự như đi đến"),
        ('"edit" to one existing post: all users will be making changes to the same post, and autosave',
         '"chỉnh sửa" một bài viết hiện có: tất cả người dùng sẽ thay đổi cùng một bài viết, và các bản sửa đổi'),
        ("revisions will be made for that post.",
         "tự động lưu sẽ được tạo cho bài viết đó."),
        ('By contrast, when changeset branching is allowed, then the model is like users going',
         'Ngược lại, khi phân nhánh changeset được phép, thì mô hình giống như người dùng đi đến'),
        ('to "add new" for a page and each user makes changes independently of each other since',
         '"thêm mới" cho một trang và mỗi người dùng thay đổi độc lập với nhau vì'),
        ("they are all operating on their own separate pages, each getting their own separate",
         "họ đều đang hoạt động trên các trang riêng biệt của mình, mỗi người nhận được"),
        ("initial auto-drafts and then once initially saved, autosave revisions on top of that",
         "bản nháp tự động ban đầu riêng và sau khi được lưu lần đầu, các bản sửa đổi tự động lưu trên đầu"),
        ("user's specific post.",
         "bài viết cụ thể của người dùng đó."),
        ("Since linear changesets are deemed to be more suitable for the majority of WordPress users,",
         "Vì các changeset tuyến tính được coi là phù hợp hơn cho đa số người dùng WordPress,"),
        ("they are the default. For WordPress sites that have heavy site management in the Customizer",
         "chúng là mặc định. Đối với các trang WordPress có quản lý trang web nặng trong Trình tùy biến"),
        ("by multiple users then branching changesets should be enabled by means of this filter.",
         "bởi nhiều người dùng thì nên bật phân nhánh changeset bằng bộ lọc này."),
        ("Whether branching is allowed. If `false`, the default,",
         "Có cho phép phân nhánh hay không. Nếu `false`, mặc định,"),
        ("then only one saved changeset exists at a time.",
         "thì chỉ có một changeset đã lưu tồn tại tại một thời điểm."),
        ("@param WP_Customize_Manager $wp_customize    Manager instance.",
         "@param WP_Customize_Manager $wp_customize    Thể hiện Manager."),
        # Gets the changeset UUID
        ("Gets the changeset UUID.",
         "Lấy UUID changeset."),
        ("@return string UUID.",
         "@return string UUID."),
        # Gets the theme being customized
        ("Gets the theme being customized.",
         "Lấy giao diện đang được tùy biến."),
        # Gets the registered settings
        ("Gets the registered settings.",
         "Lấy các cài đặt đã đăng ký."),
        # Gets the registered controls
        ("Gets the registered controls.",
         "Lấy các control đã đăng ký."),
        # Gets the registered containers
        ("Gets the registered containers.",
         "Lấy các container đã đăng ký."),
        # Gets the registered sections
        ("Gets the registered sections.",
         "Lấy các section đã đăng ký."),
        # Gets the registered panels
        ("Gets the registered panels.",
         "Lấy các panel đã đăng ký."),
        ("@return array Panels.",
         "@return array Các panel."),
    ]

if __name__ == '__main__':
    filepath = '/home/phanvan91/projects/wordpress/wp-includes/class-wp-customize-manager.php'
    replacements = get_file1_replacements()
    translate_file(filepath, replacements)
