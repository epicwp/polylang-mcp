<?php

namespace PolylangMCP\Abilities\Content;

use PolylangMCP\Abilities\AbstractAbility;
use PolylangMCP\Services\ContentService;
use WP_Error;

class GetContent extends AbstractAbility {

	public function get_name(): string {
		return 'polylang-mcp/get-content';
	}

	public function get_label(): string {
		return 'Get Content';
	}

	public function get_description(): string {
		return 'Get full post content for translation, including title, content, excerpt, meta fields, categories, tags, language, and existing translations. Use this to retrieve the source content before translating.';
	}

	public function get_input_schema(): array {
		return [
			'type'       => 'object',
			'properties' => [
				'post_id' => [
					'type'        => 'integer',
					'description' => 'The ID of the post to retrieve.',
				],
			],
			'required' => [ 'post_id' ],
		];
	}

	public function get_output_schema(): array {
		return [
			'type' => 'object',
		];
	}

	public static function execute( array $input = [] ): mixed {
		$result = ContentService::get_post_content( (int) $input['post_id'] );

		if ( $result === null ) {
			return new WP_Error( 'not_found', "Post {$input['post_id']} not found." );
		}

		return $result;
	}

	public function get_permission_callback(): callable {
		return fn() => current_user_can( 'edit_posts' );
	}
}
