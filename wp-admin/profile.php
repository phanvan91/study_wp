<?php
/**
 * Màn hình quản trị Hồ sơ Người dùng.
 *
 * @package WordPress
 * @subpackage Administration
 */

/**
 * Đây là trang hồ sơ.
 *
 * @since 2.5.0
 * @var bool
 */
define( 'IS_PROFILE_PAGE', true );

/** Tải Trang Chỉnh sửa Người dùng */
require_once __DIR__ . '/user-edit.php';
