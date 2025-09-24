<?php

namespace StellarWP\SchemaModels;

use lucatume\WPBrowser\TestCase\WPTestCase;
use DateTime;
use StellarWP\SchemaModels\Tests\Schema\MockModelSchema;

class SchemaModelTest extends WPTestCase {
	public function test_save() {
		$model_data = [
			'lastName'     => 'Angelo',
			'emails'       => [ 'angelo@stellarwp.com', 'michael@stellarwp.com' ],
			'microseconds' => microtime( true ),
			'int'          => '1234567890',
			'date'         => new DateTime( '2023-06-13 17:25:00' ),
		];

		$model = MockModelSchema::fromData( $model_data, 1 );
		$this->assertTrue( $model->isDirty() );
		$id = $model->save();
		$this->assertFalse( $model->isDirty() );

		$this->assertIsInt( $id );
		$this->assertGreaterThan( 0, $id );

		$this->assertEquals( 'Michael', $model->get_firstName() );
		$this->assertEquals( $model_data['lastName'], $model->get_lastName() );
		$this->assertEquals( $model_data['emails'], $model->get_emails() );
		$this->assertEquals( $model_data['microseconds'], $model->get_microseconds() );
		$this->assertEquals( $model_data['int'], $model->get_int() );
		$this->assertEquals( $model_data['date'], $model->get_date() );
	}

	public function test_queries_returns_models() {
		$model_data = [
			[
				'lastName' => 'Angelo',
				'emails'   => [ 'michael@stellarwp.com', 'angelo@stellarwp.com' ],
				'microseconds' => microtime( true ),
				'int'          => '1234567890',
				'date'         => new DateTime( '2023-06-13 17:25:00' ),
			],
			[
				'firstName'    => 'Dimi',
				'lastName'     => 'Dimitrov',
				'emails'       => 'dimi@stellarwp.com',
				'microseconds' => 10.0 + microtime( true ),
				'int'          => '1234567890',
				'date'         => new DateTime( '2024-06-13 17:25:00' ),
			],
		];

		$models = [];

		foreach ( $model_data as $data ) {
			$model = MockModelSchema::fromData( $data, 1 );
			$model->save();
			$models[] = $model;
		}

		$table = $models[0]->getTableInterface();

		$results = iterator_to_array($table::get_all());

		$this->assertCount( 2, $results );
		$this->assertInstanceOf( MockModelSchema::class, $results[0] );
		$this->assertInstanceOf( MockModelSchema::class, $results[1] );
		$this->assertEquals( $models[0]->get_id(), $results[0]->get_id() );
		$this->assertEquals( $models[1]->get_id(), $results[1]->get_id() );
		$this->assertEquals( $models[0]->get_firstName(), $results[0]->get_firstName() );
		$this->assertEquals( $models[1]->get_firstName(), $results[1]->get_firstName() );
		$this->assertEquals( $models[0]->get_lastName(), $results[0]->get_lastName() );
		$this->assertEquals( $models[1]->get_lastName(), $results[1]->get_lastName() );
		$this->assertEquals( $models[0]->get_emails(), $results[0]->get_emails() );
		$this->assertEquals( $models[1]->get_emails(), $results[1]->get_emails() );
		$this->assertEquals( $models[0]->get_microseconds(), $results[0]->get_microseconds() );
		$this->assertEquals( $models[1]->get_microseconds(), $results[1]->get_microseconds() );
		$this->assertEquals( $models[0]->get_int(), $results[0]->get_int() );
		$this->assertEquals( $models[1]->get_int(), $results[1]->get_int() );
		$this->assertEquals( $models[0]->get_date(), $results[0]->get_date() );
		$this->assertEquals( $models[1]->get_date(), $results[1]->get_date() );
	}

	public function test_delete() {
		$model_data = [
			'lastName' => 'Angelo',
			'emails'   => [ 'michael@stellarwp.com', 'angelo@stellarwp.com' ],
			'microseconds' => microtime( true ),
			'int'          => '1234567890',
			'date'         => new DateTime( '2023-06-13 17:25:00' ),
		];

		$model = MockModelSchema::fromData( $model_data, 1 );
		$model->save();
		$this->assertInstanceOf( MockModelSchema::class, $model->getTableInterface()::get_by_id( $model->get_id() ) );
		$this->assertGreaterThan( 0, $model->get_id() );
		$this->assertTrue( $model->delete() );
		$this->assertNull( $model->getTableInterface()::get_by_id( $model->get_id() ) );
	}
}
