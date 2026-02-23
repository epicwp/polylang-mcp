<?php

namespace PolylangMCP\Abilities\Translation;

use PolylangMCP\Abilities\AbstractAbility;
use PolylangMCP\Services\TranslationService;

class TranslateTerm extends AbstractAbility {

	public function get_name(): string {
		return 'polylang-mcp/translate-term';
	}

	public function get_label(): string {
		return 'Translate Term';
	}

	public function get_description(): string {
		return 'Create or update a translated version of a taxonomy term (category, tag, or custom taxonomy). If a translation already exists in the target language, it will be updated. The translation is automatically linked to the source term.';
	}

	public function get_input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'source_term_id' => [
					'type'        => 'integer',
					'description' => 'The ID of the source term to translate from.',
				],
				'target_language' => [
					'type'        => 'string',
					'description' => 'Target language slug (e.g. "fr", "de").',
				],
				'translated_name' => [
					'type'        => 'string',
					'description' => 'The translated term name.',
				],
				'translated_description' => [
					'type'        => 'string',
					'description' => 'The translated term description.',
				],
				'translated_slug' => [
					'type'        => 'string',
					'description' => 'The translated URL slug.',
				],
			],
			'required' => [ 'source_term_id', 'target_language', 'translated_name' ],
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
				'idempotent'  => true,
			],
			'show_in_rest' => true,
		];
	}

	public static function execute( array $input = [] ): mixed {
		return TranslationService::translate_term( $input );
	}

	public function get_permission_callback(): callable {
		return fn() => current_user_can( 'manage_options' );
	}
}
