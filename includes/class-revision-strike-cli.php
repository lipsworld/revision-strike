<?php
/**
 * WP-CLI commands for Revision Strike.
 *
 * @package Revision Strike
 * @author Steve Grunwell
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Remove revisions on old posts to clean up the database.
 */
class RevisionStrikeCLI extends WP_CLI {

	/**
	 * The current instance of the RevisionStrike class.
	 *
	 * @var RevisionStrike $instance
	 */
	protected $instance;

	/**
	 * Remove old post revisions.
	 *
	 * ## OPTIONS
	 *
	 * [--days=<days>]
	 * : Remove revisions on posts published at least <days> days ago. This is
	 * determined by the value set on Settings > Writing or a default of 30.
	 *
	 * [--limit=<limit>]
	 * : The maximum number of revisions to remove. This is determined by the
	 * value set on Settings > Writing or a default value of 50.
	 *
	 * [--post_type=<post_type>]
	 * : One or more post types (comma-separated) for which revisions should be
	 * struck. Default value is 'post'.
	 *
	 * [--verbose]
	 * : Enable verbose logging of deleted revisions.
	 *
	 * ## EXAMPLES
	 *
	 *   wp revision-strike clean
	 *   wp revision-strike clean --days=45
	 *   wp revision-strike clean --limit=75
	 *   wp revision-strike clean --post_type=post,page
	 *
	 * @synopsis [--days=<days>] [--limit=<limit>] [--post_type=<post_type>] [--verbose]
	 *
	 * @param array $args       A numeric array of position-based arguments.
	 * @param array $assoc_args An associative array of key-based arguments.
	 */
	public function clean( $args, $assoc_args ) {
		if ( isset( $assoc_args['verbose'] ) ) {
			add_action( 'wp_delete_post_revision', function ( $revision_id ) {
				WP_CLI::log( sprintf(
					/* Translators: %1$d represents the ID of the deleted revision. */
					esc_html__( 'Revision ID %1$d has been deleted.', 'revision-strike' ),
					$revision_id
				) );
			} );
		}

		$instance = $this->get_instance();
		$args     = array();

		foreach ( array( 'days', 'limit', 'post_type' ) as $arg ) {
			if ( isset( $assoc_args[ $arg ] ) ) {
				$args[ $arg ] = $assoc_args[ $arg ];
			}
		}

		$instance->strike( $args );

		WP_CLI::line();

		$stats = $instance->get_stats();
		if ( 0 === $stats['deleted'] ) {
			$message = __( 'No errors occurred, but no post revisions were removed.', 'revision-strike' );

		} elseif ( 1 === $stats['deleted'] ) {
			$message = __( 'One post revision was deleted successfully', 'revision-strike' );

		} else {
			/* Translators: %1$d represents the number of deleted revisions. */
			$message = sprintf( _n(
				'%1$d post revision was deleted successfully',
				'%1$d post revisions were deleted successfully',
				$stats['deleted'],
				'revision-strike'
			), $stats['deleted'] );
		}

		WP_CLI::success( esc_html( $message ) );
	}

	/**
	 * Remove *all* old post revisions from the WordPress database.
	 *
	 * This command will recursively strike all the old post revisions from the database,
	 * giving a site a fresh start that the regular Revision Strike cron job can maintain.
	 *
	 * ## OPTIONS
	 *
	 * [--days=<days>]
	 * : Remove revisions on posts published at least <days> days ago. This is
	 * determined by the value set on Settings > Writing or a default of 30.
	 *
	 * [--post_type=<post_type>]
	 * : One or more post types (comma-separated) for which revisions should be
	 * struck. Default value is 'post'.
	 *
	 * [--verbose]
	 * : Enable verbose logging of deleted revisions.
	 *
	 * ## EXAMPLES
	 *
	 *   wp revision-strike clean-all
	 *   wp revision-strike clean-all --days=45
	 *   wp revision-strike clean-all --post_type=post,page
	 *
	 * @synopsis [--days=<days>] [--post_type=<post_type>] [--verbose]
	 * @alias clean-all
	 *
	 * @param array $args       A numeric array of position-based arguments.
	 * @param array $assoc_args An associative array of key-based arguments.
	 */
	public function clean_all( $args, $assoc_args ) {
		$instance = $this->get_instance();
		$assoc_args['limit'] = $instance->count_eligible_revisions(
			isset( $assoc_args['days'] ) ? $assoc_args['days'] : $instance->settings->get_option( 'days' ),
			isset( $assoc_args['post_type'] ) ? $assoc_args['post_type'] : $instance->settings->get_option( 'post_type' )
		);

		return $this->clean( $args, $assoc_args );
	}

	/**
	 * Get the current RevisionStrike instance.
	 *
	 * @return RevisionStrike The current instance in $this->instance.
	 */
	protected function get_instance() {
		if ( null === $this->instance ) {
			$this->instance = new RevisionStrike();
		}
		return $this->instance;
	}
}

WP_CLI::add_command( 'revision-strike', 'RevisionStrikeCLI' );
