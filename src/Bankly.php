<?php

namespace BanklyPHP;

class Bankly {
	static $API_ENDPOINT = 'https://api.acessobank.com.br/baas';
	static $LOGIN_ENDPOINT = 'https://login.acessobank.com.br';

	private $client_id;
	private $client_secret;
	private $token_expiry = 0;
	private $token = null;
	public $debug;

	function __construct($client_id, $client_secret) {
		$this->client_id = $client_id;
		$this->client_secret = $client_secret;
		$this->debug = function($msg) {};
	}

	private function _uuidv4() {
		// from https://www.php.net/manual/pt_BR/function.uniqid.php#94959
		return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
	      mt_rand(0, 0xffff), mt_rand(0, 0xffff),
	      mt_rand(0, 0xffff),
	      mt_rand(0, 0x0fff) | 0x4000,
	      mt_rand(0, 0x3fff) | 0x8000,
	      mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
	    );
	}

	private function _get($endpoint, $variables = array()) {
		if (time() > $this->token_expiry) {
			call_user_func($this->debug, "Token has expired");
			$this->_doAuth();
		}

		if (function_exists('curl_init')) {
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, self::$API_ENDPOINT . $endpoint . '?' . http_build_query($variables));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Authorization: Bearer ' . $this->token,
				'X-Correlation-ID: ' . $this->_uuidv4(),
				'API-Version: 1.0'
			));
			
			$req = curl_exec($ch);
			$err = curl_error($ch);

			if ($err)
				throw $err;
			else {
				$req = json_decode($req);
				return $req;
			}
		} else {
			$ctx = stream_context_create(array(
				'http' => array(
					'header' => "Authorization: Bearer " . $this->token . "\r\n".
								"X-Correlation-ID: " . $this->_uuidv4() . "\r\n".
								"API-Version: 1.0"
				)
			));

			$req = file_get_contents(self::$API_ENDPOINT . $endpoint . '?' . http_build_query($variables), false, $ctx);

			if ($req) {
				return json_decode($req);
			} else {
				throw new Error("Unable to request");
			}
		}
	}

	private function _post($endpoint, $variables = array()) {
		if (time() > $this->token_expiry) {
			call_user_func($this->debug, "Token has expired");
			$this->_doAuth();
		}

		if (function_exists('curl_init')) {
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, self::$API_ENDPOINT . $endpoint);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($variables));
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-type: application/x-www-form-urlencoded',
				'Authorization: Bearer ' . $this->token,
				'X-Correlation-ID: ' . $this->_uuidv4(),
				'API-Version: 1.0'
			));
			
			$req = curl_exec($ch);
			$err = curl_error($ch);

			if ($err)
				throw $err;
			else {
				$req = json_decode($req);
				return $req;
			}
		} else {
			$ctx = stream_context_create(array(
				'http' => array(
					'method' => 'POST',
					'header' => "Content-type: application/x-www-form-urlencoded\r\n" .
								"Authorization: Bearer " . $this->token . "\r\n".
								"X-Correlation-ID: " . $this->_uuidv4() . "\r\n".
								"API-Version: 1.0",
					'content' => http_build_query($variables)
				)
			));

			$req = file_get_contents(self::$API_ENDPOINT . $endpoint, false, $ctx);

			if ($req) {
				return json_decode($req);
			} else {
				throw new Error("Unable to request");
			}
		}
	}

	private function _postJSON($endpoint, $variables = array()) {
		if (time() > $this->token_expiry) {
			call_user_func($this->debug, "Token has expired");
			$this->_doAuth();
		}

		if (function_exists('curl_init')) {
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, self::$API_ENDPOINT . $endpoint);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($variables));
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-type: application/json',
				'Authorization: Bearer ' . $this->token,
				'X-Correlation-ID: ' . $this->_uuidv4(),
				'API-Version: 1.0'
			));
			
			$req = curl_exec($ch);
			$err = curl_error($ch);

			if ($err)
				throw $err;
			else {
				$req = json_decode($req);
				return $req;
			}
		} else {
			$ctx = stream_context_create(array(
				'http' => array(
					'method' => 'POST',
					'header' => "Content-type: application/json\r\n" .
								"Authorization: Bearer " . $this->token . "\r\n".
								"X-Correlation-ID: " . $this->_uuidv4() . "\r\n".
								"API-Version: 1.0",
					'content' => json_encode($variables)
				)
			));

			$req = file_get_contents(self::$API_ENDPOINT . $endpoint, false, $ctx);

			if ($req) {
				return json_decode($req);
			} else {
				throw new Error("Unable to request");
			}
		}
	}

	private function _doAuth() {
		call_user_func($this->debug, "Will perform auth");

		if (function_exists('curl_init')) {
			$ch = curl_init();

			curl_setopt($ch, CURLOPT_URL, self::$LOGIN_ENDPOINT . '/connect/token');
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(array(
				'grant_type' => 'client_credentials',
				'client_id' => $this->client_id,
				'client_secret' => $this->client_secret
			)));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-type: application/x-www-form-urlencoded'
			));
			
			$req = curl_exec($ch);
			$err = curl_error($ch);

			if ($err)
				throw $err;
			else {
				$req = json_decode($req);
				call_user_func($this->debug, "Access token retrieved");
				$this->token = $req->access_token;

				// save next token expiration
				// with a 60 seconds security offset
				$this->token_expiry = time() + $req->expires_in - 60;
			}
		} else {
			$ctx = stream_context_create(array(
				'http' => array(
					'method' => 'POST',
					'header' => "Content-type: application/x-www-form-urlencoded",
					'content' => http_build_query(array(
						'grant_type' => 'client_credentials',
						'client_id' => $this->client_id,
						'client_secret' => $this->client_secret
					))
				)
			));

			$req = file_get_contents(self::$LOGIN_ENDPOINT . '/connect/token', false, $ctx);

			if ($req) {
				$req = json_decode($req);
				call_user_func($this->debug, "Access token retrieved");
				$this->token = $req->access_token;

				// save next token expiration
				// with a 60 seconds security offset
				$this->token_expiry = time() + $req->expires_in - 60;
			} else {
				throw new Error("Unable to request");
			}
		}
	}

	public function getBalance($branch, $account) {
		return $this->_get('/account/balance', array(
			'branch' => $branch,
			'account' => $account
		));
	}

	public function getStatement($branch, $account, $offset, $limit, $details = true, $detailsLevelBasic = true) {
		$details = $details ? "true" : "false";
		$detailsLevelBasic = $detailsLevelBasic ? "true" : "false";

		return $this->_get('/account/statement', array(
			'branch' => $branch,
			'account' => $account,
			'offset' => $offset,
			'limit' => $limit,
			'details' => $details,
			'detailsLevelBasic' => $detailsLevelBasic
		));
	}

	public function getEvents($branch, $account, $page, $pagesize, $include_details = true) {
		$include_details = $include_details ? "true" : "false";

		return $this->_get('/events', array(
			'Branch' => $branch,
			'Account' => $account,
			'Page' => $page,
			'Pagesize' => $pagesize,
			'IncludeDetails' => $include_details
		));
	}

	public function transfer($amount, $description, $sender, $recipient) {
		$sender = (array)$sender;
		$recipient = (array)$recipient;

		if ($sender['bankCode'])
			unset($sender['bankCode']);

		return $this->_postJSON('/fund-transfers', array(
			'amount' => $amount,
			'description' => $description,
			'sender' => $sender,
			'recipient' => $recipient
		));
	}

	public function getTransferStatus($branch, $account, $AuthenticationId) {
		return $this->_get('/fund-transfers/' . $AuthenticationId . '/status', array(
			'branch' => $branch,
			'account' => $account
		));
	}

	public function __get($property) {
		if ($property === 'bankList') {
			return bankly_get_banklist();
		}
	}

	static function bankList() {
		return bankly_get_banklist();
	}
}

function bankly_get_banklist() {
	if (function_exists('curl_init')) {
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, Bankly::$API_ENDPOINT . '/banklist');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		
		$req = curl_exec($ch);
		$err = curl_error($ch);

		if ($err)
			throw $err;
		else {
			$req = json_decode($req);
			return $req;
		}
	} else {
		$req = file_get_contents(Bankly::$API_ENDPOINT . '/banklist');

		if ($req) {
			return json_decode($req);
		} else {
			throw new Error("Unable to request");
		}
	}
}
