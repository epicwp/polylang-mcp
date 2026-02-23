<?php

namespace PolylangMCP\Services;

use WP_Error;

class TranslationService {

	/**
	 * Translate a post: create or update translation.
	 *
	 * @param array<string, mixed> $args {
	 *     @type int    $source_post_id
	 *     @type string $target_language
	 *     @type string $translated_title
	 *     @type string $translated_content
	 *     @type string $translated_excerpt
	 *     @type string $translated_slug
	 *     @type array  $translated_meta
	 *     @type string $status
	 * }
	 * @return array<string, mixed>|WP_Error
	 */
	public static function translate_post( array $args ): array|WP_Error {
		$source_id       = $args['source_post_id'];
		$target_lang     = $args['target_language'];
		$source          = get_post( $source_id );

		if ( ! $source ) {
			return new WP_Error( 'source_not_found', "Source post {$source_id} not found." );
		}

		$source_lang = pll_get_post_language( $source_id, 'slug' );
		if ( ! $source_lang ) {
			return new WP_Error( 'no_language', "Source post {$source_id} has no language assigned." );
		}

		$lang_obj = LanguageService::get_by_slug( $target_lang );
		if ( ! $lang_obj ) {
			return new WP_Error( 'invalid_language', "Target language '{$target_lang}' not found." );
		}

		// Build the existing translations map.
		$translations                  = pll_get_post_translations( $source_id );
		$existing_translation_id       = $translations[ $target_lang ] ?? 0;

		$post_data = [
			'post_title'   => $args['translated_title'],
			'post_content' => $args['translated_content'],
			'post_excerpt' => $args['translated_excerpt'] ?? '',
			'post_name'    => $args['translated_slug'] ?? '',
			'post_status'  => $args['status'] ?? 'publish',
			'post_type'    => $source->post_type,
		];

		if ( $existing_translation_id ) {
			// Update existing translation.
			$post_data['ID']           = $existing_translation_id;
			$post_data['lang']         = $target_lang;
			$post_data['translations'] = $translations;

			$result = pll_update_post( $post_data );
		} else {
			// Create new translation.
			$translations[ $target_lang ] = 0; // Will be filled by pll_insert_post.
			$post_data['translations']    = $translations;

			$result = pll_insert_post( $post_data, $target_lang );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$translated_post_id = (int) $result;

		// Handle meta fields.
		if ( ! empty( $args['translated_meta'] ) ) {
			foreach ( $args['translated_meta'] as $key => $value ) {
				update_post_meta( $translated_post_id, $key, $value );
			}
		}

		// Assign translated categories/tags if they exist in target language.
		self::sync_taxonomy_translations( $source_id, $translated_post_id, $target_lang );

		return [
			'success'            => true,
			'translated_post_id' => $translated_post_id,
			'url'                => get_permalink( $translated_post_id ),
		];
	}

	/**
	 * Sync taxonomy terms from source post to translated post.
	 */
	private static function sync_taxonomy_translations( int $source_id, int $translated_id, string $target_lang ): void {
		$source = get_post( $source_id );
		if ( ! $source ) {
			return;
		}

		$taxonomies = get_object_taxonomies( $source->post_type );

		foreach ( $taxonomies as $taxonomy ) {
			if ( ! pll_is_translated_taxonomy( $taxonomy ) ) {
				continue;
			}

			$source_terms = wp_get_object_terms( $source_id, $taxonomy, [ 'fields' => 'ids' ] );
			if ( is_wp_error( $source_terms ) || empty( $source_terms ) ) {
				continue;
			}

			$translated_term_ids = [];
			foreach ( $source_terms as $term_id ) {
				$translated_term = pll_get_term( $term_id, $target_lang );
				if ( $translated_term ) {
					$translated_term_ids[] = $translated_term;
				}
			}

			if ( ! empty( $translated_term_ids ) ) {
				wp_set_object_terms( $translated_id, $translated_term_ids, $taxonomy );
			}
		}
	}

	/**
	 * Translate a term: create or update translation.
	 *
	 * @param array<string, mixed> $args {
	 *     @type int    $source_term_id
	 *     @type string $target_language
	 *     @type string $translated_name
	 *     @type string $translated_description
	 *     @type string $translated_slug
	 * }
	 * @return array<string, mixed>|WP_Error
	 */
	public static function translate_term( array $args ): array|WP_Error {
		$source_id   = $args['source_term_id'];
		$target_lang = $args['target_language'];

		$source = get_term( $source_id );
		if ( ! $source || is_wp_error( $source ) ) {
			return new WP_Error( 'source_not_found', "Source term {$source_id} not found." );
		}

		$source_lang = pll_get_term_language( $source_id, 'slug' );
		if ( ! $source_lang ) {
			return new WP_Error( 'no_language', "Source term {$source_id} has no language assigned." );
		}

		$lang_obj = LanguageService::get_by_slug( $target_lang );
		if ( ! $lang_obj ) {
			return new WP_Error( 'invalid_language', "Target language '{$target_lang}' not found." );
		}

		$translations                = pll_get_term_translations( $source_id );
		$existing_translation_id     = $translations[ $target_lang ] ?? 0;

		$term_args = [
			'description'  => $args['translated_description'] ?? '',
			'slug'         => $args['translated_slug'] ?? '',
		];

		if ( $existing_translation_id ) {
			// Update existing translation.
			$term_args['name']         = $args['translated_name'];
			$term_args['lang']         = $target_lang;
			$term_args['translations'] = $translations;

			$result = pll_update_term( $existing_translation_id, $term_args );
		} else {
			// Create new translation.
			$translations[ $target_lang ] = 0; // Will be filled by pll_insert_term.
			$term_args['translations']    = $translations;

			// Translate parent if applicable.
			if ( $source->parent ) {
				$translated_parent = pll_get_term( $source->parent, $target_lang );
				if ( $translated_parent ) {
					$term_args['parent'] = $translated_parent;
				}
			}

			$result = pll_insert_term( $args['translated_name'], $source->taxonomy, $target_lang, $term_args );
		}

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$translated_term_id = is_array( $result ) ? (int) $result['term_id'] : (int) $result;

		return [
			'success'            => true,
			'translated_term_id' => $translated_term_id,
		];
	}
}
