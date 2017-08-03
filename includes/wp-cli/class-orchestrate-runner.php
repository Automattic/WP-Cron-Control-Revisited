<?php

namespace Automattic\WP\Cron_Control\CLI;

/**
 * Commands used by the Go-based runner to execute events
 */
class Orchestrate_Runner extends \WP_CLI_Command {
	/**
	 * List the next set of events to run; meant for Runner
	 *
	 * Will not be all events, just those atop the curated queue
	 *
	 * Not intended for human use, rather it powers the Go-based Runner. Use the `events list` command instead.
	 *
	 * @subcommand list-due-batch
	 */
	public function list_due_now( $args, $assoc_args ) {
		if ( 0 !== \Automattic\WP\Cron_Control\Events::instance()->run_disabled() ) {
			\WP_CLI::error( __( 'Automatic event execution is disabled', 'automattic-cron-control' ) );
		}

		$events = \Automattic\WP\Cron_Control\Events::instance()->get_events();

		$format = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );

		\WP_CLI\Utils\format_items( $format, $events['events'], array(
			'timestamp',
			'action',
			'instance',
		) );
	}

	/**
	 * Run a given event; meant for Runner
	 *
	 * Not intended for human use, rather it powers the Go-based Runner. Use the `events run` command instead.
	 *
	 * @subcommand run
	 * @synopsis --timestamp=<timestamp> --action=<action-hashed> --instance=<instance>
	 */
	public function run_event( $args, $assoc_args ) {
		if ( 0 !== \Automattic\WP\Cron_Control\Events::instance()->run_disabled() ) {
			\WP_CLI::error( __( 'Automatic event execution is disabled', 'automattic-cron-control' ) );
		}

		$timestamp = \WP_CLI\Utils\get_flag_value( $assoc_args, 'timestamp', null );
		$action    = \WP_CLI\Utils\get_flag_value( $assoc_args, 'action',    null );
		$instance  = \WP_CLI\Utils\get_flag_value( $assoc_args, 'instance',  null );

		if ( ! is_numeric( $timestamp ) ) {
			\WP_CLI::error( __( 'Invalid timestamp', 'automattic-cron-control' ) );
		}

		if ( ! is_string( $action ) ) {
			\WP_CLI::error( __( 'Invalid action', 'automattic-cron-control' ) );
		}

		if( ! is_string( $instance ) ) {
			\WP_CLI::error( __( 'Invalid instance', 'automattic-cron-control' ) );
		}

		// Prepare environment
		if ( ! defined( 'DOING_CRON' ) ) {
			define( 'DOING_CRON', true );
		}

		$now = time();
		if ( $timestamp > $now ) {
			\WP_CLI::error( sprintf( __( 'Given timestamp is for %1$s GMT, %2$s from now. The event\'s existence was not confirmed, and no attempt was made to execute it.', 'automattic-cron-control' ), date_i18n( TIME_FORMAT, $timestamp ), human_time_diff( $now, $timestamp ) ) );
		}

		// Run the event
		$run = \Automattic\WP\Cron_Control\run_event( $timestamp, $action, $instance );

		if ( is_wp_error( $run ) ) {
			\WP_CLI::error( $run->get_error_message() );
		} elseif ( isset( $run['success'] ) && true === $run['success'] ) {
			\WP_CLI::success( $run['message'] );
		} else {
			\WP_CLI::error( $run['message'] );
		}
	}

	/**
	 * Get some details needed to execute events; meant for Runner
	 *
	 * Not intended for human use, rather it powers the Go-based Runner. Use the `orchestrate manage-automatic-execution` command instead.
	 *
	 * @subcommand get-info
	 */
	public function get_info( $args, $assoc_args ) {
		$info = array(
			array(
				'multisite' => is_multisite() ? 1 : 0,
				'siteurl'   => site_url(),
				'disabled'  => \Automattic\WP\Cron_Control\Events::instance()->run_disabled(),
			),
		);

		$format = \WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );

		\WP_CLI\Utils\format_items( $format, $info, array_keys( $info[0] ) );
	}
}

\WP_CLI::add_command( 'cron-control orchestrate runner-only', 'Automattic\WP\Cron_Control\CLI\Orchestrate_Runner' );