<?php

namespace PolylangMCP\Abilities\Languages;

use PolylangMCP\Abilities\AbstractAbility;
use PolylangMCP\Services\LanguageService;

class CreateLanguage extends AbstractAbility {

	public function get_name(): string {
		return 'polylang-mcp/create-language';
	}

	public function get_label(): string {
		return 'Create Language';
	}

	public function get_description(): string {
		return 'Add a new language to Polylang. Only the locale is required — name, slug, RTL, and flag default from Polylang\'s built-in language list if not provided.';
	}

	public function get_input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'locale' => [
					'type'        => 'string',
					'description' => 'WordPress locale code (e.g. "fr_FR", "de_DE", "nl_NL").',
				],
				'name' => [
					'type'        => 'string',
					'description' => 'Display name for the language. Defaults from Polylang\'s predefined list.',
				],
				'slug' => [
					'type'        => 'string',
					'description' => 'Language slug (e.g. "fr", "de"). Defaults from locale.',
				],
				'rtl' => [
					'type'        => 'boolean',
					'description' => 'Whether the language is right-to-left.',
				],
				'flag' => [
					'type'        => 'string',
					'description' => 'ISO 3166-1 country code for the flag (e.g. "fr", "de", "us").',
				],
			],
			'required' => [ 'locale' ],
		];
	}

	public function get_output_schema(): array {
		return [
			'type' => 'object',
		];
	}

	public function get_meta(): array {
		return [
			'annotations'  => [
				'readonly'    => false,
				'destructive' => false,
				'idempotent'  => false,
			],
			'show_in_rest' => true,
		];
	}

	public static function execute( array $input = [] ): mixed {
		return LanguageService::create( $input );
	}

	public function get_permission_callback(): callable {
		return fn() => current_user_can( 'manage_options' );
	}
}
