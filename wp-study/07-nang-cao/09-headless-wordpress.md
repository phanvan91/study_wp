# Headless WordPress - Hướng Dẫn Chi Tiết

## Mục lục

1. [Tổng quan Headless WordPress](#1-tong-quan-headless-wordpress)
2. [REST API cho Headless](#2-rest-api-cho-headless)
3. [WPGraphQL - GraphQL cho WordPress](#3-wpgraphql---graphql-cho-wordpress)
4. [Next.js + WordPress](#4-nextjs--wordpress)
5. [Authentication cho Headless](#5-authentication-cho-headless)
6. [Custom Endpoints tối ưu cho Frontend](#6-custom-endpoints-toi-uu-cho-frontend)
7. [Preview Mode - Xem trước bài nháp](#7-preview-mode---xem-truoc-bai-nhap)
8. [Menus và Navigation](#8-menus-va-navigation)
9. [SEO cho Headless](#9-seo-cho-headless)
10. [Webhooks - Revalidation khi content thay đổi](#10-webhooks---revalidation-khi-content-thay-doi)
11. [Ví dụ thực tế: Blog với Next.js + WordPress](#11-vi-du-thuc-te-blog-voi-nextjs--wordpress)
12. [So sánh với Laravel API Backend](#12-so-sanh-voi-laravel-api-backend)

---

## 1. Tổng quan Headless WordPress

### Headless là gì?

```
Traditional WordPress:
  WordPress = CMS + Frontend (PHP templates)
  Browser → WordPress → PHP render HTML → Browser

Headless WordPress:
  WordPress = CMS only (backend/API)
  Frontend = React/Next.js/Vue/Nuxt riêng biệt
  Browser → Frontend App → WordPress REST API/GraphQL → Response JSON → Frontend render

Decoupled = Headless (2 cách gọi cùng 1 concept)
```

### Khi nào dùng Headless?

```
NÊN dùng:
  ✅ Cần performance cao (static site generation)
  ✅ Frontend phức tạp (SPA, animations, interactive)
  ✅ Team có React/Vue developers
  ✅ Multi-platform: web + mobile app + smart TV dùng chung API
  ✅ Microservices architecture
  ✅ JAMstack deployment (Vercel, Netlify)

KHÔNG NÊN dùng:
  ❌ Website đơn giản (blog, brochure)
  ❌ Cần Gutenberg editor preview chính xác
  ❌ Plugin ecosystem phụ thuộc frontend (contact forms, SEO, ecommerce)
  ❌ Team chỉ biết PHP/WordPress
  ❌ Budget hạn chế (cần maintain 2 codebases)
```

### Architecture Diagram

```
┌─────────────────────────────────────────────────────────┐
│                    HEADLESS ARCHITECTURE                  │
│                                                           │
│  ┌──────────────────┐          ┌──────────────────────┐  │
│  │   WordPress CMS   │  JSON   │   Frontend App        │  │
│  │                    │ ◄─────► │                        │  │
│  │  • Content editing │  API    │  • Next.js / Nuxt.js  │  │
│  │  • Media library   │         │  • Static generation   │  │
│  │  • User management │         │  • Client-side render  │  │
│  │  • Custom fields   │         │  • CDN deployment      │  │
│  │  • REST API        │         │                        │  │
│  │  • WPGraphQL       │         │  Deploy: Vercel        │  │
│  │                    │         │                        │  │
│  │  Host: any PHP     │         │  Host: Vercel/Netlify  │  │
│  └──────────────────┘          └──────────────────────┘  │
│           │                              │                │
│           └──────────┬───────────────────┘                │
│                      │                                    │
│              ┌───────▼──────┐                             │
│              │   Database    │                             │
│              │   (MySQL)     │                             │
│              └──────────────┘                             │
└─────────────────────────────────────────────────────────┘
```

---

## 2. REST API cho Headless

### 2.1. Endpoints mặc định

```bash
# Lấy posts
GET /wp-json/wp/v2/posts?per_page=10&page=1&_embed

# Lấy single post by slug
GET /wp-json/wp/v2/posts?slug=hello-world&_embed

# Lấy pages
GET /wp-json/wp/v2/pages?per_page=100&_embed

# Lấy categories
GET /wp-json/wp/v2/categories?per_page=100

# Lấy tags
GET /wp-json/wp/v2/tags?per_page=100

# Lấy media
GET /wp-json/wp/v2/media/{id}

# Tìm kiếm
GET /wp-json/wp/v2/search?search=keyword&type=post

# _embed: Include featured image, author, terms trong response
# _fields: Chỉ lấy fields cần thiết (giảm payload)
GET /wp-json/wp/v2/posts?_fields=id,title,slug,excerpt,date,_links&_embed
```

### 2.2. Tối ưu REST API cho Headless

```php
<?php
/**
 * Tối ưu REST API response cho frontend consumption.
 */

// 1. Thêm custom fields vào REST response
add_action( 'rest_api_init', function() {
    // Reading time
    register_rest_field( 'post', 'reading_time', array(
        'get_callback' => function( $post ) {
            $content    = wp_strip_all_tags( $post['content']['rendered'] );
            $word_count = str_word_count( $content );
            $minutes    = max( 1, (int) ceil( $word_count / 200 ) );
            return $minutes;
        },
        'schema' => array(
            'type'        => 'integer',
            'description' => 'Estimated reading time in minutes.',
        ),
    ) );

    // Featured image URL (flat, không cần _embed)
    register_rest_field( 'post', 'featured_image_url', array(
        'get_callback' => function( $post ) {
            $thumbnail_id = get_post_thumbnail_id( $post['id'] );
            if ( ! $thumbnail_id ) {
                return null;
            }
            return array(
                'thumbnail' => wp_get_attachment_image_url( $thumbnail_id, 'thumbnail' ),
                'medium'    => wp_get_attachment_image_url( $thumbnail_id, 'medium' ),
                'large'     => wp_get_attachment_image_url( $thumbnail_id, 'large' ),
                'full'      => wp_get_attachment_image_url( $thumbnail_id, 'full' ),
            );
        },
        'schema' => array(
            'type'        => 'object',
            'description' => 'Featured image URLs in various sizes.',
        ),
    ) );

    // Author info (flat)
    register_rest_field( 'post', 'author_info', array(
        'get_callback' => function( $post ) {
            $author = get_userdata( $post['author'] );
            if ( ! $author ) {
                return null;
            }
            return array(
                'name'   => $author->display_name,
                'avatar' => get_avatar_url( $author->ID, array( 'size' => 96 ) ),
                'bio'    => get_the_author_meta( 'description', $author->ID ),
            );
        },
    ) );

    // Category names (flat, thay vì chỉ IDs)
    register_rest_field( 'post', 'category_names', array(
        'get_callback' => function( $post ) {
            $categories = get_the_category( $post['id'] );
            return array_map( function( $cat ) {
                return array(
                    'id'   => $cat->term_id,
                    'name' => $cat->name,
                    'slug' => $cat->slug,
                );
            }, $categories );
        },
    ) );
} );

// 2. Enable CORS cho frontend domain
add_action( 'rest_api_init', function() {
    remove_filter( 'rest_pre_serve_request', 'rest_send_cors_headers' );
    add_filter( 'rest_pre_serve_request', function( $value ) {
        $origin = get_http_origin();
        $allowed = array(
            'https://frontend.example.com',
            'http://localhost:3000',  // Development
        );

        if ( in_array( $origin, $allowed, true ) ) {
            header( 'Access-Control-Allow-Origin: ' . $origin );
            header( 'Access-Control-Allow-Methods: GET, POST, OPTIONS' );
            header( 'Access-Control-Allow-Headers: Authorization, Content-Type' );
            header( 'Access-Control-Allow-Credentials: true' );
        }

        return $value;
    } );
} );

// 3. Tăng per_page limit cho headless
add_filter( 'rest_post_collection_params', function( $params ) {
    $params['per_page']['maximum'] = 100; // Default: 100, có thể tăng
    return $params;
} );
```

---

## 3. WPGraphQL - GraphQL cho WordPress

### 3.1. Cài đặt

```bash
# Plugin: WPGraphQL (miễn phí)
wp plugin install wp-graphql --activate

# GraphQL IDE: GraphiQL tại /wp-admin → GraphQL
# Endpoint: /graphql
```

### 3.2. Queries cơ bản

```graphql
# Lấy danh sách posts
query GetPosts {
  posts(first: 10, where: { status: PUBLISH }) {
    nodes {
      id
      databaseId
      title
      slug
      date
      excerpt
      content
      featuredImage {
        node {
          sourceUrl(size: MEDIUM)
          altText
        }
      }
      author {
        node {
          name
          avatar {
            url
          }
        }
      }
      categories {
        nodes {
          name
          slug
        }
      }
      tags {
        nodes {
          name
          slug
        }
      }
    }
    pageInfo {
      hasNextPage
      endCursor
    }
  }
}

# Lấy single post by slug
query GetPostBySlug($slug: ID!) {
  post(id: $slug, idType: SLUG) {
    title
    content
    date
    modified
    featuredImage {
      node {
        sourceUrl(size: LARGE)
        altText
        mediaDetails {
          width
          height
        }
      }
    }
    author {
      node {
        name
        description
        avatar { url }
      }
    }
    categories {
      nodes { name slug }
    }
    seo {
      title
      metaDesc
      opengraphImage { sourceUrl }
    }
  }
}

# Lấy pages
query GetPages {
  pages(first: 100, where: { status: PUBLISH }) {
    nodes {
      title
      slug
      content
      featuredImage {
        node { sourceUrl }
      }
    }
  }
}

# Lấy categories với posts
query GetCategoryPosts($slug: ID!) {
  category(id: $slug, idType: SLUG) {
    name
    description
    posts(first: 10) {
      nodes {
        title
        slug
        excerpt
        date
      }
    }
  }
}

# Search
query Search($term: String!) {
  posts(where: { search: $term }) {
    nodes {
      title
      slug
      excerpt
    }
  }
}
```

### 3.3. Mutations

```graphql
# Tạo comment
mutation CreateComment($input: CreateCommentInput!) {
  createComment(input: $input) {
    success
    comment {
      id
      content
      date
      author {
        node { name }
      }
    }
  }
}

# Variables:
# {
#   "input": {
#     "commentOn": 123,
#     "content": "Great post!",
#     "author": "John",
#     "authorEmail": "john@example.com"
#   }
# }

# Submit form (cần custom mutation)
mutation SubmitContactForm($input: SubmitContactFormInput!) {
  submitContactForm(input: $input) {
    success
    message
  }
}
```

### 3.4. Custom Types trong GraphQL

```php
<?php
/**
 * Register Custom Post Type cho WPGraphQL.
 */
add_action( 'init', function() {
    register_post_type( 'project', array(
        'labels'       => array( 'name' => 'Projects' ),
        'public'       => true,
        'show_in_rest' => true,           // REST API
        'show_in_graphql' => true,        // WPGraphQL
        'graphql_single_name' => 'project',
        'graphql_plural_name' => 'projects',
        'supports'     => array( 'title', 'editor', 'thumbnail', 'custom-fields' ),
    ) );

    register_taxonomy( 'project_type', 'project', array(
        'labels'       => array( 'name' => 'Project Types' ),
        'public'       => true,
        'show_in_rest' => true,
        'show_in_graphql' => true,
        'graphql_single_name' => 'projectType',
        'graphql_plural_name' => 'projectTypes',
    ) );
} );

/**
 * Register custom GraphQL field.
 */
add_action( 'graphql_register_types', function() {
    register_graphql_field( 'Post', 'readingTime', array(
        'type'        => 'Int',
        'description' => 'Estimated reading time in minutes',
        'resolve'     => function( \WPGraphQL\Model\Post $post ) {
            $content    = wp_strip_all_tags( $post->contentRaw ?? '' );
            $word_count = str_word_count( $content );
            return max( 1, (int) ceil( $word_count / 200 ) );
        },
    ) );
} );
```

---

## 4. Next.js + WordPress

### 4.1. Cấu trúc project

```
my-nextjs-blog/
├── src/
│   ├── app/                    # App Router (Next.js 14+)
│   │   ├── layout.tsx
│   │   ├── page.tsx            # Home page
│   │   ├── blog/
│   │   │   ├── page.tsx        # Blog listing
│   │   │   └── [slug]/
│   │   │       └── page.tsx    # Single post
│   │   └── [slug]/
│   │       └── page.tsx        # Static pages
│   └── lib/
│       ├── wordpress.ts        # WordPress API client
│       └── types.ts            # TypeScript types
├── next.config.js
├── package.json
└── .env.local
```

### 4.2. WordPress API Client

```typescript
// src/lib/wordpress.ts

const API_URL = process.env.WORDPRESS_API_URL || 'https://cms.example.com';

// ── REST API CLIENT ─────────────────────────────────────────────

export interface WPPost {
  id: number;
  slug: string;
  title: { rendered: string };
  content: { rendered: string };
  excerpt: { rendered: string };
  date: string;
  modified: string;
  featured_image_url?: {
    thumbnail: string;
    medium: string;
    large: string;
    full: string;
  };
  author_info?: {
    name: string;
    avatar: string;
    bio: string;
  };
  category_names?: Array<{
    id: number;
    name: string;
    slug: string;
  }>;
  reading_time?: number;
}

export interface WPPage {
  id: number;
  slug: string;
  title: { rendered: string };
  content: { rendered: string };
}

/**
 * Fetch từ WordPress REST API.
 */
async function fetchAPI<T>(
  endpoint: string,
  params: Record<string, string | number> = {}
): Promise<T> {
  const url = new URL(`${API_URL}/wp-json/wp/v2${endpoint}`);

  Object.entries(params).forEach(([key, value]) => {
    url.searchParams.set(key, String(value));
  });

  const res = await fetch(url.toString(), {
    headers: {
      'Content-Type': 'application/json',
    },
    next: { revalidate: 60 }, // ISR: revalidate mỗi 60 giây
  });

  if (!res.ok) {
    throw new Error(`WordPress API error: ${res.status}`);
  }

  return res.json();
}

// ── PUBLIC API FUNCTIONS ────────────────────────────────────────

export async function getPosts(page = 1, perPage = 10): Promise<WPPost[]> {
  return fetchAPI<WPPost[]>('/posts', {
    page,
    per_page: perPage,
    _fields: 'id,slug,title,excerpt,date,featured_image_url,author_info,category_names,reading_time',
  });
}

export async function getPostBySlug(slug: string): Promise<WPPost | null> {
  const posts = await fetchAPI<WPPost[]>('/posts', {
    slug,
    _embed: 1,
  });
  return posts[0] || null;
}

export async function getAllPostSlugs(): Promise<string[]> {
  const posts = await fetchAPI<Array<{ slug: string }>>('/posts', {
    per_page: 100,
    _fields: 'slug',
  });
  return posts.map((p) => p.slug);
}

export async function getPageBySlug(slug: string): Promise<WPPage | null> {
  const pages = await fetchAPI<WPPage[]>('/pages', { slug });
  return pages[0] || null;
}

export async function getCategories() {
  return fetchAPI('/categories', { per_page: 100 });
}

export async function searchPosts(query: string): Promise<WPPost[]> {
  return fetchAPI<WPPost[]>('/posts', {
    search: query,
    per_page: 20,
    _fields: 'id,slug,title,excerpt,date',
  });
}
```

### 4.3. Next.js Pages (App Router)

```typescript
// src/app/page.tsx - Home page

import { getPosts } from '@/lib/wordpress';
import Link from 'next/link';

export const revalidate = 60; // ISR: revalidate mỗi 60 giây

export default async function HomePage() {
  const posts = await getPosts(1, 10);

  return (
    <main>
      <h1>Blog</h1>
      <div className="posts-grid">
        {posts.map((post) => (
          <article key={post.id} className="post-card">
            {post.featured_image_url && (
              <img
                src={post.featured_image_url.medium}
                alt={post.title.rendered}
                loading="lazy"
              />
            )}
            <h2>
              <Link href={`/blog/${post.slug}`}>
                <span dangerouslySetInnerHTML={{ __html: post.title.rendered }} />
              </Link>
            </h2>
            <div className="meta">
              <span>{post.author_info?.name}</span>
              <span>{new Date(post.date).toLocaleDateString('vi-VN')}</span>
              {post.reading_time && <span>{post.reading_time} phút đọc</span>}
            </div>
            <div dangerouslySetInnerHTML={{ __html: post.excerpt.rendered }} />
          </article>
        ))}
      </div>
    </main>
  );
}
```

```typescript
// src/app/blog/[slug]/page.tsx - Single post

import { getPostBySlug, getAllPostSlugs } from '@/lib/wordpress';
import { notFound } from 'next/navigation';
import type { Metadata } from 'next';

// Static Generation: build tất cả posts tại build time
export async function generateStaticParams() {
  const slugs = await getAllPostSlugs();
  return slugs.map((slug) => ({ slug }));
}

// Dynamic metadata cho SEO
export async function generateMetadata({
  params,
}: {
  params: { slug: string };
}): Promise<Metadata> {
  const post = await getPostBySlug(params.slug);
  if (!post) return { title: 'Not Found' };

  return {
    title: post.title.rendered,
    description: post.excerpt.rendered.replace(/<[^>]*>/g, '').slice(0, 160),
    openGraph: {
      title: post.title.rendered,
      type: 'article',
      publishedTime: post.date,
      modifiedTime: post.modified,
    },
  };
}

export default async function PostPage({
  params,
}: {
  params: { slug: string };
}) {
  const post = await getPostBySlug(params.slug);

  if (!post) {
    notFound();
  }

  return (
    <article className="single-post">
      <header>
        <h1 dangerouslySetInnerHTML={{ __html: post.title.rendered }} />
        <div className="meta">
          <time dateTime={post.date}>
            {new Date(post.date).toLocaleDateString('vi-VN', {
              year: 'numeric',
              month: 'long',
              day: 'numeric',
            })}
          </time>
        </div>
      </header>

      {post.featured_image_url && (
        <img
          src={post.featured_image_url.large}
          alt={post.title.rendered}
          className="featured-image"
        />
      )}

      <div
        className="post-content"
        dangerouslySetInnerHTML={{ __html: post.content.rendered }}
      />
    </article>
  );
}
```

### 4.4. next.config.js

```javascript
// next.config.js

/** @type {import('next').NextConfig} */
const nextConfig = {
  images: {
    remotePatterns: [
      {
        protocol: 'https',
        hostname: 'cms.example.com', // WordPress domain
        pathname: '/wp-content/uploads/**',
      },
    ],
  },
  async rewrites() {
    return [
      // Proxy preview requests to WordPress
      {
        source: '/api/preview',
        destination: `${process.env.WORDPRESS_API_URL}/wp-json/my-plugin/v1/preview`,
      },
    ];
  },
};

module.exports = nextConfig;
```

---

## 5. Authentication cho Headless

### 5.1. Application Passwords (WordPress 5.6+)

```php
<?php
/**
 * Application Passwords: Built-in WordPress, không cần plugin.
 * Users → Profile → Application Passwords → Generate
 *
 * Dùng cho: server-to-server API calls, CI/CD, mobile apps
 */

// Hạn chế Application Passwords cho roles cụ thể
add_filter( 'wp_is_application_passwords_available_for_user', function( $available, $user ) {
    return in_array( 'administrator', $user->roles, true ) || in_array( 'editor', $user->roles, true );
}, 10, 2 );
```

```typescript
// Frontend: Gọi API với Application Password
const response = await fetch(`${API_URL}/wp-json/wp/v2/posts`, {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    // Base64 encode "username:application_password"
    'Authorization': 'Basic ' + btoa('admin:xxxx xxxx xxxx xxxx xxxx xxxx'),
  },
  body: JSON.stringify({
    title: 'New Post from Frontend',
    content: 'Content here',
    status: 'draft',
  }),
});
```

### 5.2. JWT Authentication

```php
<?php
/**
 * JWT Authentication cho headless WordPress.
 * Plugin: JWT Authentication for WP REST API
 * hoặc: Simple JWT Login
 */

// wp-config.php
define( 'JWT_AUTH_SECRET_KEY', 'your-strong-secret-key-here' );
define( 'JWT_AUTH_CORS_ENABLE', true );

// .htaccess: Cần enable Authorization header
// RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
```

```typescript
// Frontend: Login → lấy JWT token
async function login(username: string, password: string): Promise<string> {
  const res = await fetch(`${API_URL}/wp-json/jwt-auth/v1/token`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ username, password }),
  });

  const data = await res.json();

  if (!res.ok) {
    throw new Error(data.message || 'Login failed');
  }

  return data.token; // JWT token
}

// Sử dụng token cho authenticated requests
async function createPost(token: string, title: string, content: string) {
  const res = await fetch(`${API_URL}/wp-json/wp/v2/posts`, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${token}`,
    },
    body: JSON.stringify({ title, content, status: 'draft' }),
  });

  return res.json();
}

// Validate token
async function validateToken(token: string): Promise<boolean> {
  const res = await fetch(`${API_URL}/wp-json/jwt-auth/v1/token/validate`, {
    method: 'POST',
    headers: { 'Authorization': `Bearer ${token}` },
  });
  return res.ok;
}
```

---

## 6. Custom Endpoints tối ưu cho Frontend

```php
<?php
/**
 * Custom REST endpoints trả về đúng data frontend cần.
 * Giảm số requests và payload size.
 */

add_action( 'rest_api_init', function() {

    // Endpoint: Trang chủ (tất cả data 1 request)
    register_rest_route( 'headless/v1', '/home', array(
        'methods'             => 'GET',
        'callback'            => 'headless_get_home_data',
        'permission_callback' => '__return_true',
    ) );

    // Endpoint: Archive page
    register_rest_route( 'headless/v1', '/archive', array(
        'methods'             => 'GET',
        'callback'            => 'headless_get_archive',
        'permission_callback' => '__return_true',
        'args'                => array(
            'page'     => array( 'default' => 1, 'sanitize_callback' => 'absint' ),
            'per_page' => array( 'default' => 12, 'sanitize_callback' => 'absint' ),
            'category' => array( 'default' => '', 'sanitize_callback' => 'sanitize_text_field' ),
        ),
    ) );

    // Endpoint: Site config (menus, settings, widgets)
    register_rest_route( 'headless/v1', '/config', array(
        'methods'             => 'GET',
        'callback'            => 'headless_get_site_config',
        'permission_callback' => '__return_true',
    ) );
} );

function headless_get_home_data(): array {
    // Hero section
    $hero_post = get_posts( array(
        'numberposts' => 1,
        'post_status' => 'publish',
        'meta_key'    => '_is_featured',
        'meta_value'  => '1',
    ) );

    // Recent posts
    $recent = get_posts( array(
        'numberposts' => 6,
        'post_status' => 'publish',
    ) );

    // Categories
    $categories = get_categories( array( 'hide_empty' => true ) );

    return array(
        'hero'       => $hero_post ? format_post_for_api( $hero_post[0] ) : null,
        'recent'     => array_map( 'format_post_for_api', $recent ),
        'categories' => array_map( function( $cat ) {
            return array(
                'id'    => $cat->term_id,
                'name'  => $cat->name,
                'slug'  => $cat->slug,
                'count' => $cat->count,
            );
        }, $categories ),
        'site'       => array(
            'name'        => get_bloginfo( 'name' ),
            'description' => get_bloginfo( 'description' ),
        ),
    );
}

function headless_get_archive( WP_REST_Request $request ): WP_REST_Response {
    $page     = $request->get_param( 'page' );
    $per_page = min( $request->get_param( 'per_page' ), 50 );
    $category = $request->get_param( 'category' );

    $args = array(
        'post_type'      => 'post',
        'post_status'    => 'publish',
        'posts_per_page' => $per_page,
        'paged'          => $page,
    );

    if ( $category ) {
        $args['category_name'] = $category;
    }

    $query = new WP_Query( $args );

    $posts = array_map( 'format_post_for_api', $query->posts );

    $response = new WP_REST_Response( array(
        'posts'       => $posts,
        'total'       => $query->found_posts,
        'total_pages' => $query->max_num_pages,
        'page'        => $page,
    ) );

    // Cache header
    $response->header( 'Cache-Control', 'public, max-age=60, s-maxage=300' );

    return $response;
}

function headless_get_site_config(): array {
    // Menus
    $menus = array();
    $locations = get_nav_menu_locations();
    foreach ( $locations as $location => $menu_id ) {
        $items = wp_get_nav_menu_items( $menu_id );
        if ( $items ) {
            $menus[ $location ] = array_map( function( $item ) {
                return array(
                    'id'     => $item->ID,
                    'title'  => $item->title,
                    'url'    => $item->url,
                    'parent' => (int) $item->menu_item_parent,
                    'target' => $item->target,
                    'classes' => implode( ' ', $item->classes ),
                );
            }, $items );
        }
    }

    return array(
        'site_name'        => get_bloginfo( 'name' ),
        'site_description' => get_bloginfo( 'description' ),
        'site_url'         => get_site_url(),
        'menus'            => $menus,
        'social'           => array(
            'facebook'  => get_option( 'my_social_facebook', '' ),
            'twitter'   => get_option( 'my_social_twitter', '' ),
            'instagram' => get_option( 'my_social_instagram', '' ),
        ),
    );
}

/**
 * Format post cho API response.
 */
function format_post_for_api( WP_Post $post ): array {
    $thumbnail_id = get_post_thumbnail_id( $post->ID );

    return array(
        'id'        => $post->ID,
        'slug'      => $post->post_name,
        'title'     => get_the_title( $post ),
        'excerpt'   => get_the_excerpt( $post ),
        'content'   => apply_filters( 'the_content', $post->post_content ),
        'date'      => get_the_date( 'c', $post ),
        'modified'  => get_the_modified_date( 'c', $post ),
        'image'     => $thumbnail_id ? array(
            'medium' => wp_get_attachment_image_url( $thumbnail_id, 'medium' ),
            'large'  => wp_get_attachment_image_url( $thumbnail_id, 'large' ),
            'alt'    => get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true ),
        ) : null,
        'author'    => array(
            'name'   => get_the_author_meta( 'display_name', $post->post_author ),
            'avatar' => get_avatar_url( $post->post_author, array( 'size' => 96 ) ),
        ),
        'categories' => wp_get_post_categories( $post->ID, array( 'fields' => 'names' ) ),
        'tags'       => wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) ),
    );
}
```

---

## 7. Preview Mode - Xem trước bài nháp

```php
<?php
/**
 * WordPress endpoint cho Next.js preview mode.
 */

add_action( 'rest_api_init', function() {
    register_rest_route( 'headless/v1', '/preview', array(
        'methods'  => 'GET',
        'callback' => function( WP_REST_Request $request ) {
            $post_id = $request->get_param( 'id' );
            $token   = $request->get_param( 'token' );

            // Verify preview token
            if ( $token !== get_option( 'headless_preview_secret' ) ) {
                return new WP_Error( 'invalid_token', 'Invalid preview token.', array( 'status' => 403 ) );
            }

            $post = get_post( $post_id );
            if ( ! $post ) {
                return new WP_Error( 'not_found', 'Post not found.', array( 'status' => 404 ) );
            }

            return format_post_for_api( $post );
        },
        'permission_callback' => '__return_true',
        'args' => array(
            'id'    => array( 'required' => true, 'sanitize_callback' => 'absint' ),
            'token' => array( 'required' => true, 'sanitize_callback' => 'sanitize_text_field' ),
        ),
    ) );
} );

/**
 * Thêm Preview button redirect đến frontend.
 */
add_filter( 'preview_post_link', function( $link, $post ) {
    $frontend_url = 'https://frontend.example.com';
    $secret       = get_option( 'headless_preview_secret' );

    return add_query_arg( array(
        'preview' => 'true',
        'id'      => $post->ID,
        'token'   => $secret,
    ), $frontend_url . '/api/preview' );
}, 10, 2 );
```

```typescript
// Next.js: src/app/api/preview/route.ts (App Router)

import { NextRequest, NextResponse } from 'next/server';
import { draftMode } from 'next/headers';

export async function GET(request: NextRequest) {
  const { searchParams } = new URL(request.url);
  const id    = searchParams.get('id');
  const token = searchParams.get('token');

  // Verify token
  if (token !== process.env.PREVIEW_SECRET) {
    return NextResponse.json({ error: 'Invalid token' }, { status: 403 });
  }

  // Fetch post from WordPress
  const res = await fetch(
    `${process.env.WORDPRESS_API_URL}/wp-json/headless/v1/preview?id=${id}&token=${token}`
  );

  if (!res.ok) {
    return NextResponse.json({ error: 'Post not found' }, { status: 404 });
  }

  const post = await res.json();

  // Enable draft mode
  draftMode().enable();

  // Redirect to post page
  return NextResponse.redirect(new URL(`/blog/${post.slug}`, request.url));
}
```

---

## 8. Menus và Navigation

```php
<?php
/**
 * Expose WordPress menus qua REST API.
 */

add_action( 'rest_api_init', function() {
    register_rest_route( 'headless/v1', '/menus/(?P<location>[a-zA-Z0-9_-]+)', array(
        'methods'  => 'GET',
        'callback' => function( WP_REST_Request $request ) {
            $location  = $request->get_param( 'location' );
            $locations = get_nav_menu_locations();

            if ( ! isset( $locations[ $location ] ) ) {
                return new WP_Error( 'menu_not_found', 'Menu location not found.', array( 'status' => 404 ) );
            }

            $menu_items = wp_get_nav_menu_items( $locations[ $location ] );
            if ( ! $menu_items ) {
                return array();
            }

            // Build tree structure
            return build_menu_tree( $menu_items );
        },
        'permission_callback' => '__return_true',
    ) );
} );

/**
 * Build hierarchical menu tree.
 */
function build_menu_tree( array $items, int $parent_id = 0 ): array {
    $tree = array();

    foreach ( $items as $item ) {
        if ( (int) $item->menu_item_parent === $parent_id ) {
            $children = build_menu_tree( $items, $item->ID );

            $node = array(
                'id'       => $item->ID,
                'title'    => $item->title,
                'url'      => $item->url,
                'target'   => $item->target ?: '_self',
                'classes'  => array_filter( $item->classes ),
            );

            if ( ! empty( $children ) ) {
                $node['children'] = $children;
            }

            $tree[] = $node;
        }
    }

    return $tree;
}
```

---

## 9. SEO cho Headless

```php
<?php
/**
 * Expose Yoast SEO data qua REST API.
 * Yoast SEO tự thêm fields vào REST API nếu đã cài.
 *
 * Nếu không dùng Yoast, tự tạo SEO fields:
 */

add_action( 'rest_api_init', function() {
    register_rest_field( array( 'post', 'page' ), 'seo', array(
        'get_callback' => function( $post ) {
            $post_id = $post['id'];

            // Nếu dùng Yoast SEO
            if ( function_exists( 'YoastSEO' ) ) {
                return array(
                    'title'       => get_post_meta( $post_id, '_yoast_wpseo_title', true ) ?: get_the_title( $post_id ),
                    'description' => get_post_meta( $post_id, '_yoast_wpseo_metadesc', true ),
                    'canonical'   => get_permalink( $post_id ),
                    'og_image'    => get_post_meta( $post_id, '_yoast_wpseo_opengraph-image', true ),
                    'robots'      => array(
                        'index'  => get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true ) !== '1',
                        'follow' => get_post_meta( $post_id, '_yoast_wpseo_meta-robots-nofollow', true ) !== '1',
                    ),
                );
            }

            // Fallback: tự tạo SEO data
            $thumbnail_id = get_post_thumbnail_id( $post_id );
            return array(
                'title'       => get_the_title( $post_id ),
                'description' => wp_trim_words( wp_strip_all_tags( get_the_excerpt( $post_id ) ), 30 ),
                'canonical'   => get_permalink( $post_id ),
                'og_image'    => $thumbnail_id ? wp_get_attachment_image_url( $thumbnail_id, 'large' ) : null,
            );
        },
    ) );
} );
```

---

## 10. Webhooks - Revalidation khi content thay đổi

```php
<?php
/**
 * Khi content thay đổi trong WordPress → trigger frontend rebuild/revalidation.
 * Next.js ISR: On-Demand Revalidation
 */

class Headless_Webhooks {

    private const FRONTEND_URL      = 'https://frontend.example.com';
    private const REVALIDATE_SECRET = 'your-revalidation-secret';

    public static function register(): void {
        // Post published/updated
        add_action( 'transition_post_status', array( self::class, 'on_post_change' ), 10, 3 );

        // Post deleted
        add_action( 'before_delete_post', array( self::class, 'on_post_delete' ) );

        // Menu updated
        add_action( 'wp_update_nav_menu', array( self::class, 'on_menu_change' ) );

        // Category/tag updated
        add_action( 'edited_term', array( self::class, 'on_term_change' ), 10, 3 );
    }

    public static function on_post_change( string $new_status, string $old_status, WP_Post $post ): void {
        // Chỉ revalidate cho publish/unpublish
        if ( ! in_array( 'publish', array( $new_status, $old_status ), true ) ) {
            return;
        }

        // Chỉ cho post types cần thiết
        if ( ! in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
            return;
        }

        // Revalidate pages liên quan
        $paths = array(
            '/',                                    // Home
            '/blog',                                // Blog listing
            '/blog/' . $post->post_name,            // Single post
        );

        // Thêm category pages
        $categories = wp_get_post_categories( $post->ID, array( 'fields' => 'slugs' ) );
        foreach ( $categories as $slug ) {
            $paths[] = '/category/' . $slug;
        }

        self::revalidate_paths( $paths );
    }

    public static function on_post_delete( int $post_id ): void {
        $post = get_post( $post_id );
        if ( $post && in_array( $post->post_type, array( 'post', 'page' ), true ) ) {
            self::revalidate_paths( array( '/', '/blog', '/blog/' . $post->post_name ) );
        }
    }

    public static function on_menu_change(): void {
        // Menu thay đổi → revalidate tất cả pages (vì menu hiện trên mọi trang)
        self::revalidate_paths( array( '/' ) );
    }

    public static function on_term_change( int $term_id, int $tt_id, string $taxonomy ): void {
        if ( in_array( $taxonomy, array( 'category', 'post_tag' ), true ) ) {
            $term = get_term( $term_id );
            self::revalidate_paths( array( '/category/' . $term->slug ) );
        }
    }

    /**
     * Gọi Next.js revalidation API.
     */
    private static function revalidate_paths( array $paths ): void {
        $url = self::FRONTEND_URL . '/api/revalidate';

        wp_remote_post( $url, array(
            'body'    => wp_json_encode( array(
                'paths'  => $paths,
                'secret' => self::REVALIDATE_SECRET,
            ) ),
            'headers' => array( 'Content-Type' => 'application/json' ),
            'timeout' => 10,
            'blocking' => false, // Non-blocking, không chờ response
        ) );
    }
}

Headless_Webhooks::register();
```

```typescript
// Next.js: src/app/api/revalidate/route.ts

import { NextRequest, NextResponse } from 'next/server';
import { revalidatePath } from 'next/cache';

export async function POST(request: NextRequest) {
  const body = await request.json();

  // Verify secret
  if (body.secret !== process.env.REVALIDATION_SECRET) {
    return NextResponse.json({ error: 'Invalid secret' }, { status: 403 });
  }

  // Revalidate each path
  const paths: string[] = body.paths || [];
  for (const path of paths) {
    revalidatePath(path);
  }

  return NextResponse.json({
    revalidated: true,
    paths,
    timestamp: Date.now(),
  });
}
```

---

## 11. Ví dụ thực tế: Blog với Next.js + WordPress

### .env.local

```env
WORDPRESS_API_URL=https://cms.example.com
PREVIEW_SECRET=my-preview-secret-123
REVALIDATION_SECRET=my-revalidation-secret-456
```

### Sitemap.xml generation

```typescript
// src/app/sitemap.ts

import { MetadataRoute } from 'next';
import { getAllPostSlugs } from '@/lib/wordpress';

export default async function sitemap(): Promise<MetadataRoute.Sitemap> {
  const slugs = await getAllPostSlugs();

  const posts = slugs.map((slug) => ({
    url: `https://frontend.example.com/blog/${slug}`,
    lastModified: new Date(),
    changeFrequency: 'weekly' as const,
    priority: 0.7,
  }));

  return [
    {
      url: 'https://frontend.example.com',
      lastModified: new Date(),
      changeFrequency: 'daily',
      priority: 1.0,
    },
    {
      url: 'https://frontend.example.com/blog',
      lastModified: new Date(),
      changeFrequency: 'daily',
      priority: 0.8,
    },
    ...posts,
  ];
}
```

---

## 12. So sánh với Laravel API Backend

| Tính năng | WordPress Headless | Laravel API Backend |
|-----------|-------------------|-------------------|
| **CMS** | Built-in (full featured) | Cần build (Filament, Nova) |
| **REST API** | Built-in + custom | Build từ đầu (API Resources) |
| **GraphQL** | WPGraphQL plugin | Lighthouse PHP |
| **Auth** | Application Passwords, JWT | Sanctum, Passport |
| **Media** | Built-in media library | Spatie Media Library |
| **SEO data** | Yoast SEO plugin | Tự implement |
| **Content editing** | Gutenberg editor | Custom admin |
| **Plugins** | 60,000+ plugins | Composer packages |
| **Setup time** | Nhanh (plugin install) | Chậm (build from scratch) |
| **Flexibility** | Hạn chế bởi WP structure | Hoàn toàn tự do |
| **Performance** | Cần optimization | Tốt mặc định |
| **Scaling** | Khó (PHP + MySQL) | Dễ hơn (queues, cache) |
| **Learning curve** | Thấp (nếu biết WP) | Trung bình |

---

## Tổng kết

| Chủ đề | API/Tools |
|--------|----------|
| REST API | `/wp-json/wp/v2/`, `register_rest_route()`, `register_rest_field()` |
| GraphQL | WPGraphQL plugin, `register_graphql_field()` |
| Frontend | Next.js App Router, `generateStaticParams()`, ISR |
| Auth | Application Passwords, JWT (`jwt-auth/v1/token`) |
| Preview | `draftMode()`, `preview_post_link` filter |
| Menus | `wp_get_nav_menu_items()`, custom endpoint |
| SEO | Yoast REST fields, `register_rest_field('seo')` |
| Revalidation | `revalidatePath()`, webhook on `transition_post_status` |
| CORS | `rest_pre_serve_request` filter |

---

[← Quay lại: i18n & l10n](./08-i18n-l10n.md) | [Tiếp: Rewrite, Heartbeat & Cache →](./10-rewrite-heartbeat-cache.md)
