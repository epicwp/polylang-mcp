<?php

namespace PolylangMCP\Abilities\Environment;

use PolylangMCP\Abilities\AbstractAbility;
use PolylangMCP\Services\ContentService;

class GetTranslationStatus extends AbstractAbility {

	public function get_name(): string {
		return 'polylang-mcp/get-translation-status';
	}

	public function get_label(): string {
		return 'Get Translation Status';
	}

	public function get_description(): string {
		return 'Get an overview of translation progress across content types and languages. Shows total, translated, and untranslated counts with percentages. Use this to plan translation work.';
	}

	public function get_input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'content_type' => [
					'type'        => 'string',
					'description' => 'Filter to a specific post type (e.g. "post", "page"). Omit for all types.',
				],
				'language' => [
					'type'        => 'string',
					'description' => 'Filter to a specific target language slug. Omit for all languages.',
				],
			],
		];
	}

	public function get_output_schema(): array {
		return [
			'type' => 'object',
		];
	}

	public static function execute( array $input = [] ): mixed {
		return ContentService::get_translation_status(
			$input['content_type'] ?? null,
			$input['language'] ?? null
		);
	}

	public function get_permission_callback(): callable {
		return fn() => current_user_can( 'manage_options' );
	}
}
