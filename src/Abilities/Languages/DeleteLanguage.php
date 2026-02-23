<?php

namespace PolylangMCP\Abilities\Languages;

use PolylangMCP\Abilities\AbstractAbility;
use PolylangMCP\Services\LanguageService;

class DeleteLanguage extends AbstractAbility {

	public function get_name(): string {
		return 'polylang-mcp/delete-language';
	}

	public function get_label(): string {
		return 'Delete Language';
	}

	public function get_description(): string {
		return 'Remove a language from Polylang. This will unassign the language from all content but does not delete the content itself.';
	}

	public function get_input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'slug' => [
					'type'        => 'string',
					'description' => 'The language slug to delete (e.g. "fr", "de").',
				],
			],
			'required' => [ 'slug' ],
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
				'destructive' => true,
				'idempotent'  => false,
			],
			'show_in_rest' => true,
		];
	}

	public static function execute( array $input = [] ): mixed {
		$result = LanguageService::delete( $input['slug'] );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return [
			'success' => true,
			'message' => "Language '{$input['slug']}' deleted.",
		];
	}

	public function get_permission_callback(): callable {
		return fn() => current_user_can( 'manage_options' );
	}
}
