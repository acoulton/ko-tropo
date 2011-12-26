<?php
defined('SYSPATH') or die('No direct script access.');
/**
 * Tests for the Tropo_Call class
 *
 * @package    Tropo
 * @category   Test
 * @author     Andrew Coulton
 * @copyright  (c) 2011 Ingenerator
 * @group      tropo
 * @group      tropo.call
 * @group	   wip
 * @license    http://kohanaphp.com/license
 */
class Tropo_CallTest extends Unittest_TestCase
{

	public function reset_session() {
		// Reset session state for testing
		Session::instance()
			->destroy();
		Session::$instances = array();
	}

	public function test_should_create_new_call_from_request_with_session_payload()
	{
		$this->reset_session();
		$request = new Request('foo');
		$request->method(Request::POST)
				->body(json_encode(array(
					'session'=>array(
						'id' => 'new-call-session',
						'from' => array(
							'id' => 'caller'
						)))));
		$call = Tropo_Call::from_request($request);
		$this->assertInstanceOf('Tropo_Call', $call);
		$this->assertEquals('caller', $call->from_caller_id());
	}

	public function test_should_restore_session_from_request_with_result_payload()
	{
		$request = new Request('foo');
		$request->method(Request::POST)
				->body(json_encode(array(
					'result'=>array(
						'sessionId' => 'new-call-session',
						))));
		$call = Tropo_Call::from_request($request);
		$this->assertInstanceOf('Tropo_Call', $call);
		$this->assertEquals('caller', $call->from_caller_id());
	}

	public function test_should_assign_result_from_request_with_result_payload()
	{
		$request = new Request('foo');
		$request->method(Request::POST)
				->body(json_encode(array(
					'result'=>array(
						'sessionId' => 'new-call-session',
						'actions' => array(
							'name' => 'foo',
							'value' => 'bar'
						)
						))));
		$call = Tropo_Call::from_request($request);
		$this->assertInstanceOf('Tropo_Call', $call);
		$this->assertEquals('bar', $call->result_value('foo'));
	}

	/**
	 * @expectedException HTTP_Exception_405
	 */
	public function test_should_fail_on_request_method_other_than_post()
	{
		$this->reset_session();
		$request = new Request('foo');
		$request->body(json_encode(array('session'=>array())));
		Tropo_Call::from_request($request);
	}

	/**
	 * @expectedException HTTP_Exception_400
	 */
	public function test_should_fail_on_unrecognised_payload()
	{
		$this->reset_session();
		$request = new Request('foo');
		$request->method(Request::POST)
				->body(json_encode(array('foo'=>array())));
		Tropo_Call::from_request($request);
	}

	public function test_should_fail_when_providing_initial_data_to_existing_session()
	{
		$this->markTestIncomplete();
	}

	public function test_should_create_new_call_and_store_in_kohana_session()
	{
		$this->markTestIncomplete();
	}

	/**
	 * @expectedException Exception
	 */
	public function test_should_fail_when_call_does_not_exist_and_no_initial_data()
	{
		$this->reset_session();
		$call = Tropo_Call::instance('no-call-here');
	}

	public function test_should_clean_up_sessions_when_requested()
	{
		$this->reset_session();
		// Put some rubbish in a session
		$session = Tropo_Call::session('should-clean');
		$session->set('foo', 'rubbish');

		// Clear the session
		Tropo_Call::cleanup('should-clean');

		// Get the session again and test it's empty
		$session = Tropo_Call::session('should-clean');
		$this->assertEquals(array(), $session->as_array());
	}

	public function test_should_return_from_caller_id()
	{
		$call = new Tropo_Call(array(
			'from' => array(
				'id' => '01317185000'
			)));
		$this->assertEquals('01317185000',$call->from_caller_id());

		return $call;
	}

	/**
	 * @depends test_should_return_from_caller_id
	 */
	public function test_should_allow_from_caller_id_to_be_set($call)
	{
		$call = new Tropo_Call(array());
		$call->set_from_caller_id('foo-bar');
		$this->assertEquals('foo-bar', $call->from_caller_id());
		return $call;
	}
	
	public function provider_should_report_when_from_caller_id_unknown()
	{
		return array(
			array(0, TRUE),
			array('Unknown', TRUE),
			array('441317185666', FALSE)
			);
	}
	
	/**
	 * @dataProvider provider_should_report_when_from_caller_id_unknown()
	 */
	public function test_should_report_when_from_caller_id_unknown($id, $expected)
	{
		$call = new Tropo_Call(array(
			'from' => array(
				'id' => $id
			)));
		$this->assertEquals($expected, $call->from_caller_unknown());
	}

	public function test_should_return_result_value_when_exists()
	{
		$call = new Tropo_Call(array());
		$call->load_result_data(array(
			'actions' => array(
				'name' => 'foo',
				'value' => 'bar'
			)));
		$this->assertEquals('bar', $call->result_value('foo'));
		return $call;
	}

	public function test_should_return_default_when_value_not_exists_and_not_require()
	{
		$call = new Tropo_Call(array());
		$this->assertTrue($call->result_value('foo', TRUE));
		$this->assertNull($call->result_value('foo'));
	}

	/**
	 * @expectedException InvalidArgumentException
	 */
	public function test_should_fail_when_required_result_value_not_exists()
	{
		$call = new Tropo_Call(array());
		$fail = $call->result_value('foo', NULL, TRUE);
	}

	/**
	 * @depends test_should_return_result_value_when_exists
	 */
	public function test_should_not_serialise_result_object($call)
	{
		$serialised = serialize($call);
		$call = unserialize($serialised);
		$this->assertNull($call->result_value('foo'));
	}

	/**
	 * @depends test_should_allow_from_caller_id_to_be_set
	 * @param Tropo_Call $call
	 */
	public function test_should_serialise_session_object(Tropo_Call $call)
	{
		$serialised = serialize($call);
		$call = unserialize($serialised);
		$this->assertEquals('foo-bar', $call->from_caller_id());
	}


}