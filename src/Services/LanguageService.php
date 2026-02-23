<?php

namespace PolylangMCP\Services;

use PLL_Language;
use WP_Error;

class LanguageService {

	/**
	 * Get all languages with full details.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	public static function get_all(): array {
		$languages = [];

		foreach ( PLL()->model->get_languages_list() as $lang ) {
			$languages[] = self::format_language( $lang );
		}

		return $languages;
	}

	/**
	 * Get a language by slug.
	 */
	public static function get_by_slug( string $slug ): ?PLL_Language {
		$lang = PLL()->model->get_language( $slug );
		return $lang instanceof PLL_Language ? $lang : null;
	}

	/**
	 * Format a PLL_Language into a serializable array.
	 *
	 * @return array<string, mixed>
	 */
	public static function format_language( PLL_Language $lang ): array {
		return [
			'slug'       => $lang->slug,
			'name'       => $lang->name,
			'locale'     => $lang->locale,
			'w3c'        => $lang->w3c,
			'flag_url'   => $lang->get_display_flag_url(),
			'is_default' => $lang->is_default,
			'is_rtl'     => $lang->is_rtl,
			'term_id'    => $lang->term_id,
		];
	}

	/**
	 * Create a new language.
	 *
	 * @param array<string, mixed> $args Must include 'locale'. Optional: 'name', 'slug', 'rtl', 'flag'.
	 * @return array<string, mixed>|WP_Error
	 */
	public static function create( array $args ): array|WP_Error {
		$locale = $args['locale'];

		// Load Polylang's built-in language defaults.
		$predefined = include PLL_SETTINGS_INC . '/languages.php';

		$defaults = $predefined[ $locale ] ?? [];

		$language_args = [
			'name'       => $args['name'] ?? $defaults['name'] ?? $locale,
			'slug'       => $args['slug'] ?? $defaults['code'] ?? strtolower( substr( $locale, 0, 2 ) ),
			'locale'     => $locale,
			'rtl'        => isset( $args['rtl'] ) ? (int) $args['rtl'] : ( ( ( $defaults['dir'] ?? 'ltr' ) === 'rtl' ) ? 1 : 0 ),
			'flag'       => $args['flag'] ?? $defaults['flag'] ?? '',
			'term_group' => 0,
		];

		$result = PLL()->model->languages->add( $language_args );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$lang = PLL()->model->get_language( $language_args['slug'] );

		if ( ! $lang ) {
			return new WP_Error( 'language_not_found', 'Language was created but could not be retrieved.' );
		}

		return self::format_language( $lang );
	}

	/**
	 * Delete a language by slug.
	 *
	 * @return bool|WP_Error
	 */
	public static function delete( string $slug ): bool|WP_Error {
		$lang = self::get_by_slug( $slug );

		if ( ! $lang ) {
			return new WP_Error( 'language_not_found', "Language '{$slug}' not found." );
		}

		$deleted = PLL()->model->languages->delete( $lang->term_id );

		if ( ! $deleted ) {
			return new WP_Error( 'delete_failed', "Failed to delete language '{$slug}'." );
		}

		return true;
	}
}
