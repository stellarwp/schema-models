<?php

namespace StellarWP\SchemaModels\Tests\Schema;

use StellarWP\SchemaModels\SchemaModel;
use StellarWP\Schema\Tables\Contracts\Table_Interface;
use StellarWP\Models\ValueObjects\Relationship;

class MockModelSchemaWithRelationship extends SchemaModel {
	protected function constructRelationships(): void {
		$this->defineRelationship( 'posts', Relationship::MANY_TO_MANY, MockRelationshipTable::class );
		$this->defineRelationshipColumns( 'posts', 'mock_model_id', 'post_id' );
	}

	public function getTableInterface(): Table_Interface {
		return new MockRelationshipModelTable();
	}
}
