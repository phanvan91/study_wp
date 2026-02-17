# Gutenberg / Block Editor - Hướng Dẫn Chi Tiết

## Mục lục

1. [Giới thiệu Block Editor](#1-gioi-thieu-block-editor)
2. [Cấu trúc Block - attributes, edit, save](#2-cau-truc-block---attributes-edit-save)
3. [Tạo Custom Block đơn giản với @wordpress/scripts](#3-tao-custom-block-don-gian-voi-wordpressscripts)
4. [Block Attributes và InspectorControls](#4-block-attributes-va-inspectorcontrols)
5. [RichText, MediaUpload components](#5-richtext-mediaupload-components)
6. [Dynamic Blocks (Server-side rendering)](#6-dynamic-blocks-server-side-rendering)
7. [Block Patterns](#7-block-patterns)
8. [Block Templates](#8-block-templates)
9. [theme.json - Cấu hình theme cho Block Editor](#9-themejson---cau-hinh-theme-cho-block-editor)
10. [Ví dụ block hoàn chỉnh](#10-vi-du-block-hoan-chinh)

---

## 1. Giới thiệu Block Editor

### Block Editor là gì?

Block Editor (hay Gutenberg) là trình soạn thảo nội dung mặc định của WordPress từ phiên bản 5.0. Thay vì sử dụng một vùng soạn thảo lớn (TinyMCE), Gutenberg chia nội dung thành các "blocks" (khối) độc lập.

### Các khái niệm cơ bản

```
Block: Đơn vị nội dung nhỏ nhất (paragraph, heading, image, button, ...)
Block Type: Loại block đã được đăng ký (core/paragraph, core/image, ...)
Attributes: Dữ liệu cấu hình của block (nội dung, màu sắc, kích thước, ...)
InnerBlocks: Block có thể chứa các block con bên trong
Block Patterns: Nhóm các block được sắp xếp sẵn
Block Templates: Cấu trúc block mặc định cho post type
```

### Kiến trúc tổng quan

```
WordPress Block Editor
  |
  |-- Editor (React App)
  |     |-- Block Toolbar (thanh công cụ trên block)
  |     |-- Block Inspector / Sidebar (panel cài đặt bên phải)
  |     |-- Block Content (nội dung chính)
  |
  |-- Blocks
  |     |-- Core Blocks (paragraph, heading, image, ...)
  |     |-- Custom Blocks (bạn tự tạo)
  |     |-- Third-party Blocks (từ plugin)
  |
  |-- Data Store (@wordpress/data)
  |     |-- core/editor
  |     |-- core/block-editor
  |     |-- core/notices
  |
  |-- REST API (lưu và tải nội dung)
```

### Công nghệ sử dụng

```
- React.js: Xây dựng giao diện
- JSX: Cú pháp viết component
- @wordpress/scripts: Build tools (webpack, babel)
- @wordpress/components: Thư viện UI components
- @wordpress/block-editor: API cho block editor
- @wordpress/blocks: API đăng ký và quản lý blocks
- @wordpress/data: State management (giống Redux)
```

---

## 2. Cấu trúc Block - attributes, edit, save

### Cấu trúc cơ bản của một block

```javascript
// index.js - File chính của block
import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import save from './save';
import metadata from './block.json';

registerBlockType( metadata.name, {
    edit: Edit,  // Component hiển thị trong editor
    save,        // Component render HTML lưu vào database
} );
```

### block.json - File metadata (bắt buộc từ WP 6.0+)

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "my-plugin/my-block",
    "version": "1.0.0",
    "title": "Block Của Tôi",
    "category": "widgets",
    "icon": "smiley",
    "description": "Mô tả block của tôi.",
    "keywords": [ "custom", "block", "example" ],
    "supports": {
        "html": false,
        "align": true,
        "color": {
            "background": true,
            "text": true
        },
        "typography": {
            "fontSize": true
        },
        "spacing": {
            "margin": true,
            "padding": true
        }
    },
    "attributes": {
        "content": {
            "type": "string",
            "source": "html",
            "selector": "p"
        },
        "alignment": {
            "type": "string",
            "default": "left"
        },
        "backgroundColor": {
            "type": "string",
            "default": "#ffffff"
        }
    },
    "textdomain": "my-plugin",
    "editorScript": "file:./index.js",
    "editorStyle": "file:./index.css",
    "style": "file:./style-index.css",
    "render": "file:./render.php",
    "viewScript": "file:./view.js"
}
```

### Giải thích các thành phần trong block.json

```
apiVersion: 3         - Phiên bản API (3 là mới nhất)
name:                 - Tên duy nhất, format: namespace/block-name
category:             - Nhóm: text, media, design, widgets, theme, embed
icon:                 - Dashicon hoặc SVG
supports:             - Các tính năng block hỗ trợ
attributes:           - Dữ liệu của block
editorScript:         - JS chỉ load trong editor
editorStyle:          - CSS chỉ load trong editor
style:                - CSS load cả editor và frontend
render:               - PHP template cho dynamic block
viewScript:           - JS chỉ load trên frontend
```

### edit.js - Component hiển thị trong Editor

```javascript
// edit.js
import { useBlockProps } from '@wordpress/block-editor';

export default function Edit( { attributes, setAttributes } ) {
    const { content, alignment } = attributes;
    const blockProps = useBlockProps( {
        style: { textAlign: alignment },
    } );

    return (
        <div { ...blockProps }>
            <p>{ content }</p>
        </div>
    );
}
```

### save.js - Component render HTML lưu vào database

```javascript
// save.js
import { useBlockProps } from '@wordpress/block-editor';

export default function save( { attributes } ) {
    const { content, alignment } = attributes;
    const blockProps = useBlockProps.save( {
        style: { textAlign: alignment },
    } );

    return (
        <div { ...blockProps }>
            <p>{ content }</p>
        </div>
    );
}
```

### Vòng đời của block

```
1. User thêm block vào editor
   -> registerBlockType() được gọi
   -> edit() component render trong editor

2. User chỉnh sửa nội dung
   -> setAttributes() cập nhật dữ liệu
   -> edit() re-render với attributes mới

3. User lưu bài viết
   -> save() component render HTML
   -> HTML được lưu vào post_content trong database
   -> Định dạng: <!-- wp:my-plugin/my-block {"attr":"value"} -->HTML<!-- /wp:my-plugin/my-block -->

4. Frontend hiển thị
   -> WordPress đọc post_content
   -> Parse block markup
   -> Render HTML (static) hoặc gọi render callback (dynamic)
```

---

## 3. Tạo Custom Block đơn giản với @wordpress/scripts

### Bước 1: Cài đặt công cụ

```bash
# Tạo plugin mới
mkdir -p wp-content/plugins/my-blocks-plugin
cd wp-content/plugins/my-blocks-plugin
```

### Bước 2: Tạo file plugin chính

```php
<?php
/**
 * Plugin Name: My Blocks Plugin
 * Description: Custom Gutenberg blocks
 * Version: 1.0.0
 * Author: Dev Team
 * Text Domain: my-blocks
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Đăng ký tất cả custom blocks
 */
function my_blocks_register() {
    // Đăng ký block từ block.json
    // WordPress sẽ tự động enqueue scripts và styles
    register_block_type( __DIR__ . '/build/hello-block' );
}
add_action( 'init', 'my_blocks_register' );
```

### Bước 3: Tạo cấu trúc thư mục source

```
my-blocks-plugin/
  |-- my-blocks-plugin.php        (File chính)
  |-- package.json
  |-- src/
  |     |-- hello-block/
  |           |-- block.json
  |           |-- index.js
  |           |-- edit.js
  |           |-- save.js
  |           |-- editor.scss
  |           |-- style.scss
  |-- build/                      (Tự động tạo khi build)
```

### Bước 4: package.json

```json
{
    "name": "my-blocks-plugin",
    "version": "1.0.0",
    "description": "Custom Gutenberg blocks",
    "scripts": {
        "build": "wp-scripts build",
        "start": "wp-scripts start",
        "format": "wp-scripts format",
        "lint:js": "wp-scripts lint-js",
        "lint:css": "wp-scripts lint-style"
    },
    "devDependencies": {
        "@wordpress/scripts": "^27.0.0"
    }
}
```

```bash
# Cài đặt dependencies
npm install
```

### Bước 5: block.json

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "my-blocks/hello-block",
    "version": "1.0.0",
    "title": "Hello Block",
    "category": "widgets",
    "icon": "smiley",
    "description": "Block chào hỏi đơn giản.",
    "keywords": [ "hello", "chao", "example" ],
    "supports": {
        "html": false
    },
    "attributes": {
        "message": {
            "type": "string",
            "default": "Xin chào!"
        }
    },
    "textdomain": "my-blocks",
    "editorScript": "file:./index.js",
    "editorStyle": "file:./index.css",
    "style": "file:./style-index.css"
}
```

### Bước 6: index.js

```javascript
import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import save from './save';
import metadata from './block.json';
import './editor.scss';
import './style.scss';

registerBlockType( metadata.name, {
    edit: Edit,
    save,
} );
```

### Bước 7: edit.js

```javascript
import { useBlockProps } from '@wordpress/block-editor';
import { TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes } ) {
    const { message } = attributes;
    const blockProps = useBlockProps();

    return (
        <div { ...blockProps }>
            <TextControl
                label={ __( 'Lời chào', 'my-blocks' ) }
                value={ message }
                onChange={ ( value ) => setAttributes( { message: value } ) }
            />
            <div className="hello-block-preview">
                <p>{ message }</p>
            </div>
        </div>
    );
}
```

### Bước 8: save.js

```javascript
import { useBlockProps } from '@wordpress/block-editor';

export default function save( { attributes } ) {
    const { message } = attributes;
    const blockProps = useBlockProps.save();

    return (
        <div { ...blockProps }>
            <p className="hello-block-message">{ message }</p>
        </div>
    );
}
```

### Bước 9: Styles

```scss
// editor.scss - Chỉ hiện trong editor
.wp-block-my-blocks-hello-block {
    border: 2px dashed #ccc;
    padding: 20px;
    background: #f9f9f9;

    .hello-block-preview {
        margin-top: 10px;
        padding: 10px;
        background: white;
        border-radius: 4px;
    }
}
```

```scss
// style.scss - Hiện cả editor và frontend
.wp-block-my-blocks-hello-block {
    .hello-block-message {
        font-size: 24px;
        font-weight: bold;
        color: #333;
        text-align: center;
        padding: 20px;
    }
}
```

### Bước 10: Build và sử dụng

```bash
# Development (watch mode - tự động rebuild khi thay đổi)
npm start

# Production build
npm run build

# Kết quả:
# build/
#   |-- hello-block/
#         |-- block.json
#         |-- index.js
#         |-- index.js.map
#         |-- index.css
#         |-- style-index.css
#         |-- index.asset.php
```

---

## 4. Block Attributes và InspectorControls

### Các loại Attributes

```json
{
    "attributes": {
        "text": {
            "type": "string",
            "default": ""
        },
        "number": {
            "type": "number",
            "default": 0
        },
        "isActive": {
            "type": "boolean",
            "default": false
        },
        "items": {
            "type": "array",
            "default": []
        },
        "settings": {
            "type": "object",
            "default": {}
        },
        "content": {
            "type": "string",
            "source": "html",
            "selector": ".content"
        },
        "imageUrl": {
            "type": "string",
            "source": "attribute",
            "selector": "img",
            "attribute": "src"
        },
        "linkUrl": {
            "type": "string",
            "source": "attribute",
            "selector": "a",
            "attribute": "href"
        }
    }
}
```

### Giải thích Attribute Sources

```
"source": "html"
  -> Lấy nội dung HTML từ selector
  -> Ví dụ: <p class="content">Nội dung này</p> => "Nội dung này"

"source": "attribute"
  -> Lấy giá trị attribute của HTML element
  -> Ví dụ: <img src="url.jpg"> => "url.jpg"

"source": "text"
  -> Lấy text content (không có HTML tags)

"source": "query"
  -> Lấy dữ liệu từ nhiều elements (trả về array)

Không có "source":
  -> Lưu trực tiếp trong block comment
  -> <!-- wp:my-block {"myAttr":"value"} -->
```

### InspectorControls - Panel cài đặt bên phải

```javascript
// edit.js với InspectorControls đầy đủ
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
    PanelBody,
    PanelRow,
    TextControl,
    TextareaControl,
    ToggleControl,
    SelectControl,
    RangeControl,
    ColorPalette,
    RadioControl,
    CheckboxControl,
    FontSizePicker,
    __experimentalNumberControl as NumberControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes } ) {
    const {
        title,
        description,
        showTitle,
        layout,
        columns,
        backgroundColor,
        textColor,
        fontSize,
        borderRadius,
        alignment,
        enableAnimation,
    } = attributes;

    const blockProps = useBlockProps( {
        style: {
            backgroundColor,
            color: textColor,
            fontSize: fontSize + 'px',
            borderRadius: borderRadius + 'px',
        },
    } );

    // Màu sắc cho ColorPalette
    const colors = [
        { name: 'Đỏ', color: '#e74c3c' },
        { name: 'Xanh dương', color: '#3498db' },
        { name: 'Xanh lá', color: '#2ecc71' },
        { name: 'Vàng', color: '#f39c12' },
        { name: 'Tím', color: '#9b59b6' },
        { name: 'Trắng', color: '#ffffff' },
        { name: 'Đen', color: '#000000' },
    ];

    // Font sizes cho FontSizePicker
    const fontSizes = [
        { name: 'Nhỏ', slug: 'small', size: 14 },
        { name: 'Vừa', slug: 'medium', size: 16 },
        { name: 'Lớn', slug: 'large', size: 20 },
        { name: 'Rất lớn', slug: 'x-large', size: 28 },
    ];

    return (
        <>
            {/* Inspector Controls - Panel cài đặt bên phải */}
            <InspectorControls>

                {/* Panel 1: Nội dung */}
                <PanelBody title={ __( 'Nội dung', 'my-blocks' ) } initialOpen={ true }>
                    <TextControl
                        label={ __( 'Tiêu đề', 'my-blocks' ) }
                        value={ title }
                        onChange={ ( value ) => setAttributes( { title: value } ) }
                        help="Nhập tiêu đề của block"
                    />

                    <TextareaControl
                        label={ __( 'Mô tả', 'my-blocks' ) }
                        value={ description }
                        onChange={ ( value ) => setAttributes( { description: value } ) }
                        rows={ 4 }
                    />

                    <ToggleControl
                        label={ __( 'Hiển thị tiêu đề', 'my-blocks' ) }
                        checked={ showTitle }
                        onChange={ ( value ) => setAttributes( { showTitle: value } ) }
                    />
                </PanelBody>

                {/* Panel 2: Bố cục */}
                <PanelBody title={ __( 'Bố cục', 'my-blocks' ) } initialOpen={ false }>
                    <SelectControl
                        label={ __( 'Kiểu bố cục', 'my-blocks' ) }
                        value={ layout }
                        options={ [
                            { label: 'Lưới (Grid)', value: 'grid' },
                            { label: 'Danh sách (List)', value: 'list' },
                            { label: 'Carousel', value: 'carousel' },
                        ] }
                        onChange={ ( value ) => setAttributes( { layout: value } ) }
                    />

                    <RangeControl
                        label={ __( 'Số cột', 'my-blocks' ) }
                        value={ columns }
                        onChange={ ( value ) => setAttributes( { columns: value } ) }
                        min={ 1 }
                        max={ 6 }
                        step={ 1 }
                    />

                    <RadioControl
                        label={ __( 'Căn chỉnh', 'my-blocks' ) }
                        selected={ alignment }
                        options={ [
                            { label: 'Trái', value: 'left' },
                            { label: 'Giữa', value: 'center' },
                            { label: 'Phải', value: 'right' },
                        ] }
                        onChange={ ( value ) => setAttributes( { alignment: value } ) }
                    />
                </PanelBody>

                {/* Panel 3: Giao diện */}
                <PanelBody title={ __( 'Giao diện', 'my-blocks' ) } initialOpen={ false }>
                    <p>{ __( 'Màu nền', 'my-blocks' ) }</p>
                    <ColorPalette
                        colors={ colors }
                        value={ backgroundColor }
                        onChange={ ( value ) => setAttributes( { backgroundColor: value } ) }
                    />

                    <p>{ __( 'Màu chữ', 'my-blocks' ) }</p>
                    <ColorPalette
                        colors={ colors }
                        value={ textColor }
                        onChange={ ( value ) => setAttributes( { textColor: value } ) }
                    />

                    <FontSizePicker
                        fontSizes={ fontSizes }
                        value={ fontSize }
                        onChange={ ( value ) => setAttributes( { fontSize: value } ) }
                    />

                    <RangeControl
                        label={ __( 'Bo góc (border-radius)', 'my-blocks' ) }
                        value={ borderRadius }
                        onChange={ ( value ) => setAttributes( { borderRadius: value } ) }
                        min={ 0 }
                        max={ 50 }
                    />
                </PanelBody>

                {/* Panel 4: Nâng cao */}
                <PanelBody title={ __( 'Nâng cao', 'my-blocks' ) } initialOpen={ false }>
                    <CheckboxControl
                        label={ __( 'Bật hiệu ứng động (animation)', 'my-blocks' ) }
                        checked={ enableAnimation }
                        onChange={ ( value ) => setAttributes( { enableAnimation: value } ) }
                    />
                </PanelBody>

            </InspectorControls>

            {/* Nội dung block trong editor */}
            <div { ...blockProps }>
                { showTitle && <h3>{ title || 'Nhập tiêu đề...' }</h3> }
                <p>{ description || 'Nhập mô tả...' }</p>
            </div>
        </>
    );
}
```

### Block Toolbar Controls

```javascript
import {
    useBlockProps,
    BlockControls,
    AlignmentToolbar,
} from '@wordpress/block-editor';
import {
    ToolbarGroup,
    ToolbarButton,
    ToolbarDropdownMenu,
} from '@wordpress/components';
import { formatBold, formatItalic, link } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes } ) {
    const { alignment, isBold, isItalic } = attributes;
    const blockProps = useBlockProps();

    return (
        <>
            {/* Block Toolbar - Thanh công cụ phía trên block */}
            <BlockControls>
                {/* Alignment toolbar có sẵn */}
                <AlignmentToolbar
                    value={ alignment }
                    onChange={ ( value ) => setAttributes( { alignment: value } ) }
                />

                {/* Toolbar group tùy chỉnh */}
                <ToolbarGroup>
                    <ToolbarButton
                        icon={ formatBold }
                        label={ __( 'Đậm', 'my-blocks' ) }
                        isPressed={ isBold }
                        onClick={ () => setAttributes( { isBold: ! isBold } ) }
                    />
                    <ToolbarButton
                        icon={ formatItalic }
                        label={ __( 'Nghiêng', 'my-blocks' ) }
                        isPressed={ isItalic }
                        onClick={ () => setAttributes( { isItalic: ! isItalic } ) }
                    />
                </ToolbarGroup>

                {/* Dropdown menu */}
                <ToolbarDropdownMenu
                    icon={ link }
                    label={ __( 'Tùy chọn', 'my-blocks' ) }
                    controls={ [
                        {
                            title: 'Tùy chọn 1',
                            onClick: () => console.log( 'Option 1' ),
                        },
                        {
                            title: 'Tùy chọn 2',
                            onClick: () => console.log( 'Option 2' ),
                        },
                    ] }
                />
            </BlockControls>

            <div { ...blockProps }>
                <p style={ { textAlign: alignment } }>Nội dung block</p>
            </div>
        </>
    );
}
```

---

## 5. RichText, MediaUpload components

### RichText - Soạn thảo văn bản rich text

```javascript
import { useBlockProps, RichText } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';

// --- EDIT ---
export function Edit( { attributes, setAttributes } ) {
    const { heading, content } = attributes;
    const blockProps = useBlockProps();

    return (
        <div { ...blockProps }>
            {/* RichText cho heading */}
            <RichText
                tagName="h2"                                   // HTML tag sẽ render
                className="my-block-heading"
                value={ heading }                              // Giá trị hiện tại
                onChange={ ( value ) => setAttributes( { heading: value } ) }
                placeholder={ __( 'Nhập tiêu đề...', 'my-blocks' ) }
                allowedFormats={ [ 'core/bold', 'core/italic' ] }  // Giới hạn format
                // allowedFormats={ [] }                         // Không cho format nào
            />

            {/* RichText cho nội dung */}
            <RichText
                tagName="p"
                className="my-block-content"
                value={ content }
                onChange={ ( value ) => setAttributes( { content: value } ) }
                placeholder={ __( 'Nhập nội dung...', 'my-blocks' ) }
                // Mặc định cho phép tất cả formats
            />

            {/* RichText dạng danh sách */}
            <RichText
                tagName="ul"
                multiline="li"                                 // Mỗi dòng là 1 <li>
                value={ attributes.listItems }
                onChange={ ( value ) => setAttributes( { listItems: value } ) }
                placeholder={ __( 'Nhập mục...', 'my-blocks' ) }
            />
        </div>
    );
}

// --- SAVE ---
export function save( { attributes } ) {
    const { heading, content, listItems } = attributes;
    const blockProps = useBlockProps.save();

    return (
        <div { ...blockProps }>
            <RichText.Content tagName="h2" className="my-block-heading" value={ heading } />
            <RichText.Content tagName="p" className="my-block-content" value={ content } />
            <RichText.Content tagName="ul" multiline="li" value={ listItems } />
        </div>
    );
}
```

### block.json cho RichText

```json
{
    "attributes": {
        "heading": {
            "type": "string",
            "source": "html",
            "selector": "h2.my-block-heading"
        },
        "content": {
            "type": "string",
            "source": "html",
            "selector": "p.my-block-content"
        },
        "listItems": {
            "type": "string",
            "source": "html",
            "selector": "ul",
            "multiline": "li"
        }
    }
}
```

### MediaUpload - Upload và chọn hình ảnh

```javascript
import { useBlockProps, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes } ) {
    const { imageId, imageUrl, imageAlt } = attributes;
    const blockProps = useBlockProps();

    // Callback khi chọn hình
    const onSelectImage = ( media ) => {
        setAttributes( {
            imageId: media.id,
            imageUrl: media.url,
            imageAlt: media.alt,
        } );
    };

    // Callback khi xóa hình
    const onRemoveImage = () => {
        setAttributes( {
            imageId: 0,
            imageUrl: '',
            imageAlt: '',
        } );
    };

    return (
        <div { ...blockProps }>
            <MediaUploadCheck>
                { imageUrl ? (
                    // Đã có hình - hiển thị hình và nút thay đổi
                    <div className="my-block-image-wrapper">
                        <img src={ imageUrl } alt={ imageAlt } />

                        <div className="my-block-image-controls">
                            <MediaUpload
                                onSelect={ onSelectImage }
                                allowedTypes={ [ 'image' ] }
                                value={ imageId }
                                render={ ( { open } ) => (
                                    <Button
                                        onClick={ open }
                                        variant="secondary"
                                        isSmall
                                    >
                                        { __( 'Đổi hình', 'my-blocks' ) }
                                    </Button>
                                ) }
                            />
                            <Button
                                onClick={ onRemoveImage }
                                variant="link"
                                isDestructive
                                isSmall
                            >
                                { __( 'Xóa hình', 'my-blocks' ) }
                            </Button>
                        </div>
                    </div>
                ) : (
                    // Chưa có hình - hiển thị placeholder
                    <MediaUpload
                        onSelect={ onSelectImage }
                        allowedTypes={ [ 'image' ] }
                        value={ imageId }
                        render={ ( { open } ) => (
                            <Placeholder
                                icon="format-image"
                                label={ __( 'Hình ảnh', 'my-blocks' ) }
                                instructions={ __( 'Chọn hoặc upload hình ảnh', 'my-blocks' ) }
                            >
                                <Button
                                    onClick={ open }
                                    variant="primary"
                                >
                                    { __( 'Chọn hình', 'my-blocks' ) }
                                </Button>
                            </Placeholder>
                        ) }
                    />
                ) }
            </MediaUploadCheck>
        </div>
    );
}

// --- SAVE ---
export function save( { attributes } ) {
    const { imageUrl, imageAlt } = attributes;
    const blockProps = useBlockProps.save();

    return (
        <div { ...blockProps }>
            { imageUrl && (
                <img
                    src={ imageUrl }
                    alt={ imageAlt }
                    className="my-block-image"
                />
            ) }
        </div>
    );
}
```

### MediaUpload cho Video và File

```javascript
// Upload video
<MediaUpload
    onSelect={ ( media ) => setAttributes( {
        videoUrl: media.url,
        videoId: media.id,
    } ) }
    allowedTypes={ [ 'video' ] }
    value={ attributes.videoId }
    render={ ( { open } ) => (
        <Button onClick={ open } variant="primary">
            { __( 'Chọn video', 'my-blocks' ) }
        </Button>
    ) }
/>

// Upload nhiều hình (gallery)
<MediaUpload
    onSelect={ ( media ) => {
        const images = media.map( ( img ) => ( {
            id: img.id,
            url: img.url,
            alt: img.alt,
        } ) );
        setAttributes( { gallery: images } );
    } }
    allowedTypes={ [ 'image' ] }
    multiple={ true }           // Cho phép chọn nhiều
    gallery={ true }            // Giao diện gallery
    value={ attributes.gallery ? attributes.gallery.map( ( img ) => img.id ) : [] }
    render={ ( { open } ) => (
        <Button onClick={ open } variant="primary">
            { __( 'Chọn hình gallery', 'my-blocks' ) }
        </Button>
    ) }
/>
```

### InnerBlocks - Block chứa block con

```javascript
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

// --- EDIT ---
export function Edit() {
    const blockProps = useBlockProps();

    // Template mặc định cho InnerBlocks
    const TEMPLATE = [
        [ 'core/heading', { level: 2, placeholder: 'Tiêu đề...' } ],
        [ 'core/paragraph', { placeholder: 'Nội dung...' } ],
        [ 'core/image', {} ],
    ];

    // Giới hạn các block cho phép
    const ALLOWED_BLOCKS = [
        'core/heading',
        'core/paragraph',
        'core/image',
        'core/list',
        'core/button',
    ];

    return (
        <div { ...blockProps }>
            <InnerBlocks
                template={ TEMPLATE }
                templateLock={ false }
                // 'all' = không cho sửa template
                // 'insert' = không cho thêm/xóa block
                // false = tự do chỉnh sửa
                allowedBlocks={ ALLOWED_BLOCKS }
                // renderAppender={ InnerBlocks.ButtonBlockAppender }
                // renderAppender={ () => null }  // Ẩn nút thêm block
            />
        </div>
    );
}

// --- SAVE ---
export function save() {
    const blockProps = useBlockProps.save();

    return (
        <div { ...blockProps }>
            <InnerBlocks.Content />
        </div>
    );
}
```

---

## 6. Dynamic Blocks (Server-side rendering)

### Khi nào dùng Dynamic Block?

```
Static Block: HTML được tạo bởi save() và lưu vào database
  -> Nhanh, không cần PHP khi render
  -> Phù hợp cho nội dung tĩnh (text, image, layout)

Dynamic Block: HTML được tạo bởi PHP mỗi khi hiển thị
  -> Nội dung thay đổi theo thời gian thực
  -> Phù hợp cho: bài viết mới nhất, sản phẩm, query từ database
  -> save() trả về null
```

### Ví dụ Dynamic Block: Bài viết mới nhất

#### block.json

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "my-blocks/latest-posts",
    "title": "Bài Viết Mới Nhất",
    "category": "widgets",
    "icon": "list-view",
    "description": "Hiển thị danh sách bài viết mới nhất.",
    "supports": {
        "html": false,
        "align": [ "wide", "full" ]
    },
    "attributes": {
        "numberOfPosts": {
            "type": "number",
            "default": 5
        },
        "postType": {
            "type": "string",
            "default": "post"
        },
        "showThumbnail": {
            "type": "boolean",
            "default": true
        },
        "showExcerpt": {
            "type": "boolean",
            "default": true
        },
        "showDate": {
            "type": "boolean",
            "default": true
        },
        "columns": {
            "type": "number",
            "default": 1
        },
        "order": {
            "type": "string",
            "default": "desc"
        },
        "orderBy": {
            "type": "string",
            "default": "date"
        },
        "categoryId": {
            "type": "number",
            "default": 0
        }
    },
    "textdomain": "my-blocks",
    "editorScript": "file:./index.js",
    "editorStyle": "file:./index.css",
    "style": "file:./style-index.css",
    "render": "file:./render.php"
}
```

#### edit.js (với ServerSideRender)

```javascript
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
    PanelBody,
    RangeControl,
    ToggleControl,
    SelectControl,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import ServerSideRender from '@wordpress/server-side-render';

export default function Edit( { attributes, setAttributes } ) {
    const {
        numberOfPosts,
        postType,
        showThumbnail,
        showExcerpt,
        showDate,
        columns,
        order,
        orderBy,
    } = attributes;

    const blockProps = useBlockProps();

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Cài đặt', 'my-blocks' ) }>
                    <SelectControl
                        label={ __( 'Loại bài viết', 'my-blocks' ) }
                        value={ postType }
                        options={ [
                            { label: 'Bài viết', value: 'post' },
                            { label: 'Sản phẩm', value: 'product' },
                            { label: 'Portfolio', value: 'portfolio' },
                        ] }
                        onChange={ ( value ) => setAttributes( { postType: value } ) }
                    />

                    <RangeControl
                        label={ __( 'Số bài viết', 'my-blocks' ) }
                        value={ numberOfPosts }
                        onChange={ ( value ) => setAttributes( { numberOfPosts: value } ) }
                        min={ 1 }
                        max={ 20 }
                    />

                    <RangeControl
                        label={ __( 'Số cột', 'my-blocks' ) }
                        value={ columns }
                        onChange={ ( value ) => setAttributes( { columns: value } ) }
                        min={ 1 }
                        max={ 4 }
                    />

                    <SelectControl
                        label={ __( 'Sắp xếp theo', 'my-blocks' ) }
                        value={ orderBy }
                        options={ [
                            { label: 'Ngày tạo', value: 'date' },
                            { label: 'Tiêu đề', value: 'title' },
                            { label: 'Ngẫu nhiên', value: 'rand' },
                            { label: 'Số bình luận', value: 'comment_count' },
                        ] }
                        onChange={ ( value ) => setAttributes( { orderBy: value } ) }
                    />

                    <SelectControl
                        label={ __( 'Thứ tự', 'my-blocks' ) }
                        value={ order }
                        options={ [
                            { label: 'Mới nhất trước', value: 'desc' },
                            { label: 'Cũ nhất trước', value: 'asc' },
                        ] }
                        onChange={ ( value ) => setAttributes( { order: value } ) }
                    />
                </PanelBody>

                <PanelBody title={ __( 'Hiển thị', 'my-blocks' ) } initialOpen={ false }>
                    <ToggleControl
                        label={ __( 'Hiện ảnh đại diện', 'my-blocks' ) }
                        checked={ showThumbnail }
                        onChange={ ( value ) => setAttributes( { showThumbnail: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Hiện tóm tắt', 'my-blocks' ) }
                        checked={ showExcerpt }
                        onChange={ ( value ) => setAttributes( { showExcerpt: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Hiện ngày đăng', 'my-blocks' ) }
                        checked={ showDate }
                        onChange={ ( value ) => setAttributes( { showDate: value } ) }
                    />
                </PanelBody>
            </InspectorControls>

            <div { ...blockProps }>
                {/* Render phía server trong editor */}
                <ServerSideRender
                    block="my-blocks/latest-posts"
                    attributes={ attributes }
                />
            </div>
        </>
    );
}
```

#### save.js (trả về null cho dynamic block)

```javascript
// Dynamic block không cần save - trả về null
export default function save() {
    return null;
}
```

#### render.php (Server-side rendering)

```php
<?php
/**
 * Render callback cho block "Bài Viết Mới Nhất"
 *
 * Các biến có sẵn:
 * $attributes - Mảng attributes của block
 * $content    - Nội dung InnerBlocks (nếu có)
 * $block      - WP_Block instance
 */

$number_of_posts = isset( $attributes['numberOfPosts'] ) ? $attributes['numberOfPosts'] : 5;
$post_type       = isset( $attributes['postType'] ) ? $attributes['postType'] : 'post';
$show_thumbnail  = isset( $attributes['showThumbnail'] ) ? $attributes['showThumbnail'] : true;
$show_excerpt    = isset( $attributes['showExcerpt'] ) ? $attributes['showExcerpt'] : true;
$show_date       = isset( $attributes['showDate'] ) ? $attributes['showDate'] : true;
$columns         = isset( $attributes['columns'] ) ? $attributes['columns'] : 1;
$order           = isset( $attributes['order'] ) ? $attributes['order'] : 'desc';
$order_by        = isset( $attributes['orderBy'] ) ? $attributes['orderBy'] : 'date';
$category_id     = isset( $attributes['categoryId'] ) ? $attributes['categoryId'] : 0;

// Query bài viết
$query_args = array(
    'post_type'      => $post_type,
    'posts_per_page' => $number_of_posts,
    'post_status'    => 'publish',
    'order'          => $order,
    'orderby'        => $order_by,
);

if ( $category_id > 0 ) {
    $query_args['cat'] = $category_id;
}

$posts = new WP_Query( $query_args );

if ( ! $posts->have_posts() ) {
    echo '<p>Không có bài viết nào.</p>';
    return;
}

// Lấy wrapper attributes từ block
$wrapper_attributes = get_block_wrapper_attributes( array(
    'class' => 'latest-posts-block columns-' . $columns,
) );
?>

<div <?php echo $wrapper_attributes; ?>>
    <div class="latest-posts-grid">
        <?php while ( $posts->have_posts() ) : $posts->the_post(); ?>
            <article class="latest-posts-item">
                <?php if ( $show_thumbnail && has_post_thumbnail() ) : ?>
                    <div class="latest-posts-thumbnail">
                        <a href="<?php the_permalink(); ?>">
                            <?php the_post_thumbnail( 'medium' ); ?>
                        </a>
                    </div>
                <?php endif; ?>

                <div class="latest-posts-content">
                    <h3 class="latest-posts-title">
                        <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                    </h3>

                    <?php if ( $show_date ) : ?>
                        <time class="latest-posts-date" datetime="<?php echo get_the_date( 'c' ); ?>">
                            <?php echo get_the_date(); ?>
                        </time>
                    <?php endif; ?>

                    <?php if ( $show_excerpt ) : ?>
                        <div class="latest-posts-excerpt">
                            <?php the_excerpt(); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </article>
        <?php endwhile; ?>
    </div>
</div>

<?php
wp_reset_postdata();
```

### Đăng ký Dynamic Block bằng PHP (cách cũ, không dùng block.json)

```php
/**
 * Đăng ký dynamic block bằng PHP
 * Chỉ dùng khi không sử dụng block.json
 */
function my_blocks_register_dynamic() {
    register_block_type( 'my-blocks/site-info', array(
        'api_version'     => 3,
        'editor_script'   => 'my-blocks-site-info-editor',
        'render_callback' => 'my_blocks_site_info_render',
        'attributes'      => array(
            'showDescription' => array(
                'type'    => 'boolean',
                'default' => true,
            ),
        ),
    ) );
}
add_action( 'init', 'my_blocks_register_dynamic' );

function my_blocks_site_info_render( $attributes, $content ) {
    $show_desc = $attributes['showDescription'];

    $html = '<div class="wp-block-my-blocks-site-info">';
    $html .= '<h2>' . esc_html( get_bloginfo( 'name' ) ) . '</h2>';

    if ( $show_desc ) {
        $html .= '<p>' . esc_html( get_bloginfo( 'description' ) ) . '</p>';
    }

    $html .= '</div>';

    return $html;
}
```

---

## 7. Block Patterns

### Block Pattern là gì?

Block Pattern là nhóm các block được sắp xếp sẵn, người dùng có thể chèn vào nội dung với 1 click. Khác với Reusable Blocks, Pattern tạo bản sao độc lập (thay đổi pattern không ảnh hưởng các nơi khác).

### Đăng ký Block Pattern

```php
/**
 * Đăng ký Block Patterns
 */
function mytheme_register_block_patterns() {

    // 1. Đăng ký Pattern Category trước
    register_block_pattern_category( 'mytheme-patterns', array(
        'label' => __( 'My Theme Patterns', 'mytheme' ),
    ) );

    register_block_pattern_category( 'mytheme-hero', array(
        'label' => __( 'Hero Sections', 'mytheme' ),
    ) );

    // 2. Đăng ký Pattern

    // Pattern: Hero Section
    register_block_pattern( 'mytheme/hero-section', array(
        'title'       => __( 'Hero Section', 'mytheme' ),
        'description' => __( 'Hero section với tiêu đề lớn và nút CTA', 'mytheme' ),
        'categories'  => array( 'mytheme-hero', 'featured' ),
        'keywords'    => array( 'hero', 'banner', 'header' ),
        'blockTypes'  => array( 'core/cover' ),
        'content'     => '
            <!-- wp:cover {"overlayColor":"black","minHeight":500,"align":"full"} -->
            <div class="wp-block-cover alignfull" style="min-height:500px">
                <span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim"></span>
                <div class="wp-block-cover__inner-container">
                    <!-- wp:heading {"textAlign":"center","level":1,"style":{"color":{"text":"#ffffff"}}} -->
                    <h1 class="wp-block-heading has-text-align-center" style="color:#ffffff">Chào mừng đến với Website của chúng tôi</h1>
                    <!-- /wp:heading -->

                    <!-- wp:paragraph {"align":"center","style":{"color":{"text":"#cccccc"}}} -->
                    <p class="has-text-align-center" style="color:#cccccc">Mô tả ngắn gọn về website hoặc dịch vụ của bạn. Thu hút người dùng bằng thông điệp rõ ràng.</p>
                    <!-- /wp:paragraph -->

                    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
                    <div class="wp-block-buttons">
                        <!-- wp:button {"backgroundColor":"vivid-cyan-blue"} -->
                        <div class="wp-block-button"><a class="wp-block-button__link has-vivid-cyan-blue-background-color has-background wp-element-button">Tìm Hiểu Thêm</a></div>
                        <!-- /wp:button -->
                        <!-- wp:button {"className":"is-style-outline"} -->
                        <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button">Liên Hệ</a></div>
                        <!-- /wp:button -->
                    </div>
                    <!-- /wp:buttons -->
                </div>
            </div>
            <!-- /wp:cover -->
        ',
    ) );

    // Pattern: Card Grid (3 cột)
    register_block_pattern( 'mytheme/card-grid', array(
        'title'       => __( 'Card Grid 3 Cột', 'mytheme' ),
        'description' => __( 'Lưới 3 card với icon, tiêu đề và mô tả', 'mytheme' ),
        'categories'  => array( 'mytheme-patterns' ),
        'content'     => '
            <!-- wp:columns -->
            <div class="wp-block-columns">
                <!-- wp:column -->
                <div class="wp-block-column">
                    <!-- wp:heading {"textAlign":"center","level":3} -->
                    <h3 class="wp-block-heading has-text-align-center">Dịch Vụ 1</h3>
                    <!-- /wp:heading -->
                    <!-- wp:paragraph {"align":"center"} -->
                    <p class="has-text-align-center">Mô tả chi tiết về dịch vụ thứ nhất của bạn.</p>
                    <!-- /wp:paragraph -->
                </div>
                <!-- /wp:column -->

                <!-- wp:column -->
                <div class="wp-block-column">
                    <!-- wp:heading {"textAlign":"center","level":3} -->
                    <h3 class="wp-block-heading has-text-align-center">Dịch Vụ 2</h3>
                    <!-- /wp:heading -->
                    <!-- wp:paragraph {"align":"center"} -->
                    <p class="has-text-align-center">Mô tả chi tiết về dịch vụ thứ hai của bạn.</p>
                    <!-- /wp:paragraph -->
                </div>
                <!-- /wp:column -->

                <!-- wp:column -->
                <div class="wp-block-column">
                    <!-- wp:heading {"textAlign":"center","level":3} -->
                    <h3 class="wp-block-heading has-text-align-center">Dịch Vụ 3</h3>
                    <!-- /wp:heading -->
                    <!-- wp:paragraph {"align":"center"} -->
                    <p class="has-text-align-center">Mô tả chi tiết về dịch vụ thứ ba của bạn.</p>
                    <!-- /wp:paragraph -->
                </div>
                <!-- /wp:column -->
            </div>
            <!-- /wp:columns -->
        ',
    ) );

    // Pattern: Testimonial
    register_block_pattern( 'mytheme/testimonial', array(
        'title'      => __( 'Testimonial', 'mytheme' ),
        'categories' => array( 'mytheme-patterns' ),
        'content'    => '
            <!-- wp:group {"style":{"spacing":{"padding":{"top":"30px","bottom":"30px","left":"30px","right":"30px"}},"border":{"radius":"8px"}},"backgroundColor":"cyan-bluish-gray"} -->
            <div class="wp-block-group has-cyan-bluish-gray-background-color has-background" style="border-radius:8px;padding-top:30px;padding-right:30px;padding-bottom:30px;padding-left:30px">
                <!-- wp:paragraph {"align":"center","style":{"typography":{"fontSize":"18px","fontStyle":"italic"}}} -->
                <p class="has-text-align-center" style="font-size:18px;font-style:italic">"Sản phẩm rất tuyệt vời! Tôi đã sử dụng được 6 tháng và rất hài lòng với chất lượng dịch vụ."</p>
                <!-- /wp:paragraph -->
                <!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"700"}}} -->
                <p class="has-text-align-center" style="font-weight:700">- Nguyễn Văn A, Giám đốc Công ty XYZ</p>
                <!-- /wp:paragraph -->
            </div>
            <!-- /wp:group -->
        ',
    ) );
}
add_action( 'init', 'mytheme_register_block_patterns' );
```

### Xóa hoặc ẩn Block Patterns

```php
// Xóa pattern cụ thể
function mytheme_unregister_patterns() {
    unregister_block_pattern( 'core/query-standard-posts' );
    unregister_block_pattern( 'core/social-links-shared-background-color' );
}
add_action( 'init', 'mytheme_unregister_patterns' );

// Xóa toàn bộ core patterns
remove_theme_support( 'core-block-patterns' );

// Xóa pattern category
unregister_block_pattern_category( 'buttons' );
```

### Đăng ký Pattern từ file PHP riêng

```php
// patterns/hero.php
<?php
/**
 * Title: Hero Section
 * Slug: mytheme/hero
 * Categories: featured, mytheme-hero
 * Keywords: hero, banner
 * Block Types: core/cover
 */
?>

<!-- wp:cover {"overlayColor":"black","minHeight":500,"align":"full"} -->
<div class="wp-block-cover alignfull" style="min-height:500px">
    <span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim"></span>
    <div class="wp-block-cover__inner-container">
        <!-- wp:heading {"textAlign":"center","level":1} -->
        <h1 class="wp-block-heading has-text-align-center">Tiêu đề Hero</h1>
        <!-- /wp:heading -->
    </div>
</div>
<!-- /wp:cover -->
```

```
Cấu trúc thư mục:
mytheme/
  |-- patterns/
  |     |-- hero.php
  |     |-- card-grid.php
  |     |-- testimonial.php
  |     |-- cta-section.php

WordPress tự động đăng ký các file trong thư mục patterns/.
Điều kiện: File phải có header comment với Title và Slug.
```

---

## 8. Block Templates

### Block Template là gì?

Block Template định nghĩa cấu trúc block mặc định khi tạo bài viết mới. Khác với Pattern (người dùng chủ động chèn), Template tự động áp dụng.

### Đăng ký Block Template cho Post Type

```php
/**
 * Đăng ký block template cho Custom Post Type
 */
function mytheme_register_product_template() {
    $post_type_object = get_post_type_object( 'product' );

    if ( $post_type_object ) {
        $post_type_object->template = array(
            // Mỗi array là 1 block: [ 'block-name', attributes, innerBlocks ]
            array( 'core/image', array(
                'align' => 'wide',
            ) ),
            array( 'core/heading', array(
                'level'       => 2,
                'placeholder' => 'Tên sản phẩm...',
            ) ),
            array( 'core/paragraph', array(
                'placeholder' => 'Mô tả ngắn về sản phẩm...',
            ) ),
            array( 'core/heading', array(
                'level'   => 3,
                'content' => 'Thông số kỹ thuật',
            ) ),
            array( 'core/table', array(
                'className' => 'product-specs',
            ) ),
            array( 'core/heading', array(
                'level'   => 3,
                'content' => 'Mô tả chi tiết',
            ) ),
            array( 'core/paragraph', array(
                'placeholder' => 'Nhập mô tả chi tiết sản phẩm...',
            ) ),
            // Block với InnerBlocks
            array( 'core/columns', array(), array(
                array( 'core/column', array(), array(
                    array( 'core/heading', array(
                        'level' => 4,
                        'content' => 'Ưu điểm',
                    ) ),
                    array( 'core/list', array() ),
                ) ),
                array( 'core/column', array(), array(
                    array( 'core/heading', array(
                        'level' => 4,
                        'content' => 'Nhược điểm',
                    ) ),
                    array( 'core/list', array() ),
                ) ),
            ) ),
        );

        // Khóa template
        $post_type_object->template_lock = 'all';
        // 'all'    = Không cho thêm, xóa, di chuyển blocks
        // 'insert' = Không cho thêm/xóa, nhưng cho di chuyển
        // false    = Tự do chỉnh sửa (mặc định)
    }
}
add_action( 'init', 'mytheme_register_product_template' );
```

### Đăng ký template khi đăng ký CPT

```php
function mytheme_register_portfolio_cpt() {
    register_post_type( 'portfolio', array(
        'labels' => array( 'name' => 'Portfolio' ),
        'public'       => true,
        'show_in_rest' => true,  // Bắt buộc để dùng Gutenberg
        'supports'     => array( 'title', 'editor', 'thumbnail' ),
        'template'     => array(
            array( 'core/image', array(
                'align' => 'wide',
            ) ),
            array( 'core/paragraph', array(
                'placeholder' => 'Mô tả dự án...',
            ) ),
            array( 'core/gallery', array() ),
        ),
        'template_lock' => false,
    ) );
}
add_action( 'init', 'mytheme_register_portfolio_cpt' );
```

### Template cho Page

```php
/**
 * Thêm block template cho trang cụ thể
 */
function mytheme_page_templates( $args, $post_type ) {
    if ( $post_type === 'page' ) {
        $args['template'] = array(
            array( 'core/paragraph', array(
                'placeholder' => 'Bắt đầu viết nội dung trang...',
            ) ),
        );
    }
    return $args;
}
add_filter( 'register_post_type_args', 'mytheme_page_templates', 10, 2 );
```

---

## 9. theme.json - Cấu hình theme cho Block Editor

### theme.json là gì?

`theme.json` là file cấu hình trung tâm cho block-based themes. Nó cho phép kiểm soát:
- Color palette, typography, spacing
- Layout settings
- Block-level customizations
- CSS custom properties tự động tạo

### Cấu trúc đầy đủ

```json
{
    "$schema": "https://schemas.wp.org/wp/6.5/theme.json",
    "version": 3,

    "settings": {

        "appearanceTools": true,

        "color": {
            "custom": true,
            "customDuotone": true,
            "customGradient": true,
            "defaultPalette": false,
            "defaultGradients": false,
            "duotone": [
                {
                    "colors": [ "#000000", "#ffffff" ],
                    "slug": "den-trang",
                    "name": "Đen và Trắng"
                }
            ],
            "gradients": [
                {
                    "gradient": "linear-gradient(135deg, #3498db 0%, #2ecc71 100%)",
                    "name": "Xanh Gradient",
                    "slug": "xanh-gradient"
                }
            ],
            "palette": [
                {
                    "color": "#1a1a2e",
                    "name": "Primary",
                    "slug": "primary"
                },
                {
                    "color": "#16213e",
                    "name": "Secondary",
                    "slug": "secondary"
                },
                {
                    "color": "#0f3460",
                    "name": "Accent",
                    "slug": "accent"
                },
                {
                    "color": "#e94560",
                    "name": "Highlight",
                    "slug": "highlight"
                },
                {
                    "color": "#ffffff",
                    "name": "Background",
                    "slug": "background"
                },
                {
                    "color": "#333333",
                    "name": "Foreground",
                    "slug": "foreground"
                },
                {
                    "color": "#f5f5f5",
                    "name": "Light Gray",
                    "slug": "light-gray"
                }
            ]
        },

        "typography": {
            "customFontSize": true,
            "fluid": true,
            "fontFamilies": [
                {
                    "fontFamily": "'Inter', -apple-system, BlinkMacSystemFont, sans-serif",
                    "name": "Inter",
                    "slug": "inter",
                    "fontFace": [
                        {
                            "fontFamily": "Inter",
                            "fontWeight": "300 900",
                            "fontStyle": "normal",
                            "fontDisplay": "swap",
                            "src": [ "file:./assets/fonts/inter/Inter-VariableFont.woff2" ]
                        }
                    ]
                },
                {
                    "fontFamily": "'Playfair Display', Georgia, serif",
                    "name": "Playfair Display",
                    "slug": "playfair"
                },
                {
                    "fontFamily": "monospace",
                    "name": "Monospace",
                    "slug": "monospace"
                }
            ],
            "fontSizes": [
                {
                    "fluid": {
                        "min": "0.75rem",
                        "max": "0.875rem"
                    },
                    "name": "Small",
                    "slug": "small",
                    "size": "0.875rem"
                },
                {
                    "fluid": {
                        "min": "0.875rem",
                        "max": "1rem"
                    },
                    "name": "Medium",
                    "slug": "medium",
                    "size": "1rem"
                },
                {
                    "fluid": {
                        "min": "1rem",
                        "max": "1.25rem"
                    },
                    "name": "Large",
                    "slug": "large",
                    "size": "1.25rem"
                },
                {
                    "fluid": {
                        "min": "1.5rem",
                        "max": "2.25rem"
                    },
                    "name": "X-Large",
                    "slug": "x-large",
                    "size": "2.25rem"
                },
                {
                    "fluid": {
                        "min": "2rem",
                        "max": "3.5rem"
                    },
                    "name": "XX-Large",
                    "slug": "xx-large",
                    "size": "3.5rem"
                }
            ]
        },

        "spacing": {
            "padding": true,
            "margin": true,
            "blockGap": true,
            "units": [ "px", "em", "rem", "%", "vh", "vw" ],
            "spacingScale": {
                "steps": 7
            },
            "spacingSizes": [
                { "name": "XS", "slug": "xs", "size": "0.5rem" },
                { "name": "S", "slug": "s", "size": "1rem" },
                { "name": "M", "slug": "m", "size": "1.5rem" },
                { "name": "L", "slug": "l", "size": "2rem" },
                { "name": "XL", "slug": "xl", "size": "3rem" },
                { "name": "2XL", "slug": "2xl", "size": "5rem" }
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
            "defaultPresets": true,
            "presets": [
                {
                    "name": "Natural",
                    "slug": "natural",
                    "shadow": "0 2px 4px rgba(0,0,0,0.1)"
                },
                {
                    "name": "Deep",
                    "slug": "deep",
                    "shadow": "0 10px 30px rgba(0,0,0,0.15)"
                },
                {
                    "name": "Card",
                    "slug": "card",
                    "shadow": "0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24)"
                }
            ]
        },

        "blocks": {
            "core/button": {
                "border": {
                    "radius": true
                },
                "color": {
                    "custom": true
                }
            },
            "core/heading": {
                "typography": {
                    "fontSizes": [
                        { "name": "H1", "slug": "h1", "size": "2.5rem" },
                        { "name": "H2", "slug": "h2", "size": "2rem" },
                        { "name": "H3", "slug": "h3", "size": "1.5rem" }
                    ]
                }
            },
            "core/paragraph": {
                "typography": {
                    "lineHeight": true
                }
            }
        }
    },

    "styles": {
        "color": {
            "background": "var(--wp--preset--color--background)",
            "text": "var(--wp--preset--color--foreground)"
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
                "right": "var(--wp--preset--spacing--m)",
                "bottom": "0",
                "left": "var(--wp--preset--spacing--m)"
            }
        },

        "elements": {
            "h1": {
                "typography": {
                    "fontFamily": "var(--wp--preset--font-family--playfair)",
                    "fontSize": "var(--wp--preset--font-size--xx-large)",
                    "fontWeight": "700",
                    "lineHeight": "1.2"
                },
                "color": {
                    "text": "var(--wp--preset--color--primary)"
                }
            },
            "h2": {
                "typography": {
                    "fontFamily": "var(--wp--preset--font-family--playfair)",
                    "fontSize": "var(--wp--preset--font-size--x-large)",
                    "fontWeight": "700",
                    "lineHeight": "1.3"
                }
            },
            "h3": {
                "typography": {
                    "fontSize": "var(--wp--preset--font-size--large)",
                    "fontWeight": "600"
                }
            },
            "link": {
                "color": {
                    "text": "var(--wp--preset--color--accent)"
                },
                ":hover": {
                    "color": {
                        "text": "var(--wp--preset--color--highlight)"
                    }
                }
            },
            "button": {
                "color": {
                    "background": "var(--wp--preset--color--accent)",
                    "text": "#ffffff"
                },
                "border": {
                    "radius": "4px"
                },
                "typography": {
                    "fontWeight": "600"
                },
                ":hover": {
                    "color": {
                        "background": "var(--wp--preset--color--primary)"
                    }
                }
            }
        },

        "blocks": {
            "core/site-title": {
                "typography": {
                    "fontFamily": "var(--wp--preset--font-family--playfair)",
                    "fontSize": "1.5rem",
                    "fontWeight": "700"
                }
            },
            "core/navigation": {
                "typography": {
                    "fontSize": "0.9rem",
                    "fontWeight": "500"
                }
            },
            "core/post-title": {
                "typography": {
                    "fontFamily": "var(--wp--preset--font-family--playfair)"
                }
            },
            "core/quote": {
                "border": {
                    "left": {
                        "color": "var(--wp--preset--color--accent)",
                        "width": "4px",
                        "style": "solid"
                    }
                },
                "spacing": {
                    "padding": {
                        "left": "var(--wp--preset--spacing--m)"
                    }
                },
                "typography": {
                    "fontStyle": "italic"
                }
            },
            "core/code": {
                "typography": {
                    "fontFamily": "var(--wp--preset--font-family--monospace)",
                    "fontSize": "0.875rem"
                },
                "color": {
                    "background": "var(--wp--preset--color--light-gray)"
                },
                "spacing": {
                    "padding": {
                        "top": "var(--wp--preset--spacing--s)",
                        "right": "var(--wp--preset--spacing--m)",
                        "bottom": "var(--wp--preset--spacing--s)",
                        "left": "var(--wp--preset--spacing--m)"
                    }
                },
                "border": {
                    "radius": "4px"
                }
            }
        }
    },

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
    ],

    "customTemplates": [
        {
            "name": "blank",
            "title": "Blank (Không có header/footer)",
            "postTypes": [ "page", "post" ]
        },
        {
            "name": "full-width",
            "title": "Full Width",
            "postTypes": [ "page" ]
        },
        {
            "name": "with-sidebar",
            "title": "Với Sidebar",
            "postTypes": [ "page", "post" ]
        }
    ]
}
```

### CSS Custom Properties từ theme.json

```
theme.json tự động tạo CSS Custom Properties:

Color:
  --wp--preset--color--primary: #1a1a2e;
  --wp--preset--color--secondary: #16213e;

Font Family:
  --wp--preset--font-family--inter: 'Inter', sans-serif;

Font Size:
  --wp--preset--font-size--small: 0.875rem;
  --wp--preset--font-size--medium: 1rem;

Spacing:
  --wp--preset--spacing--xs: 0.5rem;
  --wp--preset--spacing--s: 1rem;

Shadow:
  --wp--preset--shadow--natural: 0 2px 4px rgba(0,0,0,0.1);

Sử dụng trong CSS:
  .my-element {
      color: var(--wp--preset--color--primary);
      font-family: var(--wp--preset--font-family--inter);
      padding: var(--wp--preset--spacing--m);
  }
```

---

## 10. Ví dụ block hoàn chỉnh

### Block "Team Member Card" - Đầy đủ tính năng

#### Cấu trúc thư mục

```
src/team-member-card/
  |-- block.json
  |-- index.js
  |-- edit.js
  |-- save.js
  |-- editor.scss
  |-- style.scss
```

#### block.json

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "my-blocks/team-member-card",
    "version": "1.0.0",
    "title": "Team Member Card",
    "category": "widgets",
    "icon": "admin-users",
    "description": "Card hiển thị thông tin thành viên đội ngũ.",
    "keywords": [ "team", "member", "card", "profile" ],
    "supports": {
        "html": false,
        "align": [ "wide" ],
        "color": {
            "background": true,
            "text": true
        },
        "spacing": {
            "padding": true,
            "margin": true
        }
    },
    "attributes": {
        "name": {
            "type": "string",
            "source": "html",
            "selector": ".team-card__name"
        },
        "position": {
            "type": "string",
            "source": "html",
            "selector": ".team-card__position"
        },
        "bio": {
            "type": "string",
            "source": "html",
            "selector": ".team-card__bio"
        },
        "imageId": {
            "type": "number",
            "default": 0
        },
        "imageUrl": {
            "type": "string",
            "source": "attribute",
            "selector": ".team-card__avatar img",
            "attribute": "src"
        },
        "imageAlt": {
            "type": "string",
            "source": "attribute",
            "selector": ".team-card__avatar img",
            "attribute": "alt"
        },
        "email": {
            "type": "string",
            "default": ""
        },
        "phone": {
            "type": "string",
            "default": ""
        },
        "facebook": {
            "type": "string",
            "default": ""
        },
        "linkedin": {
            "type": "string",
            "default": ""
        },
        "twitter": {
            "type": "string",
            "default": ""
        },
        "showSocial": {
            "type": "boolean",
            "default": true
        },
        "showContact": {
            "type": "boolean",
            "default": true
        },
        "cardStyle": {
            "type": "string",
            "default": "default"
        },
        "imageShape": {
            "type": "string",
            "default": "circle"
        }
    },
    "textdomain": "my-blocks",
    "editorScript": "file:./index.js",
    "editorStyle": "file:./index.css",
    "style": "file:./style-index.css"
}
```

#### index.js

```javascript
import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import save from './save';
import metadata from './block.json';
import './editor.scss';
import './style.scss';

registerBlockType( metadata.name, {
    edit: Edit,
    save,
    // Icon tùy chỉnh bằng SVG
    icon: {
        src: (
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
            </svg>
        ),
        foreground: '#3498db',
    },
    // Ví dụ block trong inserter
    example: {
        attributes: {
            name: 'Nguyễn Văn A',
            position: 'Giám Đốc Công Nghệ',
            bio: 'Có hơn 10 năm kinh nghiệm trong lĩnh vực phát triển phần mềm.',
            imageUrl: 'https://via.placeholder.com/200',
        },
    },
} );
```

#### edit.js

```javascript
import {
    useBlockProps,
    InspectorControls,
    RichText,
    MediaUpload,
    MediaUploadCheck,
} from '@wordpress/block-editor';
import {
    PanelBody,
    TextControl,
    ToggleControl,
    SelectControl,
    Button,
    Placeholder,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes } ) {
    const {
        name,
        position,
        bio,
        imageId,
        imageUrl,
        imageAlt,
        email,
        phone,
        facebook,
        linkedin,
        twitter,
        showSocial,
        showContact,
        cardStyle,
        imageShape,
    } = attributes;

    const blockProps = useBlockProps( {
        className: `card-style-${ cardStyle } image-${ imageShape }`,
    } );

    const onSelectImage = ( media ) => {
        setAttributes( {
            imageId: media.id,
            imageUrl: media.url,
            imageAlt: media.alt || name,
        } );
    };

    const onRemoveImage = () => {
        setAttributes( {
            imageId: 0,
            imageUrl: '',
            imageAlt: '',
        } );
    };

    return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Giao diện', 'my-blocks' ) }>
                    <SelectControl
                        label={ __( 'Kiểu card', 'my-blocks' ) }
                        value={ cardStyle }
                        options={ [
                            { label: 'Mặc định', value: 'default' },
                            { label: 'Có bóng', value: 'shadow' },
                            { label: 'Có viền', value: 'bordered' },
                            { label: 'Tối giản', value: 'minimal' },
                        ] }
                        onChange={ ( value ) => setAttributes( { cardStyle: value } ) }
                    />

                    <SelectControl
                        label={ __( 'Hình dạng ảnh', 'my-blocks' ) }
                        value={ imageShape }
                        options={ [
                            { label: 'Tròn', value: 'circle' },
                            { label: 'Vuông', value: 'square' },
                            { label: 'Bo góc', value: 'rounded' },
                        ] }
                        onChange={ ( value ) => setAttributes( { imageShape: value } ) }
                    />
                </PanelBody>

                <PanelBody title={ __( 'Thông tin liên hệ', 'my-blocks' ) } initialOpen={ false }>
                    <ToggleControl
                        label={ __( 'Hiện thông tin liên hệ', 'my-blocks' ) }
                        checked={ showContact }
                        onChange={ ( value ) => setAttributes( { showContact: value } ) }
                    />
                    { showContact && (
                        <>
                            <TextControl
                                label={ __( 'Email', 'my-blocks' ) }
                                value={ email }
                                onChange={ ( value ) => setAttributes( { email: value } ) }
                                type="email"
                            />
                            <TextControl
                                label={ __( 'Số điện thoại', 'my-blocks' ) }
                                value={ phone }
                                onChange={ ( value ) => setAttributes( { phone: value } ) }
                                type="tel"
                            />
                        </>
                    ) }
                </PanelBody>

                <PanelBody title={ __( 'Mạng xã hội', 'my-blocks' ) } initialOpen={ false }>
                    <ToggleControl
                        label={ __( 'Hiện link mạng xã hội', 'my-blocks' ) }
                        checked={ showSocial }
                        onChange={ ( value ) => setAttributes( { showSocial: value } ) }
                    />
                    { showSocial && (
                        <>
                            <TextControl
                                label="Facebook URL"
                                value={ facebook }
                                onChange={ ( value ) => setAttributes( { facebook: value } ) }
                                type="url"
                            />
                            <TextControl
                                label="LinkedIn URL"
                                value={ linkedin }
                                onChange={ ( value ) => setAttributes( { linkedin: value } ) }
                                type="url"
                            />
                            <TextControl
                                label="Twitter URL"
                                value={ twitter }
                                onChange={ ( value ) => setAttributes( { twitter: value } ) }
                                type="url"
                            />
                        </>
                    ) }
                </PanelBody>
            </InspectorControls>

            <div { ...blockProps }>
                <div className="team-card">
                    {/* Avatar */}
                    <div className="team-card__avatar">
                        <MediaUploadCheck>
                            { imageUrl ? (
                                <div className="team-card__image-wrapper">
                                    <img src={ imageUrl } alt={ imageAlt } />
                                    <div className="team-card__image-actions">
                                        <MediaUpload
                                            onSelect={ onSelectImage }
                                            allowedTypes={ [ 'image' ] }
                                            value={ imageId }
                                            render={ ( { open } ) => (
                                                <Button onClick={ open } isSmall variant="secondary">
                                                    { __( 'Đổi', 'my-blocks' ) }
                                                </Button>
                                            ) }
                                        />
                                        <Button onClick={ onRemoveImage } isSmall isDestructive>
                                            { __( 'Xóa', 'my-blocks' ) }
                                        </Button>
                                    </div>
                                </div>
                            ) : (
                                <MediaUpload
                                    onSelect={ onSelectImage }
                                    allowedTypes={ [ 'image' ] }
                                    render={ ( { open } ) => (
                                        <Placeholder
                                            icon="admin-users"
                                            label={ __( 'Ảnh đại diện', 'my-blocks' ) }
                                        >
                                            <Button onClick={ open } variant="primary" isSmall>
                                                { __( 'Chọn ảnh', 'my-blocks' ) }
                                            </Button>
                                        </Placeholder>
                                    ) }
                                />
                            ) }
                        </MediaUploadCheck>
                    </div>

                    {/* Thông tin */}
                    <div className="team-card__info">
                        <RichText
                            tagName="h3"
                            className="team-card__name"
                            value={ name }
                            onChange={ ( value ) => setAttributes( { name: value } ) }
                            placeholder={ __( 'Họ và tên...', 'my-blocks' ) }
                            allowedFormats={ [] }
                        />

                        <RichText
                            tagName="p"
                            className="team-card__position"
                            value={ position }
                            onChange={ ( value ) => setAttributes( { position: value } ) }
                            placeholder={ __( 'Chức vụ...', 'my-blocks' ) }
                            allowedFormats={ [] }
                        />

                        <RichText
                            tagName="p"
                            className="team-card__bio"
                            value={ bio }
                            onChange={ ( value ) => setAttributes( { bio: value } ) }
                            placeholder={ __( 'Tiểu sử ngắn...', 'my-blocks' ) }
                            allowedFormats={ [ 'core/bold', 'core/italic' ] }
                        />

                        {/* Preview contact và social */}
                        { showContact && ( email || phone ) && (
                            <div className="team-card__contact">
                                { email && <span>Email: { email }</span> }
                                { phone && <span>SĐT: { phone }</span> }
                            </div>
                        ) }

                        { showSocial && ( facebook || linkedin || twitter ) && (
                            <div className="team-card__social">
                                { facebook && <span>FB</span> }
                                { linkedin && <span>LI</span> }
                                { twitter && <span>TW</span> }
                            </div>
                        ) }
                    </div>
                </div>
            </div>
        </>
    );
}
```

#### save.js

```javascript
import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function save( { attributes } ) {
    const {
        name,
        position,
        bio,
        imageUrl,
        imageAlt,
        email,
        phone,
        facebook,
        linkedin,
        twitter,
        showSocial,
        showContact,
        cardStyle,
        imageShape,
    } = attributes;

    const blockProps = useBlockProps.save( {
        className: `card-style-${ cardStyle } image-${ imageShape }`,
    } );

    return (
        <div { ...blockProps }>
            <div className="team-card">
                { imageUrl && (
                    <div className="team-card__avatar">
                        <img src={ imageUrl } alt={ imageAlt || name } />
                    </div>
                ) }

                <div className="team-card__info">
                    <RichText.Content tagName="h3" className="team-card__name" value={ name } />
                    <RichText.Content tagName="p" className="team-card__position" value={ position } />
                    <RichText.Content tagName="p" className="team-card__bio" value={ bio } />

                    { showContact && ( email || phone ) && (
                        <div className="team-card__contact">
                            { email && (
                                <a href={ `mailto:${ email }` } className="team-card__email">
                                    { email }
                                </a>
                            ) }
                            { phone && (
                                <a href={ `tel:${ phone }` } className="team-card__phone">
                                    { phone }
                                </a>
                            ) }
                        </div>
                    ) }

                    { showSocial && ( facebook || linkedin || twitter ) && (
                        <div className="team-card__social">
                            { facebook && (
                                <a href={ facebook } target="_blank" rel="noopener noreferrer"
                                   className="team-card__social-link team-card__social-link--facebook">
                                    Facebook
                                </a>
                            ) }
                            { linkedin && (
                                <a href={ linkedin } target="_blank" rel="noopener noreferrer"
                                   className="team-card__social-link team-card__social-link--linkedin">
                                    LinkedIn
                                </a>
                            ) }
                            { twitter && (
                                <a href={ twitter } target="_blank" rel="noopener noreferrer"
                                   className="team-card__social-link team-card__social-link--twitter">
                                    Twitter
                                </a>
                            ) }
                        </div>
                    ) }
                </div>
            </div>
        </div>
    );
}
```

#### style.scss

```scss
// style.scss - Hiển thị cả editor và frontend
.wp-block-my-blocks-team-member-card {
    max-width: 400px;

    .team-card {
        text-align: center;
    }

    // Avatar styles
    .team-card__avatar {
        margin-bottom: 1rem;

        img {
            width: 150px;
            height: 150px;
            object-fit: cover;
        }
    }

    // Hình dạng ảnh
    &.image-circle .team-card__avatar img {
        border-radius: 50%;
    }

    &.image-square .team-card__avatar img {
        border-radius: 0;
    }

    &.image-rounded .team-card__avatar img {
        border-radius: 12px;
    }

    // Thông tin
    .team-card__name {
        font-size: 1.4rem;
        font-weight: 700;
        margin-bottom: 0.25rem;
        color: #333;
    }

    .team-card__position {
        font-size: 0.9rem;
        color: #3498db;
        font-weight: 600;
        margin-bottom: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .team-card__bio {
        font-size: 0.95rem;
        color: #666;
        line-height: 1.6;
        margin-bottom: 1rem;
    }

    // Contact
    .team-card__contact {
        margin-bottom: 1rem;

        a {
            display: block;
            color: #555;
            text-decoration: none;
            margin-bottom: 0.25rem;

            &:hover {
                color: #3498db;
            }
        }
    }

    // Social links
    .team-card__social {
        display: flex;
        justify-content: center;
        gap: 0.75rem;

        .team-card__social-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #ecf0f1;
            color: #555;
            text-decoration: none;
            font-size: 0;
            transition: all 0.3s ease;

            &::before {
                font-size: 14px;
            }

            &--facebook {
                &:hover { background-color: #3b5998; color: white; }
                &::before { content: "FB"; font-size: 12px; font-weight: 700; }
            }

            &--linkedin {
                &:hover { background-color: #0077b5; color: white; }
                &::before { content: "LI"; font-size: 12px; font-weight: 700; }
            }

            &--twitter {
                &:hover { background-color: #1da1f2; color: white; }
                &::before { content: "TW"; font-size: 12px; font-weight: 700; }
            }
        }
    }

    // Card styles
    &.card-style-default {
        .team-card {
            padding: 2rem;
        }
    }

    &.card-style-shadow {
        .team-card {
            padding: 2rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            border-radius: 12px;
        }
    }

    &.card-style-bordered {
        .team-card {
            padding: 2rem;
            border: 2px solid #ecf0f1;
            border-radius: 8px;
        }
    }

    &.card-style-minimal {
        .team-card {
            padding: 1rem 0;
        }
    }
}
```

#### editor.scss

```scss
// editor.scss - Chỉ hiện trong editor
.wp-block-my-blocks-team-member-card {
    border: 1px dashed #ddd;
    border-radius: 8px;

    .team-card__image-wrapper {
        position: relative;

        .team-card__image-actions {
            position: absolute;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 8px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        &:hover .team-card__image-actions {
            opacity: 1;
        }
    }

    // Style cho placeholder
    .components-placeholder {
        min-height: 150px;
    }
}
```

#### Đăng ký block trong PHP

```php
<?php
// my-blocks-plugin.php

function my_blocks_register_all() {
    // Đăng ký từng block từ thư mục build
    $blocks = array(
        'hello-block',
        'team-member-card',
        'latest-posts',
    );

    foreach ( $blocks as $block ) {
        register_block_type( __DIR__ . '/build/' . $block );
    }
}
add_action( 'init', 'my_blocks_register_all' );
```

### Tổng kết quy trình phát triển block

```
1. Tạo cấu trúc thư mục trong src/
2. Viết block.json (metadata, attributes)
3. Viết edit.js (giao diện trong editor)
4. Viết save.js (HTML lưu vào database) hoặc render.php (dynamic block)
5. Viết styles (editor.scss, style.scss)
6. Build: npm run build
7. Đăng ký trong PHP: register_block_type()
8. Test trong editor và frontend
9. Lặp lại bước 2-8 cho từng block mới
```
