<?php

namespace PolylangMCP\Abilities\Content;

use PolylangMCP\Abilities\AbstractAbility;
use PolylangMCP\Services\ContentService;

class GetUntranslatedContent extends AbstractAbility {

	public function get_name(): string {
		return 'polylang-mcp/get-untranslated-content';
	}

	public function get_label(): string {
		return 'Get Untranslated Content';
	}

	public function get_description(): string {
		return 'Get a paginated list of content items that are missing a translation in the specified target language. Returns post ID, title, type, source language, and modified date for each item.';
	}

	public function get_input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'target_language' => [
					'type'        => 'string',
					'description' => 'Language slug to find missing translations for (e.g. "fr", "de").',
				],
				'content_type' => [
					'type'        => 'string',
					'description' => 'Filter to a specific post type. Omit for all translatable types.',
				],
				'limit' => [
					'type'        => 'integer',
					'description' => 'Maximum items to return (default 20, max 100).',
					'default'     => 20,
					'maximum'     => 100,
				],
				'offset' => [
					'type'        => 'integer',
					'description' => 'Number of items to skip for pagination (default 0).',
					'default'     => 0,
				],
			],
			'required' => [ 'target_language' ],
		];
	}

	public function get_output_schema(): array {
		return [
			'type' => 'object',
		];
	}

	public static function execute( array $input = [] ): mixed {
		$limit  = min( (int) ( $input['limit'] ?? 20 ), 100 );
		$offset = max( (int) ( $input['offset'] ?? 0 ), 0 );

		return ContentService::get_untranslated(
			$input['target_language'],
			$input['content_type'] ?? null,
			$limit,
			$offset
		);
	}

	public function get_permission_callback(): callable {
		return fn() => current_user_can( 'manage_options' );
	}
}
