<?php
/**
 * Many to many relationship with posts.
 *
 * @since 0.1.0
 *
 * @package StellarWP\SchemaModels\Relationships;
 */

declare( strict_types=1 );

namespace StellarWP\SchemaModels\Relationships;

use StellarWP\Models\ModelRelationshipDefinition;
use StellarWP\Schema\Tables\Contracts\Table as Table_Interface;
use StellarWP\SchemaModels\Contracts\Relationships\ManyToManyWithPosts as ManyToManyWithPostsContract;

/**
 * Many to many relationship with posts.
 *
 * @since 0.1.0
 *
 * @package StellarWP\SchemaModels\Relationships;
 */
class ManyToManyWithPosts extends ModelRelationshipDefinition implements ManyToManyWithPostsContract {
	/**
	 * The table interface.
	 *
	 * @since 0.1.0
	 *
	 * @var class-string<Table_Interface>
	 */
	private string $tableInterface;

	/**
	 * The this entity column.
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	private string $thisEntityColumn;

	/**
	 * The other entity column.
	 *
	 * @since 0.1.0
	 *
	 * @var string
	 */
	private string $otherEntityColumn;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key The relationship key/name.
	 */
	public function __construct( string $key ) {
		parent::__construct( $key );

		$this->manyToMany();
	}

	/**
	 * Sets the this entity column.
	 *
	 * @since 0.1.0
	 *
	 * @param string $thisEntityColumn The this entity column.
	 *
	 * @return self
	 */
	public function setThisEntityColumn( string $thisEntityColumn ): self {
		$this->thisEntityColumn = $thisEntityColumn;

		return $this;
	}

	/**
	 * Sets the other entity column.
	 *
	 * @since 0.1.0
	 *
	 * @param string $otherEntityColumn The other entity column.
	 *
	 * @return self
	 */
	public function setOtherEntityColumn( string $otherEntityColumn ): self {
		$this->otherEntityColumn = $otherEntityColumn;

		return $this;
	}

	/**
	 * Sets the table interface.
	 *
	 * @since 0.1.0
	 *
	 * @param class-string<Table_Interface> $tableInterface The table interface.
	 *
	 * @return self
	 */
	public function setTableInterface( string $tableInterface ): self {
		$this->tableInterface = $tableInterface;

		return $this;
	}

	/**
	 * Gets the this entity column.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function getThisEntityColumn(): string {
		return $this->thisEntityColumn;
	}

	/**
	 * Gets the other entity column.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function getOtherEntityColumn(): string {
		return $this->otherEntityColumn;
	}

	/**
	 * Gets the table interface.
	 *
	 * @since 0.1.0
	 *
	 * @return class-string<Table_Interface>
	 */
	public function getTableInterface(): string {
		return $this->tableInterface;
	}
}
