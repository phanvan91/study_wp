# Block Theme va Full Site Editing (FSE)

## Muc Luc

1. [Block Theme la gi](#1-block-theme-la-gi)
2. [theme.json chi tiet](#2-themejson)
3. [Template Parts: header.html, footer.html](#3-template-parts)
4. [Block Templates](#4-block-templates)
5. [Block Patterns](#5-block-patterns)
6. [Template Editor va Global Styles](#6-template-editor)
7. [Code vi du: Block Theme hoan chinh](#7-code-vi-du)
8. [So sanh Classic Theme vs Block Theme](#8-so-sanh)
9. [Best Practices](#9-best-practices)

---

## 1. Block Theme la gi

Block Theme (theme khoi) la loai theme **moi** trong WordPress (tu WP 5.9+) su dung **block editor** cho TOAN BO trang, khong chi noi dung bai viet.

### Classic Theme vs Block Theme:

```
CLASSIC THEME (truyen thong):
- PHP templates (index.php, single.php, header.php...)
- Template Hierarchy voi PHP
- CSS/JS enqueue trong functions.php
- Customizer API cho tuy chinh
- Widgets va Sidebars
- Navigation Menus (wp_nav_menu)

BLOCK THEME (moi):
- HTML templates (index.html, single.html, header.html...)
- Block markup thay vi PHP
- theme.json cho styles va settings
- Site Editor thay vi Customizer
- Block-based Widgets
- Navigation Block thay vi wp_nav_menu
```

### Cau truc toi thieu cua Block Theme:

```
my-block-theme/
|-- style.css           # Theme header (giong classic)
|-- theme.json          # Settings va Styles (THAY functions.php phan lon)
|-- templates/
|   |-- index.html      # Template mac dinh (THAY index.php)
|-- parts/
    |-- header.html     # Header template part
    |-- footer.html     # Footer template part
```

### Yeu cau:
- WordPress 5.9+
- File `templates/index.html` (bat buoc)
- File `theme.json` (khuyen dung manh)

### Uu diem cua Block Theme:

| Dac diem | Giai thich |
|----------|-----------|
| **No-code editing** | Nguoi dung co the chinh sua layout bang keo tha |
| **Global Styles** | Thay doi fonts, colors toan trang tu 1 noi |
| **theme.json** | 1 file cau hinh thay vi nhieu PHP files |
| **Portable** | Styles co the export/import de dang |
| **Performance** | CSS duoc toi uu tu dong |
| **Tuong lai WP** | Day la huong phat trien chinh cua WordPress |

---

## 2. theme.json

`theme.json` la file **trung tam** cua Block Theme. No dinh nghia:
- **Settings**: Cac tuy chon cho editor (colors, fonts, spacing...)
- **Styles**: CSS mac dinh cho toan trang va tung block
- **Custom Templates**: Cac template tuy chinh
- **Template Parts**: Cac phan template

### Cau truc theme.json day du:

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
                    "name": "Xanh Duong Chinh"
                },
                {
                    "slug": "secondary",
                    "color": "#23282d",
                    "name": "Xam Dam"
                },
                {
                    "slug": "accent",
                    "color": "#e74c3c",
                    "name": "Do Nhan Manh"
                },
                {
                    "slug": "light",
                    "color": "#f5f5f5",
                    "name": "Xam Nhat"
                },
                {
                    "slug": "dark",
                    "color": "#1a1a1a",
                    "name": "Den"
                },
                {
                    "slug": "white",
                    "color": "#ffffff",
                    "name": "Trang"
                }
            ],

            "gradients": [
                {
                    "slug": "primary-to-secondary",
                    "gradient": "linear-gradient(135deg, #0073aa 0%, #23282d 100%)",
                    "name": "Chinh sang Phu"
                },
                {
                    "slug": "light-to-white",
                    "gradient": "linear-gradient(180deg, #f5f5f5 0%, #ffffff 100%)",
                    "name": "Nhat sang Trang"
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
                    "name": "Nho",
                    "fluid": {
                        "min": "0.8rem",
                        "max": "0.875rem"
                    }
                },
                {
                    "slug": "medium",
                    "size": "1rem",
                    "name": "Vua",
                    "fluid": {
                        "min": "0.9rem",
                        "max": "1rem"
                    }
                },
                {
                    "slug": "large",
                    "size": "1.5rem",
                    "name": "Lon",
                    "fluid": {
                        "min": "1.25rem",
                        "max": "1.5rem"
                    }
                },
                {
                    "slug": "x-large",
                    "size": "2.25rem",
                    "name": "Rat Lon",
                    "fluid": {
                        "min": "1.75rem",
                        "max": "2.25rem"
                    }
                },
                {
                    "slug": "xx-large",
                    "size": "3.5rem",
                    "name": "Cuc Lon",
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
                    "name": "Nhe",
                    "slug": "light",
                    "shadow": "0 2px 4px rgba(0,0,0,0.1)"
                },
                {
                    "name": "Vua",
                    "slug": "medium",
                    "shadow": "0 4px 8px rgba(0,0,0,0.12)"
                },
                {
                    "name": "Manh",
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
            "title": "Khong Co Tieu De",
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

### Giai thich CSS Variables tu dong tao:

```css
/* theme.json tu dong tao cac CSS variables nay: */

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

/* Ban co the dung chung trong CSS: */
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
            <!-- wp:navigation-link {"label":"Trang Chu","url":"/","kind":"custom","isTopLevelLink":true} /-->
            <!-- wp:navigation-link {"label":"Blog","url":"/blog","kind":"custom","isTopLevelLink":true} /-->
            <!-- wp:navigation-link {"label":"Gioi Thieu","url":"/about","kind":"custom","isTopLevelLink":true} /-->
            <!-- wp:navigation-link {"label":"Lien He","url":"/contact","kind":"custom","isTopLevelLink":true} /-->
        <!-- /wp:navigation -->

    </div>
    <!-- /wp:group -->

</div>
<!-- /wp:group -->
```

### Giai thich cu phap block:

```html
<!-- wp:block-name {"attributes":"values"} -->
<html-output>
    <!-- noi dung ben trong -->
</html-output>
<!-- /wp:block-name -->

<!--
MOI block bao gom:
1. Block comment (<!-- wp:group {...} -->)
   - Ten block: wp:group, wp:paragraph, wp:heading...
   - Attributes: JSON object (colors, spacing, layout...)
2. HTML output (giua opening va closing comment)
3. Closing comment (<!-- /wp:group -->)

Day la cu phap cua Gutenberg blocks khi luu vao database
-->

<!-- Vi du cac block thuong dung: -->

<!-- Heading -->
<!-- wp:heading {"level":2,"textAlign":"center"} -->
<h2 class="has-text-align-center">Tieu De</h2>
<!-- /wp:heading -->

<!-- Paragraph -->
<!-- wp:paragraph {"align":"center","fontSize":"large"} -->
<p class="has-text-align-center has-large-font-size">Noi dung</p>
<!-- /wp:paragraph -->

<!-- Image -->
<!-- wp:image {"id":123,"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large">
    <img src="image.jpg" alt="Mo ta" class="wp-image-123"/>
</figure>
<!-- /wp:image -->

<!-- Columns (2 cot) -->
<!-- wp:columns -->
<div class="wp-block-columns">
    <!-- wp:column -->
    <div class="wp-block-column">
        <!-- noi dung cot 1 -->
    </div>
    <!-- /wp:column -->
    <!-- wp:column -->
    <div class="wp-block-column">
        <!-- noi dung cot 2 -->
    </div>
    <!-- /wp:column -->
</div>
<!-- /wp:columns -->

<!-- Group (container) -->
<!-- wp:group {"layout":{"type":"constrained"}} -->
<div class="wp-block-group">
    <!-- noi dung -->
</div>
<!-- /wp:group -->

<!-- Query Loop (hien thi danh sach bai viet) -->
<!-- wp:query {"queryId":1,"query":{"perPage":6,"postType":"post"}} -->
<div class="wp-block-query">
    <!-- wp:post-template {"layout":{"type":"grid","columnCount":3}} -->
        <!-- wp:post-featured-image {"isLink":true} /-->
        <!-- wp:post-title {"isLink":true} /-->
        <!-- wp:post-date /-->
        <!-- wp:post-excerpt {"moreText":"Doc them"} /-->
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

        <!-- Cot 1: Gioi thieu -->
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"level":4,"textColor":"white"} -->
            <h4 class="has-white-color has-text-color">Ve Chung Toi</h4>
            <!-- /wp:heading -->

            <!-- wp:paragraph {"textColor":"light"} -->
            <p class="has-light-color has-text-color">Website chia se kien thuc lap trinh va cong nghe. Giup ban phat trien su nghiep developer.</p>
            <!-- /wp:paragraph -->
        </div>
        <!-- /wp:column -->

        <!-- Cot 2: Lien ket -->
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"level":4,"textColor":"white"} -->
            <h4 class="has-white-color has-text-color">Lien Ket</h4>
            <!-- /wp:heading -->

            <!-- wp:navigation {"overlayMenu":"never","layout":{"type":"flex","orientation":"vertical"},"style":{"spacing":{"blockGap":"0.5rem"}}} -->
                <!-- wp:navigation-link {"label":"Trang Chu","url":"/"} /-->
                <!-- wp:navigation-link {"label":"Blog","url":"/blog"} /-->
                <!-- wp:navigation-link {"label":"Lien He","url":"/contact"} /-->
            <!-- /wp:navigation -->
        </div>
        <!-- /wp:column -->

        <!-- Cot 3: Lien he -->
        <!-- wp:column -->
        <div class="wp-block-column">
            <!-- wp:heading {"level":4,"textColor":"white"} -->
            <h4 class="has-white-color has-text-color">Lien He</h4>
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

### templates/index.html (template mac dinh):

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

                <!-- wp:post-excerpt {"moreText":"Doc them →","excerptLength":25} /-->

                <!-- wp:separator {"className":"is-style-wide"} -->
                <hr class="wp-block-separator is-style-wide"/>
                <!-- /wp:separator -->

            </div>
            <!-- /wp:group -->
        <!-- /wp:post-template -->

        <!-- wp:query-pagination {"layout":{"type":"flex","justifyContent":"center"}} -->
        <div class="wp-block-query-pagination">
            <!-- wp:query-pagination-previous {"label":"← Truoc"} /-->
            <!-- wp:query-pagination-numbers /-->
            <!-- wp:query-pagination-next {"label":"Sau →"} /-->
        </div>
        <!-- /wp:query-pagination -->

        <!-- wp:query-no-results -->
        <!-- wp:paragraph {"align":"center"} -->
        <p class="has-text-align-center">Khong tim thay bai viet nao.</p>
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
            <!-- wp:post-navigation-link {"type":"previous","label":"← Bai truoc"} /-->
            <!-- wp:post-navigation-link {"label":"Bai sau →"} /-->
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
    <h2 class="has-text-align-center">Trang khong ton tai</h2>
    <!-- /wp:heading -->

    <!-- wp:paragraph {"align":"center"} -->
    <p class="has-text-align-center">Xin loi, trang ban dang tim khong ton tai hoac da bi di chuyen.</p>
    <!-- /wp:paragraph -->

    <!-- wp:search {"label":"Tim kiem","buttonText":"Tim","buttonPosition":"button-inside","buttonUseIcon":true} /-->

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
        <!-- wp:search {"label":"Tim kiem","buttonText":"Tim"} /-->

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
        <p>Khong tim thay ket qua nao. Hay thu voi tu khoa khac.</p>
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

### Dang ky Block Patterns trong functions.php:

```php
<?php
/**
 * Block Patterns - Cac mau block co san de nguoi dung chen nhanh
 *
 * Tuong tu "Blade components" trong Laravel nhung dung blocks
 */

/**
 * Dang ky Pattern Category
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
 * Dang ky Patterns
 */
function developer_register_patterns() {

    // === Pattern 1: Hero Section ===
    register_block_pattern( 'developer-theme/hero-section', array(
        'title'       => __( 'Hero Section', 'developer-theme' ),
        'description' => __( 'Phan hero voi tieu de, mo ta va nut bam.', 'developer-theme' ),
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
                        <h1 class="has-text-align-center has-white-color has-text-color has-xx-large-font-size">Chao Mung Den Voi Website</h1>
                        <!-- /wp:heading -->

                        <!-- wp:paragraph {"align":"center","textColor":"light","fontSize":"large"} -->
                        <p class="has-text-align-center has-light-color has-text-color has-large-font-size">Mo ta ngan gon ve website hoac dich vu cua ban. Tao an tuong manh voi khach hang.</p>
                        <!-- /wp:paragraph -->

                        <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
                        <div class="wp-block-buttons">
                            <!-- wp:button {"backgroundColor":"primary","textColor":"white"} -->
                            <div class="wp-block-button"><a class="wp-block-button__link has-white-color has-primary-background-color has-text-color has-background wp-element-button">Bat Dau Ngay</a></div>
                            <!-- /wp:button -->

                            <!-- wp:button {"className":"is-style-outline"} -->
                            <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button">Tim Hieu Them</a></div>
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
                <h2 class="has-text-align-center has-white-color has-text-color">San Sang Bat Dau?</h2>
                <!-- /wp:heading -->

                <!-- wp:paragraph {"align":"center"} -->
                <p class="has-text-align-center">Lien he voi chung toi ngay hom nay de duoc tu van mien phi.</p>
                <!-- /wp:paragraph -->

                <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
                <div class="wp-block-buttons">
                    <!-- wp:button {"backgroundColor":"white","textColor":"primary"} -->
                    <div class="wp-block-button"><a class="wp-block-button__link has-primary-color has-white-background-color has-text-color has-background wp-element-button">Lien He Ngay</a></div>
                    <!-- /wp:button -->
                </div>
                <!-- /wp:buttons -->

            </div>
            <!-- /wp:group -->
        ',
    ) );

    // === Pattern 3: Features Grid (3 cot) ===
    register_block_pattern( 'developer-theme/features-grid', array(
        'title'      => __( 'Features Grid', 'developer-theme' ),
        'categories' => array( 'developer-theme' ),
        'content'    => '
            <!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"backgroundColor":"light","layout":{"type":"constrained"}} -->
            <div class="wp-block-group alignfull has-light-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)">

                <!-- wp:heading {"textAlign":"center"} -->
                <h2 class="has-text-align-center">Tinh Nang Noi Bat</h2>
                <!-- /wp:heading -->

                <!-- wp:columns {"style":{"spacing":{"blockGap":{"left":"var:preset|spacing|40"}}}} -->
                <div class="wp-block-columns">

                    <!-- wp:column -->
                    <div class="wp-block-column">
                        <!-- wp:heading {"level":3,"textAlign":"center","fontSize":"x-large"} -->
                        <h3 class="has-text-align-center has-x-large-font-size">&#9889;</h3>
                        <!-- /wp:heading -->
                        <!-- wp:heading {"level":4,"textAlign":"center"} -->
                        <h4 class="has-text-align-center">Nhanh Chong</h4>
                        <!-- /wp:heading -->
                        <!-- wp:paragraph {"align":"center"} -->
                        <p class="has-text-align-center">Toc do tai trang sieu nhanh, toi uu cho trai nghiem nguoi dung tot nhat.</p>
                        <!-- /wp:paragraph -->
                    </div>
                    <!-- /wp:column -->

                    <!-- wp:column -->
                    <div class="wp-block-column">
                        <!-- wp:heading {"level":3,"textAlign":"center","fontSize":"x-large"} -->
                        <h3 class="has-text-align-center has-x-large-font-size">&#128274;</h3>
                        <!-- /wp:heading -->
                        <!-- wp:heading {"level":4,"textAlign":"center"} -->
                        <h4 class="has-text-align-center">Bao Mat</h4>
                        <!-- /wp:heading -->
                        <!-- wp:paragraph {"align":"center"} -->
                        <p class="has-text-align-center">Bao mat da tang, bao ve du lieu nguoi dung voi cac tieu chuan cao nhat.</p>
                        <!-- /wp:paragraph -->
                    </div>
                    <!-- /wp:column -->

                    <!-- wp:column -->
                    <div class="wp-block-column">
                        <!-- wp:heading {"level":3,"textAlign":"center","fontSize":"x-large"} -->
                        <h3 class="has-text-align-center has-x-large-font-size">&#127912;</h3>
                        <!-- /wp:heading -->
                        <!-- wp:heading {"level":4,"textAlign":"center"} -->
                        <h4 class="has-text-align-center">Dep Mat</h4>
                        <!-- /wp:heading -->
                        <!-- wp:paragraph {"align":"center"} -->
                        <p class="has-text-align-center">Giao dien hien dai, tuy chinh linh hoat, tuong thich moi thiet bi.</p>
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

### Patterns tu file rieng (WP 6.0+):

```
my-block-theme/
|-- patterns/
    |-- hero.php          # Tu dong dang ky pattern
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
        <h1 class="has-text-align-center has-white-color has-text-color"><?php esc_html_e( 'Tieu De Chinh', 'developer-theme' ); ?></h1>
        <!-- /wp:heading -->

        <!-- wp:paragraph {"align":"center","textColor":"light"} -->
        <p class="has-text-align-center has-light-color has-text-color"><?php esc_html_e( 'Mo ta ngan gon ve website cua ban.', 'developer-theme' ); ?></p>
        <!-- /wp:paragraph -->
    </div>
</div>
<!-- /wp:cover -->
```

---

## 6. Template Editor va Global Styles

### Template Editor:

```
Truy cap:
- Admin > Appearance > Editor (hoac Site Editor)
- Hoac click "Edit Site" tren Admin Bar

Chuc nang:
1. Chinh sua templates (index, single, page, archive, 404...)
2. Chinh sua template parts (header, footer, sidebar)
3. Them/xoa blocks tu template
4. Thay doi layout khong can code

Luu y:
- Khi nguoi dung chinh sua template trong editor, thay doi duoc luu trong database
- Template goc trong theme khong bi anh huong
- Co the "Reset" ve template goc bat cu luc nao
```

### Global Styles:

```
Truy cap:
- Trong Site Editor > Click icon "Styles" (hinh tron nua den nua trang)

Chuc nang:
1. Thay doi fonts, colors cho TOAN TRANG
2. Tuy chinh style cho tung LOAI BLOCK
3. Export/Import style presets

Global Styles ghi de len theme.json:
theme.json (developer tao) < Global Styles (nguoi dung tuy chinh)
```

### Style Variations:

```
Tao file styles/dark.json de co them giao dien "Dark Mode":

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

## 7. Code vi du: Block Theme hoan chinh

### Cau truc thu muc:

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

### functions.php (don gian hon Classic Theme):

```php
<?php
/**
 * Block Theme functions
 *
 * Block Theme can it code PHP hon Classic Theme vi theme.json
 * da xu ly phan lon settings va styles
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
    // Da ngon ngu
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
 * Enqueue styles (it hon Classic Theme vi theme.json da xu ly nhieu)
 */
function developer_block_theme_scripts() {
    // Style chinh
    wp_enqueue_style(
        'developer-block-theme-style',
        get_stylesheet_uri(),
        array(),
        wp_get_theme()->get( 'Version' )
    );

    // Custom CSS bo sung
    wp_enqueue_style(
        'developer-block-theme-custom',
        get_template_directory_uri() . '/assets/css/custom.css',
        array( 'developer-block-theme-style' ),
        wp_get_theme()->get( 'Version' )
    );
}
add_action( 'wp_enqueue_scripts', 'developer_block_theme_scripts' );

/**
 * Dang ky Block Pattern Categories
 */
function developer_block_theme_pattern_categories() {
    register_block_pattern_category( 'developer-block-theme', array(
        'label' => __( 'Developer Block Theme', 'developer-block-theme' ),
    ) );
}
add_action( 'init', 'developer_block_theme_pattern_categories' );

/**
 * Dang ky Block Styles
 * Them cac style variants cho blocks co san
 */
function developer_block_theme_register_block_styles() {
    // Style moi cho Group block: Card style
    register_block_style( 'core/group', array(
        'name'  => 'card',
        'label' => __( 'Card', 'developer-block-theme' ),
    ) );

    // Style moi cho Group block: Shadow
    register_block_style( 'core/group', array(
        'name'  => 'shadow',
        'label' => __( 'Shadow', 'developer-block-theme' ),
    ) );

    // Style moi cho Image: Rounded
    register_block_style( 'core/image', array(
        'name'  => 'rounded-full',
        'label' => __( 'Tron', 'developer-block-theme' ),
    ) );

    // Style moi cho Button: Arrow
    register_block_style( 'core/button', array(
        'name'  => 'arrow',
        'label' => __( 'Voi Mui Ten', 'developer-block-theme' ),
    ) );
}
add_action( 'init', 'developer_block_theme_register_block_styles' );
```

### assets/css/custom.css:

```css
/**
 * Custom CSS bo sung cho Block Theme
 * theme.json da xu ly phan lon styles,
 * file nay chi chua nhung gi theme.json khong lam duoc
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
    /* Giam gap tren mobile */
    .wp-block-columns {
        gap: 1rem;
    }

    /* Stack columns tren mobile */
    .wp-block-column {
        flex-basis: 100% !important;
    }
}
```

---

## 8. So sanh Classic Theme vs Block Theme

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
functions.php (phan lon)       theme.json
style.css (styles)             theme.json styles + style.css
functions.php (enqueue)        theme.json fontFaces + functions.php
inc/customizer.php             Global Styles UI (tren trung)
template-parts/                patterns/
wp_nav_menu()                  <!-- wp:navigation -->
dynamic_sidebar()              Khong can (dung blocks truc tiep)
```

### So sanh code:

```php
// === CLASSIC: Hien thi header ===
// header.php (PHP)
<header>
    <div class="container">
        <?php the_custom_logo(); ?>
        <h1><?php bloginfo('name'); ?></h1>
        <?php wp_nav_menu(array('theme_location' => 'primary')); ?>
    </div>
</header>

// === BLOCK: Hien thi header ===
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


// === CLASSIC: Loop hien thi bai viet ===
// index.php (PHP)
<?php while (have_posts()) : the_post(); ?>
    <article>
        <?php the_post_thumbnail(); ?>
        <h2><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
        <p><?php the_excerpt(); ?></p>
    </article>
<?php endwhile; ?>
<?php the_posts_pagination(); ?>

// === BLOCK: Loop hien thi bai viet ===
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


// === CLASSIC: Styles va settings ===
// functions.php
add_theme_support('editor-color-palette', array(
    array('name' => 'Primary', 'slug' => 'primary', 'color' => '#0073aa'),
));
wp_enqueue_style('google-fonts', 'https://fonts.googleapis.com/...');

// === BLOCK: Styles va settings ===
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

### Khi nao dung Classic vs Block Theme?

| Tinh huong | Nen dung |
|------------|----------|
| Du an moi, WordPress 6.0+ | Block Theme |
| Can tuy chinh phuc tap bang PHP | Classic Theme |
| Nguoi dung muon tu chinh sua layout | Block Theme |
| Site don gian, blog, portfolio | Block Theme |
| WooCommerce phuc tap | Classic Theme (ho tro tot hon) |
| Theme da co san can cap nhat | Classic Theme (de maintain) |

---

## 9. Best Practices

### 1. Dung theme.json toi da

```json
// Dung theme.json cho colors, fonts, spacing thay vi CSS
// WordPress se tu dong tao CSS toi uu

// DUNG: Dinh nghia trong theme.json
"settings": {
    "color": {
        "palette": [{"slug": "primary", "color": "#0073aa"}]
    }
}

// SAI: Hard-code CSS
.element { color: #0073aa; }

// DUNG: Dung CSS variable
.element { color: var(--wp--preset--color--primary); }
```

### 2. Fluid Typography

```json
// Dung fluid font sizes de responsive tu dong
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
// WordPress se tu dong tinh font size theo viewport width
```

### 3. Patterns cho content phuc tap

```php
// Thay vi hard-code layout trong template,
// tao patterns de nguoi dung co the tai su dung

// patterns/testimonial.php
// patterns/pricing-table.php
// patterns/team-members.php
```

### 4. Style Variations cho flexibility

```
// Tao nhieu style variations
styles/
  dark.json      -- Dark mode
  warm.json      -- Warm colors
  minimal.json   -- Minimalist
  bold.json      -- Bold typography

// Nguoi dung chon trong: Site Editor > Styles > Browse styles
```

### 5. Fonts tu host (khong dung CDN)

```json
// Tu host fonts de tuong thich GDPR
"fontFace": [
    {
        "fontFamily": "Inter",
        "fontWeight": "400",
        "src": ["file:./assets/fonts/inter/Inter-Regular.woff2"]
    }
]
// "file:./..." tro den file trong thu muc theme
```

### 6. functions.php toi gian

```php
// Block Theme can RAT IT code trong functions.php
// Chi dung cho:
// - load_theme_textdomain()
// - add_image_size()
// - register_block_pattern_category()
// - register_block_style()
// - Custom functionality khong lam duoc bang theme.json

// KHONG can:
// - register_nav_menus() (dung Navigation block)
// - register_sidebar() (dung blocks truc tiep)
// - add_theme_support('editor-color-palette') (dung theme.json)
// - wp_enqueue_style('google-fonts') (dung theme.json fontFace)
```

---

**Tiep theo:** [07 - Theme Nang Cao](./07-theme-nang-cao.md) - Child Theme, WooCommerce, Performance, i18n
