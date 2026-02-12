# Gutenberg / Block Editor - Huong Dan Chi Tiet

## Muc luc

1. [Gioi thieu Block Editor](#1-gioi-thieu-block-editor)
2. [Cau truc Block - attributes, edit, save](#2-cau-truc-block---attributes-edit-save)
3. [Tao Custom Block don gian voi @wordpress/scripts](#3-tao-custom-block-don-gian-voi-wordpressscripts)
4. [Block Attributes va InspectorControls](#4-block-attributes-va-inspectorcontrols)
5. [RichText, MediaUpload components](#5-richtext-mediaupload-components)
6. [Dynamic Blocks (Server-side rendering)](#6-dynamic-blocks-server-side-rendering)
7. [Block Patterns](#7-block-patterns)
8. [Block Templates](#8-block-templates)
9. [theme.json - Cau hinh theme cho Block Editor](#9-themejson---cau-hinh-theme-cho-block-editor)
10. [Vi du block hoan chinh](#10-vi-du-block-hoan-chinh)

---

## 1. Gioi thieu Block Editor

### Block Editor la gi?

Block Editor (hay Gutenberg) la trinh soan thao noi dung mac dinh cua WordPress tu phien ban 5.0. Thay vi su dung mot vung soan thao lon (TinyMCE), Gutenberg chia noi dung thanh cac "blocks" (khoi) doc lap.

### Cac khai niem co ban

```
Block: Don vi noi dung nho nhat (paragraph, heading, image, button, ...)
Block Type: Loai block da duoc dang ky (core/paragraph, core/image, ...)
Attributes: Du lieu cau hinh cua block (noi dung, mau sac, kich thuoc, ...)
InnerBlocks: Block co the chua cac block con ben trong
Block Patterns: Nhom cac block duoc sap xep san
Block Templates: Cau truc block mac dinh cho post type
```

### Kien truc tong quan

```
WordPress Block Editor
  |
  |-- Editor (React App)
  |     |-- Block Toolbar (thanh cong cu tren block)
  |     |-- Block Inspector / Sidebar (panel cai dat ben phai)
  |     |-- Block Content (noi dung chinh)
  |
  |-- Blocks
  |     |-- Core Blocks (paragraph, heading, image, ...)
  |     |-- Custom Blocks (ban tu tao)
  |     |-- Third-party Blocks (tu plugin)
  |
  |-- Data Store (@wordpress/data)
  |     |-- core/editor
  |     |-- core/block-editor
  |     |-- core/notices
  |
  |-- REST API (luu va tai noi dung)
```

### Cong nghe su dung

```
- React.js: Xay dung giao dien
- JSX: Cu phap viet component
- @wordpress/scripts: Build tools (webpack, babel)
- @wordpress/components: Thu vien UI components
- @wordpress/block-editor: API cho block editor
- @wordpress/blocks: API dang ky va quan ly blocks
- @wordpress/data: State management (giong Redux)
```

---

## 2. Cau truc Block - attributes, edit, save

### Cau truc co ban cua mot block

```javascript
// index.js - File chinh cua block
import { registerBlockType } from '@wordpress/blocks';
import Edit from './edit';
import save from './save';
import metadata from './block.json';

registerBlockType( metadata.name, {
    edit: Edit,  // Component hien thi trong editor
    save,        // Component render HTML luu vao database
} );
```

### block.json - File metadata (bat buoc tu WP 6.0+)

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "my-plugin/my-block",
    "version": "1.0.0",
    "title": "Block Cua Toi",
    "category": "widgets",
    "icon": "smiley",
    "description": "Mo ta block cua toi.",
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

### Giai thich cac thanh phan trong block.json

```
apiVersion: 3         - Phien ban API (3 la moi nhat)
name:                 - Ten duy nhat, format: namespace/block-name
category:             - Nhom: text, media, design, widgets, theme, embed
icon:                 - Dashicon hoac SVG
supports:             - Cac tinh nang block ho tro
attributes:           - Du lieu cua block
editorScript:         - JS chi load trong editor
editorStyle:          - CSS chi load trong editor
style:                - CSS load ca editor va frontend
render:               - PHP template cho dynamic block
viewScript:           - JS chi load tren frontend
```

### edit.js - Component hien thi trong Editor

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

### save.js - Component render HTML luu vao database

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

### Vong doi cua block

```
1. User them block vao editor
   -> registerBlockType() duoc goi
   -> edit() component render trong editor

2. User chinh sua noi dung
   -> setAttributes() cap nhat du lieu
   -> edit() re-render voi attributes moi

3. User luu bai viet
   -> save() component render HTML
   -> HTML duoc luu vao post_content trong database
   -> Dinh dang: <!-- wp:my-plugin/my-block {"attr":"value"} -->HTML<!-- /wp:my-plugin/my-block -->

4. Frontend hien thi
   -> WordPress doc post_content
   -> Parse block markup
   -> Render HTML (static) hoac goi render callback (dynamic)
```

---

## 3. Tao Custom Block don gian voi @wordpress/scripts

### Buoc 1: Cai dat cong cu

```bash
# Tao plugin moi
mkdir -p wp-content/plugins/my-blocks-plugin
cd wp-content/plugins/my-blocks-plugin
```

### Buoc 2: Tao file plugin chinh

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
 * Dang ky tat ca custom blocks
 */
function my_blocks_register() {
    // Dang ky block tu block.json
    // WordPress se tu dong enqueue scripts va styles
    register_block_type( __DIR__ . '/build/hello-block' );
}
add_action( 'init', 'my_blocks_register' );
```

### Buoc 3: Tao cau truc thu muc source

```
my-blocks-plugin/
  |-- my-blocks-plugin.php        (File chinh)
  |-- package.json
  |-- src/
  |     |-- hello-block/
  |           |-- block.json
  |           |-- index.js
  |           |-- edit.js
  |           |-- save.js
  |           |-- editor.scss
  |           |-- style.scss
  |-- build/                      (Tu dong tao khi build)
```

### Buoc 4: package.json

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
# Cai dat dependencies
npm install
```

### Buoc 5: block.json

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "my-blocks/hello-block",
    "version": "1.0.0",
    "title": "Hello Block",
    "category": "widgets",
    "icon": "smiley",
    "description": "Block chao hoi don gian.",
    "keywords": [ "hello", "chao", "example" ],
    "supports": {
        "html": false
    },
    "attributes": {
        "message": {
            "type": "string",
            "default": "Xin chao!"
        }
    },
    "textdomain": "my-blocks",
    "editorScript": "file:./index.js",
    "editorStyle": "file:./index.css",
    "style": "file:./style-index.css"
}
```

### Buoc 6: index.js

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

### Buoc 7: edit.js

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
                label={ __( 'Loi chao', 'my-blocks' ) }
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

### Buoc 8: save.js

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

### Buoc 9: Styles

```scss
// editor.scss - Chi hien trong editor
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
// style.scss - Hien ca editor va frontend
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

### Buoc 10: Build va su dung

```bash
# Development (watch mode - tu dong rebuild khi thay doi)
npm start

# Production build
npm run build

# Ket qua:
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

## 4. Block Attributes va InspectorControls

### Cac loai Attributes

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

### Giai thich Attribute Sources

```
"source": "html"
  -> Lay noi dung HTML tu selector
  -> Vi du: <p class="content">Noi dung nay</p> => "Noi dung nay"

"source": "attribute"
  -> Lay gia tri attribute cua HTML element
  -> Vi du: <img src="url.jpg"> => "url.jpg"

"source": "text"
  -> Lay text content (khong co HTML tags)

"source": "query"
  -> Lay du lieu tu nhieu elements (tra ve array)

Khong co "source":
  -> Luu truc tiep trong block comment
  -> <!-- wp:my-block {"myAttr":"value"} -->
```

### InspectorControls - Panel cai dat ben phai

```javascript
// edit.js voi InspectorControls day du
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

    // Mau sac cho ColorPalette
    const colors = [
        { name: 'Do', color: '#e74c3c' },
        { name: 'Xanh duong', color: '#3498db' },
        { name: 'Xanh la', color: '#2ecc71' },
        { name: 'Vang', color: '#f39c12' },
        { name: 'Tim', color: '#9b59b6' },
        { name: 'Trang', color: '#ffffff' },
        { name: 'Den', color: '#000000' },
    ];

    // Font sizes cho FontSizePicker
    const fontSizes = [
        { name: 'Nho', slug: 'small', size: 14 },
        { name: 'Vua', slug: 'medium', size: 16 },
        { name: 'Lon', slug: 'large', size: 20 },
        { name: 'Rat lon', slug: 'x-large', size: 28 },
    ];

    return (
        <>
            {/* Inspector Controls - Panel cai dat ben phai */}
            <InspectorControls>

                {/* Panel 1: Noi dung */}
                <PanelBody title={ __( 'Noi dung', 'my-blocks' ) } initialOpen={ true }>
                    <TextControl
                        label={ __( 'Tieu de', 'my-blocks' ) }
                        value={ title }
                        onChange={ ( value ) => setAttributes( { title: value } ) }
                        help="Nhap tieu de cua block"
                    />

                    <TextareaControl
                        label={ __( 'Mo ta', 'my-blocks' ) }
                        value={ description }
                        onChange={ ( value ) => setAttributes( { description: value } ) }
                        rows={ 4 }
                    />

                    <ToggleControl
                        label={ __( 'Hien thi tieu de', 'my-blocks' ) }
                        checked={ showTitle }
                        onChange={ ( value ) => setAttributes( { showTitle: value } ) }
                    />
                </PanelBody>

                {/* Panel 2: Bo cuc */}
                <PanelBody title={ __( 'Bo cuc', 'my-blocks' ) } initialOpen={ false }>
                    <SelectControl
                        label={ __( 'Kieu bo cuc', 'my-blocks' ) }
                        value={ layout }
                        options={ [
                            { label: 'Luoi (Grid)', value: 'grid' },
                            { label: 'Danh sach (List)', value: 'list' },
                            { label: 'Carousel', value: 'carousel' },
                        ] }
                        onChange={ ( value ) => setAttributes( { layout: value } ) }
                    />

                    <RangeControl
                        label={ __( 'So cot', 'my-blocks' ) }
                        value={ columns }
                        onChange={ ( value ) => setAttributes( { columns: value } ) }
                        min={ 1 }
                        max={ 6 }
                        step={ 1 }
                    />

                    <RadioControl
                        label={ __( 'Can chinh', 'my-blocks' ) }
                        selected={ alignment }
                        options={ [
                            { label: 'Trai', value: 'left' },
                            { label: 'Giua', value: 'center' },
                            { label: 'Phai', value: 'right' },
                        ] }
                        onChange={ ( value ) => setAttributes( { alignment: value } ) }
                    />
                </PanelBody>

                {/* Panel 3: Giao dien */}
                <PanelBody title={ __( 'Giao dien', 'my-blocks' ) } initialOpen={ false }>
                    <p>{ __( 'Mau nen', 'my-blocks' ) }</p>
                    <ColorPalette
                        colors={ colors }
                        value={ backgroundColor }
                        onChange={ ( value ) => setAttributes( { backgroundColor: value } ) }
                    />

                    <p>{ __( 'Mau chu', 'my-blocks' ) }</p>
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
                        label={ __( 'Bo goc (border-radius)', 'my-blocks' ) }
                        value={ borderRadius }
                        onChange={ ( value ) => setAttributes( { borderRadius: value } ) }
                        min={ 0 }
                        max={ 50 }
                    />
                </PanelBody>

                {/* Panel 4: Nang cao */}
                <PanelBody title={ __( 'Nang cao', 'my-blocks' ) } initialOpen={ false }>
                    <CheckboxControl
                        label={ __( 'Bat hieu ung dong (animation)', 'my-blocks' ) }
                        checked={ enableAnimation }
                        onChange={ ( value ) => setAttributes( { enableAnimation: value } ) }
                    />
                </PanelBody>

            </InspectorControls>

            {/* Noi dung block trong editor */}
            <div { ...blockProps }>
                { showTitle && <h3>{ title || 'Nhap tieu de...' }</h3> }
                <p>{ description || 'Nhap mo ta...' }</p>
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
            {/* Block Toolbar - Thanh cong cu phia tren block */}
            <BlockControls>
                {/* Alignment toolbar co san */}
                <AlignmentToolbar
                    value={ alignment }
                    onChange={ ( value ) => setAttributes( { alignment: value } ) }
                />

                {/* Toolbar group tuy chinh */}
                <ToolbarGroup>
                    <ToolbarButton
                        icon={ formatBold }
                        label={ __( 'Dam', 'my-blocks' ) }
                        isPressed={ isBold }
                        onClick={ () => setAttributes( { isBold: ! isBold } ) }
                    />
                    <ToolbarButton
                        icon={ formatItalic }
                        label={ __( 'Nghieng', 'my-blocks' ) }
                        isPressed={ isItalic }
                        onClick={ () => setAttributes( { isItalic: ! isItalic } ) }
                    />
                </ToolbarGroup>

                {/* Dropdown menu */}
                <ToolbarDropdownMenu
                    icon={ link }
                    label={ __( 'Tuy chon', 'my-blocks' ) }
                    controls={ [
                        {
                            title: 'Tuy chon 1',
                            onClick: () => console.log( 'Option 1' ),
                        },
                        {
                            title: 'Tuy chon 2',
                            onClick: () => console.log( 'Option 2' ),
                        },
                    ] }
                />
            </BlockControls>

            <div { ...blockProps }>
                <p style={ { textAlign: alignment } }>Noi dung block</p>
            </div>
        </>
    );
}
```

---

## 5. RichText, MediaUpload components

### RichText - Soan thao van ban rich text

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
                tagName="h2"                                   // HTML tag se render
                className="my-block-heading"
                value={ heading }                              // Gia tri hien tai
                onChange={ ( value ) => setAttributes( { heading: value } ) }
                placeholder={ __( 'Nhap tieu de...', 'my-blocks' ) }
                allowedFormats={ [ 'core/bold', 'core/italic' ] }  // Gioi han format
                // allowedFormats={ [] }                         // Khong cho format nao
            />

            {/* RichText cho noi dung */}
            <RichText
                tagName="p"
                className="my-block-content"
                value={ content }
                onChange={ ( value ) => setAttributes( { content: value } ) }
                placeholder={ __( 'Nhap noi dung...', 'my-blocks' ) }
                // Mac dinh cho phep tat ca formats
            />

            {/* RichText dang danh sach */}
            <RichText
                tagName="ul"
                multiline="li"                                 // Moi dong la 1 <li>
                value={ attributes.listItems }
                onChange={ ( value ) => setAttributes( { listItems: value } ) }
                placeholder={ __( 'Nhap muc...', 'my-blocks' ) }
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

### MediaUpload - Upload va chon hinh anh

```javascript
import { useBlockProps, MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { Button, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function Edit( { attributes, setAttributes } ) {
    const { imageId, imageUrl, imageAlt } = attributes;
    const blockProps = useBlockProps();

    // Callback khi chon hinh
    const onSelectImage = ( media ) => {
        setAttributes( {
            imageId: media.id,
            imageUrl: media.url,
            imageAlt: media.alt,
        } );
    };

    // Callback khi xoa hinh
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
                    // Da co hinh - hien thi hinh va nut thay doi
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
                                        { __( 'Doi hinh', 'my-blocks' ) }
                                    </Button>
                                ) }
                            />
                            <Button
                                onClick={ onRemoveImage }
                                variant="link"
                                isDestructive
                                isSmall
                            >
                                { __( 'Xoa hinh', 'my-blocks' ) }
                            </Button>
                        </div>
                    </div>
                ) : (
                    // Chua co hinh - hien thi placeholder
                    <MediaUpload
                        onSelect={ onSelectImage }
                        allowedTypes={ [ 'image' ] }
                        value={ imageId }
                        render={ ( { open } ) => (
                            <Placeholder
                                icon="format-image"
                                label={ __( 'Hinh anh', 'my-blocks' ) }
                                instructions={ __( 'Chon hoac upload hinh anh', 'my-blocks' ) }
                            >
                                <Button
                                    onClick={ open }
                                    variant="primary"
                                >
                                    { __( 'Chon hinh', 'my-blocks' ) }
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

### MediaUpload cho Video va File

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
            { __( 'Chon video', 'my-blocks' ) }
        </Button>
    ) }
/>

// Upload nhieu hinh (gallery)
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
    multiple={ true }           // Cho phep chon nhieu
    gallery={ true }            // Giao dien gallery
    value={ attributes.gallery ? attributes.gallery.map( ( img ) => img.id ) : [] }
    render={ ( { open } ) => (
        <Button onClick={ open } variant="primary">
            { __( 'Chon hinh gallery', 'my-blocks' ) }
        </Button>
    ) }
/>
```

### InnerBlocks - Block chua block con

```javascript
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';

// --- EDIT ---
export function Edit() {
    const blockProps = useBlockProps();

    // Template mac dinh cho InnerBlocks
    const TEMPLATE = [
        [ 'core/heading', { level: 2, placeholder: 'Tieu de...' } ],
        [ 'core/paragraph', { placeholder: 'Noi dung...' } ],
        [ 'core/image', {} ],
    ];

    // Gioi han cac block cho phep
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
                // 'all' = khong cho sua template
                // 'insert' = khong cho them/xoa block
                // false = tu do chinh sua
                allowedBlocks={ ALLOWED_BLOCKS }
                // renderAppender={ InnerBlocks.ButtonBlockAppender }
                // renderAppender={ () => null }  // An nut them block
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

### Khi nao dung Dynamic Block?

```
Static Block: HTML duoc tao boi save() va luu vao database
  -> Nhanh, khong can PHP khi render
  -> Phu hop cho noi dung tinh (text, image, layout)

Dynamic Block: HTML duoc tao boi PHP moi khi hien thi
  -> Noi dung thay doi theo thoi gian thuc
  -> Phu hop cho: bai viet moi nhat, san pham, query tu database
  -> save() tra ve null
```

### Vi du Dynamic Block: Bai viet moi nhat

#### block.json

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "my-blocks/latest-posts",
    "title": "Bai Viet Moi Nhat",
    "category": "widgets",
    "icon": "list-view",
    "description": "Hien thi danh sach bai viet moi nhat.",
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

#### edit.js (voi ServerSideRender)

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
                <PanelBody title={ __( 'Cai dat', 'my-blocks' ) }>
                    <SelectControl
                        label={ __( 'Loai bai viet', 'my-blocks' ) }
                        value={ postType }
                        options={ [
                            { label: 'Bai viet', value: 'post' },
                            { label: 'San pham', value: 'product' },
                            { label: 'Portfolio', value: 'portfolio' },
                        ] }
                        onChange={ ( value ) => setAttributes( { postType: value } ) }
                    />

                    <RangeControl
                        label={ __( 'So bai viet', 'my-blocks' ) }
                        value={ numberOfPosts }
                        onChange={ ( value ) => setAttributes( { numberOfPosts: value } ) }
                        min={ 1 }
                        max={ 20 }
                    />

                    <RangeControl
                        label={ __( 'So cot', 'my-blocks' ) }
                        value={ columns }
                        onChange={ ( value ) => setAttributes( { columns: value } ) }
                        min={ 1 }
                        max={ 4 }
                    />

                    <SelectControl
                        label={ __( 'Sap xep theo', 'my-blocks' ) }
                        value={ orderBy }
                        options={ [
                            { label: 'Ngay tao', value: 'date' },
                            { label: 'Tieu de', value: 'title' },
                            { label: 'Ngau nhien', value: 'rand' },
                            { label: 'So binh luan', value: 'comment_count' },
                        ] }
                        onChange={ ( value ) => setAttributes( { orderBy: value } ) }
                    />

                    <SelectControl
                        label={ __( 'Thu tu', 'my-blocks' ) }
                        value={ order }
                        options={ [
                            { label: 'Moi nhat truoc', value: 'desc' },
                            { label: 'Cu nhat truoc', value: 'asc' },
                        ] }
                        onChange={ ( value ) => setAttributes( { order: value } ) }
                    />
                </PanelBody>

                <PanelBody title={ __( 'Hien thi', 'my-blocks' ) } initialOpen={ false }>
                    <ToggleControl
                        label={ __( 'Hien anh dai dien', 'my-blocks' ) }
                        checked={ showThumbnail }
                        onChange={ ( value ) => setAttributes( { showThumbnail: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Hien tom tat', 'my-blocks' ) }
                        checked={ showExcerpt }
                        onChange={ ( value ) => setAttributes( { showExcerpt: value } ) }
                    />
                    <ToggleControl
                        label={ __( 'Hien ngay dang', 'my-blocks' ) }
                        checked={ showDate }
                        onChange={ ( value ) => setAttributes( { showDate: value } ) }
                    />
                </PanelBody>
            </InspectorControls>

            <div { ...blockProps }>
                {/* Render phia server trong editor */}
                <ServerSideRender
                    block="my-blocks/latest-posts"
                    attributes={ attributes }
                />
            </div>
        </>
    );
}
```

#### save.js (tra ve null cho dynamic block)

```javascript
// Dynamic block khong can save - tra ve null
export default function save() {
    return null;
}
```

#### render.php (Server-side rendering)

```php
<?php
/**
 * Render callback cho block "Bai Viet Moi Nhat"
 *
 * Cac bien co san:
 * $attributes - Mang attributes cua block
 * $content    - Noi dung InnerBlocks (neu co)
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

// Query bai viet
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
    echo '<p>Khong co bai viet nao.</p>';
    return;
}

// Lay wrapper attributes tu block
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

### Dang ky Dynamic Block bang PHP (cach cu, khong dung block.json)

```php
/**
 * Dang ky dynamic block bang PHP
 * Chi dung khi khong su dung block.json
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

### Block Pattern la gi?

Block Pattern la nhom cac block duoc sap xep san, nguoi dung co the chen vao noi dung voi 1 click. Khac voi Reusable Blocks, Pattern tao ban sao doc lap (thay doi pattern khong anh huong cac noi khac).

### Dang ky Block Pattern

```php
/**
 * Dang ky Block Patterns
 */
function mytheme_register_block_patterns() {

    // 1. Dang ky Pattern Category truoc
    register_block_pattern_category( 'mytheme-patterns', array(
        'label' => __( 'My Theme Patterns', 'mytheme' ),
    ) );

    register_block_pattern_category( 'mytheme-hero', array(
        'label' => __( 'Hero Sections', 'mytheme' ),
    ) );

    // 2. Dang ky Pattern

    // Pattern: Hero Section
    register_block_pattern( 'mytheme/hero-section', array(
        'title'       => __( 'Hero Section', 'mytheme' ),
        'description' => __( 'Hero section voi tieu de lon va nut CTA', 'mytheme' ),
        'categories'  => array( 'mytheme-hero', 'featured' ),
        'keywords'    => array( 'hero', 'banner', 'header' ),
        'blockTypes'  => array( 'core/cover' ),
        'content'     => '
            <!-- wp:cover {"overlayColor":"black","minHeight":500,"align":"full"} -->
            <div class="wp-block-cover alignfull" style="min-height:500px">
                <span aria-hidden="true" class="wp-block-cover__background has-black-background-color has-background-dim"></span>
                <div class="wp-block-cover__inner-container">
                    <!-- wp:heading {"textAlign":"center","level":1,"style":{"color":{"text":"#ffffff"}}} -->
                    <h1 class="wp-block-heading has-text-align-center" style="color:#ffffff">Chao mung den voi Website cua chung toi</h1>
                    <!-- /wp:heading -->

                    <!-- wp:paragraph {"align":"center","style":{"color":{"text":"#cccccc"}}} -->
                    <p class="has-text-align-center" style="color:#cccccc">Mo ta ngan gon ve website hoac dich vu cua ban. Thu hut nguoi dung bang thong diep ro rang.</p>
                    <!-- /wp:paragraph -->

                    <!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
                    <div class="wp-block-buttons">
                        <!-- wp:button {"backgroundColor":"vivid-cyan-blue"} -->
                        <div class="wp-block-button"><a class="wp-block-button__link has-vivid-cyan-blue-background-color has-background wp-element-button">Tim Hieu Them</a></div>
                        <!-- /wp:button -->
                        <!-- wp:button {"className":"is-style-outline"} -->
                        <div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button">Lien He</a></div>
                        <!-- /wp:button -->
                    </div>
                    <!-- /wp:buttons -->
                </div>
            </div>
            <!-- /wp:cover -->
        ',
    ) );

    // Pattern: Card Grid (3 cot)
    register_block_pattern( 'mytheme/card-grid', array(
        'title'       => __( 'Card Grid 3 Cot', 'mytheme' ),
        'description' => __( 'Luoi 3 card voi icon, tieu de va mo ta', 'mytheme' ),
        'categories'  => array( 'mytheme-patterns' ),
        'content'     => '
            <!-- wp:columns -->
            <div class="wp-block-columns">
                <!-- wp:column -->
                <div class="wp-block-column">
                    <!-- wp:heading {"textAlign":"center","level":3} -->
                    <h3 class="wp-block-heading has-text-align-center">Dich Vu 1</h3>
                    <!-- /wp:heading -->
                    <!-- wp:paragraph {"align":"center"} -->
                    <p class="has-text-align-center">Mo ta chi tiet ve dich vu thu nhat cua ban.</p>
                    <!-- /wp:paragraph -->
                </div>
                <!-- /wp:column -->

                <!-- wp:column -->
                <div class="wp-block-column">
                    <!-- wp:heading {"textAlign":"center","level":3} -->
                    <h3 class="wp-block-heading has-text-align-center">Dich Vu 2</h3>
                    <!-- /wp:heading -->
                    <!-- wp:paragraph {"align":"center"} -->
                    <p class="has-text-align-center">Mo ta chi tiet ve dich vu thu hai cua ban.</p>
                    <!-- /wp:paragraph -->
                </div>
                <!-- /wp:column -->

                <!-- wp:column -->
                <div class="wp-block-column">
                    <!-- wp:heading {"textAlign":"center","level":3} -->
                    <h3 class="wp-block-heading has-text-align-center">Dich Vu 3</h3>
                    <!-- /wp:heading -->
                    <!-- wp:paragraph {"align":"center"} -->
                    <p class="has-text-align-center">Mo ta chi tiet ve dich vu thu ba cua ban.</p>
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
                <p class="has-text-align-center" style="font-size:18px;font-style:italic">"San pham rat tuyet voi! Toi da su dung duoc 6 thang va rat hai long voi chat luong dich vu."</p>
                <!-- /wp:paragraph -->
                <!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"700"}}} -->
                <p class="has-text-align-center" style="font-weight:700">- Nguyen Van A, Giam doc Cong ty XYZ</p>
                <!-- /wp:paragraph -->
            </div>
            <!-- /wp:group -->
        ',
    ) );
}
add_action( 'init', 'mytheme_register_block_patterns' );
```

### Xoa hoac an Block Patterns

```php
// Xoa pattern cu the
function mytheme_unregister_patterns() {
    unregister_block_pattern( 'core/query-standard-posts' );
    unregister_block_pattern( 'core/social-links-shared-background-color' );
}
add_action( 'init', 'mytheme_unregister_patterns' );

// Xoa toan bo core patterns
remove_theme_support( 'core-block-patterns' );

// Xoa pattern category
unregister_block_pattern_category( 'buttons' );
```

### Dang ky Pattern tu file PHP rieng

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
        <h1 class="wp-block-heading has-text-align-center">Tieu de Hero</h1>
        <!-- /wp:heading -->
    </div>
</div>
<!-- /wp:cover -->
```

```
Cau truc thu muc:
mytheme/
  |-- patterns/
  |     |-- hero.php
  |     |-- card-grid.php
  |     |-- testimonial.php
  |     |-- cta-section.php

WordPress tu dong dang ky cac file trong thu muc patterns/.
Dieu kien: File phai co header comment voi Title va Slug.
```

---

## 8. Block Templates

### Block Template la gi?

Block Template dinh nghia cau truc block mac dinh khi tao bai viet moi. Khac voi Pattern (nguoi dung chu dong chen), Template tu dong ap dung.

### Dang ky Block Template cho Post Type

```php
/**
 * Dang ky block template cho Custom Post Type
 */
function mytheme_register_product_template() {
    $post_type_object = get_post_type_object( 'product' );

    if ( $post_type_object ) {
        $post_type_object->template = array(
            // Moi array la 1 block: [ 'block-name', attributes, innerBlocks ]
            array( 'core/image', array(
                'align' => 'wide',
            ) ),
            array( 'core/heading', array(
                'level'       => 2,
                'placeholder' => 'Ten san pham...',
            ) ),
            array( 'core/paragraph', array(
                'placeholder' => 'Mo ta ngan ve san pham...',
            ) ),
            array( 'core/heading', array(
                'level'   => 3,
                'content' => 'Thong so ky thuat',
            ) ),
            array( 'core/table', array(
                'className' => 'product-specs',
            ) ),
            array( 'core/heading', array(
                'level'   => 3,
                'content' => 'Mo ta chi tiet',
            ) ),
            array( 'core/paragraph', array(
                'placeholder' => 'Nhap mo ta chi tiet san pham...',
            ) ),
            // Block voi InnerBlocks
            array( 'core/columns', array(), array(
                array( 'core/column', array(), array(
                    array( 'core/heading', array(
                        'level' => 4,
                        'content' => 'Uu diem',
                    ) ),
                    array( 'core/list', array() ),
                ) ),
                array( 'core/column', array(), array(
                    array( 'core/heading', array(
                        'level' => 4,
                        'content' => 'Nhuoc diem',
                    ) ),
                    array( 'core/list', array() ),
                ) ),
            ) ),
        );

        // Khoa template
        $post_type_object->template_lock = 'all';
        // 'all'    = Khong cho them, xoa, di chuyen blocks
        // 'insert' = Khong cho them/xoa, nhung cho di chuyen
        // false    = Tu do chinh sua (mac dinh)
    }
}
add_action( 'init', 'mytheme_register_product_template' );
```

### Dang ky template khi dang ky CPT

```php
function mytheme_register_portfolio_cpt() {
    register_post_type( 'portfolio', array(
        'labels' => array( 'name' => 'Portfolio' ),
        'public'       => true,
        'show_in_rest' => true,  // Bat buoc de dung Gutenberg
        'supports'     => array( 'title', 'editor', 'thumbnail' ),
        'template'     => array(
            array( 'core/image', array(
                'align' => 'wide',
            ) ),
            array( 'core/paragraph', array(
                'placeholder' => 'Mo ta du an...',
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
 * Them block template cho trang cu the
 */
function mytheme_page_templates( $args, $post_type ) {
    if ( $post_type === 'page' ) {
        $args['template'] = array(
            array( 'core/paragraph', array(
                'placeholder' => 'Bat dau viet noi dung trang...',
            ) ),
        );
    }
    return $args;
}
add_filter( 'register_post_type_args', 'mytheme_page_templates', 10, 2 );
```

---

## 9. theme.json - Cau hinh theme cho Block Editor

### theme.json la gi?

`theme.json` la file cau hinh trung tam cho block-based themes. No cho phep kiem soat:
- Color palette, typography, spacing
- Layout settings
- Block-level customizations
- CSS custom properties tu dong tao

### Cau truc day du

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
                    "name": "Den va Trang"
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
            "title": "Blank (Khong co header/footer)",
            "postTypes": [ "page", "post" ]
        },
        {
            "name": "full-width",
            "title": "Full Width",
            "postTypes": [ "page" ]
        },
        {
            "name": "with-sidebar",
            "title": "Voi Sidebar",
            "postTypes": [ "page", "post" ]
        }
    ]
}
```

### CSS Custom Properties tu theme.json

```
theme.json tu dong tao CSS Custom Properties:

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

Su dung trong CSS:
  .my-element {
      color: var(--wp--preset--color--primary);
      font-family: var(--wp--preset--font-family--inter);
      padding: var(--wp--preset--spacing--m);
  }
```

---

## 10. Vi du block hoan chinh

### Block "Team Member Card" - Day du tinh nang

#### Cau truc thu muc

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
    "description": "Card hien thi thong tin thanh vien doi ngu.",
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
    // Icon tuy chinh bang SVG
    icon: {
        src: (
            <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z" />
            </svg>
        ),
        foreground: '#3498db',
    },
    // Vi du block trong inserter
    example: {
        attributes: {
            name: 'Nguyen Van A',
            position: 'Giam Doc Cong Nghe',
            bio: 'Co hon 10 nam kinh nghiem trong linh vuc phat trien phan mem.',
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
                <PanelBody title={ __( 'Giao dien', 'my-blocks' ) }>
                    <SelectControl
                        label={ __( 'Kieu card', 'my-blocks' ) }
                        value={ cardStyle }
                        options={ [
                            { label: 'Mac dinh', value: 'default' },
                            { label: 'Co bong', value: 'shadow' },
                            { label: 'Co vien', value: 'bordered' },
                            { label: 'Toi gian', value: 'minimal' },
                        ] }
                        onChange={ ( value ) => setAttributes( { cardStyle: value } ) }
                    />

                    <SelectControl
                        label={ __( 'Hinh dang anh', 'my-blocks' ) }
                        value={ imageShape }
                        options={ [
                            { label: 'Tron', value: 'circle' },
                            { label: 'Vuong', value: 'square' },
                            { label: 'Bo goc', value: 'rounded' },
                        ] }
                        onChange={ ( value ) => setAttributes( { imageShape: value } ) }
                    />
                </PanelBody>

                <PanelBody title={ __( 'Thong tin lien he', 'my-blocks' ) } initialOpen={ false }>
                    <ToggleControl
                        label={ __( 'Hien thong tin lien he', 'my-blocks' ) }
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
                                label={ __( 'So dien thoai', 'my-blocks' ) }
                                value={ phone }
                                onChange={ ( value ) => setAttributes( { phone: value } ) }
                                type="tel"
                            />
                        </>
                    ) }
                </PanelBody>

                <PanelBody title={ __( 'Mang xa hoi', 'my-blocks' ) } initialOpen={ false }>
                    <ToggleControl
                        label={ __( 'Hien link mang xa hoi', 'my-blocks' ) }
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
                                                    { __( 'Doi', 'my-blocks' ) }
                                                </Button>
                                            ) }
                                        />
                                        <Button onClick={ onRemoveImage } isSmall isDestructive>
                                            { __( 'Xoa', 'my-blocks' ) }
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
                                            label={ __( 'Anh dai dien', 'my-blocks' ) }
                                        >
                                            <Button onClick={ open } variant="primary" isSmall>
                                                { __( 'Chon anh', 'my-blocks' ) }
                                            </Button>
                                        </Placeholder>
                                    ) }
                                />
                            ) }
                        </MediaUploadCheck>
                    </div>

                    {/* Thong tin */}
                    <div className="team-card__info">
                        <RichText
                            tagName="h3"
                            className="team-card__name"
                            value={ name }
                            onChange={ ( value ) => setAttributes( { name: value } ) }
                            placeholder={ __( 'Ho va ten...', 'my-blocks' ) }
                            allowedFormats={ [] }
                        />

                        <RichText
                            tagName="p"
                            className="team-card__position"
                            value={ position }
                            onChange={ ( value ) => setAttributes( { position: value } ) }
                            placeholder={ __( 'Chuc vu...', 'my-blocks' ) }
                            allowedFormats={ [] }
                        />

                        <RichText
                            tagName="p"
                            className="team-card__bio"
                            value={ bio }
                            onChange={ ( value ) => setAttributes( { bio: value } ) }
                            placeholder={ __( 'Tieu su ngan...', 'my-blocks' ) }
                            allowedFormats={ [ 'core/bold', 'core/italic' ] }
                        />

                        {/* Preview contact va social */}
                        { showContact && ( email || phone ) && (
                            <div className="team-card__contact">
                                { email && <span>Email: { email }</span> }
                                { phone && <span>SDT: { phone }</span> }
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
// style.scss - Hien thi ca editor va frontend
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

    // Hinh dang anh
    &.image-circle .team-card__avatar img {
        border-radius: 50%;
    }

    &.image-square .team-card__avatar img {
        border-radius: 0;
    }

    &.image-rounded .team-card__avatar img {
        border-radius: 12px;
    }

    // Thong tin
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
// editor.scss - Chi hien trong editor
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

#### Dang ky block trong PHP

```php
<?php
// my-blocks-plugin.php

function my_blocks_register_all() {
    // Dang ky tung block tu thu muc build
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

### Tong ket quy trinh phat trien block

```
1. Tao cau truc thu muc trong src/
2. Viet block.json (metadata, attributes)
3. Viet edit.js (giao dien trong editor)
4. Viet save.js (HTML luu vao database) hoac render.php (dynamic block)
5. Viet styles (editor.scss, style.scss)
6. Build: npm run build
7. Dang ky trong PHP: register_block_type()
8. Test trong editor va frontend
9. Lap lai buoc 2-8 cho tung block moi
```
