<?php

namespace PolylangMCP\Abilities\Languages;

use PolylangMCP\Abilities\AbstractAbility;
use PolylangMCP\Services\LanguageService;

class ListLanguages extends AbstractAbility {

	public function get_name(): string {
		return 'polylang-mcp/list-languages';
	}

	public function get_label(): string {
		return 'List Languages';
	}

	public function get_description(): string {
		return 'List all languages configured in Polylang with their slug, name, locale, flag URL, default status, and RTL direction.';
	}

	public function get_input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => (object) [],
		];
	}

	public function get_output_schema(): array {
		return [
			'type'  => 'array',
			'items' => [
				'type' => 'object',
			],
		];
	}

	public static function execute( array $input = [] ): mixed {
		return LanguageService::get_all();
	}

	public function get_permission_callback(): callable {
		return fn() => current_user_can( 'read' );
	}
}
