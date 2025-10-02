<?php
/**
 * The schema model.
 *
 * @since 0.1.0
 *
 * @package StellarWP\SchemaModels;
 */

namespace StellarWP\SchemaModels;

use InvalidArgumentException;
use StellarWP\SchemaModels\Exceptions\BadMethodCallSchemaModelException;
use StellarWP\SchemaModels\Contracts\SchemaModel as SchemaModelInterface;
use StellarWP\DB\DB;
use StellarWP\Schema\Tables\Contracts\Table as Table_Interface;
use StellarWP\Schema\Tables\Contracts\Table_Schema_Interface;
use RuntimeException;
use StellarWP\Models\Model;
use StellarWP\Models\ModelPropertyDefinition;
use StellarWP\Models\ModelRelationshipCollection;
use StellarWP\SchemaModels\Contracts\Relationships\ManyToManyWithPosts as ManyToManyWithPostsContract;
use StellarWP\Schema\Columns\Contracts\Column;
use DateTime;
use DateTimeInterface;

/**
 * The schema model.
 *
 * @since 0.1.0
 *
 * @package StellarWP\SchemaModels;
 */
abstract class SchemaModel extends Model implements SchemaModelInterface {
	/**
	 * The relationship data of the model.
	 *
	 * @since 0.1.0
	 *
	 * @var array
	 */
	private array $relationshipData = [];

	/**
	 * Gets the table interface of the model.
	 *
	 * @since 0.1.0
	 *
	 * @return Table_Interface
	 */
	abstract public static function getTableInterface(): Table_Interface;

	/**
	 * Gets the table class of the model.
	 *
	 * @since 0.1.0
	 *
	 * @return class-string<Table_Interface>
	 */
	protected static function getTableClass(): string {
		return get_class( static::getTableInterface() );
	}

	/**
	 * Gets the relationship collection of the model.
	 *
	 * @since 0.1.0
	 *
	 * @return ModelRelationshipCollection
	 */
	public function getRelationships(): ModelRelationshipCollection {
		return $this->relationshipCollection;
	}

	/**
	 * Gets the primary value of the model.
	 *
	 * @since 0.1.0
	 *
	 * @return mixed
	 */
	public function getPrimaryValue() {
		return $this->getAttribute( $this->getPrimaryColumn() );
	}

	/**
	 * Gets the primary column of the model.
	 *
	 * @since 0.1.0
	 *
	 * @return string
	 */
	public function getPrimaryColumn(): string {
		return $this->getTableClass()::uid_column();
	}

	/**
	 * Magic method to get the relationships of the model.
	 *
	 * @since 0.1.0
	 *
	 * @param string $name The name of the method.
	 * @param array  $arguments The arguments of the method.
	 *
	 * @return array|void The relationships of the model.
	 *
	 * @throws BadMethodCallSchemaModelException If the method does not exist on the model.
	 * @throws BadMethodCallSchemaModelException If the relationship does not exist on the model.
	 * @throws BadMethodCallSchemaModelException If the relationship is not a many to many relationship.
	 */
	public function __call( string $name, array $arguments ) {
		if ( ! str_starts_with( $name, 'get_' ) && ! str_starts_with( $name, 'set_' ) ) {
			throw new BadMethodCallSchemaModelException( "Method {$name} does not exist on the model." );
		}

		$property = str_replace( [ 'get_', 'set_' ], '', $name );

		if ( ! $this->hasProperty( $property ) && ! $this->getRelationships()->has( $property ) ) {
			throw new BadMethodCallSchemaModelException( "`{$property}` is not a property or a relationship on the model." );
		}

		$is_getter = str_starts_with( $name, 'get_' );

		if ( $is_getter ) {
			if ( $this->getRelationships()->has( $property ) ) {
				return $this->$property;
			}

			return $this->getAttribute( $property );
		}

		$args = $arguments['0'] ?? null;
		if ( $this->getRelationships()->has( $property ) ) {
			$args ? $this->setCachedRelationship( $property, (array) $args ) : $this->deleteRelationshipData( $property );
			return;
		}

		$this->setAttribute( $property, $args );
	}

