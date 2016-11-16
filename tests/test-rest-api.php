<?php
/**
 * Class WPCCR_REST_API_Test
 *
 * @package WP_Cron_Control_Revisited
 */

/**
 * Sample test case.
 */
class WPCCR_REST_API_Test extends WP_UnitTestCase {
	/**
	 * Prepare for REST API tests
	 */
	public function setUp() {
		parent::setUp();

		global $wp_rest_server;
		$this->server = $wp_rest_server = new WP_REST_Server;
		do_action( 'rest_api_init' );
	}

	/**
	 * Verify that GET requests to the endpoint fail
	 */
	public function test_invalid_request() {
		$request = new WP_REST_Request( 'GET', '/' . WP_Cron_Control_Revisited\REST_API_NAMESPACE . '/' . WP_Cron_Control_Revisited\REST_API_ENDPOINT_LIST );
		$response = $this->server->dispatch( $request );
		$this->assertResponseStatus( 404, $response );
	}

	/**
	 * Test that list endpoint returns expected format
	 */
	public function test_get_items() {
		$ev = WP_Cron_Control_Revisited_Tests\Utils::create_test_event();

		$request = new WP_REST_Request( 'POST', '/' . WP_Cron_Control_Revisited\REST_API_NAMESPACE . '/' . WP_Cron_Control_Revisited\REST_API_ENDPOINT_LIST );
		$request->set_body( wp_json_encode( array( 'secret' => WP_CRON_CONTROL_SECRET, ) ) );
		$request->set_header( 'content-type', 'application/json' );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertResponseStatus( 200, $response );
		$this->assertArrayHasKey( 'events', $data );
		$this->assertArrayHasKey( 'endpoint', $data );

		$this->assertResponseData( array(
			'events'   => array(
				array(
					'timestamp' => $ev['timestamp'],
					'action'    => md5( $ev['action'] ),
					'instance'  => md5( maybe_serialize( $ev['args'] ) ),
				),
			),
			'endpoint' => get_rest_url( null, WP_Cron_Control_Revisited\REST_API_NAMESPACE . '/' . WP_Cron_Control_Revisited\REST_API_ENDPOINT_RUN ),
		), $response );
	}

	/**
	 * Test that list endpoint returns expected format
	 */
	public function test_run_event() {
		$ev = WP_Cron_Control_Revisited_Tests\Utils::create_test_event();
		$ev['action'] = md5( $ev['action'] );
		$ev['instance'] = md5( maybe_serialize( $ev['args'] ) );
		$ev['secret'] = WP_CRON_CONTROL_SECRET;
		unset( $ev['args'] );

		$request = new WP_REST_Request( 'PUT', '/' . WP_Cron_Control_Revisited\REST_API_NAMESPACE . '/' . WP_Cron_Control_Revisited\REST_API_ENDPOINT_RUN );
		$request->set_body( wp_json_encode( $ev ) );
		$request->set_header( 'content-type', 'application/json' );

		$response = $this->server->dispatch( $request );
		$data     = $response->get_data();

		$this->assertResponseStatus( 200, $response );
		$this->assertArrayHasKey( 'success', $data );
		$this->assertArrayHasKey( 'message', $data );


	}

	/**
	 * Check response code
	 */
	protected function assertResponseStatus( $status, $response ) {
		$this->assertEquals( $status, $response->get_status() );
	}

	/**
	 * Ensure response includes the expected data
	 */
	protected function assertResponseData( $data, $response ) {
		WP_Cron_Control_Revisited_Tests\Utils::compare_arrays( $data, $response->get_data(), $this );
	}

	/**
	 * Clean up
	 */
	public function tearDown() {
		parent::tearDown();

		global $wp_rest_server;
		$wp_rest_server = null;
	}

}