# Block Theme và Full Site Editing (FSE)

## Mục Lục

1. [Block Theme là gì](#1-block-theme-la-gi)
2. [theme.json chi tiết](#2-themejson)
3. [Template Parts: header.html, footer.html](#3-template-parts)
4. [Block Templates](#4-block-templates)
5. [Block Patterns](#5-block-patterns)
6. [Template Editor và Global Styles](#6-template-editor)
7. [Code ví dụ: Block Theme hoàn chỉnh](#7-code-vi-du)
8. [So sánh Classic Theme vs Block Theme](#8-so-sanh)
9. [Best Practices](#9-best-practices)

---

## 1. Block Theme là gì

Block Theme (theme khối) là loại theme **mới** trong WordPress (từ WP 5.9+) sử dụng **block editor** cho TOÀN BỘ trang, không chỉ nội dung bài viết.

### Classic Theme vs Block Theme:

```
CLASSIC THEME (truyền thống):
- PHP templates (index.php, single.php, header.php...)
- Template Hierarchy với PHP
- CSS/JS enqueue trong functions.php
- Customizer API cho tùy chỉnh
- Widgets và Sidebars
- Navigation Menus (wp_nav_menu)

BLOCK THEME (mới):
- HTML templates (index.html, single.html, header.html...)
- Block markup thay vì PHP
- theme.json cho styles và settings
- Site Editor thay vì Customizer
- Block-based Widgets
- Navigation Block thay vì wp_nav_menu
```

### Cấu trúc tối thiểu của Block Theme:

```
my-block-theme/
|-- style.css           # Theme header (giống classic)
|-- theme.json          # Settings và Styles (THAY functions.php phần lớn)
|-- templates/
|   |-- index.html      # Template mặc định (THAY index.php)
|-- parts/
    |-- header.html     # Header template part
    |-- footer.html     # Footer template part
```

### Yêu cầu:
- WordPress 5.9+
- File `templates/index.html` (bắt buộc)
- File `theme.json` (khuyến dụng mạnh)

### Ưu điểm của Block Theme:

| Đặc điểm | Giải thích |
|----------|-----------|
| **No-code editing** | Người dùng có thể chỉnh sửa layout bằng kéo thả |
| **Global Styles** | Thay đổi fonts, colors toàn trang từ 1 nơi |
| **theme.json** | 1 file cấu hình thay vì nhiều PHP files |
| **Portable** | Styles có thể export/import dễ dàng |
| **Performance** | CSS được tối ưu tự động |
| **Tương lai WP** | Đây là hướng phát triển chính của WordPress |

---

## 2. theme.json

`theme.json` là file **trung tâm** của Block Theme. Nó định nghĩa:
- **Settings**: Các tùy chọn cho editor (colors, fonts, spacing...)
- **Styles**: CSS mặc định cho toàn trang và từng block
- **Custom Templates**: Các template tùy chỉnh
- **Template Parts**: Các phần template

### Cấu trúc theme.json đầy đủ:

```json
{
    "$schema": "https://schemas.wp.org/trunk/theme.json",
    "version": 3,

    "settings": {
        "appearanceTools": true,
        "useRootPaddingAwareAlignments": true,

        "color": {
            "custom": true,
            "customDuotone": true,
            "customGradient": true,
            "defaultDuotone": false,
            "defaultGradients": false,
            "defaultPalette": false,

            "palette": [
                {
                    "slug": "primary",
                    "color": "#0073aa",
                    "name": "Xanh Dương Chính"
                },
                {
                    "slug": "secondary",
                    "color": "#23282d",
                    "name": "Xám Đậm"
                },
                {
                    "slug": "accent",
                    "color": "#e74c3c",
                    "name": "Đỏ Nhấn Mạnh"
                },
                {
                    "slug": "light",
                    "color": "#f5f5f5",
                    "name": "Xám Nhạt"
                },
                {
                    "slug": "dark",
                    "color": "#1a1a1a",
                    "name": "Đen"
                },
                {
                    "slug": "white",
                    "color": "#ffffff",
                    "name": "Trắng"
                }
            ],

            "gradients": [
                {
                    "slug": "primary-to-secondary",
                    "gradient": "linear-gradient(135deg, #0073aa 0%, #23282d 100%)",
                    "name": "Chính sang Phụ"
                },
                {
                    "slug": "light-to-white",
                    "gradient": "linear-gradient(180deg, #f5f5f5 0%, #ffffff 100%)",
                    "name": "Nhạt sang Trắng"
                }
            ]
        },

        "typography": {
            "customFontSize": true,
            "dropCap": true,
            "fluid": true,
            "lineHeight": true,
            "letterSpacing": true,
            "textDecoration": true,
            "textTransform": true,
            "writingMode": true,

            "fontFamilies": [
                {
                    "fontFamily": "-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif",
                    "slug": "system",
                    "name": "System Font"
                },
                {
                    "fontFamily": "'Inter', sans-serif",
                    "slug": "inter",
                    "name": "Inter",
                    "fontFace": [
                        {
                            "fontFamily": "Inter",
                            "fontWeight": "400",
                            "fontStyle": "normal",
                            "src": ["file:./assets/fonts/inter/Inter-Regular.woff2"]
                        },
                        {
                            "fontFamily": "Inter",
                            "fontWeight": "500",
                            "fontStyle": "normal",
                            "src": ["file:./assets/fonts/inter/Inter-Medium.woff2"]
                        },
                        {
                            "fontFamily": "Inter",
                            "fontWeight": "600",
                            "fontStyle": "normal",
                            "src": ["file:./assets/fonts/inter/Inter-SemiBold.woff2"]
                        },
                        {
                            "fontFamily": "Inter",
                            "fontWeight": "700",
                            "fontStyle": "normal",
                            "src": ["file:./assets/fonts/inter/Inter-Bold.woff2"]
                        }
                    ]
                },
                {
                    "fontFamily": "'Playfair Display', serif",
                    "slug": "playfair",
                    "name": "Playfair Display",
                    "fontFace": [
                        {
                            "fontFamily": "Playfair Display",
                            "fontWeight": "400 900",
                            "fontStyle": "normal",
                            "src": ["file:./assets/fonts/playfair/PlayfairDisplay-Variable.woff2"]
                        }
                    ]
                }
            ],

            "fontSizes": [
                {
                    "slug": "small",
                    "size": "0.875rem",
                    "name": "Nhỏ",
                    "fluid": {
                        "min": "0.8rem",
                        "max": "0.875rem"
                    }
                },
                {
                    "slug": "medium",
                    "size": "1rem",
                    "name": "Vừa",
                    "fluid": {
                        "min": "0.9rem",
                        "max": "1rem"
                    }
                },
                {
                    "slug": "large",
                    "size": "1.5rem",
                    "name": "Lớn",
                    "fluid": {
                        "min": "1.25rem",
                        "max": "1.5rem"
                    }
                },
                {
                    "slug": "x-large",
                    "size": "2.25rem",
                    "name": "Rất Lớn",
                    "fluid": {
                        "min": "1.75rem",
                        "max": "2.25rem"
                    }
                },
                {
                    "slug": "xx-large",
                    "size": "3.5rem",
                    "name": "Cực Lớn",
                    "fluid": {
                        "min": "2.5rem",
                        "max": "3.5rem"
                    }
                }
            ]
        },

        "spacing": {
            "customSpacingSize": true,
            "units": ["px", "em", "rem", "%", "vw", "vh"],

            "spacingScale": {
                "steps": 7
            },

            "spacingSizes": [
                { "slug": "10", "size": "0.25rem", "name": "1" },
                { "slug": "20", "size": "0.5rem",  "name": "2" },
                { "slug": "30", "size": "1rem",    "name": "3" },
                { "slug": "40", "size": "1.5rem",  "name": "4" },
                { "slug": "50", "size": "2rem",    "name": "5" },
                { "slug": "60", "size": "3rem",    "name": "6" },
                { "slug": "70", "size": "4rem",    "name": "7" },
                { "slug": "80", "size": "6rem",    "name": "8" }
            ]
        },

        "layout": {
            "contentSize": "800px",
            "wideSize": "1200px"
        },

        "border": {
            "color": true,
            "radius": true,
            "style": true,
            "width": true
        },

        "shadow": {
            "presets": [
                {
                    "name": "Nhẹ",
                    "slug": "light",
                    "shadow": "0 2px 4px rgba(0,0,0,0.1)"
                },
                {
                    "name": "Vừa",
                    "slug": "medium",
                    "shadow": "0 4px 8px rgba(0,0,0,0.12)"
                },
                {
                    "name": "Mạnh",
                    "slug": "heavy",
                    "shadow": "0 8px 24px rgba(0,0,0,0.15)"
                }
            ]
        },

        "blocks": {
            "core/button": {
                "border": {
                    "radius": true
                }
            },
            "core/paragraph": {
                "color": {
                    "custom": true
                }
            },
            "core/heading": {
                "color": {
                    "custom": true
                }
            },
            "core/post-title": {
                "typography": {
                    "fontSizes": []
                }
            }
        }
    },

    "styles": {
        "color": {
            "background": "var(--wp--preset--color--white)",
            "text": "var(--wp--preset--color--dark)"
        },

        "typography": {
            "fontFamily": "var(--wp--preset--font-family--inter)",
            "fontSize": "var(--wp--preset--font-size--medium)",
            "lineHeight": "1.7"
        },

        "spacing": {
            "blockGap": "1.5rem",
            "padding": {
                "top": "0",
                "right": "var(--wp--preset--spacing--30)",
                "bottom": "0",
                "left": "var(--wp--preset--spacing--30)"
            }
        },

        "elements": {
            "link": {
                "color": {
                    "text": "var(--wp--preset--color--primary)"
                },
                ":hover": {
                    "color": {
                        "text": "var(--wp--preset--color--accent)"
                    }
                }
            },
            "h1": {
                "typography": {
                    "fontFamily": "var(--wp--preset--font-family--playfair)",
                    "fontSize": "var(--wp--preset--font-size--xx-large)",
                    "fontWeight": "700",
                    "lineHeight": "1.2"
                },
                "color": {
                    "text": "var(--wp--preset--color--secondary)"
                }
            },
            "h2": {
                "typography": {
                    "fontFamily": "var(--wp--preset--font-family--playfair)",
                    "fontSize": "var(--wp--preset--font-size--x-large)",
                    "fontWeight": "700",
                    "lineHeight": "1.3"
                },
                "color": {
                    "text": "var(--wp--preset--color--secondary)"
                }
            },
            "h3": {
                "typography": {
                    "fontSize": "var(--wp--preset--font-size--large)",
                    "fontWeight": "600"
                }
            },
            "button": {
                "color": {
                    "background": "var(--wp--preset--color--primary)",
                    "text": "var(--wp--preset--color--white)"
                },
                "typography": {
                    "fontWeight": "500"
                },
                "border": {
                    "radius": "4px"
                },
                ":hover": {
                    "color": {
                        "background": "var(--wp--preset--color--secondary)"
                    }
                }
            },
            "caption": {
                "typography": {
                    "fontSize": "var(--wp--preset--font-size--small)"
                },
                "color": {
                    "text": "var(--wp--preset--color--secondary)"
                }
            }
        },

        "blocks": {
            "core/site-title": {
                "typography": {
                    "fontSize": "1.5rem",
                    "fontWeight": "700"
                },
                "elements": {
                    "link": {
                        "color": {
                            "text": "var(--wp--preset--color--white)"
                        },
                        ":hover": {
                            "color": {
                                "text": "var(--wp--preset--color--white)"
                            }
                        },
                        "typography": {
                            "textDecoration": "none"
                        }
                    }
                }
            },
            "core/navigation": {
                "typography": {
                    "fontSize": "var(--wp--preset--font-size--small)",
                    "fontWeight": "500"
                },
                "elements": {
                    "link": {
                        "color": {
                            "text": "var(--wp--preset--color--white)"
                        },
                        ":hover": {
                            "color": {
                                "text": "var(--wp--preset--color--light)"
                            }
                        }
                    }
                }
            },
            "core/post-title": {
                "typography": {
                    "fontFamily": "var(--wp--preset--font-family--playfair)",
                    "fontWeight": "700"
                },
                "elements": {
                    "link": {
                        "color": {
                            "text": "var(--wp--preset--color--secondary)"
                        },
                        ":hover": {
                            "color": {
                                "text": "var(--wp--preset--color--primary)"
                            }
                        },
                        "typography": {
                            "textDecoration": "none"
                        }
                    }
                }
            },
            "core/post-date": {
                "color": {
                    "text": "var(--wp--preset--color--secondary)"
                },
                "typography": {
                    "fontSize": "var(--wp--preset--font-size--small)"
                }
            },
            "core/post-excerpt": {
                "typography": {
                    "lineHeight": "1.6"
                }
            },
            "core/query-pagination": {
                "typography": {
                    "fontWeight": "500"
                },
                "elements": {
                    "link": {
                        "color": {
                            "text": "var(--wp--preset--color--primary)"
                        }
                    }
                }
            },
            "core/separator": {
                "color": {
                    "text": "var(--wp--preset--color--light)"
                },
                "border": {
                    "color": "currentColor",
                    "style": "solid",
                    "width": "0 0 1px 0"
                }
            }
        }
    },

    "customTemplates": [
        {
            "name": "full-width",
            "title": "Full Width",
            "postTypes": ["page", "post"]
        },
        {
            "name": "no-title",
            "title": "Không Có Tiêu Đề",
            "postTypes": ["page"]
        },
        {
            "name": "landing-page",
            "title": "Landing Page",
            "postTypes": ["page"]
        }
    ],

    "templateParts": [
        {
            "name": "header",
            "title": "Header",
            "area": "header"
        },
        {
            "name": "footer",
            "title": "Footer",
            "area": "footer"
        },
        {
            "name": "sidebar",
            "title": "Sidebar",
            "area": "uncategorized"
        }
    ]
}
```

### Giải thích CSS Variables tự động tạo:

```css
/* theme.json tự động tạo các CSS variables này: */

/* Colors */
--wp--preset--color--primary: #0073aa;
--wp--preset--color--secondary: #23282d;
--wp--preset--color--accent: #e74c3c;
--wp--preset--color--light: #f5f5f5;
--wp--preset--color--dark: #1a1a1a;
--wp--preset--color--white: #ffffff;

/* Font Families */
--wp--preset--font-family--system: -apple-system, ...;
--wp--preset--font-family--inter: 'Inter', sans-serif;
--wp--preset--font-family--playfair: 'Playfair Display', serif;

/* Font Sizes */
--wp--preset--font-size--small: 0.875rem;
--wp--preset--font-size--medium: 1rem;
--wp--preset--font-size--large: 1.5rem;
--wp--preset--font-size--x-large: 2.25rem;
--wp--preset--font-size--xx-large: 3.5rem;

/* Spacing */
--wp--preset--spacing--10: 0.25rem;
--wp--preset--spacing--20: 0.5rem;
--wp--preset--spacing--30: 1rem;
/* ... */

/* Layout */
--wp--style--global--content-size: 800px;
--wp--style--global--wide-size: 1200px;

/* Shadows */
--wp--preset--shadow--light: 0 2px 4px rgba(0,0,0,0.1);
--wp--preset--shadow--medium: 0 4px 8px rgba(0,0,0,0.12);

/* Bạn có thể dùng chúng trong CSS: */
.my-element {
    color: var(--wp--preset--color--primary);
    font-family: var(--wp--preset--font-family--inter);
    padding: var(--wp--preset--spacing--40);
}
```

---

## 3. Template Parts

### parts/header.html:

```html
<!-- wp:group {"style":{"spacing":{"padding":{"top":"0","bottom":"0"}}},"backgroundColor":"secondary","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-secondary-background-color has-background" style="padding-top:0;padding-bottom:0">

    <!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between","flexWrap":"wrap"}} -->
    <div class="wp-block-group">

        <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"}} -->
        <div class="wp-block-group">
            <!-- wp:site-logo {"width":48} /-->
            <!-- wp:site-title {"level":0} /-->
        </div>
        <!-- /wp:group -->

        <!-- wp:navigation {"overlayMenu":"mobile","layout":{"type":"flex","justifyContent":"right"}} -->
            <!-- wp:navigation-link {"label":"Trang Chủ","url":"/","kind":"custom","isTopLevelLink":true} /-->
            <!-- wp:navigation-link {"label":"Blog","url":"/blog","kind":"custom","isTopLevelLink":true} /-->
            <!-- wp:navigation-link {"label":"Giới Thiệu","url":"/about","kind":"custom","isTopLevelLink":true} /-->
            <!-- wp:navigation-link {"label":"Liên Hệ","url":"/contact","kind":"custom","isTopLevelLink":true} /-->
        <!-- /wp:navigation -->

    </div>
    <!-- /wp:group -->

</div>
<!-- /wp:group -->
```

### Giải thích cú pháp block:

```html
<!-- wp:block-name {"attributes":"values"} -->
<html-output>
    <!-- nội dung bên trong -->
</html-output>
<!-- /wp:block-name -->

<!--
MỖI block bao gồm:
1. Block comment (<!-- wp:group {...} -->)
   - Tên block: wp:group, wp:paragraph, wp:heading...
   - Attributes: JSON object (colors, spacing, layout...)
2. HTML output (giữa opening và closing comment)
3. Closing comment (<!-- /wp:group -->)

Đây là cú pháp của Gutenberg blocks khi lưu vào database
-->

<!-- Ví dụ các block thường dùng: -->

<!-- Heading -->
<!-- wp:heading {"level":2,"textAlign":"center"} -->
<h2 class="has-text-align-center">Tiêu Đề</h2>
<!-- /wp:heading -->

<!-- Paragraph -->
<!-- wp:paragraph {"align":"center","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size">Nội dung</p>
<!-- /wp:paragraph -->

<!-- Image -->
<!-- wp:image {"id":123,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large">
    <img src="image.jpg" alt="Mô tả" class="wp-image-123"/>
</figure>
<!-- /wp:image -->

<!-- Columns (2 cột) -->
<!-- wp:columns -->
<div class="wp-block-columns">
    <!-- wp:column -->
    <div class="wp-block-column">
        <!-- nội dung cột 1 -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column -->
    <div class="wp-block-column">
        <!-- nội dung cột 2 -->
    </div>
    <!-- /wp:column -->
</div>
<!-- /wp:columns -->

<!-- Group (container) -->
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
    <!-- nội dung -->
</div>
<!-- /wp:group -->

<!-- Query Loop (hiển thị danh sách bài viết) -->
<!-- wp:query {"queryId":1,"query":{"perPage":6,"postType":"post"}} -->
<div class="wp-block-query">
    <!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
        <!-- wp:post-featured-image {"isLink":true} /-->
        <!-- wp:post-title {"isLink":true} /-->
        <!-- wp:post-date /-->
        <!-- wp:post-excerpt {"moreText":"Đọc thêm"} /-->
    <!-- /wp:post-template -->

    <!-- wp:query-pagination -->
    <div class="wp-block-query-pagination">
        <!-- wp:query-pagination-previous /-->
        <!-- wp:query-pagination-numbers /-->
        <!-- wp:query-pagination-next /-->
    </div>
    <!-- /wp:query-pagination -->
</div>
<!-- /wp:query -->

<!-- Template Part (include) -->
<!-- wp:template-part {"slug":"header","area":"header"} /-->
<!-- wp:template-part {"slug":"footer","area":"footer"} /-->
```

### parts/footer.html:

```html
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|40"}}},"backgroundColor":"secondary","textColor":"white","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-white-color has-secondary-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--40)">

    <!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|50"}}}} -->
    <div class="wp-block-columns">

        <!-- Cột 1: Giới thiệu -->
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"level":4,"textColor":"white"} -->
            <h4 class="has-white-color has-text-color">Về Chúng Tôi</h4>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"textColor":"light"} -->
            <p class="has-light-color has-text-color">Website chia sẻ kiến thức lập trình và công nghệ. Giúp bạn phát triển sự nghiệp developer.</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- Cột 2: Liên kết -->
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"level":4,"textColor":"white"} -->
            <h4 class="has-white-color has-text-color">Liên Kết</h4>
            <!-- /wp:heading -->

            <!-- wp:navigation {"overlayMenu":"never","layout":{"type":"flex","orientation":"vertical"},"style":{"spacing":{"blockGap":"0.5rem"}}} -->
                <!-- wp:navigation-link {"label":"Trang Chủ","url":"/"} /-->
                <!-- wp:navigation-link {"label":"Blog","url":"/blog"} /-->
                <!-- wp:navigation-link {"label":"Liên Hệ","url":"/contact"} /-->
            <!-- /wp:navigation -->
        </div>
        <!-- /wp:column -->

        <!-- Cột 3: Liên hệ -->
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"level":4,"textColor":"white"} -->
            <h4 class="has-white-color has-text-color">Liên Hệ</h4>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"textColor":"light"} -->
            <p class="has-light-color has-text-color">Email: info@example.com<br>Phone: 0123-456-789</p>
            <!-- /wp:paragraph -->

            <!-- wp:social-links {"iconColor":"white","iconColorValue":"#ffffff","className":"is-style-logos-only"} -->
            <ul class="wp-block-social-links has-icon-color is-style-logos-only">
                <!-- wp:social-link {"url":"https://facebook.com","service":"facebook"} /-->
                <!-- wp:social-link {"url":"https://twitter.com","service":"twitter"} /-->
                <!-- wp:social-link {"url":"https://instagram.com","service":"instagram"} /-->
            </ul>
            <!-- /wp:social-links -->
        </div>
        <!-- /wp:column -->

    </div>
    <!-- /wp:columns -->

    <!-- Copyright -->
    <!-- wp:separator {"backgroundColor":"light","className":"is-style-wide"} -->
    <hr class="wp-block-separator has-text-color has-light-color has-alpha-channel-opacity has-light-background-color has-background is-style-wide"/>
    <!-- /wp:separator -->

    <!-- wp:paragraph {"align":"center","textColor":"light","fontSize":"small"} -->
    <p class="has-text-align-center has-light-color has-text-color has-small-font-size">&copy; 2024 Developer Theme. Built with WordPress.</p>
    <!-- /wp:paragraph -->

</div>
<!-- /wp:group -->
```

---

## 4. Block Templates

### templates/index.html (template mặc định):

```html
<!-- wp:template-part {"slug":"header","area":"header"} /-->

<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">

    <!-- wp:query {"queryId":1,"query":{"perPage":10,"pages":0,"offset":0,"postType":"post","order":"desc","orderBy":"date","author":"","search":"","exclude":[],"sticky":"","inherit":true}} -->
    <div class="wp-block-query">

        <!-- wp:post-template -->
            <!-- wp:group {"style":{"spacing":{"blockGap":"var:preset|spacing|20","padding":{"bottom":"var:preset|spacing|40"}}},"layout":{"type":"constrained"}} -->
            <div class="wp-block-group" style="padding-bottom:var(--wp--preset--spacing--40)">

                <!-- wp:post-featured-image {"isLink":true,"aspectRatio":"16/9","style":{"border":{"radius":"8px"}}} /-->

                <!-- wp:post-title {"isLink":true,"fontSize":"large"} /-->

                <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"},"style":{"spacing":{"blockGap":"var:preset|spacing|20"}},"fontSize":"small"} -->
                <div class="wp-block-group has-small-font-size">
                    <!-- wp:post-date /-->
                    <!-- wp:post-author-name {"isLink":true} /-->
                    <!-- wp:post-terms {"term":"category"} /-->
                </div>
                <!-- /wp:group -->

                <!-- wp:post-excerpt {"moreText":"Đọc thêm →","excerptLength":25} /-->

                <!-- wp:separator {"className":"is-style-wide"} -->
                <hr class="wp-block-separator is-style-wide"/>
                <!-- /wp:separator -->

            </div>
            <!-- /wp:group -->
        <!-- /wp:post-template -->

        <!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
        <div class="wp-block-query-pagination">
            <!-- wp:query-pagination-previous {"label":"← Trước"} /-->
            <!-- wp:query-pagination-numbers /-->
            <!-- wp:query-pagination-next {"label":"Sau →"} /-->
        </div>
        <!-- /wp:query-pagination -->

        <!-- wp:query-no-results -->
        <!-- wp:paragraph {"align":"center"} -->
        <p class="has-text-align-center">Không tìm thấy bài viết nào.</p>
        <!-- /wp:paragraph -->
        <!-- /wp:query-no-results -->

    </div>
    <!-- /wp:query -->

</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","area":"footer"} /-->
```

### templates/single.html:

```html
<!-- wp:template-part {"slug":"header","area":"header"} /-->

<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">

    <!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"720px"}} -->
    <div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">

        <!-- wp:post-terms {"term":"category","style":{"typography":{"textTransform":"uppercase","fontWeight":"600"}},"fontSize":"small"} /-->

        <!-- wp:post-title {"level":1} /-->

        <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap"},"style":{"spacing":{"blockGap":"var:preset|spacing|30"}},"fontSize":"small"} -->
        <div class="wp-block-group has-small-font-size">
            <!-- wp:post-author-name {"isLink":true} /-->
            <!-- wp:post-date /-->
        </div>
        <!-- /wp:group -->

        <!-- wp:post-featured-image {"style":{"border":{"radius":"8px"}},"aspectRatio":"16/9"} /-->

        <!-- wp:post-content {"layout":{"type":"constrained"}} /-->

        <!-- wp:post-terms {"term":"post_tag","prefix":"Tags: "} /-->

        <!-- wp:separator {"className":"is-style-wide"} -->
        <hr class="wp-block-separator is-style-wide"/>
        <!-- /wp:separator -->

        <!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between"}} -->
        <div class="wp-block-group">
            <!-- wp:post-navigation-link {"type":"previous","label":"← Bài trước"} /-->
            <!-- wp:post-navigation-link {"label":"Bài sau →"} /-->
        </div>
        <!-- /wp:group -->

        <!-- wp:comments -->
        <div class="wp-block-comments">
            <!-- wp:comments-title /-->
            <!-- wp:comment-template -->
                <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"}} -->
                <div class="wp-block-group">
                    <!-- wp:avatar {"size":40} /-->
                    <!-- wp:group -->
                    <div class="wp-block-group">
                        <!-- wp:comment-author-name /-->
                        <!-- wp:comment-date /-->
                        <!-- wp:comment-content /-->
                        <!-- wp:comment-reply-link /-->
                        <!-- wp:comment-edit-link /-->
                    </div>
                    <!-- /wp:group -->
                </div>
                <!-- /wp:group -->
            <!-- /wp:comment-template -->
            <!-- wp:comments-pagination -->
            <div class="wp-block-comments-pagination">
                <!-- wp:comments-pagination-previous /-->
                <!-- wp:comments-pagination-numbers /-->
                <!-- wp:comments-pagination-next /-->
            </div>
            <!-- /wp:comments-pagination -->
            <!-- wp:post-comments-form /-->
        </div>
        <!-- /wp:comments -->

    </div>
    <!-- /wp:group -->

</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","area":"footer"} /-->
```

### templates/page.html:

```html
<!-- wp:template-part {"slug":"header","area":"header"} /-->

<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">

    <!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)">
        <!-- wp:post-title {"level":1,"textAlign":"center"} /-->
        <!-- wp:post-featured-image {"style":{"border":{"radius":"8px"}}} /-->
        <!-- wp:post-content {"layout":{"type":"constrained"}} /-->
    </div>
    <!-- /wp:group -->

</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","area":"footer"} /-->
```

### templates/404.html:

```html
<!-- wp:template-part {"slug":"header","area":"header"} /-->

<!-- wp:group {"tagName":"main","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"layout":{"type":"constrained","contentSize":"600px"}} -->
<main class="wp-block-group" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)">

    <!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"8rem"}},"textColor":"light"} -->
    <h1 class="has-text-align-center has-light-color has-text-color" style="font-size:8rem">404</h1>
    <!-- /wp:heading -->

    <!-- wp:heading {"textAlign":"center","level":2} -->
    <h2 class="has-text-align-center">Trang không tồn tại</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">Xin lỗi, trang bạn đang tìm không tồn tại hoặc đã bị di chuyển.</p>
    <!-- /wp:paragraph -->

    <!-- wp:search {"label":"Tìm kiếm","buttonText":"Tìm","buttonPosition":"button-inside","buttonUseIcon":true} /-->

</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","area":"footer"} /-->
```

### templates/search.html:

```html
<!-- wp:template-part {"slug":"header","area":"header"} /-->

<!-- wp:group {"tagName":"main","layout":{"type":"constrained"}} -->
<main class="wp-block-group">

    <!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
    <div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50)">

        <!-- wp:query-title {"type":"search"} /-->
        <!-- wp:search {"label":"Tìm kiếm","buttonText":"Tìm"} /-->

    </div>
    <!-- /wp:group -->

    <!-- wp:query {"queryId":2,"query":{"perPage":10,"postType":"post","inherit":true}} -->
    <div class="wp-block-query">
        <!-- wp:post-template -->
            <!-- wp:group {"layout":{"type":"flex","flexWrap":"nowrap","verticalAlignment":"top"},"style":{"spacing":{"blockGap":"var:preset|spacing|40","padding":{"bottom":"var:preset|spacing|30"}}}} -->
            <div class="wp-block-group" style="padding-bottom:var(--wp--preset--spacing--30)">
                <!-- wp:post-featured-image {"isLink":true,"width":"200px","height":"150px","style":{"border":{"radius":"4px"}}} /-->
                <!-- wp:group -->
                <div class="wp-block-group">
                    <!-- wp:post-title {"isLink":true,"fontSize":"medium"} /-->
                    <!-- wp:post-excerpt {"excerptLength":20} /-->
                    <!-- wp:post-date {"fontSize":"small"} /-->
                </div>
                <!-- /wp:group -->
            </div>
            <!-- /wp:group -->
        <!-- /wp:post-template -->

        <!-- wp:query-pagination -->
        <div class="wp-block-query-pagination">
            <!-- wp:query-pagination-previous /-->
            <!-- wp:query-pagination-numbers /-->
            <!-- wp:query-pagination-next /-->
        </div>
        <!-- /wp:query-pagination -->

        <!-- wp:query-no-results -->
        <!-- wp:paragraph -->
        <p>Không tìm thấy kết quả nào. Hãy thử với từ khóa khác.</p>
        <!-- /wp:paragraph -->
        <!-- /wp:query-no-results -->
    </div>
    <!-- /wp:query -->

</main>
<!-- /wp:group -->

<!-- wp:template-part {"slug":"footer","area":"footer"} /-->
```

---

## 5. Block Patterns

### Đăng ký Block Patterns trong functions.php:

```php
<?php
/**
 * Block Patterns - Các mẫu block có sẵn để người dùng chèn nhanh
 *
 * Tương tự "Blade components" trong Laravel nhưng dùng blocks
 */

/**
 * Đăng ký Pattern Category
 */
function developer_register_pattern_categories() {
    register_block_pattern_category( 'developer-theme', array(
        'label' => __( 'Developer Theme', 'developer-theme' ),
    ) );

    register_block_pattern_category( 'developer-hero', array(
        'label' => __( 'Hero Sections', 'developer-theme' ),
    ) );

    register_block_pattern_category( 'developer-cta', array(
        'label' => __( 'Call to Action', 'developer-theme' ),
    ) );
}
add_action( 'init', 'developer_register_pattern_categories' );

/**
 * Đăng ký Patterns
 */
function developer_register_patterns() {

    // === Pattern 1: Hero Section ===
    register_block_pattern( 'developer-theme/hero-section', array(
        'title'       => __( 'Hero Section', 'developer-theme' ),
        'description' => __( 'Phần hero với tiêu đề, mô tả và nút bấm.', 'developer-theme' ),
        'categories'  => array( 'developer-theme', 'developer-hero' ),
        'keywords'    => array( 'hero', 'banner', 'header' ),
        'viewportWidth' => 1200,
        'content'     => '
            <!-- wp:cover {"overlayColor":"secondary","minHeight":500,"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}}} -->
            <div class="wp-block-cover alignfull" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70);min-height:500px">
                <span aria-hidden="true" class="wp-block-cover__background has-secondary-background-color has-background-dim-100 has-background-dim"></span>
                <div class="wp-block-cover__inner-container">
                    <!-- wp:group {"layout":{"type":"constrained","contentSize":"700px"}} -->
                    <div class="wp-block-group">
                        <!-- wp:heading {"textAlign":"center","level":1,"textColor":"white","fontSize":"xx-large"} -->
                        <h1 class="has-text-align-center has-white-color has-text-color has-xx-large-font-size">Chào Mừng Đến Với Website</h1>
                        <!-- /wp:heading -->

                        <!-- wp:paragraph {"align":"center","textColor":"light","fontSize":"large"} -->
                        <p class="has-text-align-center has-light-color has-text-color has-large-font-size">Mô tả ngắn gọn về website hoặc dịch vụ của bạn. Tạo ấn tượng mạnh với khách hàng.</p>
                        <!-- /wp:paragraph -->

                        <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
                        <div class="wp-block-buttons">
                            <!-- wp:button {"backgroundColor":"primary","textColor":"white"} -->
                            <div class="wp-block-button"><a class="wp-block-button__link has-white-color has-primary-background-color has-text-color has-background wp-element-button">Bắt Đầu Ngay</a></div>
                            <!-- /wp:button -->

                            <!-- wp:button {"className":"is-style-outline"} -->
                            <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button">Tìm Hiểu Thêm</a></div>
                            <!-- /wp:button -->
                        </div>
                        <!-- /wp:buttons -->
                    </div>
                    <!-- /wp:group -->
                </div>
            </div>
            <!-- /wp:cover -->
        ',
    ) );

    // === Pattern 2: CTA Section ===
    register_block_pattern( 'developer-theme/cta-section', array(
        'title'      => __( 'CTA Section', 'developer-theme' ),
        'categories' => array( 'developer-theme', 'developer-cta' ),
        'content'    => '
            <!-- wp:group {"align":"full","backgroundColor":"primary","textColor":"white","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained","contentSize":"600px"}} -->
            <div class="wp-block-group alignfull has-white-color has-primary-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">

                <!-- wp:heading {"textAlign":"center","textColor":"white"} -->
                <h2 class="has-text-align-center has-white-color has-text-color">Sẵn Sàng Bắt Đầu?</h2>
                <!-- /wp:heading -->

                <!-- wp:paragraph {"align":"center"} -->
                <p class="has-text-align-center">Liên hệ với chúng tôi ngay hôm nay để được tư vấn miễn phí.</p>
                <!-- /wp:paragraph -->

                <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
                <div class="wp-block-buttons">
                    <!-- wp:button {"backgroundColor":"white","textColor":"primary"} -->
                    <div class="wp-block-button"><a class="wp-block-button__link has-primary-color has-white-background-color has-text-color has-background wp-element-button">Liên Hệ Ngay</a></div>
                    <!-- /wp:button -->
                </div>
                <!-- /wp:buttons -->

            </div>
            <!-- /wp:group -->
        ',
    ) );

    // === Pattern 3: Features Grid (3 cột) ===
    register_block_pattern( 'developer-theme/features-grid', array(
        'title'      => __( 'Features Grid', 'developer-theme' ),
        'categories' => array( 'developer-theme' ),
        'content'    => '
            <!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"backgroundColor":"light","layout":{"type":"constrained"}} -->
            <div class="wp-block-group alignfull has-light-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">

                <!-- wp:heading {"textAlign":"center"} -->
                <h2 class="has-text-align-center">Tính Năng Nổi Bật</h2>
                <!-- /wp:heading -->

                <!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40"}}}} -->
                <div class="wp-block-columns">

                    <!-- wp:column -->
                    <div class="wp-block-column">
                        <!-- wp:heading {"level":3,"textAlign":"center","fontSize":"x-large"} -->
                        <h3 class="has-text-align-center has-x-large-font-size">&#9889;</h3>
                        <!-- /wp:heading -->
                        <!-- wp:heading {"level":4,"textAlign":"center"} -->
                        <h4 class="has-text-align-center">Nhanh Chóng</h4>
                        <!-- /wp:heading -->
                        <!-- wp:paragraph {"align":"center"} -->
                        <p class="has-text-align-center">Tốc độ tải trang siêu nhanh, tối ưu cho trải nghiệm người dùng tốt nhất.</p>
                        <!-- /wp:paragraph -->
                    </div>
                    <!-- /wp:column -->

                    <!-- wp:column -->
                    <div class="wp-block-column">
                        <!-- wp:heading {"level":3,"textAlign":"center","fontSize":"x-large"} -->
                        <h3 class="has-text-align-center has-x-large-font-size">&#128274;</h3>
                        <!-- /wp:heading -->
                        <!-- wp:heading {"level":4,"textAlign":"center"} -->
                        <h4 class="has-text-align-center">Bảo Mật</h4>
                        <!-- /wp:heading -->
                        <!-- wp:paragraph {"align":"center"} -->
                        <p class="has-text-align-center">Bảo mật đa tầng, bảo vệ dữ liệu người dùng với các tiêu chuẩn cao nhất.</p>
                        <!-- /wp:paragraph -->
                    </div>
                    <!-- /wp:column -->

                    <!-- wp:column -->
                    <div class="wp-block-column">
                        <!-- wp:heading {"level":3,"textAlign":"center","fontSize":"x-large"} -->
                        <h3 class="has-text-align-center has-x-large-font-size">&#127912;</h3>
                        <!-- /wp:heading -->
                        <!-- wp:heading {"level":4,"textAlign":"center"} -->
                        <h4 class="has-text-align-center">Đẹp Mắt</h4>
                        <!-- /wp:heading -->
                        <!-- wp:paragraph {"align":"center"} -->
                        <p class="has-text-align-center">Giao diện hiện đại, tùy chỉnh linh hoạt, tương thích mọi thiết bị.</p>
                        <!-- /wp:paragraph -->
                    </div>
                    <!-- /wp:column -->

                </div>
                <!-- /wp:columns -->

            </div>
            <!-- /wp:group -->
        ',
    ) );
}
add_action( 'init', 'developer_register_patterns' );
```

### Patterns từ file riêng (WP 6.0+):

```
my-block-theme/
|-- patterns/
    |-- hero.php          # Tự động đăng ký pattern
    |-- cta.php
    |-- features.php
```

```php
<?php
/**
 * patterns/hero.php
 *
 * Title: Hero Section
 * Slug: developer-theme/hero
 * Categories: developer-theme, developer-hero
 * Keywords: hero, banner
 * Viewport Width: 1200
 * Block Types: core/post-content
 * Post Types: page
 * Inserter: true
 */
?>

<!-- wp:cover {"overlayColor":"secondary","minHeight":500,"align":"full"} -->
<div class="wp-block-cover alignfull" style="min-height:500px">
    <span aria-hidden="true" class="wp-block-cover__background has-secondary-background-color has-background-dim-100 has-background-dim"></span>
    <div class="wp-block-cover__inner-container">
        <!-- wp:heading {"textAlign":"center","level":1,"textColor":"white"} -->
        <h1 class="has-text-align-center has-white-color has-text-color"><?php esc_html_e( 'Tiêu Đề Chính', 'developer-theme' ); ?></h1>
        <!-- /wp:heading -->

        <!-- wp:paragraph {"align":"center","textColor":"light"} -->
        <p class="has-text-align-center has-light-color has-text-color"><?php esc_html_e( 'Mô tả ngắn gọn về website của bạn.', 'developer-theme' ); ?></p>
        <!-- /wp:paragraph -->
    </div>
</div>
<!-- /wp:cover -->
```

---

## 6. Template Editor và Global Styles

### Template Editor:

```
Truy cập:
- Admin > Appearance > Editor (hoặc Site Editor)
- Hoặc click "Edit Site" trên Admin Bar

Chức năng:
1. Chỉnh sửa templates (index, single, page, archive, 404...)
2. Chỉnh sửa template parts (header, footer, sidebar)
3. Thêm/xóa blocks từ template
4. Thay đổi layout không cần code

Lưu ý:
- Khi người dùng chỉnh sửa template trong editor, thay đổi được lưu trong database
- Template gốc trong theme không bị ảnh hưởng
- Có thể "Reset" về template gốc bất cứ lúc nào
```

### Global Styles:

```
Truy cập:
- Trong Site Editor > Click icon "Styles" (hình tròn nửa đen nửa trắng)

Chức năng:
1. Thay đổi fonts, colors cho TOÀN TRANG
2. Tùy chỉnh style cho từng LOẠI BLOCK
3. Export/Import style presets

Global Styles ghi đè lên theme.json:
theme.json (developer tạo) < Global Styles (người dùng tùy chỉnh)
```

### Style Variations:

```
Tạo file styles/dark.json để có thêm giao diện "Dark Mode":

my-block-theme/
|-- styles/
    |-- dark.json
    |-- warm.json
    |-- minimal.json
```

```json
// styles/dark.json
{
    "$schema": "https://schemas.wp.org/trunk/theme.json",
    "version": 3,
    "title": "Dark Mode",
    "settings": {
        "color": {
            "palette": [
                {
                    "slug": "primary",
                    "color": "#6cb4ee",
                    "name": "Primary"
                },
                {
                    "slug": "secondary",
                    "color": "#c0c0c0",
                    "name": "Secondary"
                },
                {
                    "slug": "dark",
                    "color": "#1a1a2e",
                    "name": "Dark"
                },
                {
                    "slug": "white",
                    "color": "#e0e0e0",
                    "name": "White"
                },
                {
                    "slug": "light",
                    "color": "#2d2d44",
                    "name": "Light"
                }
            ]
        }
    },
    "styles": {
        "color": {
            "background": "#1a1a2e",
            "text": "#e0e0e0"
        }
    }
}
```

---

## 7. Code ví dụ: Block Theme hoàn chỉnh

### Cấu trúc thư mục:

```
developer-block-theme/
|-- style.css
|-- theme.json
|-- functions.php
|
|-- templates/
|   |-- index.html
|   |-- single.html
|   |-- page.html
|   |-- archive.html
|   |-- search.html
|   |-- 404.html
|   |-- home.html
|   |-- full-width.html
|
|-- parts/
|   |-- header.html
|   |-- footer.html
|   |-- sidebar.html
|
|-- patterns/
|   |-- hero.php
|   |-- cta.php
|   |-- features.php
|   |-- testimonials.php
|
|-- styles/
|   |-- dark.json
|   |-- warm.json
|
|-- assets/
|   |-- fonts/
|   |   |-- inter/
|   |       |-- Inter-Regular.woff2
|   |       |-- Inter-Medium.woff2
|   |       |-- Inter-SemiBold.woff2
|   |       |-- Inter-Bold.woff2
|   |-- css/
|   |   |-- custom.css
|   |-- images/
|       |-- default-thumbnail.jpg
|
|-- screenshot.png
```

### functions.php (đơn giản hơn Classic Theme):

```php
<?php
/**
 * Block Theme functions
 *
 * Block Theme cần ít code PHP hơn Classic Theme vì theme.json
 * đã xử lý phần lớn settings và styles
 *
 * @package Developer_Block_Theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Theme setup
 */
function developer_block_theme_setup() {
    // Đa ngôn ngữ
    load_theme_textdomain( 'developer-block-theme', get_template_directory() . '/languages' );

    // Featured images
    add_theme_support( 'post-thumbnails' );

    // Custom image sizes
    add_image_size( 'developer-card', 600, 400, true );
    add_image_size( 'developer-hero', 1920, 600, true );

    // Editor styles
    add_theme_support( 'editor-styles' );
    add_editor_style( 'assets/css/custom.css' );

    // WP Block Styles
    add_theme_support( 'wp-block-styles' );

    // Responsive embeds
    add_theme_support( 'responsive-embeds' );
}
add_action( 'after_setup_theme', 'developer_block_theme_setup' );

/**
 * Enqueue styles (ít hơn Classic Theme vì theme.json đã xử lý nhiều)
 */
function developer_block_theme_scripts() {
    // Style chính
    wp_enqueue_style(
        'developer-block-theme-style',
        get_stylesheet_uri(),
        array(),
        wp_get_theme()->get( 'Version' )
    );

    // Custom CSS bổ sung
    wp_enqueue_style(
        'developer-block-theme-custom',
        get_template_directory_uri() . '/assets/css/custom.css',
        array( 'developer-block-theme-style' ),
        wp_get_theme()->get( 'Version' )
    );
}
add_action( 'wp_enqueue_scripts', 'developer_block_theme_scripts' );

/**
 * Đăng ký Block Pattern Categories
 */
function developer_block_theme_pattern_categories() {
    register_block_pattern_category( 'developer-block-theme', array(
        'label' => __( 'Developer Block Theme', 'developer-block-theme' ),
    ) );
}
add_action( 'init', 'developer_block_theme_pattern_categories' );

/**
 * Đăng ký Block Styles
 * Thêm các style variants cho blocks có sẵn
 */
function developer_block_theme_register_block_styles() {
    // Style mới cho Group block: Card style
    register_block_style( 'core/group', array(
        'name'  => 'card',
        'label' => __( 'Card', 'developer-block-theme' ),
    ) );

    // Style mới cho Group block: Shadow
    register_block_style( 'core/group', array(
        'name'  => 'shadow',
        'label' => __( 'Shadow', 'developer-block-theme' ),
    ) );

    // Style mới cho Image: Rounded
    register_block_style( 'core/image', array(
        'name'  => 'rounded-full',
        'label' => __( 'Tròn', 'developer-block-theme' ),
    ) );

    // Style mới cho Button: Arrow
    register_block_style( 'core/button', array(
        'name'  => 'arrow',
        'label' => __( 'Với Mũi Tên', 'developer-block-theme' ),
    ) );
}
add_action( 'init', 'developer_block_theme_register_block_styles' );
```

### assets/css/custom.css:

```css
/**
 * Custom CSS bổ sung cho Block Theme
 * theme.json đã xử lý phần lớn styles,
 * file này chỉ chứa những gì theme.json không làm được
 */

/* === ANIMATIONS === */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* === CUSTOM BLOCK STYLES === */

/* Group: Card style */
.is-style-card {
    background: #fff;
    border-radius: 8px;
    padding: 2rem;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    transition: box-shadow 0.3s ease;
}

.is-style-card:hover {
    box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
}

/* Group: Shadow style */
.is-style-shadow {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
}

/* Image: Rounded full */
.is-style-rounded-full img {
    border-radius: 50%;
}

/* Button: Arrow style */
.is-style-arrow .wp-block-button__link::after {
    content: ' \2192'; /* Right arrow */
    margin-left: 0.5em;
    transition: margin-left 0.3s;
}

.is-style-arrow .wp-block-button__link:hover::after {
    margin-left: 0.75em;
}

/* === SMOOTH SCROLL === */
html {
    scroll-behavior: smooth;
}

/* === ACCESSIBILITY === */
.screen-reader-text {
    clip: rect(1px, 1px, 1px, 1px);
    clip-path: inset(50%);
    height: 1px;
    margin: -1px;
    overflow: hidden;
    padding: 0;
    position: absolute;
    width: 1px;
    word-wrap: normal !important;
}

.screen-reader-text:focus {
    clip: auto !important;
    clip-path: none;
    height: auto;
    width: auto;
    z-index: 100000;
    background: #fff;
    padding: 1rem;
}

/* === RESPONSIVE ADJUSTMENTS === */
@media (max-width: 768px) {
    /* Giảm gap trên mobile */
    .wp-block-columns {
        gap: 1rem;
    }

    /* Stack columns trên mobile */
    .wp-block-column {
        flex-basis: 100% !important;
    }
}
```

---

## 8. So sánh Classic Theme vs Block Theme

### Mapping file:

```
CLASSIC THEME                  BLOCK THEME
-----------                    -----------
header.php                     parts/header.html
footer.php                     parts/footer.html
sidebar.php                    parts/sidebar.html
index.php                      templates/index.html
single.php                     templates/single.html
page.php                       templates/page.html
archive.php                    templates/archive.html
search.php                     templates/search.html
404.php                        templates/404.html
functions.php (phần lớn)       theme.json
style.css (styles)             theme.json styles + style.css
functions.php (enqueue)        theme.json fontFaces + functions.php
inc/customizer.php             Global Styles UI (trên trình)
template-parts/                patterns/
wp_nav_menu()                  <!-- wp:navigation -->
dynamic_sidebar()              Không cần (dùng blocks trực tiếp)
```

### So sánh code:

```php
// === CLASSIC: Hiển thị header ===
// header.php (PHP)
<header>
    <div class="container">
        <?php the_custom_logo(); ?>
        <h1><?php bloginfo('name'); ?></h1>
        <?php wp_nav_menu(array('theme_location' => 'primary')); ?>
    </div>
</header>

// === BLOCK: Hiển thị header ===
// parts/header.html (Block markup)
<!-- wp:group {"backgroundColor":"secondary","layout":{"type":"constrained"}} -->
<div class="wp-block-group has-secondary-background-color has-background">
    <!-- wp:group {"layout":{"type":"flex","justifyContent":"space-between"}} -->
    <div class="wp-block-group">
        <!-- wp:site-logo /-->
        <!-- wp:site-title /-->
        <!-- wp:navigation /-->
    </div>
    <!-- /wp:group -->
</div>
<!-- /wp:group -->


// === CLASSIC: Loop hiển thị bài viết ===
// index.php (PHP)
<?php while (have_posts()) : the_post(); ?>
    <article>
        <?php the_post_thumbnail(); ?>
        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <p><?php the_excerpt(); ?></p>
    </article>
<?php endwhile; ?>
<?php the_posts_pagination(); ?>

// === BLOCK: Loop hiển thị bài viết ===
// templates/index.html (Block markup)
<!-- wp:query {"query":{"inherit":true}} -->
<div class="wp-block-query">
    <!-- wp:post-template -->
        <!-- wp:post-featured-image {"isLink":true} /-->
        <!-- wp:post-title {"isLink":true} /-->
        <!-- wp:post-excerpt /-->
    <!-- /wp:post-template -->
    <!-- wp:query-pagination -->
    <div class="wp-block-query-pagination">
        <!-- wp:query-pagination-previous /-->
        <!-- wp:query-pagination-numbers /-->
        <!-- wp:query-pagination-next /-->
    </div>
    <!-- /wp:query-pagination -->
</div>
<!-- /wp:query -->


// === CLASSIC: Styles và settings ===
// functions.php
add_theme_support('editor-color-palette', array(
    array('name' => 'Primary', 'slug' => 'primary', 'color' => '#0073aa'),
));
wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/...');

// === BLOCK: Styles và settings ===
// theme.json
{
    "settings": {
        "color": {
            "palette": [
                {"slug": "primary", "color": "#0073aa", "name": "Primary"}
            ]
        },
        "typography": {
            "fontFamilies": [
                {"fontFamily": "'Inter'", "slug": "inter", "fontFace": [...]}
            ]
        }
    }
}
```

### Khi nào dùng Classic vs Block Theme?

| Tình huống | Nên dùng |
|------------|----------|
| Dự án mới, WordPress 6.0+ | Block Theme |
| Cần tùy chỉnh phức tạp bằng PHP | Classic Theme |
| Người dùng muốn tự chỉnh sửa layout | Block Theme |
| Site đơn giản, blog, portfolio | Block Theme |
| WooCommerce phức tạp | Classic Theme (hỗ trợ tốt hơn) |
| Theme đã có sẵn cần cập nhật | Classic Theme (dễ maintain) |

---

## 9. Best Practices

### 1. Dùng theme.json tối đa

```json
// Dùng theme.json cho colors, fonts, spacing thay vì CSS
// WordPress sẽ tự động tạo CSS tối ưu

// ĐÚNG: Định nghĩa trong theme.json
"settings": {
    "color": {
        "palette": [{"slug": "primary", "color": "#0073aa"}]
    }
}

// SAI: Hard-code CSS
.element { color: #0073aa; }

// ĐÚNG: Dùng CSS variable
.element { color: var(--wp--preset--color--primary); }
```

### 2. Fluid Typography

```json
// Dùng fluid font sizes để responsive tự động
"fontSizes": [
    {
        "slug": "large",
        "size": "1.5rem",
        "fluid": {
            "min": "1.25rem",
            "max": "1.5rem"
        }
    }
]
// WordPress sẽ tự động tính font size theo viewport width
```

### 3. Patterns cho content phức tạp

```php
// Thay vì hard-code layout trong template,
// tạo patterns để người dùng có thể tái sử dụng

// patterns/testimonial.php
// patterns/pricing-table.php
// patterns/team-members.php
```

### 4. Style Variations cho flexibility

```
// Tạo nhiều style variations
styles/
  dark.json      -- Dark mode
  warm.json      -- Warm colors
  minimal.json   -- Minimalist
  bold.json      -- Bold typography

// Người dùng chọn trong: Site Editor > Styles > Browse styles
```

### 5. Fonts tự host (không dùng CDN)

```json
// Tự host fonts để tương thích GDPR
"fontFace": [
    {
        "fontFamily": "Inter",
        "fontWeight": "400",
        "src": ["file:./assets/fonts/inter/Inter-Regular.woff2"]
    }
]
// "file:./..." trỏ đến file trong thư mục theme
```

### 6. functions.php tối giản

```php
// Block Theme cần RẤT ÍT code trong functions.php
// Chỉ dùng cho:
// - load_theme_textdomain()
// - add_image_size()
// - register_block_pattern_category()
// - register_block_style()
// - Custom functionality không làm được bằng theme.json

// KHÔNG cần:
// - register_nav_menus() (dùng Navigation block)
// - register_sidebar() (dùng blocks trực tiếp)
// - add_theme_support('editor-color-palette') (dùng theme.json)
// - wp_enqueue_style('google-fonts') (dùng theme.json fontFace)
```

---

**Tiếp theo:** [07 - Theme Nâng Cao](./07-theme-nang-cao.md) - Child Theme, WooCommerce, Performance, i18n
