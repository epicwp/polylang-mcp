<?php
/**
 * Plugin Name: Polylang MCP Server
 * Plugin URI: https://github.com/polylang/polylang-mcp
 * Description: MCP server for AI-powered Polylang translations. Registers WordPress abilities that the MCP Adapter exposes as tools for AI clients.
 * Version: 1.0.0
 * Requires at least: 6.9
 * Requires PHP: 8.0
 * Author: Polylang
 * License: GPL-2.0-or-later
 * Text Domain: polylang-mcp
 */

defined( 'ABSPATH' ) || exit;

define( 'POLYLANG_MCP_VERSION', '1.0.0' );
define( 'POLYLANG_MCP_FILE', __FILE__ );
define( 'POLYLANG_MCP_DIR', plugin_dir_path( __FILE__ ) );

require_once POLYLANG_MCP_DIR . 'vendor/autoload.php';

// Bootstrap the bundled MCP adapter if no other copy is active.
if ( ! defined( 'WP_MCP_DIR' ) && file_exists( POLYLANG_MCP_DIR . 'vendor/wordpress/mcp-adapter/mcp-adapter.php' ) ) {
	require_once POLYLANG_MCP_DIR . 'vendor/wordpress/mcp-adapter/mcp-adapter.php';
}

add_action( 'plugins_loaded', [ \PolylangMCP\Plugin::class, 'init' ] );
