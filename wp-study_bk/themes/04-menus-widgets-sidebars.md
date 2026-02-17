# Menus, Widgets và Sidebars trong WordPress Theme

## Mục Lục

1. [Navigation Menus](#1-navigation-menus)
2. [Custom Walker cho Menu](#2-custom-walker)
3. [Mega Menu](#3-mega-menu)
4. [Breadcrumbs](#4-breadcrumbs)
5. [Sidebars và Widget Areas](#5-sidebars)
6. [Widgets trong Theme](#6-widgets)
7. [Footer Widgets](#7-footer-widgets)
8. [Code ví dụ: Theme hoàn chỉnh](#8-code-vi-du)
9. [Best Practices](#9-best-practices)

---

## 1. Navigation Menus

### Bước 1: Đăng ký vị trí menu (functions.php)

```php
<?php
/**
 * Đăng ký các vị trí menu cho theme
 * Phải gọi trong hook 'after_setup_theme' hoặc 'init'
 */
function developer_register_menus() {
    register_nav_menus( array(
        // 'location_id' => 'Label hiển thị trong Admin'
        'primary'     => __( 'Menu Chính (Header)', 'developer-theme' ),
        'secondary'   => __( 'Menu Phụ (Header Top Bar)', 'developer-theme' ),
        'footer'      => __( 'Menu Footer', 'developer-theme' ),
        'mobile'      => __( 'Menu Mobile', 'developer-theme' ),
        'social'      => __( 'Menu Mạng Xã Hội', 'developer-theme' ),
    ) );

    // Đăng ký 1 vị trí duy nhất:
    // register_nav_menu( 'primary', __( 'Menu Chính', 'developer-theme' ) );
}
add_action( 'after_setup_theme', 'developer_register_menus' );
```

### Bước 2: Hiển thị menu trong template

```php
<?php
/**
 * wp_nav_menu() - Hiển thị menu đã đăng ký
 * Tất cả các tham số:
 */
wp_nav_menu( array(
    // === BẮT BUỘC (chọn 1 trong 3 cách) ===
    'theme_location'  => 'primary',        // Vị trí menu (đã đăng ký)
    // 'menu'         => 'Main Menu',       // Tên menu (trong Admin > Menus)
    // 'menu'         => 5,                 // ID của menu

    // === CONTAINER (phần tử bọc ngoài) ===
    'container'       => 'nav',            // Tag bọc ngoài: 'div', 'nav', false
    'container_class' => 'main-navigation', // Class cho container
    'container_id'    => 'site-navigation', // ID cho container
    'container_aria_label' => 'Primary Menu', // ARIA label

    // === MENU (phần tử <ul>) ===
    'menu_class'      => 'nav-menu primary-menu', // Class cho <ul>
    'menu_id'         => 'primary-menu',          // ID cho <ul>

    // === ITEMS ===
    'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
    // %1$s = menu_id, %2$s = menu_class, %3$s = menu items

    // === BEHAVIOR ===
    'depth'           => 3,               // Độ sâu: 0 = tất cả, 1 = không sub-menu, 2, 3...
    'fallback_cb'     => 'wp_page_menu',  // Fallback khi chưa có menu (false = không hiện gì)
    'walker'          => '',              // Custom Walker class

    // === THÊM NỘI DUNG ===
    'before'          => '',              // Trước <a> (trong <li>)
    'after'           => '',              // Sau <a> (trong <li>)
    'link_before'     => '',              // Trước link text (trong <a>)
    'link_after'      => '',              // Sau link text (trong <a>)

    // === ECHO ===
    'echo'            => true,            // true = echo, false = return string
) );
?>
```

### Ví dụ các cách hiển thị menu:

```php
<!-- === Menu Header đơn giản === -->
<header class="site-header">
    <nav class="main-nav">
        <?php
        wp_nav_menu( array(
            'theme_location' => 'primary',
            'container'      => false,      // Không cần container vì đã có <nav>
            'menu_class'     => 'nav-list',
            'depth'          => 2,
            'fallback_cb'    => false,
        ) );
        ?>
    </nav>
</header>

<!-- === Menu với icon trước link === -->
<?php
wp_nav_menu( array(
    'theme_location' => 'primary',
    'container'      => 'nav',
    'link_before'    => '<span class="menu-icon"></span><span class="menu-text">',
    'link_after'     => '</span>',
    // Kết quả: <a href="..."><span class="menu-icon"></span><span class="menu-text">Text</span></a>
) );
?>

<!-- === Menu Footer === -->
<footer class="site-footer">
    <nav class="footer-nav">
        <?php
        wp_nav_menu( array(
            'theme_location' => 'footer',
            'container'      => false,
            'menu_class'     => 'footer-menu-list',
            'depth'          => 1,          // Chỉ 1 cấp, không sub-menu
            'fallback_cb'    => false,
        ) );
        ?>
    </nav>
</footer>

<!-- === Menu Social với custom walker === -->
<?php
wp_nav_menu( array(
    'theme_location' => 'social',
    'container'      => 'nav',
    'container_class' => 'social-navigation',
    'menu_class'     => 'social-links',
    'depth'          => 1,
    'link_before'    => '<span class="screen-reader-text">',
    'link_after'     => '</span>',
    // Kết quả: <a href="https://facebook.com"><span class="screen-reader-text">Facebook</span></a>
    // CSS sẽ dùng :before pseudo-element với icon dựa trên URL
) );
?>

<!-- === Kiểm tra menu trước khi hiển thị === -->
<?php if ( has_nav_menu( 'primary' ) ) : ?>
    <nav class="main-navigation">
        <?php
        wp_nav_menu( array(
            'theme_location' => 'primary',
        ) );
        ?>
    </nav>
<?php else : ?>
    <p><?php esc_html_e( 'Vui lòng thiết lập menu trong Admin > Appearance > Menus', 'developer-theme' ); ?></p>
<?php endif; ?>
```

### CSS cho Navigation Menu:

```css
/* === Navigation Menu Styles === */

/* Reset */
.nav-menu {
    list-style: none;
    margin: 0;
    padding: 0;
    display: flex;
    align-items: center;
    gap: 0;
}

/* Menu item */
.nav-menu li {
    position: relative;
}

/* Menu link */
.nav-menu a {
    display: block;
    padding: 1rem 1.25rem;
    color: #333;
    text-decoration: none;
    font-weight: 500;
    font-size: 0.9375rem;
    transition: color 0.3s, background-color 0.3s;
}

.nav-menu a:hover,
.nav-menu .current-menu-item > a,
.nav-menu .current-menu-ancestor > a {
    color: #0073aa;
    background-color: rgba(0, 115, 170, 0.05);
}

/* WordPress tự động thêm các class này:
   .current-menu-item     - Menu item đang active
   .current-menu-ancestor - Menu cha của item active
   .current-menu-parent   - Menu cha trực tiếp
   .menu-item-has-children - Có sub-menu
*/

/* Sub-menu */
.nav-menu .sub-menu {
    list-style: none;
    margin: 0;
    padding: 0.5rem 0;
    position: absolute;
    top: 100%;
    left: 0;
    min-width: 220px;
    background: #fff;
    border: 1px solid #eee;
    border-radius: 4px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: all 0.3s ease;
    z-index: 100;
}

/* Hiện sub-menu khi hover */
.nav-menu li:hover > .sub-menu {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

/* Sub-menu items */
.nav-menu .sub-menu a {
    padding: 0.5rem 1.25rem;
    font-size: 0.875rem;
    font-weight: 400;
    white-space: nowrap;
}

/* Sub-sub-menu (cấp 3) */
.nav-menu .sub-menu .sub-menu {
    top: 0;
    left: 100%;
}

/* Arrow cho items có sub-menu */
.nav-menu .menu-item-has-children > a::after {
    content: ' \25BC'; /* Down arrow */
    font-size: 0.625rem;
    margin-left: 0.25rem;
}

.nav-menu .sub-menu .menu-item-has-children > a::after {
    content: ' \25B6'; /* Right arrow */
}

/* === Mobile Menu === */
@media (max-width: 768px) {
    .menu-toggle {
        display: block;
        padding: 0.75rem 1rem;
        background: none;
        border: 1px solid #ddd;
        border-radius: 4px;
        cursor: pointer;
        font-size: 1rem;
    }

    .nav-menu {
        display: none;
        flex-direction: column;
        width: 100%;
        position: absolute;
        top: 100%;
        left: 0;
        background: #fff;
        border-top: 1px solid #eee;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .main-navigation.toggled .nav-menu {
        display: flex;
    }

    .nav-menu .sub-menu {
        position: static;
        opacity: 1;
        visibility: visible;
        transform: none;
        box-shadow: none;
        border: none;
        padding-left: 1rem;
        background: #f9f9f9;
    }

    .nav-menu a {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #f0f0f0;
    }
}
```

---

## 2. Custom Walker

### Walker_Nav_Menu là gì?

Walker là class cho phép bạn **tùy chỉnh hoàn toàn** HTML output của menu. Mặc định, `wp_nav_menu()` tạo HTML cố định. Walker cho phép bạn thay đổi tag, thêm class, thêm icon...

```php
<?php
/**
 * Custom Walker: Tạo Bootstrap 5 compatible menu
 *
 * Đặt file này trong: inc/walker-nav-menu.php
 * Require trong functions.php: require get_template_directory() . '/inc/walker-nav-menu.php';
 */
class Developer_Bootstrap_Walker extends Walker_Nav_Menu {

    /**
     * start_lvl - Bắt đầu 1 cấp menu con (sub-menu)
     * Mặc định: <ul class="sub-menu">
     *
     * @param string $output HTML output
     * @param int    $depth  Độ sâu (0 = cấp 1, 1 = cấp 2...)
     * @param array  $args   Tham số của wp_nav_menu()
     */
    public function start_lvl( &$output, $depth = 0, $args = null ) {
        $indent = str_repeat( "\t", $depth );
        // Thay sub-menu bằng dropdown-menu của Bootstrap
        $output .= "\n{$indent}<ul class=\"dropdown-menu\">\n";
    }

    /**
     * end_lvl - Kết thúc 1 cấp menu con
     */
    public function end_lvl( &$output, $depth = 0, $args = null ) {
        $indent = str_repeat( "\t", $depth );
        $output .= "{$indent}</ul>\n";
    }

    /**
     * start_el - Bắt đầu 1 menu item
     * Đây là phần QUAN TRỌNG NHẤT - tùy chỉnh HTML của mỗi item
     *
     * @param string   $output HTML output
     * @param WP_Post  $item   Menu item object
     * @param int      $depth  Độ sâu
     * @param stdClass $args   Tham số
     * @param int      $id     Item ID
     */
    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        $indent = str_repeat( "\t", $depth );

        // Kiểm tra có sub-menu không
        $has_children = in_array( 'menu-item-has-children', $item->classes );

        // === Tạo class cho <li> ===
        $classes   = empty( $item->classes ) ? array() : (array) $item->classes;
        $classes[] = 'nav-item';

        // Thêm class Bootstrap
        if ( $has_children && $depth === 0 ) {
            $classes[] = 'dropdown';       // Cấp 1 có sub-menu
        }
        if ( $has_children && $depth > 0 ) {
            $classes[] = 'dropend';        // Cấp 2+ có sub-menu
        }
        if ( in_array( 'current-menu-item', $classes ) ) {
            $classes[] = 'active';         // Item đang active
        }

        $class_names = join( ' ', array_filter( $classes ) );
        $class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

        // ID của item
        $id_attr = ' id="menu-item-' . esc_attr( $item->ID ) . '"';

        // === Tạo <li> ===
        $output .= $indent . '<li' . $id_attr . $class_names . '>';

        // === Tạo <a> ===
        $atts = array();
        $atts['title']  = ! empty( $item->attr_title ) ? $item->attr_title : '';
        $atts['target'] = ! empty( $item->target ) ? $item->target : '';
        $atts['rel']    = ! empty( $item->xfn ) ? $item->xfn : '';
        $atts['href']   = ! empty( $item->url ) ? $item->url : '';

        // Class cho <a>
        $link_classes = array( 'nav-link' );
        if ( $has_children && $depth === 0 ) {
            $link_classes[]      = 'dropdown-toggle';
            $atts['data-bs-toggle'] = 'dropdown';
            $atts['aria-expanded']  = 'false';
            $atts['role']           = 'button';
        }
        if ( $has_children && $depth > 0 ) {
            $link_classes[] = 'dropdown-item';
            $link_classes[] = 'dropdown-toggle';
        }
        if ( ! $has_children && $depth > 0 ) {
            $link_classes = array( 'dropdown-item' );
        }
        if ( in_array( 'current-menu-item', $item->classes ) ) {
            $atts['aria-current'] = 'page';
        }

        $atts['class'] = implode( ' ', $link_classes );

        // Build attributes string
        $attributes = '';
        foreach ( $atts as $attr => $value ) {
            if ( ! empty( $value ) ) {
                $attributes .= ' ' . $attr . '="' . esc_attr( $value ) . '"';
            }
        }

        // Build link
        $item_output  = isset( $args->before ) ? $args->before : '';
        $item_output .= '<a' . $attributes . '>';
        $item_output .= ( isset( $args->link_before ) ? $args->link_before : '' );
        $item_output .= apply_filters( 'the_title', $item->title, $item->ID );
        $item_output .= ( isset( $args->link_after ) ? $args->link_after : '' );
        $item_output .= '</a>';
        $item_output .= isset( $args->after ) ? $args->after : '';

        $output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args );
    }

    /**
     * end_el - Kết thúc 1 menu item
     */
    public function end_el( &$output, $item, $depth = 0, $args = null ) {
        $output .= "</li>\n";
    }
}

// === Sử dụng Walker trong template ===
wp_nav_menu( array(
    'theme_location' => 'primary',
    'container'      => false,
    'menu_class'     => 'navbar-nav',
    'depth'          => 2,
    'walker'         => new Developer_Bootstrap_Walker(),
) );
?>
```

### Walker đơn giản hơn - Thêm icon và description:

```php
<?php
/**
 * Walker thêm icon và description cho menu item
 */
class Developer_Icon_Walker extends Walker_Nav_Menu {

    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        $indent = str_repeat( "\t", $depth );

        // Lấy custom classes
        $classes = empty( $item->classes ) ? array() : (array) $item->classes;
        $class_names = join( ' ', array_filter( $classes ) );
        $class_names = $class_names ? ' class="' . esc_attr( $class_names ) . '"' : '';

        $output .= $indent . '<li' . $class_names . '>';

        // Build link
        $output .= '<a href="' . esc_url( $item->url ) . '"';
        if ( ! empty( $item->target ) ) {
            $output .= ' target="' . esc_attr( $item->target ) . '"';
        }
        $output .= '>';

        // Icon (lấy từ CSS class của menu item)
        // Khi tạo menu trong Admin, thêm class như: icon-home, icon-about...
        foreach ( $item->classes as $class ) {
            if ( strpos( $class, 'icon-' ) === 0 ) {
                $icon_name = str_replace( 'icon-', '', $class );
                $output .= '<span class="menu-icon dashicons dashicons-' . esc_attr( $icon_name ) . '"></span>';
                break;
            }
        }

        // Title
        $output .= '<span class="menu-title">' . esc_html( $item->title ) . '</span>';

        // Description (nhập trong Admin > Menus > Screen Options > Description)
        if ( ! empty( $item->description ) && $depth === 0 ) {
            $output .= '<span class="menu-description">' . esc_html( $item->description ) . '</span>';
        }

        $output .= '</a>';
    }
}
?>
```

---

## 3. Mega Menu

```php
<?php
/**
 * Mega Menu Walker
 * Tạo mega menu với nhiều cột, hình ảnh, description
 *
 * Cách dùng: Trong Admin > Menus, tạo menu item với class CSS "mega-menu"
 * Sub-menu items sẽ được hiển thị dạng grid
 */
class Developer_Mega_Menu_Walker extends Walker_Nav_Menu {

    private $is_mega = false;

    public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
        $indent = str_repeat( "\t", $depth );

        $classes = empty( $item->classes ) ? array() : (array) $item->classes;

        // Kiểm tra có phải mega menu không (admin thêm class "mega-menu")
        if ( $depth === 0 && in_array( 'mega-menu', $classes ) ) {
            $this->is_mega = true;
            $classes[] = 'has-mega-menu';
        }

        $class_names = join( ' ', array_filter( $classes ) );
        $class_names = ' class="' . esc_attr( $class_names ) . '"';

        $output .= $indent . '<li' . $class_names . '>';

        // Link
        $output .= '<a href="' . esc_url( $item->url ) . '"';
        $output .= ' class="' . ( $depth === 0 ? 'nav-link' : 'mega-link' ) . '"';
        $output .= '>';
        $output .= esc_html( $item->title );

        if ( $depth === 0 && in_array( 'menu-item-has-children', $classes ) ) {
            $output .= ' <span class="dropdown-arrow">&#9662;</span>';
        }

        $output .= '</a>';

        // Thêm description cho sub-items trong mega menu
        if ( $this->is_mega && $depth === 1 && ! empty( $item->description ) ) {
            $output .= '<p class="mega-item-desc">' . esc_html( $item->description ) . '</p>';
        }
    }

    public function start_lvl( &$output, $depth = 0, $args = null ) {
        $indent = str_repeat( "\t", $depth );

        if ( $this->is_mega && $depth === 0 ) {
            // Mega menu: sử dụng div thay vì ul
            $output .= "\n{$indent}<div class=\"mega-menu-panel\">\n";
            $output .= "{$indent}\t<div class=\"mega-menu-inner\">\n";
            $output .= "{$indent}\t\t<ul class=\"mega-menu-list\">\n";
        } else {
            $output .= "\n{$indent}<ul class=\"sub-menu\">\n";
        }
    }

    public function end_lvl( &$output, $depth = 0, $args = null ) {
        $indent = str_repeat( "\t", $depth );

        if ( $this->is_mega && $depth === 0 ) {
            $output .= "{$indent}\t\t</ul>\n";
            $output .= "{$indent}\t</div>\n";
            $output .= "{$indent}</div>\n";
            $this->is_mega = false; // Reset flag
        } else {
            $output .= "{$indent}</ul>\n";
        }
    }
}
```

### CSS cho Mega Menu:

```css
/* === Mega Menu CSS === */
.has-mega-menu {
    position: static; /* Quan trọng: để mega menu full width */
}

.mega-menu-panel {
    display: none;
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #fff;
    border-top: 3px solid #0073aa;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
    padding: 2rem;
    z-index: 1000;
}

.has-mega-menu:hover .mega-menu-panel {
    display: block;
    animation: fadeInDown 0.3s ease;
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.mega-menu-inner {
    max-width: 1200px;
    margin: 0 auto;
}

.mega-menu-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: grid;
    grid-template-columns: repeat(4, 1fr); /* 4 cột */
    gap: 1.5rem;
}

.mega-menu-list li {
    padding: 0;
}

.mega-link {
    display: block;
    padding: 0.5rem 0;
    color: #333;
    font-weight: 600;
    text-decoration: none;
    border-bottom: 2px solid transparent;
    transition: all 0.3s;
}

.mega-link:hover {
    color: #0073aa;
    border-bottom-color: #0073aa;
}

.mega-item-desc {
    font-size: 0.8125rem;
    color: #666;
    margin-top: 0.25rem;
    line-height: 1.4;
}

/* Responsive mega menu */
@media (max-width: 768px) {
    .mega-menu-panel {
        position: static;
        box-shadow: none;
        border-top: none;
        padding: 0 0 0 1rem;
    }

    .mega-menu-list {
        grid-template-columns: 1fr;
    }
}
```

---

## 4. Breadcrumbs

```php
<?php
/**
 * Custom Breadcrumbs Function
 * Không cần plugin, tự tạo breadcrumbs
 *
 * Đặt trong: inc/template-tags.php
 * Gọi trong template: developer_breadcrumbs();
 */
function developer_breadcrumbs() {
    // Không hiển thị trên trang chủ
    if ( is_front_page() ) {
        return;
    }

    $separator = '<span class="breadcrumb-separator">/</span>';
    $home_text = __( 'Trang Chủ', 'developer-theme' );

    echo '<nav class="breadcrumbs" aria-label="Breadcrumb">';
    echo '<ol class="breadcrumb-list" itemscope itemtype="https://schema.org/BreadcrumbList">';

    // Trang chủ (luôn có)
    $position = 1;
    echo '<li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
    echo '<a href="' . esc_url( home_url( '/' ) ) . '" itemprop="item"><span itemprop="name">' . esc_html( $home_text ) . '</span></a>';
    echo '<meta itemprop="position" content="' . $position . '" />';
    echo '</li>';
    echo $separator;
    $position++;

    // === Category archive ===
    if ( is_category() ) {
        $cat = get_queried_object();

        // Hiển thị parent categories
        if ( $cat->parent !== 0 ) {
            $parents = get_ancestors( $cat->term_id, 'category' );
            $parents = array_reverse( $parents );

            foreach ( $parents as $parent_id ) {
                $parent = get_category( $parent_id );
                echo '<li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
                echo '<a href="' . esc_url( get_category_link( $parent_id ) ) . '" itemprop="item"><span itemprop="name">' . esc_html( $parent->name ) . '</span></a>';
                echo '<meta itemprop="position" content="' . $position . '" />';
                echo '</li>';
                echo $separator;
                $position++;
            }
        }

        echo '<li class="breadcrumb-item current" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
        echo '<span itemprop="name">' . esc_html( $cat->name ) . '</span>';
        echo '<meta itemprop="position" content="' . $position . '" />';
        echo '</li>';

    // === Tag archive ===
    } elseif ( is_tag() ) {
        echo '<li class="breadcrumb-item current">';
        printf( esc_html__( 'The: %s', 'developer-theme' ), single_tag_title( '', false ) );
        echo '</li>';

    // === Single Post ===
    } elseif ( is_single() ) {
        // Hiển thị category
        $categories = get_the_category();
        if ( $categories ) {
            $cat = $categories[0];

            // Parent categories
            if ( $cat->parent !== 0 ) {
                $parents = get_ancestors( $cat->term_id, 'category' );
                $parents = array_reverse( $parents );
                foreach ( $parents as $parent_id ) {
                    $parent = get_category( $parent_id );
                    echo '<li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
                    echo '<a href="' . esc_url( get_category_link( $parent_id ) ) . '" itemprop="item"><span itemprop="name">' . esc_html( $parent->name ) . '</span></a>';
                    echo '<meta itemprop="position" content="' . $position . '" />';
                    echo '</li>';
                    echo $separator;
                    $position++;
                }
            }

            echo '<li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
            echo '<a href="' . esc_url( get_category_link( $cat->term_id ) ) . '" itemprop="item"><span itemprop="name">' . esc_html( $cat->name ) . '</span></a>';
            echo '<meta itemprop="position" content="' . $position . '" />';
            echo '</li>';
            echo $separator;
            $position++;
        }

        echo '<li class="breadcrumb-item current" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
        echo '<span itemprop="name">' . esc_html( get_the_title() ) . '</span>';
        echo '<meta itemprop="position" content="' . $position . '" />';
        echo '</li>';

    // === Page ===
    } elseif ( is_page() ) {
        $page = get_queried_object();

        // Parent pages
        if ( $page->post_parent ) {
            $parents = get_ancestors( $page->ID, 'page' );
            $parents = array_reverse( $parents );

            foreach ( $parents as $parent_id ) {
                echo '<li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
                echo '<a href="' . esc_url( get_permalink( $parent_id ) ) . '" itemprop="item"><span itemprop="name">' . esc_html( get_the_title( $parent_id ) ) . '</span></a>';
                echo '<meta itemprop="position" content="' . $position . '" />';
                echo '</li>';
                echo $separator;
                $position++;
            }
        }

        echo '<li class="breadcrumb-item current">';
        echo '<span>' . esc_html( get_the_title() ) . '</span>';
        echo '</li>';

    // === Search ===
    } elseif ( is_search() ) {
        echo '<li class="breadcrumb-item current">';
        printf( esc_html__( 'Tìm kiếm: "%s"', 'developer-theme' ), get_search_query() );
        echo '</li>';

    // === 404 ===
    } elseif ( is_404() ) {
        echo '<li class="breadcrumb-item current">';
        esc_html_e( '404 - Không Tìm Thấy', 'developer-theme' );
        echo '</li>';

    // === Author ===
    } elseif ( is_author() ) {
        echo '<li class="breadcrumb-item current">';
        printf( esc_html__( 'Tác giả: %s', 'developer-theme' ), get_the_author() );
        echo '</li>';

    // === Date archive ===
    } elseif ( is_date() ) {
        echo '<li class="breadcrumb-item current">';
        if ( is_year() ) {
            echo get_the_date( 'Y' );
        } elseif ( is_month() ) {
            echo get_the_date( 'F Y' );
        } elseif ( is_day() ) {
            echo get_the_date();
        }
        echo '</li>';

    // === Custom Post Type archive ===
    } elseif ( is_post_type_archive() ) {
        echo '<li class="breadcrumb-item current">';
        post_type_archive_title();
        echo '</li>';

    // === Custom Taxonomy ===
    } elseif ( is_tax() ) {
        $term = get_queried_object();
        $taxonomy = get_taxonomy( $term->taxonomy );

        echo '<li class="breadcrumb-item">';
        echo '<a href="' . esc_url( get_post_type_archive_link( $taxonomy->object_type[0] ) ) . '">';
        echo esc_html( $taxonomy->labels->name );
        echo '</a></li>';
        echo $separator;

        echo '<li class="breadcrumb-item current">';
        echo esc_html( $term->name );
        echo '</li>';
    }

    echo '</ol>';
    echo '</nav>';
}
```

### CSS cho Breadcrumbs:

```css
.breadcrumbs {
    padding: 0.75rem 0;
    font-size: 0.875rem;
    color: #666;
}

.breadcrumb-list {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0;
}

.breadcrumb-item {
    display: inline-flex;
    align-items: center;
}

.breadcrumb-item a {
    color: #0073aa;
    text-decoration: none;
}

.breadcrumb-item a:hover {
    text-decoration: underline;
}

.breadcrumb-item.current {
    color: #333;
    font-weight: 500;
}

.breadcrumb-separator {
    margin: 0 0.5rem;
    color: #ccc;
}
```

---

## 5. Sidebars và Widget Areas

### Đăng ký Sidebar:

```php
<?php
/**
 * Đăng ký tất cả Widget Areas (Sidebars)
 * Đặt trong functions.php, hook 'widgets_init'
 */
function developer_register_sidebars() {

    // === Sidebar chính ===
    register_sidebar( array(
        'name'          => __( 'Sidebar Chính', 'developer-theme' ),
        'id'            => 'sidebar-main',          // ID duy nhất, dùng để gọi
        'description'   => __( 'Hiển thị bên phải các trang blog.', 'developer-theme' ),
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        // %1$s = widget ID, %2$s = widget class
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

    // === Sidebar cho trang sản phẩm ===
    register_sidebar( array(
        'name'          => __( 'Sidebar Sản Phẩm', 'developer-theme' ),
        'id'            => 'sidebar-shop',
        'description'   => __( 'Hiển thị bên phải trang sản phẩm.', 'developer-theme' ),
        'before_widget' => '<div id="%1$s" class="widget shop-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

    // === Footer Widget Areas (3 cột) ===
    for ( $i = 1; $i <= 3; $i++ ) {
        register_sidebar( array(
            'name'          => sprintf( __( 'Footer Cột %d', 'developer-theme' ), $i ),
            'id'            => 'footer-' . $i,
            'description'   => sprintf( __( 'Widget area cho footer cột %d.', 'developer-theme' ), $i ),
            'before_widget' => '<div id="%1$s" class="widget footer-widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h4 class="widget-title">',
            'after_title'   => '</h4>',
        ) );
    }

    // === Sidebar cho Header Top Bar ===
    register_sidebar( array(
        'name'          => __( 'Header Top Bar', 'developer-theme' ),
        'id'            => 'header-top-bar',
        'description'   => __( 'Hiển thị thông tin trên cùng (số điện thoại, email...).', 'developer-theme' ),
        'before_widget' => '<div id="%1$s" class="widget topbar-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<span class="widget-title screen-reader-text">',
        'after_title'   => '</span>',
    ) );

    // === Sidebar cho trang single bài viết ===
    register_sidebar( array(
        'name'          => __( 'After Post Content', 'developer-theme' ),
        'id'            => 'after-post',
        'description'   => __( 'Hiển thị sau nội dung bài viết (CTA, newsletter...).', 'developer-theme' ),
        'before_widget' => '<div id="%1$s" class="widget after-post-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );
}
add_action( 'widgets_init', 'developer_register_sidebars' );
```

### Hiển thị Sidebar trong template:

```php
<?php
/**
 * sidebar.php - Sidebar mặc định
 */

// Kiểm tra có widget nào không
if ( ! is_active_sidebar( 'sidebar-main' ) ) {
    return; // Không có widget -> không hiển thị gì
}
?>

<aside id="secondary" class="widget-area sidebar" role="complementary" aria-label="<?php esc_attr_e( 'Sidebar', 'developer-theme' ); ?>">
    <?php dynamic_sidebar( 'sidebar-main' ); ?>
    <!-- dynamic_sidebar() hiển thị tất cả widgets đã thêm vào sidebar này -->
</aside>

<?php
/**
 * sidebar-shop.php - Sidebar cho trang sản phẩm
 */
if ( ! is_active_sidebar( 'sidebar-shop' ) ) {
    return;
}
?>
<aside class="widget-area sidebar-shop">
    <?php dynamic_sidebar( 'sidebar-shop' ); ?>
</aside>

<?php
/**
 * Trong template (index.php, single.php...), gọi:
 */
get_sidebar();         // Load sidebar.php
get_sidebar( 'shop' ); // Load sidebar-shop.php

// Hoặc hiển thị trực tiếp, không cần file riêng:
if ( is_active_sidebar( 'after-post' ) ) : ?>
    <div class="after-post-area">
        <?php dynamic_sidebar( 'after-post' ); ?>
    </div>
<?php endif;
```

### Sidebar có điều kiện:

```php
<?php
/**
 * Hiển thị sidebar khác nhau tùy theo trang
 */
function developer_get_sidebar() {
    if ( is_post_type_archive( 'product' ) || is_singular( 'product' ) ) {
        // Trang sản phẩm: dùng sidebar shop
        get_sidebar( 'shop' );
    } elseif ( is_page_template( 'page-templates/template-full-width.php' ) ) {
        // Template full width: không có sidebar
        return;
    } else {
        // Mặc định
        get_sidebar();
    }
}

// Trong template:
// developer_get_sidebar();
```

---

## 6. Widgets trong Theme

### Tạo Custom Widget:

```php
<?php
/**
 * Custom Widget: Recent Posts với Thumbnail
 *
 * Đặt file này trong: inc/widgets.php
 * Require trong functions.php: require get_template_directory() . '/inc/widgets.php';
 */
class Developer_Recent_Posts_Widget extends WP_Widget {

    /**
     * Constructor - Đăng ký widget
     */
    public function __construct() {
        parent::__construct(
            'developer_recent_posts',   // Base ID (duy nhất)
            __( 'Dev: Bài Viết Mới', 'developer-theme' ), // Tên hiển thị trong Admin
            array(
                'description'             => __( 'Hiển thị bài viết mới nhất với hình thu nhỏ.', 'developer-theme' ),
                'classname'               => 'developer-recent-posts-widget',
                'customize_selective_refresh' => true, // Hỗ trợ Customizer live preview
            )
        );
    }

    /**
     * Frontend - Hiển thị widget trên trang
     *
     * @param array $args     Widget arguments (before_widget, after_widget, before_title, after_title)
     * @param array $instance Widget settings
     */
    public function widget( $args, $instance ) {
        $title       = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Bài Viết Mới', 'developer-theme' );
        $title       = apply_filters( 'widget_title', $title, $instance, $this->id_base );
        $number      = ! empty( $instance['number'] ) ? absint( $instance['number'] ) : 5;
        $show_thumb  = ! empty( $instance['show_thumb'] );
        $show_date   = ! empty( $instance['show_date'] );
        $show_cat    = ! empty( $instance['show_category'] );
        $category    = ! empty( $instance['category'] ) ? absint( $instance['category'] ) : 0;

        // Query
        $query_args = array(
            'post_type'           => 'post',
            'posts_per_page'      => $number,
            'post_status'         => 'publish',
            'ignore_sticky_posts' => true,
            'no_found_rows'       => true,       // Không cần pagination -> nhanh hơn
        );

        if ( $category > 0 ) {
            $query_args['cat'] = $category;
        }

        $recent_posts = new WP_Query( $query_args );

        if ( ! $recent_posts->have_posts() ) {
            return;
        }

        // Output
        echo $args['before_widget'];

        if ( $title ) {
            echo $args['before_title'] . esc_html( $title ) . $args['after_title'];
        }

        echo '<ul class="recent-posts-list">';

        while ( $recent_posts->have_posts() ) :
            $recent_posts->the_post();
        ?>
            <li class="recent-post-item">
                <?php if ( $show_thumb && has_post_thumbnail() ) : ?>
                    <div class="recent-post-thumb">
                        <a href="<?php the_permalink(); ?>">
                            <?php the_post_thumbnail( 'thumbnail', array( 'loading' => 'lazy' ) ); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <div class="recent-post-content">
                    <h4 class="recent-post-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h4>

                    <?php if ( $show_date ) : ?>
                        <span class="recent-post-date">
                            <time datetime="<?php echo get_the_date( 'c' ); ?>">
                                <?php echo get_the_date(); ?>
                            </time>
                        </span>
                    <?php endif; ?>

                    <?php if ( $show_cat ) : ?>
                        <span class="recent-post-cat">
                            <?php
                            $cats = get_the_category();
                            echo $cats ? esc_html( $cats[0]->name ) : '';
                            ?>
                        </span>
                    <?php endif; ?>
                </div>
            </li>
        <?php
        endwhile;
        wp_reset_postdata();

        echo '</ul>';
        echo $args['after_widget'];
    }

    /**
     * Backend - Form settings trong Admin
     *
     * @param array $instance Widget settings
     */
    public function form( $instance ) {
        $title     = isset( $instance['title'] ) ? $instance['title'] : __( 'Bài Viết Mới', 'developer-theme' );
        $number    = isset( $instance['number'] ) ? absint( $instance['number'] ) : 5;
        $show_thumb = isset( $instance['show_thumb'] ) ? (bool) $instance['show_thumb'] : true;
        $show_date = isset( $instance['show_date'] ) ? (bool) $instance['show_date'] : true;
        $show_cat  = isset( $instance['show_category'] ) ? (bool) $instance['show_category'] : false;
        $category  = isset( $instance['category'] ) ? absint( $instance['category'] ) : 0;
        ?>

        <!-- Tiêu đề -->
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>">
                <?php esc_html_e( 'Tiêu đề:', 'developer-theme' ); ?>
            </label>
            <input class="widefat" type="text"
                   id="<?php echo $this->get_field_id( 'title' ); ?>"
                   name="<?php echo $this->get_field_name( 'title' ); ?>"
                   value="<?php echo esc_attr( $title ); ?>" />
        </p>

        <!-- Số bài viết -->
        <p>
            <label for="<?php echo $this->get_field_id( 'number' ); ?>">
                <?php esc_html_e( 'Số bài viết:', 'developer-theme' ); ?>
            </label>
            <input class="tiny-text" type="number" min="1" max="20"
                   id="<?php echo $this->get_field_id( 'number' ); ?>"
                   name="<?php echo $this->get_field_name( 'number' ); ?>"
                   value="<?php echo esc_attr( $number ); ?>" />
        </p>

        <!-- Hiển thị thumbnail -->
        <p>
            <input type="checkbox"
                   id="<?php echo $this->get_field_id( 'show_thumb' ); ?>"
                   name="<?php echo $this->get_field_name( 'show_thumb' ); ?>"
                   <?php checked( $show_thumb ); ?> />
            <label for="<?php echo $this->get_field_id( 'show_thumb' ); ?>">
                <?php esc_html_e( 'Hiển thị hình thu nhỏ', 'developer-theme' ); ?>
            </label>
        </p>

        <!-- Hiển thị ngày -->
        <p>
            <input type="checkbox"
                   id="<?php echo $this->get_field_id( 'show_date' ); ?>"
                   name="<?php echo $this->get_field_name( 'show_date' ); ?>"
                   <?php checked( $show_date ); ?> />
            <label for="<?php echo $this->get_field_id( 'show_date' ); ?>">
                <?php esc_html_e( 'Hiển thị ngày đăng', 'developer-theme' ); ?>
            </label>
        </p>

        <!-- Hiển thị danh mục -->
        <p>
            <input type="checkbox"
                   id="<?php echo $this->get_field_id( 'show_category' ); ?>"
                   name="<?php echo $this->get_field_name( 'show_category' ); ?>"
                   <?php checked( $show_cat ); ?> />
            <label for="<?php echo $this->get_field_id( 'show_category' ); ?>">
                <?php esc_html_e( 'Hiển thị danh mục', 'developer-theme' ); ?>
            </label>
        </p>

        <!-- Lọc theo danh mục -->
        <p>
            <label for="<?php echo $this->get_field_id( 'category' ); ?>">
                <?php esc_html_e( 'Danh mục:', 'developer-theme' ); ?>
            </label>
            <?php
            wp_dropdown_categories( array(
                'show_option_all' => __( 'Tất cả danh mục', 'developer-theme' ),
                'orderby'         => 'name',
                'selected'        => $category,
                'id'              => $this->get_field_id( 'category' ),
                'name'            => $this->get_field_name( 'category' ),
                'class'           => 'widefat',
            ) );
            ?>
        </p>

        <?php
    }

    /**
     * Update - Lưu settings
     *
     * @param array $new_instance New settings
     * @param array $old_instance Old settings
     * @return array Sanitized settings
     */
    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title']         = sanitize_text_field( $new_instance['title'] );
        $instance['number']        = absint( $new_instance['number'] );
        $instance['show_thumb']    = isset( $new_instance['show_thumb'] );
        $instance['show_date']     = isset( $new_instance['show_date'] );
        $instance['show_category'] = isset( $new_instance['show_category'] );
        $instance['category']      = absint( $new_instance['category'] );
        return $instance;
    }
}

/**
 * Đăng ký widget
 */
function developer_register_widgets() {
    register_widget( 'Developer_Recent_Posts_Widget' );
}
add_action( 'widgets_init', 'developer_register_widgets' );
```

### Widget CTA (Call to Action):

```php
<?php
/**
 * Widget CTA - Hiển thị khung kêu gọi hành động
 */
class Developer_CTA_Widget extends WP_Widget {

    public function __construct() {
        parent::__construct(
            'developer_cta',
            __( 'Dev: CTA Box', 'developer-theme' ),
            array(
                'description' => __( 'Khung kêu gọi hành động với nút bấm.', 'developer-theme' ),
                'classname'   => 'developer-cta-widget',
            )
        );
    }

    public function widget( $args, $instance ) {
        $title   = ! empty( $instance['title'] ) ? $instance['title'] : '';
        $text    = ! empty( $instance['text'] ) ? $instance['text'] : '';
        $btn_text = ! empty( $instance['button_text'] ) ? $instance['button_text'] : __( 'Tìm Hiểu Thêm', 'developer-theme' );
        $btn_url = ! empty( $instance['button_url'] ) ? $instance['button_url'] : '#';
        $bg_color = ! empty( $instance['bg_color'] ) ? $instance['bg_color'] : '#0073aa';

        echo $args['before_widget'];
        ?>
        <div class="cta-box" style="background-color: <?php echo esc_attr( $bg_color ); ?>;">
            <?php if ( $title ) : ?>
                <h3 class="cta-title"><?php echo esc_html( $title ); ?></h3>
            <?php endif; ?>

            <?php if ( $text ) : ?>
                <p class="cta-text"><?php echo esc_html( $text ); ?></p>
            <?php endif; ?>

            <a href="<?php echo esc_url( $btn_url ); ?>" class="cta-button">
                <?php echo esc_html( $btn_text ); ?>
            </a>
        </div>
        <?php
        echo $args['after_widget'];
    }

    public function form( $instance ) {
        $title    = isset( $instance['title'] ) ? $instance['title'] : '';
        $text     = isset( $instance['text'] ) ? $instance['text'] : '';
        $btn_text = isset( $instance['button_text'] ) ? $instance['button_text'] : __( 'Tìm Hiểu Thêm', 'developer-theme' );
        $btn_url  = isset( $instance['button_url'] ) ? $instance['button_url'] : '';
        $bg_color = isset( $instance['bg_color'] ) ? $instance['bg_color'] : '#0073aa';
        ?>
        <p>
            <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Tiêu đề:', 'developer-theme' ); ?></label>
            <input class="widefat" type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'text' ); ?>"><?php esc_html_e( 'Nội dung:', 'developer-theme' ); ?></label>
            <textarea class="widefat" rows="3" id="<?php echo $this->get_field_id( 'text' ); ?>" name="<?php echo $this->get_field_name( 'text' ); ?>"><?php echo esc_textarea( $text ); ?></textarea>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'button_text' ); ?>"><?php esc_html_e( 'Nút bấm:', 'developer-theme' ); ?></label>
            <input class="widefat" type="text" id="<?php echo $this->get_field_id( 'button_text' ); ?>" name="<?php echo $this->get_field_name( 'button_text' ); ?>" value="<?php echo esc_attr( $btn_text ); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'button_url' ); ?>"><?php esc_html_e( 'URL:', 'developer-theme' ); ?></label>
            <input class="widefat" type="url" id="<?php echo $this->get_field_id( 'button_url' ); ?>" name="<?php echo $this->get_field_name( 'button_url' ); ?>" value="<?php echo esc_url( $btn_url ); ?>" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id( 'bg_color' ); ?>"><?php esc_html_e( 'Màu nền:', 'developer-theme' ); ?></label>
            <input class="widefat" type="color" id="<?php echo $this->get_field_id( 'bg_color' ); ?>" name="<?php echo $this->get_field_name( 'bg_color' ); ?>" value="<?php echo esc_attr( $bg_color ); ?>" />
        </p>
        <?php
    }

    public function update( $new_instance, $old_instance ) {
        $instance = $old_instance;
        $instance['title']       = sanitize_text_field( $new_instance['title'] );
        $instance['text']        = sanitize_textarea_field( $new_instance['text'] );
        $instance['button_text'] = sanitize_text_field( $new_instance['button_text'] );
        $instance['button_url']  = esc_url_raw( $new_instance['button_url'] );
        $instance['bg_color']    = sanitize_hex_color( $new_instance['bg_color'] );
        return $instance;
    }
}
```

---

## 7. Footer Widgets

### Hiển thị Footer Widgets:

```php
<?php
/**
 * template-parts/footer/footer-widgets.php
 *
 * Hiển thị 3 cột footer widgets
 * Gọi từ footer.php: get_template_part( 'template-parts/footer/footer-widgets' );
 */

// Đếm số cột footer có widget
$footer_columns = 0;
for ( $i = 1; $i <= 3; $i++ ) {
    if ( is_active_sidebar( 'footer-' . $i ) ) {
        $footer_columns++;
    }
}

// Không có widget nào -> không hiển thị
if ( $footer_columns === 0 ) {
    return;
}
?>

<div class="footer-widgets-area">
    <div class="container">
        <div class="footer-widgets-grid columns-<?php echo $footer_columns; ?>">

            <?php for ( $i = 1; $i <= 3; $i++ ) : ?>
                <?php if ( is_active_sidebar( 'footer-' . $i ) ) : ?>
                    <div class="footer-widget-column">
                        <?php dynamic_sidebar( 'footer-' . $i ); ?>
                    </div>
                <?php endif; ?>
            <?php endfor; ?>

        </div>
    </div>
</div>
```

### CSS cho Footer Widgets:

```css
.footer-widgets-area {
    background: #23282d;
    color: rgba(255, 255, 255, 0.8);
    padding: 3rem 0;
}

.footer-widgets-grid {
    display: grid;
    gap: 2rem;
}

.footer-widgets-grid.columns-1 { grid-template-columns: 1fr; }
.footer-widgets-grid.columns-2 { grid-template-columns: repeat(2, 1fr); }
.footer-widgets-grid.columns-3 { grid-template-columns: repeat(3, 1fr); }

.footer-widget-column .widget {
    margin-bottom: 1.5rem;
}

.footer-widget-column .widget-title {
    color: #fff;
    font-size: 1.125rem;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #0073aa;
}

.footer-widget-column .widget a {
    color: rgba(255, 255, 255, 0.7);
    transition: color 0.3s;
}

.footer-widget-column .widget a:hover {
    color: #fff;
}

.footer-widget-column .widget ul {
    list-style: none;
    padding: 0;
}

.footer-widget-column .widget li {
    padding: 0.375rem 0;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

@media (max-width: 768px) {
    .footer-widgets-grid {
        grid-template-columns: 1fr !important;
    }
}
```

---

## 8. Code ví dụ: Theme hoàn chỉnh

### functions.php (phần menu và widgets):

```php
<?php
/**
 * Toàn bộ đăng ký menu, sidebar, widgets
 * Thêm vào functions.php của theme
 */

// === MENUS ===
function developer_complete_setup() {
    register_nav_menus( array(
        'primary'   => __( 'Menu Chính', 'developer-theme' ),
        'secondary' => __( 'Menu Top Bar', 'developer-theme' ),
        'footer'    => __( 'Menu Footer', 'developer-theme' ),
        'mobile'    => __( 'Menu Mobile', 'developer-theme' ),
    ) );
}
add_action( 'after_setup_theme', 'developer_complete_setup' );

// === SIDEBARS ===
function developer_complete_widgets_init() {
    // Main sidebar
    register_sidebar( array(
        'name'          => __( 'Sidebar Chính', 'developer-theme' ),
        'id'            => 'sidebar-main',
        'before_widget' => '<div id="%1$s" class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ) );

    // Footer 1-4
    for ( $i = 1; $i <= 4; $i++ ) {
        register_sidebar( array(
            'name'          => sprintf( __( 'Footer %d', 'developer-theme' ), $i ),
            'id'            => 'footer-' . $i,
            'before_widget' => '<div id="%1$s" class="widget %2$s">',
            'after_widget'  => '</div>',
            'before_title'  => '<h4 class="widget-title">',
            'after_title'   => '</h4>',
        ) );
    }

    // Register custom widgets
    register_widget( 'Developer_Recent_Posts_Widget' );
    register_widget( 'Developer_CTA_Widget' );
}
add_action( 'widgets_init', 'developer_complete_widgets_init' );

// === MOBILE MENU SCRIPTS ===
function developer_complete_scripts() {
    wp_enqueue_script(
        'developer-navigation',
        get_template_directory_uri() . '/assets/js/navigation.js',
        array(),
        '1.0.0',
        true
    );
}
add_action( 'wp_enqueue_scripts', 'developer_complete_scripts' );
```

### header.php hoàn chỉnh với responsive menu:

```php
<?php
/**
 * header.php - Header đầy đủ với top bar, logo, responsive menu
 */
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div id="page" class="site">

    <a class="skip-link screen-reader-text" href="#primary">
        <?php esc_html_e( 'Chuyển đến nội dung', 'developer-theme' ); ?>
    </a>

    <!-- === TOP BAR === -->
    <?php if ( has_nav_menu( 'secondary' ) || is_active_sidebar( 'header-top-bar' ) ) : ?>
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-inner">
                <!-- Thông tin liên hệ -->
                <?php if ( is_active_sidebar( 'header-top-bar' ) ) : ?>
                    <div class="top-bar-info">
                        <?php dynamic_sidebar( 'header-top-bar' ); ?>
                    </div>
                <?php endif; ?>

                <!-- Menu phụ (Login, Register, Language...) -->
                <?php if ( has_nav_menu( 'secondary' ) ) : ?>
                    <nav class="top-bar-nav">
                        <?php
                        wp_nav_menu( array(
                            'theme_location' => 'secondary',
                            'container'      => false,
                            'menu_class'     => 'top-menu',
                            'depth'          => 1,
                            'fallback_cb'    => false,
                        ) );
                        ?>
                    </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- === MAIN HEADER === -->
    <header id="masthead" class="site-header">
        <div class="container">
            <div class="header-inner">

                <!-- Logo / Site Branding -->
                <div class="site-branding">
                    <?php if ( has_custom_logo() ) : ?>
                        <div class="custom-logo-link">
                            <?php the_custom_logo(); ?>
                        </div>
                    <?php else : ?>
                        <?php if ( is_front_page() && is_home() ) : ?>
                            <h1 class="site-title">
                                <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                                    <?php bloginfo( 'name' ); ?>
                                </a>
                            </h1>
                        <?php else : ?>
                            <p class="site-title">
                                <a href="<?php echo esc_url( home_url( '/' ) ); ?>">
                                    <?php bloginfo( 'name' ); ?>
                                </a>
                            </p>
                        <?php endif; ?>

                        <?php
                        $description = get_bloginfo( 'description', 'display' );
                        if ( $description || is_customize_preview() ) :
                        ?>
                            <p class="site-description"><?php echo $description; ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <!-- Desktop Navigation -->
                <nav id="site-navigation" class="main-navigation" aria-label="<?php esc_attr_e( 'Menu Chính', 'developer-theme' ); ?>">
                    <?php if ( has_nav_menu( 'primary' ) ) : ?>
                        <?php
                        wp_nav_menu( array(
                            'theme_location' => 'primary',
                            'container'      => false,
                            'menu_id'        => 'primary-menu',
                            'menu_class'     => 'nav-menu',
                            'depth'          => 3,
                            'fallback_cb'    => false,
                        ) );
                        ?>
                    <?php endif; ?>
                </nav>

                <!-- Header Actions (Search, Cart...) -->
                <div class="header-actions">
                    <!-- Search Toggle -->
                    <button class="search-toggle" aria-label="<?php esc_attr_e( 'Tìm kiếm', 'developer-theme' ); ?>">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8"></circle>
                            <path d="M21 21l-4.35-4.35"></path>
                        </svg>
                    </button>

                    <!-- Mobile Menu Toggle -->
                    <button class="menu-toggle" aria-controls="mobile-menu" aria-expanded="false"
                            aria-label="<?php esc_attr_e( 'Mở menu', 'developer-theme' ); ?>">
                        <span class="hamburger">
                            <span class="hamburger-line"></span>
                            <span class="hamburger-line"></span>
                            <span class="hamburger-line"></span>
                        </span>
                    </button>
                </div>

            </div>
        </div>

        <!-- Search Overlay -->
        <div class="search-overlay" id="search-overlay" hidden>
            <div class="container">
                <?php get_search_form(); ?>
                <button class="search-close" aria-label="<?php esc_attr_e( 'Đóng tìm kiếm', 'developer-theme' ); ?>">
                    &times;
                </button>
            </div>
        </div>
    </header>

    <!-- === MOBILE MENU (Offcanvas) === -->
    <div id="mobile-menu" class="mobile-menu-panel" aria-hidden="true">
        <div class="mobile-menu-header">
            <span class="mobile-menu-title"><?php esc_html_e( 'Menu', 'developer-theme' ); ?></span>
            <button class="mobile-menu-close" aria-label="<?php esc_attr_e( 'Đóng menu', 'developer-theme' ); ?>">
                &times;
            </button>
        </div>
        <div class="mobile-menu-body">
            <?php
            wp_nav_menu( array(
                'theme_location' => has_nav_menu( 'mobile' ) ? 'mobile' : 'primary',
                'container'      => false,
                'menu_class'     => 'mobile-nav-list',
                'depth'          => 2,
                'fallback_cb'    => false,
            ) );
            ?>
        </div>
    </div>
    <div class="mobile-menu-overlay" id="mobile-overlay"></div>

    <!-- === BREADCRUMBS === -->
    <?php if ( ! is_front_page() ) : ?>
        <div class="breadcrumbs-bar">
            <div class="container">
                <?php developer_breadcrumbs(); ?>
            </div>
        </div>
    <?php endif; ?>
```

### assets/js/navigation.js:

```javascript
/**
 * Navigation - Xử lý mobile menu, search toggle, dropdown
 */
(function() {
    'use strict';

    // === MOBILE MENU ===
    const menuToggle  = document.querySelector('.menu-toggle');
    const mobileMenu  = document.getElementById('mobile-menu');
    const mobileClose = document.querySelector('.mobile-menu-close');
    const overlay     = document.getElementById('mobile-overlay');

    function openMobileMenu() {
        if (!mobileMenu) return;
        mobileMenu.classList.add('is-open');
        mobileMenu.setAttribute('aria-hidden', 'false');
        overlay.classList.add('is-visible');
        menuToggle.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden'; // Không cho scroll
    }

    function closeMobileMenu() {
        if (!mobileMenu) return;
        mobileMenu.classList.remove('is-open');
        mobileMenu.setAttribute('aria-hidden', 'true');
        overlay.classList.remove('is-visible');
        menuToggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    if (menuToggle) {
        menuToggle.addEventListener('click', openMobileMenu);
    }
    if (mobileClose) {
        mobileClose.addEventListener('click', closeMobileMenu);
    }
    if (overlay) {
        overlay.addEventListener('click', closeMobileMenu);
    }

    // Đóng khi nhấn Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeMobileMenu();
            closeSearch();
        }
    });

    // === MOBILE SUB-MENU TOGGLE ===
    const mobileHasChildren = document.querySelectorAll('.mobile-nav-list .menu-item-has-children > a');
    mobileHasChildren.forEach(function(link) {
        // Thêm nút toggle bên cạnh link
        const toggle = document.createElement('button');
        toggle.className = 'sub-menu-toggle';
        toggle.innerHTML = '+';
        toggle.setAttribute('aria-expanded', 'false');
        link.parentNode.insertBefore(toggle, link.nextSibling);

        toggle.addEventListener('click', function() {
            const subMenu = this.nextElementSibling;
            const isOpen = subMenu.classList.contains('is-open');

            subMenu.classList.toggle('is-open');
            this.innerHTML = isOpen ? '+' : '-';
            this.setAttribute('aria-expanded', !isOpen);
        });
    });

    // === SEARCH TOGGLE ===
    const searchToggle = document.querySelector('.search-toggle');
    const searchOverlay = document.getElementById('search-overlay');
    const searchClose = document.querySelector('.search-close');

    function openSearch() {
        if (!searchOverlay) return;
        searchOverlay.removeAttribute('hidden');
        searchOverlay.querySelector('input[type="search"]').focus();
    }

    function closeSearch() {
        if (!searchOverlay) return;
        searchOverlay.setAttribute('hidden', '');
    }

    if (searchToggle) {
        searchToggle.addEventListener('click', openSearch);
    }
    if (searchClose) {
        searchClose.addEventListener('click', closeSearch);
    }

    // === DESKTOP KEYBOARD NAVIGATION ===
    // Hỗ trợ tab qua sub-menu
    const navItems = document.querySelectorAll('.nav-menu .menu-item-has-children');
    navItems.forEach(function(item) {
        const links = item.querySelectorAll('a');
        const lastLink = links[links.length - 1];

        // Khi tab ra khỏi item cuối cùng của sub-menu, đóng sub-menu
        if (lastLink) {
            lastLink.addEventListener('blur', function() {
                item.classList.remove('focus');
            });
        }

        // Mở sub-menu khi focus
        item.querySelector('a').addEventListener('focus', function() {
            item.classList.add('focus');
        });
    });

})();
```

---

## 9. Best Practices

### 1. Menu

```php
// Luôn kiểm tra trước khi hiển thị
if ( has_nav_menu( 'primary' ) ) {
    wp_nav_menu( array( 'theme_location' => 'primary' ) );
}

// Dùng fallback_cb = false nếu không muốn hiện gì khi chưa có menu
wp_nav_menu( array( 'fallback_cb' => false ) );

// Dùng theme_location, KHÔNG hard-code menu name
// SAI: 'menu' => 'Main Menu'     (sẽ hỏng nếu đổi tên menu)
// ĐÚNG: 'theme_location' => 'primary'  (luôn đúng)
```

### 2. Sidebar

```php
// Luôn kiểm tra is_active_sidebar trước khi render
if ( is_active_sidebar( 'sidebar-main' ) ) {
    dynamic_sidebar( 'sidebar-main' );
}

// Dùng ID có ý nghĩa, có prefix
// SAI: 'sidebar-1', 'sidebar-2'
// ĐÚNG: 'sidebar-main', 'sidebar-shop', 'footer-1'
```

### 3. Widget

```php
// Luôn sanitize dữ liệu trong update()
$instance['title'] = sanitize_text_field( $new_instance['title'] );
$instance['url']   = esc_url_raw( $new_instance['url'] );
$instance['number'] = absint( $new_instance['number'] );

// Dùng no_found_rows => true cho widget queries (không cần pagination)
$query = new WP_Query( array(
    'no_found_rows' => true,
    'posts_per_page' => 5,
) );
```

### 4. Accessibility

```php
// ARIA labels cho navigation
<nav aria-label="<?php esc_attr_e( 'Menu Chính', 'developer-theme' ); ?>">

// ARIA cho mobile toggle
<button aria-expanded="false" aria-controls="mobile-menu">

// Skip link (đầu trang)
<a class="skip-link screen-reader-text" href="#primary">Skip to content</a>

// Screen reader text cho icon-only buttons
<button>
    <span class="screen-reader-text"><?php esc_html_e( 'Tìm kiếm', 'developer-theme' ); ?></span>
    <svg>...</svg>
</button>
```

### 5. Performance

```php
// Lazy load images trong widget
the_post_thumbnail( 'thumbnail', array( 'loading' => 'lazy' ) );

// no_found_rows cho widget queries
'no_found_rows' => true,

// Giới hạn depth cho menu
'depth' => 2, // Không cần load hết các cấp
```

---

**Tiếp theo:** [05 - Customizer API](./05-customizer-api.md) - Tạo trang tùy chỉnh theme với live preview
