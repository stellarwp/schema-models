<?php

namespace StellarWP\SchemaModels\Tests\Schema;

use StellarWP\SchemaModels\SchemaModel;
use StellarWP\Schema\Tables\Contracts\Table as Table_Interface;

class MockModelSchema extends SchemaModel {
	public static function getTableInterface(): Table_Interface {
		return new MockModelTable();
	}
}