	/**
	 * Deletes the relationship data for a given key.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key The key of the relationship.
	 *
	 * @throws InvalidArgumentException If the relationship does not exist.
	 */
	public function deleteRelationshipData( string $key ): void {
		if ( ! $this->getRelationships()->has( $key ) ) {
			throw new InvalidArgumentException( "Relationship {$key} does not exist." );
		}

		/** @var ModelRelationshipDefinition $relationship */
		$definition = $this->getRelationships()->get( $key )->getDefinition();

		if ( $definition instanceof ManyToManyWithPostsContract ) {
			$definition->getTableInterface()::delete( $this->getPrimaryValue(), $definition->getThisEntityColumn() );
		}
	}

	/**
	 * Fetches a relationship's value.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key Relationship name.
	 *
	 * @return Model|list<Model>|null
	 */
	protected function fetchRelationship( string $key ) {
		$relationship = $this->getRelationships()->getOrFail( $key );
		$definition   = $relationship->getDefinition();

		if ( ! $definition instanceof ManyToManyWithPostsContract ) {
			throw new InvalidArgumentException( "Relationship {$key} is not a many to many relationship. I don't know how to fetch it." );
		}

		$table = $definition->getTableInterface();

		return wp_list_pluck(
			$table::get_all_by(
				$definition->getThisEntityColumn(),
				$this->getPrimaryValue(),
				'=',
				1000
			),
			$definition->getOtherEntityColumn()
		);
	}

	/**
	 * Adds an ID to a relationship.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key The key of the relationship.
	 * @param mixed  $id  The ID to add.
	 *
	 * @throws InvalidArgumentException If the relationship does not exist.
	 */
	public function addToRelationship( string $key, $id ): void {
		if ( ! $this->getRelationships()->has( $key ) ) {
			throw new InvalidArgumentException( "Relationship {$key} does not exist." );
		}

		if ( ! isset( $this->relationshipData[ $key ] ) ) {
			$this->relationshipData[ $key ] = [];
		}

		if ( ! isset( $this->relationshipData[ $key ]['insert'] ) ) {
			$this->relationshipData[ $key ]['insert'] = [];
		}

		$this->relationshipData[ $key ]['insert'][] = $id;

		if ( ! empty( $this->relationshipData[ $key ]['delete'] ) ) {
			$this->relationshipData[ $key ]['delete'] = array_diff( $this->relationshipData[ $key ]['delete'], [ $id ] );
		}
	}

	/**
	 * Removes an ID from a relationship.
	 *
	 * @since 0.1.0
	 *
	 * @param string $key The key of the relationship.
	 * @param mixed  $id  The ID to remove.
	 *
	 * @throws InvalidArgumentException If the relationship does not exist.
	 */
	public function removeFromRelationship( string $key, $id ): void {
		if ( ! $this->getRelationships()->has( $key ) ) {
			throw new InvalidArgumentException( "Relationship {$key} does not exist." );
		}

		if ( ! isset( $this->relationshipData[ $key ] ) ) {
			$this->relationshipData[ $key ] = [];
		}

		if ( ! isset( $this->relationshipData[ $key ]['delete'] ) ) {
			$this->relationshipData[ $key ]['delete'] = [];
		}

		$this->relationshipData[ $key ]['delete'][] = $id;

		if ( ! empty( $this->relationshipData[ $key ]['insert'] ) ) {
			$this->relationshipData[ $key ]['insert'] = array_diff( $this->relationshipData[ $key ]['insert'], [ $id ] );
		}
	}

