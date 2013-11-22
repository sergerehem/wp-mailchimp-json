<?php
/*
Controller name: Core
Controller description: Basic introspection methods
*/
//$dir = json_api_dir();
//include_once "mailchimp-api/MailChimp.class.php";
//@include_once "MailChimp.class.php";

class JSON_API_Core_Controller {
	private $api_key = "82637b83575e30573f9bfac888ce0d5c-us3";
	private $api_endpoint = 'https://us3.api.mailchimp.com/2.0/';
	private $verify_ssl   = false;
/*
  public function info() {
    global $json_api;
    $php = '';
    if (!empty($json_api->query->controller)) {
      return $json_api->controller_info($json_api->query->controller);
    } else {
      $dir = json_api_dir();
      if (file_exists("$dir/json-api.php")) {
        $php = file_get_contents("$dir/json-api.php");
      } else {
        // Check one directory up, in case json-api.php was moved
        $dir = dirname($dir);
        if (file_exists("$dir/json-api.php")) {
          $php = file_get_contents("$dir/json-api.php");
        }
      }
      if (preg_match('/^\s*Version:\s*(.+)$/m', $php, $matches)) {
        $version = $matches[1];
      } else {
        $version = '(Unknown)';
      }
      $active_controllers = explode(',', get_option('json_api_controllers', 'core'));
      $controllers = array_intersect($json_api->get_controllers(), $active_controllers);
      return array(
        'json_api_version' => $version,
        'controllers' => array_values($controllers)
      );
    }
  }
*/

	//http://localhost/wordpress/?json=mailchimp_lists
  public function mailchimp_lists() {
		$ret = $this->mailchimp_call('lists/list');
    return $this->list_results($ret);
  }

	// http://localhost/wordpress/?json=mailchimp_list_groups&id=438cb5c612
  public function mailchimp_list_groups() {
    global $json_api;
		extract($json_api->query->get(array('id')));

		$ret = $this->mailchimp_call('lists/interest-groupings', array(
                'id'                => $id,
                'counts'            => true,
            ));

    return $this->list_results($ret);

  }

	// http://localhost/wordpress/?json=mailchimp_list_subscribe&id=438cb5c612&email=serge.rehem@gmail.com
  public function mailchimp_list_subscribe() {
    global $json_api;
		extract($json_api->query->get(array('id','email')));

		$ret = $this->mailchimp_call('lists/subscribe', array(
                'id'                => $id,
                'email'             => array('email'=>$email),
//                'merge_vars'        => array('FNAME'=>'Davy', 'LNAME'=>'Jones'),
                'double_optin'      => false, //TODO: mudar se quiser
                'update_existing'   => true,
                'replace_interests' => false,
                'send_welcome'      => false,
            ));
		return $ret;
	}

  public function get_mailchimp_list_info() {
    global $json_api;
		extract($json_api->query->get(array('id','name')));
		if ($id || $name) {
		  return array(
				'status' => 'OK',
		    'name' => 'LIST '.$id .' NAME '.$name,
		    'count' => 10
		  );
		}
	  return array(
	    'status' => 'ERROR',
	    'message' => 'list '.$id.' not found'
	  );
  }

  protected function list_results($lists) {
    global $wp_query;
    return array(
      'count' => count($lists),
      'lists' => $lists
    );
  }
 
 /**
	 * Call an API method. Every request needs the API key, so that is added automatically -- you don't need to pass it in.
	 * @param  string $method The API method to call, e.g. 'lists/list'
	 * @param  array  $args   An array of arguments to pass to the method. Will be json-encoded for you.
	 * @return array          Associative array of json decoded API response.
	 */
	public function mailchimp_call($method, $args=array())
	{
		return $this->_raw_request($method, $args);
	}

	/**
	 * Performs the underlying HTTP request. Not very exciting
	 * @param  string $method The API method to be called
	 * @param  array  $args   Assoc array of parameters to be passed
	 * @return array          Assoc array of decoded result
	 */
	private function _raw_request($method, $args=array())
	{      
		$args['apikey'] = $this->api_key;

		$url = $this->api_endpoint.'/'.$method.'.json';

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		curl_setopt($ch, CURLOPT_USERAGENT, 'PHP-MCAPI/2.0');		
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verify_ssl);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($args));
		$result = curl_exec($ch);
		curl_close($ch);

		return $result ? json_decode($result, true) : false;
	}
}

