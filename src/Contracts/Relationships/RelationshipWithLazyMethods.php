<?php
/**
 * The relationship with posts contract.
 *
 * @since 0.1.0
 *
 * @package StellarWP\SchemaModels\Contracts\Relationships;
 */

declare( strict_types=1 );

namespace StellarWP\SchemaModels\Contracts\Relationships;

use StellarWP\Models\Contracts\LazyModel as LazyModelInterface;

interface RelationshipWithLazyMethods {
	/**
	 * Converts a value to a lazy model.
	 *
	 * @since 0.1.0
	 *
	 * @param mixed $value The value to convert.
	 *
	 * @return ?LazyModelInterface
	 */
	public function toLazy( $value ): ?LazyModelInterface;
}