	/**
	 * Generates the property definitions for the model.
	 *
	 * @since 0.1.0
	 *
	 * @return array<string,ModelPropertyDefinition>
	 *
	 * @throws RuntimeException On unknown reserved keyword.
	 */
	protected static function generatePropertyDefinitions(): array {
		/** @var Table_Schema_Interface $table_schema */
		$table_schema = static::getTableClass()::get_current_schema();

		/** @var array<string,ModelPropertyDefinition> $property_definitions */
		$property_definitions = [];

		foreach ( $table_schema->get_columns() as $column ) {
			$definition_type = [ $column->get_php_type() ];
			if ( 'json' === $column->get_php_type() ) {
				$definition_type[] = 'array';
			}

			if ( DateTimeInterface::class === $column->get_php_type() ) {
				$definition_type[] = 'object';
			}

			$definition = ( new ModelPropertyDefinition() )->type( ...$definition_type );
			if ( $column->get_nullable() ) {
				$definition->nullable();
			}

			if ( $column->get_default() ) {
				$default = $column->get_default();
				if ( in_array( $default, Column::SQL_RESERVED_DEFAULTS, true ) ) {
					switch ( $default ) {
						case 'CURRENT_TIMESTAMP':
						case 'CURRENT_DATE':
						case 'CURRENT_TIME':
							$default = new DateTime();
							break;
						case 'NULL':
							$default = null;
							break;
						default:
							throw new RuntimeException( 'Unknown default RESERVED Keyword: ' . $default );
					}
				}

				$definition->default( $default );
			}

			if ( is_callable( [ static::getTableClass(), 'cast_value_based_on_type' ] ) ) {
				$definition->castWith( fn( $value ) => static::getTableClass()::cast_value_based_on_type( $column->get_php_type(), $value ) );
			}

			$property_definitions[ $column->get_name() ] = $definition;
		}

		static::$properties = $property_definitions;

		return $property_definitions;
	}

	/**
	 * Updates the cached value for a given relationship.
	 *
	 * @since 0.1.0
	 *
	 * @param string                 $key Relationship name.
	 * @param Model|list<Model>|null $value The relationship value to cache.
	 *
	 * @throws InvalidArgumentException If the relationship is not an integer.
	 */
	protected function setCachedRelationship( string $key, $value ): void {
		$relationship = $this->getRelationships()->get( $key );

		if ( ! $relationship ) {
			throw new InvalidArgumentException( "Relationship '$key' is not defined on this model." );
		}

		if ( ! isset( $this->relationshipData[ $key ] ) || ! is_array( $this->relationshipData[ $key ] ) ) {
			$this->relationshipData[ $key ] = [];
		}

		$old_value = $relationship->isLoaded() && $relationship->getDefinition()->hasCachingEnabled() ? $relationship->getValue( fn() => true ) : null;
		$relationship->setValue( $value );

		if ( $old_value ) {
			if ( is_array( $old_value ) ) {
				foreach ( $old_value as $i ) {
					$this->removeFromRelationship( $key, $i instanceof self ? $i->getPrimaryValue() : $i );
				}
			} else {
				$this->removeFromRelationship( $key, $old_value instanceof self ? $old_value->getPrimaryValue() : $old_value );
			}
		}

		if ( is_array( $value ) ) {
			foreach ( $value as $i ) {
				$this->addToRelationship( $key, $i instanceof self ? $i->getPrimaryValue() : $i );
			}
		} else {
			$this->addToRelationship( $key, $value instanceof self ? $value->getPrimaryValue() : $value );
		}
	}

