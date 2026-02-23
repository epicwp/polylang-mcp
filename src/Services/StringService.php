<?php

namespace PolylangMCP\Services;

use PLL_MO;
use WP_Error;

class StringService {

	/**
	 * Translate a registered string.
	 *
	 * @return array<string, mixed>|WP_Error
	 */
	public static function translate_string( string $original, string $language, string $translation ): array|WP_Error {
		$lang = LanguageService::get_by_slug( $language );

		if ( ! $lang ) {
			return new WP_Error( 'invalid_language', "Language '{$language}' not found." );
		}

		$mo = new PLL_MO();
		$mo->import_from_db( $lang );
		$mo->add_entry( $mo->make_entry( $original, $translation ) );
		$mo->export_to_db( $lang );

		return [
			'success' => true,
			'message' => "String translated to '{$language}'.",
		];
	}

	/**
	 * Get all string groups and their translation status.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_string_groups( ?string $group = null ): array {
		// Ensure admin strings class is available.
		if ( ! class_exists( 'PLL_Admin_Strings' ) ) {
			require_once ABSPATH . 'wp-content/plugins/polylang/admin/admin-strings.php';
		}

		$strings   = \PLL_Admin_Strings::get_strings();
		$languages = PLL()->model->get_languages_list();
		$groups    = [];

		// Load MO objects for all languages.
		$mo_objects = [];
		foreach ( $languages as $lang ) {
			$mo = new PLL_MO();
			$mo->import_from_db( $lang );
			$mo_objects[ $lang->slug ] = $mo;
		}

		foreach ( $strings as $string_data ) {
			$context = $string_data['context'] ?? 'Polylang';

			if ( $group && $context !== $group ) {
				continue;
			}

			if ( ! isset( $groups[ $context ] ) ) {
				$groups[ $context ] = [
					'name'    => $context,
					'strings' => [],
				];
			}

			$translations = [];
			foreach ( $languages as $lang ) {
				$mo         = $mo_objects[ $lang->slug ];
				$translated = $mo->translate( $string_data['string'] );
				$is_translated = $translated !== $string_data['string'] && $translated !== '';

				$translations[ $lang->slug ] = [
					'translated' => $is_translated,
					'value'      => $is_translated ? $translated : null,
				];
			}

			$groups[ $context ]['strings'][] = [
				'name'         => $string_data['name'],
				'string'       => $string_data['string'],
				'multiline'    => $string_data['multiline'] ?? false,
				'translations' => $translations,
			];
		}

		return array_values( $groups );
	}
}
