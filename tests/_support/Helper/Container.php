<?php
/**
 * Schema Models's container for tests.
 *
 * @since 0.1.0
 *
 * @package StellarWP\SchemaModels\Tests
 */

declare( strict_types=1 );

namespace StellarWP\SchemaModels\Tests;

use StellarWP\ContainerContract\ContainerInterface;

use lucatume\DI52\Container as DI52_Container;

/**
 * Schema Models's container for tests.
 *
 * @since 0.1.0
 *
 * @package StellarWP\SchemaModels\Tests
 */
class Container extends DI52_Container implements ContainerInterface {}
