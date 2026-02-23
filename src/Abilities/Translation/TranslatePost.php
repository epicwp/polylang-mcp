<?php

namespace PolylangMCP\Abilities\Translation;

use PolylangMCP\Abilities\AbstractAbility;
use PolylangMCP\Services\TranslationService;

class TranslatePost extends AbstractAbility {

	public function get_name(): string {
		return 'polylang-mcp/translate-post';
	}

	public function get_label(): string {
		return 'Translate Post';
	}

	public function get_description(): string {
		return 'Create or update a translated version of a post. If a translation already exists in the target language, it will be updated. The translation is automatically linked to the source post. Categories and tags are synced if translations exist in the target language.';
	}

	public function get_input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'source_post_id' => [
					'type'        => 'integer',
					'description' => 'The ID of the source post to translate from.',
				],
				'target_language' => [
					'type'        => 'string',
					'description' => 'Target language slug (e.g. "fr", "de").',
				],
				'translated_title' => [
					'type'        => 'string',
					'description' => 'The translated post title.',
				],
				'translated_content' => [
					'type'        => 'string',
					'description' => 'The translated post content (HTML).',
				],
				'translated_excerpt' => [
					'type'        => 'string',
					'description' => 'The translated post excerpt.',
				],
				'translated_slug' => [
					'type'        => 'string',
					'description' => 'The translated URL slug.',
				],
				'translated_meta' => [
					'type'        => 'object',
					'description' => 'Key-value pairs of translated meta fields.',
				],
				'status' => [
					'type'        => 'string',
					'description' => 'Post status for the translation (default "publish").',
					'enum'        => [ 'publish', 'draft', 'pending', 'private' ],
					'default'     => 'publish',
				],
			],
			'required' => [ 'source_post_id', 'target_language', 'translated_title', 'translated_content' ],
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
		return TranslationService::translate_post( $input );
	}

	public function get_permission_callback(): callable {
		return fn() => current_user_can( 'manage_options' );
	}
}
