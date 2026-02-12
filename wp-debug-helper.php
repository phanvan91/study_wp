<?php
/**
 * WordPress Debug Helper
 * 
 * Helper functions for debugging WordPress applications
 * Similar to Laravel's dd() function
 *
 * Helper functions để debug ứng dụng WordPress
 * Tương tự hàm dd() của Laravel
 */

// Enable error display immediately - Must be at the very top
// Bật hiển thị lỗi ngay lập tức - Phải ở đầu file
error_reporting( E_ALL );
ini_set( 'display_errors', '1' );
ini_set( 'display_startup_errors', '1' );

// Set error handler immediately to catch all errors
// Thiết lập error handler ngay lập tức để bắt tất cả lỗi
$error_types = array(
	E_ERROR             => 'Fatal Error',
	E_WARNING           => 'Warning',
	E_PARSE             => 'Parse Error',
	E_NOTICE            => 'Notice',
	E_CORE_ERROR        => 'Core Error',
	E_CORE_WARNING      => 'Core Warning',
	E_COMPILE_ERROR     => 'Compile Error',
	E_COMPILE_WARNING   => 'Compile Warning',
	E_USER_ERROR        => 'User Error',
	E_USER_WARNING      => 'User Warning',
	E_USER_NOTICE       => 'User Notice',
	E_STRICT            => 'Strict Notice',
	E_RECOVERABLE_ERROR => 'Recoverable Error',
	E_DEPRECATED        => 'Deprecated',
	E_USER_DEPRECATED   => 'User Deprecated',
);

set_error_handler( function( $errno, $errstr, $errfile, $errline ) use ( $error_types ) {
	if ( ! ( error_reporting() & $errno ) ) {
		return false;
	}
	
	$error_type = isset( $error_types[ $errno ] ) ? $error_types[ $errno ] : 'Unknown Error';
	$errfile = basename( $errfile );
	
	echo '<div style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: #fff; padding: 20px; margin: 15px 0; border-radius: 8px; border-left: 5px solid #a71e2a; box-shadow: 0 4px 6px rgba(0,0,0,0.1); font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;">';
	echo '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
	echo '<span style="font-size: 20px; margin-right: 10px;">⚠️</span>';
	echo '<strong style="font-size: 16px; font-weight: 600;">' . htmlspecialchars( $error_type ) . '</strong>';
	echo '</div>';
	echo '<div style="background: rgba(0,0,0,0.2); padding: 12px; border-radius: 5px; margin: 10px 0; font-size: 14px; line-height: 1.6;">' . htmlspecialchars( $errstr ) . '</div>';
	echo '<div style="display: flex; gap: 15px; font-size: 12px; opacity: 0.9; margin-top: 10px;">';
	echo '<span style="background: rgba(0,0,0,0.2); padding: 4px 8px; border-radius: 4px;">📄 ' . htmlspecialchars( $errfile ) . '</span>';
	echo '<span style="background: rgba(0,0,0,0.2); padding: 4px 8px; border-radius: 4px;">📍 Line ' . htmlspecialchars( $errline ) . '</span>';
	echo '</div>';
	echo '</div>';
	
	return true;
} );