	/**
	 * Saves the relationship data.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private function saveRelationshipData(): void {
		foreach ( $this->getRelationships()->getAll() as $key => $relationship ) {
			$definition = $relationship->getDefinition();
			if ( ! $definition instanceof ManyToManyWithPostsContract ) {
				continue;
			}

			if ( ! empty( $this->relationshipData[ $key ]['insert'] ) ) {
				$insert_data = [];
				foreach ( $this->relationshipData[ $key ]['insert'] as $insert_id ) {
					$insert_data[] = [
						$definition->getThisEntityColumn() => $this->getPrimaryValue(),
						$definition->getOtherEntityColumn() => $insert_id,
					];
				}

				// First delete them to avoid duplicates.
				$definition->getTableInterface()::delete_many(
					$this->relationshipData[ $key ]['insert'],
					$definition->getOtherEntityColumn(),
					DB::prepare( ' AND %i = %d', $definition->getThisEntityColumn(), $this->getPrimaryValue() )
				);

				$definition->getTableInterface()::insert_many( $insert_data );
			}

			if ( ! empty( $this->relationshipData[ $key ]['delete'] ) ) {
				$definition->getTableInterface()::delete_many(
					$this->relationshipData[ $key ]['delete'],
					$definition->getOtherEntityColumn(),
					DB::prepare( ' AND %i = %d', $definition->getThisEntityColumn(), $this->getPrimaryValue() )
				);
			}
		}
	}

	/**
	 * Saves the model.
	 *
	 * @since 0.1.0
	 *
	 * @return int The id of the saved model.
	 *
	 * @throws RuntimeException If the model fails to save.
	 */
	public function save(): int {
		if ( ! $this->isDirty() ) {
			$this->saveRelationshipData();
			return $this->getPrimaryValue();
		}

		$result = $this->getTableClass()::upsert( $this->toArray() );

		if ( ! $result ) {
			throw new RuntimeException( __( 'Failed to save the model.', 'stellarwp-schema-models' ) );
		}

		$id = $this->getPrimaryValue();

		if ( ! $id ) {
			$id = DB::last_insert_id();
			$this->setAttribute( $this->getPrimaryColumn(), $id );
		}

		$this->commitChanges();

		$this->saveRelationshipData();

		return $id;
	}

	/**
	 * Deletes the model.
	 *
	 * @since 0.1.0
	 *
	 * @return bool Whether the model was deleted.
	 *
	 * @throws RuntimeException If the model ID required to delete the model is not set.
	 */
	public function delete(): bool {
		$uid = $this->getPrimaryValue();

		if ( ! $uid ) {
			throw new RuntimeException( __( 'Model ID is required to delete the model.', 'stellarwp-schema-models' ) );
		}

		$this->deleteAllRelationshipData();

		return $this->getTableClass()::delete( $uid );
	}

	/**
	 * Deletes all the relationship data.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private function deleteAllRelationshipData(): void {
		$relationships = $this->getRelationships()->getAll();
		if ( empty( $relationships ) ) {
			return;
		}

		foreach ( array_keys( $relationships ) as $key ) {
			$this->deleteRelationshipData( $key );
		}
	}

	/**
	 * Constructs a model instance from database query data.
	 *
	 * @param object|array $data The data to construct the model from.
	 * @param int          $mode The level of strictness to take when constructing the object, by default it will ignore extra keys but error on missing keys.
	 *
	 * @return static
	 *
	 * @throws InvalidArgumentException If the abstract is used directly.
	 * @throws InvalidArgumentException If the data is not an object or array.
	 * @throws InvalidArgumentException If the property does not exist.
	 * @throws InvalidArgumentException If the relationship does not exist.
	 */
	public static function fromData( $data, $mode = self::BUILD_MODE_IGNORE_EXTRA ) {
		if ( ! is_object( $data ) && ! is_array( $data ) ) {
			throw new InvalidArgumentException( 'Query data must be an object or array' );
		}

		$data = (array) $data;

		if ( self::class === static::class ) {
			throw new InvalidArgumentException( 'SchemaModel cannot be instantiated directly.' );
		}

		// @phpstan-ignore-next-line It is safe to instantiate the model like that since the constructor is final.
		$model = new static();

		foreach ( static::propertyKeys() as $key ) {
			$property_definition = static::getPropertyDefinition( $key );
			if ( $key !== $model->getPrimaryColumn() && ! array_key_exists( $key, $data ) && ! $property_definition->hasDefault() ) {
				throw new InvalidArgumentException( "Property '$key' does not exist." );
			}

			if ( ! isset( $data[ $key ] ) ) {
				continue;
			}

			$model->setAttribute( $key, static::castValueForProperty( static::getPropertyDefinition( $key ), $data[ $key ], $key ) );
		}

		foreach ( array_keys( $model->getRelationships()->getAll() ) as $key ) {
			if ( ! isset( $data[ $key ] ) ) {
				continue;
			}

			$model->setCachedRelationship( $key, $data[ $key ] );
		}

		if ( $model->getPrimaryValue() ) {
			$model->commitChanges();
		}

		return $model;
	}
}
