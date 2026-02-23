<?php

namespace PolylangMCP\Abilities\Content;

use PolylangMCP\Abilities\AbstractAbility;
use PolylangMCP\Services\ContentService;
use WP_Error;

class GetTerm extends AbstractAbility {

	public function get_name(): string {
		return 'polylang-mcp/get-term';
	}

	public function get_label(): string {
		return 'Get Term';
	}

	public function get_description(): string {
		return 'Get term details for translation, including name, slug, description, taxonomy, parent, language, and existing translations.';
	}

	public function get_input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'term_id' => [
					'type'        => 'integer',
					'description' => 'The ID of the term to retrieve.',
				],
			],
			'required' => [ 'term_id' ],
		];
	}

	public function get_output_schema(): array {
		return [
			'type' => 'object',
		];
	}

	public static function execute( array $input = [] ): mixed {
		$result = ContentService::get_term_content( (int) $input['term_id'] );

		if ( $result === null ) {
			return new WP_Error( 'not_found', "Term {$input['term_id']} not found." );
		}

		return $result;
	}

	public function get_permission_callback(): callable {
		return fn() => current_user_can( 'edit_posts' );
	}
}
