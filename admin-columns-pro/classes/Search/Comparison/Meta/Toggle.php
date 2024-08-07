<?php

namespace ACP\Search\Comparison\Meta;

use AC\Helper\Select\Options;
use ACP\Search\Comparison;
use ACP\Search\Comparison\Values;
use ACP\Search\Operators;

class Toggle extends Comparison\Meta
	implements Values {

	private $choices;

	public function __construct( string $meta_key, array $choices ) {
		parent::__construct( new Operators( [
			Operators::EQ,
		] ), $meta_key );

		$this->choices = $choices;
	}

	public function get_values(): Options {
		return Options::create_from_array( $this->choices );
	}

}