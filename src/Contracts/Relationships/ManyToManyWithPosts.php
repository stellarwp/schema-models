<?php
/**
 * The many to many relationship with posts contract.
 *
 * @since 0.1.0
 *
 * @package StellarWP\SchemaModels\Contracts\Relationships;
 */

declare( strict_types=1 );

namespace StellarWP\SchemaModels\Contracts\Relationships;

use StellarWP\Schema\Tables\Contracts\Table as Table_Interface;

/**
 * The many to many relationship with posts contract.
 *
 * @since 0.1.0
 *
 * @package StellarWP\SchemaModels\Contracts\Relationships;
 */
interface ManyToManyWithPosts {
	/**
	 * Sets the this entity column.
	 *
	 * @since 0.1.0
	 *
	 * @param string $thisEntityColumn The this entity column.
	 *
	 * @return self
	 */
	public function setThisEntityColumn( string $thisEntityColumn ): self;

	/**
	 * Sets the other entity column.
	 *
	 * @since 0.1.0
	 *
	 * @param string $otherEntityColumn The other entity column.
	 *
	 * @return self
	 */
	public function setOtherEntityColumn( string $otherEntityColumn ): self;

	/**
	 * Sets the table interface.
	 *
	 * @since 0.1.0
	 *
	 * @param class-string<Table_Interface> $tableInterface The table interface.
	 *
	 * @return self
	 */
	public function setTableInterface( string $tableInterface ): self;

	/**
	 * Gets the this entity column.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function getThisEntityColumn(): string;

	/**
	 * Gets the other entity column.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function getOtherEntityColumn(): string;

	/**
	 * Gets the table interface.
	 *
	 * @since 0.1.0
	 *
	 * @return class-string<Table_Interface>
	 */
	public function getTableInterface(): string;
}
