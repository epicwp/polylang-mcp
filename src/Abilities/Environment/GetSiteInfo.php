<?php

namespace PolylangMCP\Abilities\Environment;

use PolylangMCP\Abilities\AbstractAbility;
use PolylangMCP\Services\ContentService;
use PolylangMCP\Services\LanguageService;

class GetSiteInfo extends AbstractAbility {

	public function get_name(): string {
		return 'polylang-mcp/get-site-info';
	}

	public function get_label(): string {
		return 'Get Site Info';
	}

	public function get_description(): string {
		return 'Get WordPress environment and Polylang configuration. This should be the first tool called to understand the site context, available languages, translatable content types, and Polylang settings.';
	}

	public function get_input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => (object) [],
		];
	}

	public function get_output_schema(): array {
		return [
			'type' => 'object',
		];
	}

	public static function execute( array $input = [] ): mixed {
		global $wp_version;

		$theme   = wp_get_theme();
		$plugins = get_option( 'active_plugins', [] );
		$plugins = array_map( fn( $p ) => explode( '/', $p )[0], $plugins );

		$post_types = ContentService::get_translatable_post_types();
		$taxonomies = ContentService::get_translatable_taxonomies();
		$languages  = LanguageService::get_all();

		$default_lang = pll_default_language( 'slug' );

		return [
			'wordpress' => [
				'version'   => $wp_version,
				'site_name' => get_bloginfo( 'name' ),
				'site_url'  => get_site_url(),
				'home_url'  => get_home_url(),
				'theme'     => $theme->get( 'Name' ),
				'plugins'   => $plugins,
			],
			'polylang' => [
				'version'      => POLYLANG_VERSION,
				'is_pro'       => defined( 'POLYLANG_PRO' ) && POLYLANG_PRO,
				'default_lang' => $default_lang,
				'languages'    => $languages,
			],
			'content' => [
				'post_types' => $post_types,
				'taxonomies' => $taxonomies,
			],
		];
	}

	public function get_permission_callback(): callable {
		return fn() => current_user_can( 'manage_options' );
	}
}
