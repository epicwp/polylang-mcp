<?php

namespace PolylangMCP\Abilities\Translation;

use PolylangMCP\Abilities\AbstractAbility;
use PolylangMCP\Services\StringService;

class GetStringGroups extends AbstractAbility {

	public function get_name(): string {
		return 'polylang-mcp/get-string-groups';
	}

	public function get_label(): string {
		return 'Get String Groups';
	}

	public function get_description(): string {
		return 'List all registered string translation groups with their strings and per-language translation status. Optionally filter by group name.';
	}

	public function get_input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'group' => [
					'type'        => 'string',
					'description' => 'Filter to a specific string group name. Omit for all groups.',
				],
			],
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
		return StringService::get_string_groups( $input['group'] ?? null );
	}

	public function get_permission_callback(): callable {
		return fn() => current_user_can( 'manage_options' );
	}
}
