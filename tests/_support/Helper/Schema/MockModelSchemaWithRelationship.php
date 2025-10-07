<?php

namespace StellarWP\SchemaModels\Tests\Schema;

use StellarWP\SchemaModels\SchemaModel;
use StellarWP\Schema\Tables\Contracts\Table as Table_Interface;
use StellarWP\Models\ValueObjects\Relationship;
use StellarWP\SchemaModels\Relationships\ManyToManyWithPosts;

class MockModelSchemaWithRelationship extends SchemaModel {
	protected static function relationships(): array {
		return [
			'posts' => ( new ManyToManyWithPosts( 'posts' ) )
			->setTableInterface( MockRelationshipTable::class )
			->setThisEntityColumn( 'mock_model_id' )
			->setOtherEntityColumn( 'post_id' )
			->setHydrateWith( fn( int $post_id ) => get_post( $post_id ) )
			->setValidateSanitizeRelationshipWith( function ( $post_or_post_id ) {
				$p = get_post( $post_or_post_id );
				if ( ! $p instanceof \WP_Post ) {
					throw new \InvalidArgumentException( 'Post must be a valid WP_Post object.' );
				}

				return $p->ID;
			}),
		];
	}

	public static function getTableInterface(): Table_Interface {
		return new MockRelationshipModelTable();
	}
}
