<?php

namespace PolylangMCP\Abilities\Translation;

use PolylangMCP\Abilities\AbstractAbility;
use PolylangMCP\Services\StringService;

class TranslateString extends AbstractAbility {

	public function get_name(): string {
		return 'polylang-mcp/translate-string';
	}

	public function get_label(): string {
		return 'Translate String';
	}

	public function get_description(): string {
		return 'Set the translation of a registered Polylang string. The original string must match exactly. Use get-string-groups first to see available strings and their current translations.';
	}

	public function get_input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'string' => [
					'type'        => 'string',
					'description' => 'The original string (must match exactly).',
				],
				'language' => [
					'type'        => 'string',
					'description' => 'Target language slug (e.g. "fr", "de").',
				],
				'translation' => [
					'type'        => 'string',
					'description' => 'The translated string.',
				],
			],
			'required' => [ 'string', 'language', 'translation' ],
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
		return StringService::translate_string(
			$input['string'],
			$input['language'],
			$input['translation']
		);
	}

	public function get_permission_callback(): callable {
		return fn() => current_user_can( 'manage_options' );
	}
}