set_exception_handler( function( $throwable ) {
	// Handle both Exception and Error (PHP 7+)
	// Xử lý cả Exception và Error (PHP 7+)
	$is_error = $throwable instanceof Error;
	$error_type = $is_error ? 'Uncaught Error' : 'Uncaught Exception';
	
	echo '<div style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: #fff; padding: 20px; margin: 15px 0; border-radius: 8px; border-left: 5px solid #a71e2a; box-shadow: 0 4px 6px rgba(0,0,0,0.1); font-family: -apple-system, BlinkMacSystemFont, \'Segoe UI\', Roboto, sans-serif;">';
	echo '<div style="display: flex; align-items: center; margin-bottom: 10px;">';
	echo '<span style="font-size: 20px; margin-right: 10px;">💥</span>';
	echo '<strong style="font-size: 16px; font-weight: 600;">' . htmlspecialchars( $error_type, ENT_QUOTES, 'UTF-8' ) . '</strong>';
	echo '</div>';
	echo '<div style="background: rgba(0,0,0,0.2); padding: 12px; border-radius: 5px; margin: 10px 0; font-size: 14px; line-height: 1.6;">' . htmlspecialchars( $throwable->getMessage(), ENT_QUOTES, 'UTF-8' ) . '</div>';
	echo '<div style="display: flex; gap: 15px; font-size: 12px; opacity: 0.9; margin-bottom: 15px;">';
	echo '<span style="background: rgba(0,0,0,0.2); padding: 4px 8px; border-radius: 4px;">📄 ' . htmlspecialchars( basename( $throwable->getFile() ), ENT_QUOTES, 'UTF-8' ) . '</span>';
	echo '<span style="background: rgba(0,0,0,0.2); padding: 4px 8px; border-radius: 4px;">📍 Line ' . htmlspecialchars( $throwable->getLine(), ENT_QUOTES, 'UTF-8' ) . '</span>';
	echo '</div>';
	echo '<details style="margin-top: 15px;">';
	echo '<summary style="cursor: pointer; padding: 10px; background: rgba(0,0,0,0.2); border-radius: 5px; font-size: 13px; font-weight: 500;">📚 Stack Trace (click to expand)</summary>';
	echo '<pre style="background: rgba(0,0,0,0.3); padding: 15px; margin-top: 10px; border-radius: 5px; overflow-x: auto; font-size: 12px; line-height: 1.5; white-space: pre-wrap; word-wrap: break-word;">';
	echo htmlspecialchars( $throwable->getTraceAsString(), ENT_QUOTES, 'UTF-8' );
	echo '</pre>';
	echo '</details>';
	echo '</div>';
} );

/**
 * Enable error display for development
 * Bật hiển thị lỗi cho development
 */
if ( ! function_exists( 'enable_debug' ) ) {
	function enable_debug() {
		error_reporting( E_ALL );
		ini_set( 'display_errors', '1' );
		ini_set( 'display_startup_errors', '1' );
	}
}

/**
 * Dump and Die - Debug helper function similar to Laravel's dd()
 *
 * Dump và Die - Function helper debug tương tự dd() của Laravel
 *
 * @param mixed ...$args Variables to dump
 */
