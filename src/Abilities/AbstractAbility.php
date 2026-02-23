<?php

namespace PolylangMCP\Abilities;

abstract class AbstractAbility {

	abstract public function get_name(): string;
	abstract public function get_label(): string;
	abstract public function get_description(): string;
	abstract public function get_input_schema(): array;
	abstract public function get_output_schema(): array;
	abstract public static function execute( array $input = [] ): mixed;
	abstract public function get_permission_callback(): callable;

	public function get_meta(): array {
		return [
			'annotations'  => [
				'readonly'    => true,
				'destructive' => false,
				'idempotent'  => true,
			],
			'show_in_rest' => true,
		];
	}

	public function get_args(): array {
		return [
			'label'               => $this->get_label(),
			'description'         => $this->get_description(),
			'category'            => 'polylang-mcp',
			'input_schema'        => $this->get_input_schema(),
			'output_schema'       => $this->get_output_schema(),
			'execute_callback'    => [ static::class, 'execute' ],
			'permission_callback' => $this->get_permission_callback(),
			'meta'                => $this->get_meta(),
		];
	}
}
