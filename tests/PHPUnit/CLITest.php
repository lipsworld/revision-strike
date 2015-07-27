<?php
/**
 * Tests for the plugin's bootstrapping.
 *
 * @package Revision Strike
 * @author Steve Grunwell
 */

namespace Grunwell\RevisionStrike;

use WP_Mock as M;
use Mockery;
use ReflectionProperty;
use RevisionStrike;
use RevisionStrikeCLI;

class CLITest extends TestCase {

	protected $testFiles = [
		'class-revision-strike.php',
		'class-revision-strike-cli.php',
	];

	public function test_clean() {
		$cli = new RevisionStrikeCLI;

		M::wpPassthruFunction( 'esc_html__' );

		M::expectActionAdded( 'wp_delete_post_revision', array( $cli, 'count_deleted_revision' ) );
		M::expectAction( RevisionStrike::STRIKE_ACTION, false );

		$cli->clean( array(), array() );
	}

	public function test_clean_with_days_argument() {
		$cli = new RevisionStrikeCLI;

		M::wpPassthruFunction( 'absint', array(
			'times' => 1,
			'args'  => array( 7 ),
		) );

		M::expectAction( RevisionStrike::STRIKE_ACTION, 7 );

		$cli->clean( array(), array( 'days' => 7 ) );
	}

	public function test_clean_with_verbose_argument() {
		$cli = new RevisionStrikeCLI;

		M::expectActionAdded(
			'wp_delete_post_revision',
			array( $cli, 'log_deleted_revision' ),
			10,
			2
		);

		M::expectAction( RevisionStrike::STRIKE_ACTION, false );

		$cli->clean( array(), array( 'verbose' => true ) );
	}

	public function test_clean_reporting() {
		$cli = new RevisionStrikeCLI;

		$property = new ReflectionProperty( $cli, 'progress' );
		$property->setAccessible( true );
		$property->setValue( $cli, 5 );

		M::wpPassthruFunction( 'esc_html__', array(
			'times' => 0,
		) );
		M::wpPassthruFunction( '_n' );

		M::expectAction( RevisionStrike::STRIKE_ACTION, false );

		$cli->clean( array(), array() );
	}

}