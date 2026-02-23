<?php

namespace PolylangMCP;

use PolylangMCP\Abilities\Environment\GetSiteInfo;
use PolylangMCP\Abilities\Environment\GetTranslationStatus;
use PolylangMCP\Abilities\Languages\ListLanguages;
use PolylangMCP\Abilities\Languages\CreateLanguage;
use PolylangMCP\Abilities\Languages\DeleteLanguage;
use PolylangMCP\Abilities\Content\ListContentTypes;
use PolylangMCP\Abilities\Content\GetUntranslatedContent;
use PolylangMCP\Abilities\Content\GetContent;
use PolylangMCP\Abilities\Content\GetTerm;
use PolylangMCP\Abilities\Translation\TranslatePost;
use PolylangMCP\Abilities\Translation\TranslateTerm;
use PolylangMCP\Abilities\Translation\TranslateString;
use PolylangMCP\Abilities\Translation\GetStringGroups;
use WP\MCP\Core\McpAdapter;
use WP\MCP\Transport\HttpTransport;
use WP\MCP\Infrastructure\ErrorHandling\ErrorLogMcpErrorHandler;
use WP\MCP\Infrastructure\Observability\NullMcpObservabilityHandler;

class Plugin {

	private const ABILITY_CLASSES = [
		GetSiteInfo::class,
		GetTranslationStatus::class,
		ListLanguages::class,
		CreateLanguage::class,
		DeleteLanguage::class,
		ListContentTypes::class,
		GetUntranslatedContent::class,
		GetContent::class,
		GetTerm::class,
		TranslatePost::class,
		TranslateTerm::class,
		TranslateString::class,
		GetStringGroups::class,
	];

	public static function init(): void {
		if ( ! self::check_dependencies() ) {
			return;
		}

		add_action( 'wp_abilities_api_categories_init', [ __CLASS__, 'register_category' ] );
		add_action( 'wp_abilities_api_init', [ __CLASS__, 'register_abilities' ] );
		add_action( 'mcp_adapter_init', [ __CLASS__, 'create_server' ] );

		// Ensure the MCP adapter is bootstrapped. It initializes lazily and only
		// when explicitly instantiated. WooCommerce gates this behind a feature
		// flag, so we boot it ourselves if nobody else has.
		if ( class_exists( McpAdapter::class ) ) {
			McpAdapter::instance();
		}
	}

	private static function check_dependencies(): bool {
		if ( ! function_exists( 'PLL' ) ) {
			add_action( 'admin_notices', function () {
				echo '<div class="notice notice-error"><p>';
				echo esc_html__( 'Polylang MCP Server requires Polylang to be installed and active.', 'polylang-mcp' );
				echo '</p></div>';
			} );
			return false;
		}

		return true;
	}

	public static function register_category(): void {
		wp_register_ability_category(
			'polylang-mcp',
			[
				'label'       => __( 'Polylang MCP', 'polylang-mcp' ),
				'description' => __( 'AI translation abilities for Polylang.', 'polylang-mcp' ),
			]
		);
	}

	public static function register_abilities(): void {
		foreach ( self::ABILITY_CLASSES as $class ) {
			$ability = new $class();
			wp_register_ability( $ability->get_name(), $ability->get_args() );
		}
	}

	/**
	 * @param McpAdapter $adapter
	 */
	public static function create_server( $adapter ): void {
		$ability_names = array_map(
			fn( string $class ) => ( new $class() )->get_name(),
			self::ABILITY_CLASSES
		);

		$adapter->create_server(
			'polylang-mcp-server',
			'polylang-mcp',
			'mcp',
			'Polylang MCP Server',
			'AI translation for Polylang',
			POLYLANG_MCP_VERSION,
			[ HttpTransport::class ],
			ErrorLogMcpErrorHandler::class,
			NullMcpObservabilityHandler::class,
			$ability_names,
			[],
			[]
		);
	}
}