if ( ! function_exists( 'dd' ) ) {
	function dd( ...$args ) {
		// Enable error display when dd() is called
		// Bật hiển thị lỗi khi dd() được gọi
		enable_debug();
		
		// Check if we're in a web context (not CLI)
		// Kiểm tra xem có đang trong web context (không phải CLI)
		$is_cli = ( defined( 'WP_CLI' ) && constant( 'WP_CLI' ) ) || php_sapi_name() === 'cli';
		$is_web = ! $is_cli;
		
		if ( $is_web ) {
			echo '<div style="background: linear-gradient(135deg, #1e1e1e 0%, #2d2d2d 100%); color: #d4d4d4; padding: 25px; margin: 20px; border-radius: 12px; box-shadow: 0 8px 16px rgba(0,0,0,0.3); font-family: \'SF Mono\', \'Monaco\', \'Inconsolata\', \'Fira Code\', \'Consolas\', monospace; font-size: 14px; line-height: 1.6; border: 1px solid #3a3a3a;">';
			echo '<div style="display: flex; align-items: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #3a3a3a;">';
			echo '<span style="font-size: 24px; margin-right: 12px;">🐛</span>';
			echo '<h3 style="color: #4ec9b0; margin: 0; font-size: 18px; font-weight: 600; letter-spacing: 0.5px;">Debug Output</h3>';
			echo '</div>';
		}
		
		foreach ( $args as $index => $arg ) {
			if ( $is_web && $index > 0 ) {
				echo '<div style="height: 1px; background: linear-gradient(90deg, transparent, #3a3a3a, transparent); margin: 25px 0;"></div>';
			}
			
			if ( $is_web ) {
				$type = gettype( $arg );
				$type_colors = array(
					'string'  => '#ce9178',
					'integer' => '#4ec9b0',
					'boolean' => '#569cd6',
					'array'   => '#dcdcaa',
					'object'  => '#4ec9b0',
					'NULL'    => '#808080',
				);
				$type_color = isset( $type_colors[ $type ] ) ? $type_colors[ $type ] : '#d4d4d4';
				
				echo '<div style="margin-bottom: 15px;">';
				echo '<div style="display: flex; align-items: center; margin-bottom: 8px;">';
				echo '<span style="background: ' . htmlspecialchars( $type_color, ENT_QUOTES, 'UTF-8' ) . '; color: #1e1e1e; padding: 3px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">' . htmlspecialchars( $type, ENT_QUOTES, 'UTF-8' ) . '</span>';
				if ( is_array( $arg ) ) {
					echo '<span style="margin-left: 10px; color: #808080; font-size: 12px;">(' . count( $arg ) . ' items)</span>';
				}
				echo '</div>';
				echo '<pre style="background: #252526; padding: 18px; border-radius: 8px; overflow-x: auto; margin: 0; border: 1px solid #3a3a3a; box-shadow: inset 0 2px 4px rgba(0,0,0,0.2);">';
			}
			
			if ( is_bool( $arg ) ) {
				$color = $arg ? '#4ec9b0' : '#f48771';
				if ( $is_web ) {
					echo '<span style="color: ' . htmlspecialchars( $color, ENT_QUOTES, 'UTF-8' ) . '; font-weight: 600;">' . ( $arg ? 'true' : 'false' ) . '</span>';
				} else {
					echo $arg ? 'true' : 'false';
				}
			} elseif ( is_null( $arg ) ) {
				if ( $is_web ) {
					echo '<span style="color: #808080; font-style: italic;">null</span>';
				} else {
					echo 'null';
				}
			} elseif ( is_string( $arg ) ) {
				if ( $is_web ) {
					echo '<span style="color: #ce9178;">"' . htmlspecialchars( $arg, ENT_QUOTES, 'UTF-8' ) . '"</span>';
				} else {
					echo htmlspecialchars( $arg, ENT_QUOTES, 'UTF-8' );
				}
			} elseif ( is_numeric( $arg ) ) {
				if ( $is_web ) {
					echo '<span style="color: #4ec9b0;">' . htmlspecialchars( $arg, ENT_QUOTES, 'UTF-8' ) . '</span>';
				} else {
					echo $arg;
				}
			} else {
				ob_start();
				var_dump( $arg );
				$output = ob_get_clean();
				if ( $is_web ) {
					// Syntax highlight var_dump output
					$output = preg_replace( '/string\((\d+)\)/', '<span style="color: #ce9178;">string</span>($1)', $output );
					$output = preg_replace( '/int\((\d+)\)/', '<span style="color: #4ec9b0;">int</span>($1)', $output );
					$output = preg_replace( '/bool\((true|false)\)/', '<span style="color: #569cd6;">bool</span>($1)', $output );
					$output = preg_replace( '/array\((\d+)\)/', '<span style="color: #dcdcaa;">array</span>($1)', $output );
					echo $output;
				} else {
					echo $output;
				}
			}
			
			if ( $is_web ) {
				echo '</pre>';
				echo '</div>';
			}
		}
		
		if ( $is_web ) {
			// Get backtrace info
			// Lấy thông tin backtrace
			$backtrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 2 );
			if ( ! empty( $backtrace[1] ) ) {
				$caller = $backtrace[1];
				$file = isset( $caller['file'] ) ? basename( $caller['file'] ) : 'unknown';
				$line = isset( $caller['line'] ) ? $caller['line'] : 'unknown';
				$function = isset( $caller['function'] ) ? $caller['function'] : 'unknown';
				
				echo '<div style="margin-top: 25px; padding-top: 20px; border-top: 2px solid #3a3a3a;">';
				echo '<div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">';
				echo '<span style="color: #808080; font-size: 12px;">📍 Called from:</span>';
				echo '<span style="background: rgba(78, 201, 176, 0.2); color: #4ec9b0; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500;">' . htmlspecialchars( $file, ENT_QUOTES, 'UTF-8' ) . ':' . htmlspecialchars( $line, ENT_QUOTES, 'UTF-8' ) . '</span>';
				echo '<span style="color: #808080; font-size: 12px;">in</span>';
				echo '<span style="background: rgba(78, 201, 176, 0.2); color: #4ec9b0; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-weight: 500;">' . htmlspecialchars( $function, ENT_QUOTES, 'UTF-8' ) . '()</span>';
				echo '</div>';
				echo '</div>';
			}
			echo '</div>';
		}
		
		die();
	}
}

// Enable debug mode immediately after defining functions
// Bật chế độ debug ngay sau khi định nghĩa functions
enable_debug();

