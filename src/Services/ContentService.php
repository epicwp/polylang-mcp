<?php

namespace PolylangMCP\Services;

class ContentService {

	/**
	 * Get translatable post types.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_translatable_post_types(): array {
		$types  = PLL()->model->get_translated_post_types();
		$result = [];

		foreach ( $types as $type ) {
			$obj = get_post_type_object( $type );
			if ( ! $obj ) {
				continue;
			}

			$count = wp_count_posts( $type );

			$result[] = [
				'name'  => $type,
				'label' => $obj->labels->name,
				'count' => (int) $count->publish + (int) $count->draft + (int) $count->private,
			];
		}

		return $result;
	}

	/**
	 * Get translatable taxonomies.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_translatable_taxonomies(): array {
		$taxonomies = PLL()->model->get_translated_taxonomies();
		$result     = [];

		foreach ( $taxonomies as $tax ) {
			$obj = get_taxonomy( $tax );
			if ( ! $obj ) {
				continue;
			}

			$result[] = [
				'name'  => $tax,
				'label' => $obj->labels->name,
				'count' => (int) wp_count_terms( [ 'taxonomy' => $tax, 'hide_empty' => false ] ),
			];
		}

		return $result;
	}

	/**
	 * Get translation status per content type and language.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_translation_status( ?string $content_type = null, ?string $language = null ): array {
		$languages  = PLL()->model->get_languages_list();
		$post_types = $content_type ? [ $content_type ] : PLL()->model->get_translated_post_types();
		$status     = [];

		foreach ( $post_types as $type ) {
			$obj = get_post_type_object( $type );
			if ( ! $obj ) {
				continue;
			}

			$type_status = [
				'label'     => $obj->labels->name,
				'languages' => [],
			];

			$filter_langs = $language
				? array_filter( $languages, fn( $l ) => $l->slug === $language )
				: $languages;

			foreach ( $filter_langs as $lang ) {
				$translated = pll_count_posts( $lang, [ 'post_type' => $type ] );

				// Count posts in all other languages that don't have a translation in this lang.
				$untranslated = 0;
				foreach ( $languages as $source_lang ) {
					if ( $source_lang->slug === $lang->slug ) {
						continue;
					}

					$source_posts = get_posts( [
						'post_type'      => $type,
						'lang'           => $source_lang->slug,
						'posts_per_page' => -1,
						'fields'         => 'ids',
						'post_status'    => [ 'publish', 'draft', 'private' ],
					] );

					foreach ( $source_posts as $post_id ) {
						if ( ! pll_get_post( $post_id, $lang->slug ) ) {
							$untranslated++;
						}
					}
				}

				$total      = $translated + $untranslated;
				$percentage = $total > 0 ? round( ( $translated / $total ) * 100, 1 ) : 100;

				$type_status['languages'][ $lang->slug ] = [
					'translated'   => $translated,
					'untranslated' => $untranslated,
					'total'        => $total,
					'percentage'   => $percentage,
				];
			}

			$status[ $type ] = $type_status;
		}

		return $status;
	}

	/**
	 * Get content items missing translation in a target language.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_untranslated( string $target_language, ?string $content_type = null, int $limit = 20, int $offset = 0 ): array {
		$post_types = $content_type ? [ $content_type ] : PLL()->model->get_translated_post_types();
		$languages  = PLL()->model->get_languages_list();
		$items      = [];

		foreach ( $post_types as $type ) {
			foreach ( $languages as $source_lang ) {
				if ( $source_lang->slug === $target_language ) {
					continue;
				}

				$posts = get_posts( [
					'post_type'      => $type,
					'lang'           => $source_lang->slug,
					'posts_per_page' => -1,
					'fields'         => 'ids',
					'post_status'    => [ 'publish', 'draft', 'private' ],
				] );

				foreach ( $posts as $post_id ) {
					if ( ! pll_get_post( $post_id, $target_language ) ) {
						$post    = get_post( $post_id );
						$items[] = [
							'id'            => $post_id,
							'title'         => $post->post_title,
							'type'          => 'post',
							'content_type'  => $type,
							'language'      => $source_lang->slug,
							'modified_date' => $post->post_modified,
						];
					}
				}
			}
		}

		$total    = count( $items );
		$items    = array_slice( $items, $offset, $limit );
		$has_more = ( $offset + $limit ) < $total;

		return [
			'items'    => $items,
			'total'    => $total,
			'has_more' => $has_more,
		];
	}

	/**
	 * Get full post content for translation.
	 *
	 * @return array<string, mixed>|null
	 */
	public static function get_post_content( int $post_id ): ?array {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return null;
		}

		$language     = pll_get_post_language( $post_id, 'slug' );
		$translations = pll_get_post_translations( $post_id );
		$trans_detail = [];

		foreach ( $translations as $lang => $tid ) {
			if ( $tid === $post_id ) {
				continue;
			}
			$t = get_post( $tid );
			if ( $t ) {
				$trans_detail[ $lang ] = [
					'id'    => $tid,
					'title' => $t->post_title,
				];
			}
		}

		$categories = [];
		$tags       = [];

		if ( is_object_in_taxonomy( $post->post_type, 'category' ) ) {
			$categories = wp_get_post_terms( $post_id, 'category', [ 'fields' => 'all' ] );
			$categories = array_map( fn( $term ) => [
				'id'   => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
			], $categories );
		}

		if ( is_object_in_taxonomy( $post->post_type, 'post_tag' ) ) {
			$tags = wp_get_post_terms( $post_id, 'post_tag', [ 'fields' => 'all' ] );
			$tags = array_map( fn( $term ) => [
				'id'   => $term->term_id,
				'name' => $term->name,
				'slug' => $term->slug,
			], $tags );
		}

		$meta = get_post_meta( $post_id );
		// Filter out internal/private meta.
		$public_meta = [];
		foreach ( $meta as $key => $values ) {
			if ( str_starts_with( $key, '_' ) ) {
				continue;
			}
			$public_meta[ $key ] = count( $values ) === 1 ? $values[0] : $values;
		}

		return [
			'id'           => $post_id,
			'title'        => $post->post_title,
			'content'      => $post->post_content,
			'excerpt'      => $post->post_excerpt,
			'type'         => $post->post_type,
			'status'       => $post->post_status,
			'language'     => $language,
			'translations' => $trans_detail,
			'categories'   => $categories,
			'tags'         => $tags,
			'meta'         => $public_meta,
		];
	}

	/**
	 * Get term details for translation.
	 *
	 * @return array<string, mixed>|null
	 */
	public static function get_term_content( int $term_id ): ?array {
		$term = get_term( $term_id );
		if ( ! $term || is_wp_error( $term ) ) {
			return null;
		}

		$language     = pll_get_term_language( $term_id, 'slug' );
		$translations = pll_get_term_translations( $term_id );
		$trans_detail = [];

		foreach ( $translations as $lang => $tid ) {
			if ( $tid === $term_id ) {
				continue;
			}
			$t = get_term( $tid );
			if ( $t && ! is_wp_error( $t ) ) {
				$trans_detail[ $lang ] = [
					'id'   => $tid,
					'name' => $t->name,
				];
			}
		}

		return [
			'id'           => $term_id,
			'name'         => $term->name,
			'slug'         => $term->slug,
			'description'  => $term->description,
			'taxonomy'     => $term->taxonomy,
			'parent'       => $term->parent,
			'language'     => $language,
			'translations' => $trans_detail,
		];
	}
}
