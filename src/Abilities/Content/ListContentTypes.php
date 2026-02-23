<?php

namespace PolylangMCP\Abilities\Content;

use PolylangMCP\Abilities\AbstractAbility;
use PolylangMCP\Services\ContentService;

class ListContentTypes extends AbstractAbility {

	public function get_name(): string {
		return 'polylang-mcp/list-content-types';
	}

	public function get_label(): string {
		return 'List Content Types';
	}

	public function get_description(): string {
		return 'List all translatable post types and taxonomies configured in Polylang, with their labels and total content counts.';
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
		return [
			'post_types' => ContentService::get_translatable_post_types(),
			'taxonomies' => ContentService::get_translatable_taxonomies(),
		];
	}

	public function get_permission_callback(): callable {
		return fn() => current_user_can( 'read' );
	}
}
