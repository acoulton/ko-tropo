<?php
defined('SYSPATH') or die('No direct script access.');
class Tropo_Call
{
	protected $_session_data = NULL;
	protected $_result = NULL;

	/**
	 *
	 * @param string $id
	 * @return Session
	 */
	public static function session($id)
	{
		return Session::instance(NULL, 'tropo-'.$id);
	}

	/**
	 * Gets the Tropo_Call object from a request - either creating a new call,
	 * or populating the call's session data from the sessionId contained in
	 * a result object.
	 *
	 * @param Request $request
	 * @return Tropo_Call
	 */
	public static function from_request(Request $request)
	{
		if ($request->method() != Request::POST)
		{
			throw new HTTP_Exception_405("Tropo requests are expected to submit with POST method");
		}

		// Determine whether this request has a result or a session object
		$data = json_decode($request->body(), TRUE);
		if (isset($data['session']))
		{
			// The request is a new call session
			$session_id = $data['session']['id'];
			return self::instance($session_id, $data['session']);
		}
		elseif (isset($data['result']))
		{
			// The request is a call continuation, with a result
			$session_id = $data['result']['sessionId'];
			$call = self::instance($session_id);
			$call->load_result_data($data['result']);
			return $call;
		}
		else
		{
			throw new HTTP_Exception_400("Could not interpret request body ".$request->body());
		}
	}

	/**
	 * @param string $session_id
	 * @param array $initial_data
	 * @return Tropo_Call
	 */
	public static function instance($session_id, $initial_data = NULL)
	{
		$session = self::session($session_id);

		$call = $session->get('call');

		if ($call AND $initial_data)
		{
			throw new Exception('uh-oh,conflicted call data');
		}
		elseif ($initial_data)
		{
			$call = new Tropo_Call($initial_data);
			$session->set('call', $call);
			return $call;
		}
		elseif ($call)
		{
			return $call;
		}
		else
		{
			throw new Exception('uh-oh,no call data');
		}
	}

	/**
	 * Clears any record of the call from the session
	 * @param string $session_id
	 */
	public static function cleanup($session_id)
	{
		self::session($session_id)
				->destroy();
	}

	public function __construct($data = NULL)
	{
		$this->_session_data = $data;
	}

	public function __sleep() {
		return array('_session_data');
	}

	public function from_caller_id()
	{
		return $this->_session_data['from']['id'];
	}

	public function set_from_caller_id($caller_id)
	{
		$this->_session_data['from']['id'] = $caller_id;
	}

	public function load_result_data($result)
	{
		if (isset($result['actions']) AND Arr::is_assoc($result['actions']))
		{
			$result['actions'] = array($result['actions']);
		}
		$this->_result = $result;
	}

	public function result_value($field, $default = NULL, $require = FALSE)
	{
		$actions = Arr::get($this->_result, 'actions', array());
		foreach ($actions as $action)
		{
			if ($action['name'] === $field)
			{
				return $action['value'];
			}
		}

		if ($require)
		{
			throw new InvalidArgumentException("Result field $field was not found");
		}

		return $default;
	}

}