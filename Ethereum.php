<?php
if (!class_exists('JSON_RPC')) {
	class JSON_RPC {
		protected $host, $port, $version;
		protected $id = 0;
		function __construct($host, $port, $version = "2.0") {
			$this->host = $host;
			$this->port = $port;
			$this->version = $version;
		}
		function request($method, $params = array()) {
			$data = array();
			$data['jsonrpc'] = $this->version;
			$data['id'] = $this->id++;
			$data['method'] = $method;
			$data['params'] = $params;
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $this->host);
			curl_setopt($ch, CURLOPT_PORT, $this->port);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json'
			));
			curl_setopt($ch, CURLOPT_POST, TRUE);
			curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
			$ret = curl_exec($ch);
			if ($ret !== FALSE) {
				$formatted = $this->format_response($ret);
				if (isset($formatted->error)) {
					throw new RPCException($formatted->error->message, $formatted->error->code);
				} else {
					return $formatted;
				}
			} else {
				throw new RPCException("Server did not respond");
			}
		}
		function format_response($response) {
			return @json_decode($response);
		}
	}
	class RPCException extends Exception {
		public function __construct($message, $code = 0, Exception $previous = null) {
			parent::__construct($message, $code, $previous);
		}
		public function __toString() {
			return __CLASS__ . ": " . (($this->code > 0) ? "[{$this->code}]:" : "") . " {$this->message}\n";
		}
	}
}
if (!class_exists('EthereumRPC')) {
	class EthereumRPC extends JSON_RPC {
		public function ether_request($method, $params = array()) {
			try {
				$ret = $this->request($method, $params);
				return $ret->result;
			}
			catch(RPCException $e) {
				throw $e;
			}
		}
		/*
		private function decode_hex($input)
		{
			if(substr($input, 0, 2) == '0x')
				$input = substr($input, 2);
		
			if(preg_match('/[a-f0-9]+/', $input))
				return hexdec($input);
		
			return $input;
		}
		*/
	}
}
class Ethereum {
	public $settings = array(
		'description' => 'Accept payments though the Ethereum network using your own Parity node and no third party API.',
	);
	private $eth = null;
	function payment_button($params) {
		global $billic, $db;
		if (get_config('ethereum_rpc_ip') == '') {
			return false;
		}
		return 'Pay using Ethereum';
	}
	function payment_features() {
		return '<img src="data:image/png;base64,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">';
	}
	function payment_page($params) {
		global $billic, $db;
		if (get_config('ethereum_rpc_ip') == '') {
			return 'Ethereum is not enabled';
		}
		$html = '';
		if ($billic->user['verified'] == 0 && get_config('ethereum_require_verification') == 1) {
			return 'verify';
		} else {
			$account = $db->q('SELECT * FROM `Ethereum_accounts` WHERE `invoice_id` = ?', $params['invoice']['id']);
			$account = $account[0];
			if (empty($account)) {
				$exchange = get_config('ethereum_rate_source');
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HEADER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);
				switch ($exchange) {
					case 'coinmarketcap.com':
					default:
						curl_setopt($ch, CURLOPT_URL, 'https://api.coinmarketcap.com/v1/ticker/Ethereum/?convert=' . get_config('billic_currency_code'));
						$eth = curl_exec($ch);
						$eth = trim($eth);
						$eth = @json_decode($eth, true);
						$eth = $eth[0]['price_' . strtolower(get_config('billic_currency_code')) ];
					break;
					case 'cryptocompare.com':
						curl_setopt($ch, CURLOPT_URL, 'https://min-api.cryptocompare.com/data/price?fsym=ETH&tsyms=' . get_config('billic_currency_code'));
						$eth = curl_exec($ch);
						$eth = trim($eth);
						$eth = @json_decode($eth, true);
						$eth = $eth[get_config('billic_currency_code') ];
					break;
					case 'coinbase.com':
						curl_setopt($ch, CURLOPT_HTTPHEADER, array(
							'CB-VERSION: 2015-04-08'
						));
						curl_setopt($ch, CURLOPT_URL, 'https://api.coinbase.com/v2/prices/ETH-' . get_config('billic_currency_code') . '/buy');
						$eth = curl_exec($ch);
						$eth = trim($eth);
						$eth = @json_decode($eth, true);
						$eth = $eth['data']['amount'];
					break;
					case 'bitstamp.net':
						curl_setopt($ch, CURLOPT_URL, 'https://www.bitstamp.net/api/v2/ticker/eth' . strtolower(get_config('billic_currency_code')) . '/');
						$eth = curl_exec($ch);
						$eth = trim($eth);
						$eth = @json_decode($eth, true);
						$eth = $eth['last'];
					break;
				}
				$eth = round((1 / $eth) * $params['charge'], 18);
				if (!$eth || !is_numeric($eth) || $eth <= 0) {
					err('Failed to get Ethereum exchange rate. Please try again later. If the problem persists please contact support.');
				}
				$markup = get_config('ethereum_markup');
				if (!empty($markup)) {
					$eth = round($eth + (($eth / 100) * get_config('ethereum_markup')) , 18);
				}
				$this->eth = new EthereumRPC(get_config('ethereum_rpc_ip') , get_config('ethereum_rpc_port'));
				if (!$this->eth->ether_request('net_version')) err('Ethereum RPC Error. Please contact support');
				$account = $db->q('SELECT * FROM `Ethereum_accounts` WHERE `invoice_assigned` IS NULL');
				$account = $account[0];
				if (empty($account)) {
					$password = $billic->rand_str(20);
					$address = $this->eth->ether_request('personal_newAccount', array(
						$password
					));
					if (strlen($address) != 42 || substr($address, 0, 2) != '0x') {
						err('Failed to generate a new ethereum address! Please contact support');
					}
					$db->q('INSERT INTO `Ethereum_accounts` (`created`,`address`,`password`) VALUES (NOW(),?,?)', $address, $billic->encrypt($password));
				}
				$db->q('UPDATE `Ethereum_accounts` SET `invoice_id` = ?, `invoice_assigned` = NOW(), `invoice_expected_eth` = ?, `invoice_charge_amt` = ? WHERE `invoice_assigned` IS NULL LIMIT 1', $params['invoice']['id'], $eth, $params['charge']);
				$account = $db->q('SELECT * FROM `Ethereum_accounts` WHERE `invoice_id` = ?', $params['invoice']['id']);
				$account = $account[0];
				$html.= $this->payment_words($account, $eth, $account['address']);
			} else {
				$db->q('UPDATE `Ethereum_accounts` SET `invoice_assigned` = NOW() WHERE `invoice_id` = ?', $params['invoice']['id']);
				$html.= $this->payment_words($account, $account['invoice_expected_eth'], $account['address']);
			}
		}
		return $html;
	}
	function payment_words($account, $eth, $address) {
		$ret = '<br>';
		$paid = $this->currentPaidVal($account);
		if ($paid > 0.000001) {
			$ret.= '<div class="alert alert-info" role="alert">Thanks, we\'ve received ' . $this->beautify($paid) . ' ETH so far.</div> <div class="alert alert-warning" role="alert">Please send the balance of ' . $this->beautify(($account['invoice_expected_eth'] - $paid)) . ' ETH to complete payment.</div>';
		}
		$ret.= 'Please send <b>' . $this->beautify($eth) . '</b> ETH to <b>' . $address . '</b>';
		$date = new DateTime($account['invoice_assigned']);
		$date->add(new DateInterval('PT' . $this->recycleTime() . 'H'));
		$ret.= '<br><p>Any payment confirmed after ' . $date->format(DateTime::RSS) . ' will be lost. <a href="' . $_SERVER['REQUEST_URI'] . '">Click here</a> to extend this deadline.</p>';
		$ret.= '<br><br><img src="http://chart.apis.google.com/chart?chs=300x300&cht=qr&chl=' . urlencode('ethereum:' . $address . '?value=' . $eth) . '&choe=UTF-8&chld=H|0">';
		return $ret;
	}
	function recycleTime() {
		$hours = ceil(get_config('ethereum_recycle_address_hours'));
		if ($hours > 0 && $hours < 48) {
			return $hours;
		}
		return 2;
	}
	function currentPaidVal($account) {
		return $this->beautify((0 - $account['balance_last']) + $account['balance_latest']);
	}
	function recycle($account) {
		global $billic, $db;
		$db->q('UPDATE `Ethereum_accounts` SET `invoice_id` = NULL, `invoice_assigned` = NULL, `invoice_expected_eth` = NULL, `invoice_charge_amt` = NULL, `balance_last` = `balance_latest` WHERE `address` = ?', $account['address']);
	}
	function payment_callback() {
		global $billic, $db;
	}
	function beautify($eth) {
		$ret = rtrim(sprintf('%.18f', $eth) , '0');
		if (substr($ret, -1) == '.') $ret = substr($ret, 0, -1);
		return $ret;
	}
	function cron() {
		global $billic, $db;
		$accounts = $db->q('SELECT * FROM `Ethereum_accounts` WHERE `invoice_assigned` IS NOT NULL');
		foreach ($accounts as $account) {
			if ($this->eth === null) {
				$this->eth = new EthereumRPC(get_config('ethereum_rpc_ip') , get_config('ethereum_rpc_port'));
				if (!$this->eth->ether_request('net_version')) err('Ethereum RPC Error');
			}
			$bal = $this->eth->ether_request('eth_getBalance', array(
				$account['address'],
				'latest'
			));
			if (substr($bal, 0, 2) != '0x') echo 'Ethereum: Invalid balance returned for account ' . $account['address'] . PHP_EOL;
			if ($bal == '0x0') $bal = 0;
			else $bal = sprintf('%.18f', (hexdec(substr($bal, 2)) / pow(10, 18)));
			$db->q('UPDATE `Ethereum_accounts` SET `balance_latest` = ? WHERE `address` = ?', $bal, $account['address']);
			$paid = $this->currentPaidVal($account);
			if ($paid >= (($account['invoice_expected_eth'] / 100) * (100 - get_config('ethereum_loss_percent')))) {
				$this->recycle($account);
				$now = new DateTime();
				$billic->module('Invoices');
				$billic->modules['Invoices']->addpayment(array(
					'gateway' => 'Ethereum',
					'invoiceid' => $account['invoice_id'],
					'amount' => $account['invoice_charge_amt'],
					'currency' => get_config('billic_currency_code') ,
					'transactionid' => $account['address'] . ' ' . $now->format(DateTime::ISO8601) ,
				));
			}
			$now = new DateTime();
			$date = new DateTime($account['invoice_assigned']);
			$date->add(new DateInterval('PT' . $this->recycleTime() . 'H'));
			if ($now > $date) {
				$this->recycle($account);
			}
		}
	}
	function settings($array) {
		global $billic, $db;
		if (isset($_POST['show_wallet_passwords'])) {
			echo '<table class="table table-striped">';
			$accounts = $db->q('SELECT * FROM `Ethereum_accounts`');
			foreach ($accounts as $account) {
				echo '<tr><td><a href="https://etherscan.io/address/' . $account['address'] . '" target="_new">' . $account['address'] . '</a></td><td>'.$billic->decrypt($account['password']).'</td></tr>';
			}
			echo '</table>';
		} else
		if (isset($_POST['check_connection'])) {
			$this->eth = new EthereumRPC(get_config('ethereum_rpc_ip') , get_config('ethereum_rpc_port'));
			if ($this->eth->ether_request('net_version')) {
				echo '<div class="alert alert-success" role="alert">RPC connection test successful.</div>';
			} else {
				echo '<div class="alert alert-danger" role="alert">RPC connection test failed! Check RPC Host and Port.</div>';
			}
		} else
		if (empty($_POST['update'])) {
			echo '<form method="POST"><input type="hidden" name="billic_ajax_module" value="Ethereum"><table class="table table-striped">';
			echo '<tr><th>Setting</th><th>Value</th></tr>';
			echo '<tr><td>Require Verification</td><td><input type="checkbox" name="ethereum_require_verification" value="1"' . (get_config('ethereum_require_verification') == 1 ? ' checked' : '') . '></td></tr>';
			echo '<tr><td>RPC IP</td><td><input type="text" class="form-control" name="ethereum_rpc_ip" value="' . safe(get_config('ethereum_rpc_ip')) . '"></td></tr>';
			echo '<tr><td>RPC Port</td><td><input type="text" class="form-control" name="ethereum_rpc_port" value="' . safe(get_config('ethereum_rpc_port')) . '"></td></tr>';
			echo '<tr><td>Exchange Rate</td><td><select class="form-control" name="ethereum_rate_source"><option value="coinmarketcap.com"' . (get_config('ethereum_rate_source') == 'coinmarketcap.com' ? ' selected' : '') . '>coinmarketcap.com</option><option value="cryptocompare.com"' . (get_config('ethereum_rate_source') == 'cryptocompare.com' ? ' selected' : '') . '>cryptocompare.com</option><option value="coinbase.com"' . (get_config('ethereum_rate_source') == 'coinbase.com' ? ' selected' : '') . '>coinbase.com</option><option value="bitstamp.net"' . (get_config('ethereum_rate_source') == 'bitstamp.net' ? ' selected' : '') . '>bitstamp.net</option></select></td></tr>';
			echo '<tr><td>Exchange Rate Markup</td><td><div class="input-group" style="width: 150px"><input type="text" class="form-control" name="ethereum_markup" value="' . safe(get_config('ethereum_markup')) . '"><div class="input-group-addon">%</div></div><sup>This should be between 0% and 100%</sup></td></tr>';
			echo '<tr><td>Allow Missing Payment</td><td><div class="input-group" style="width: 150px"><input type="text" class="form-control" name="ethereum_loss_percent" value="' . safe(get_config('ethereum_loss_percent')) . '"><div class="input-group-addon">%</div></div><sup>This should be between 0% and 100%. Allows a short payment to be applied (rounding errors, fees taken by third parties while sending the payment, etc)</sup></td></tr>';
			echo '<tr><td>Recycle Address Time</td><td><div class="input-group" style="width: 150px"><input type="text" class="form-control" name="ethereum_recycle_address_hours" value="' . safe(get_config('ethereum_recycle_address_hours')) . '"><div class="input-group-addon">hours</div></div><sup>An address will be recycled to a different invoice after X hours of not being paid. This can be extended by the customer. Default 2 hours.</sup></td></tr>';
			echo '<tr><td colspan="2" align="center"><input type="submit" class="btn btn-default" name="update" value="Update &raquo;"></td></tr>';
			echo '</table></form>';
			echo '<table class="table table-striped">';
			echo '<tr><th>Address</th><th>Balance</th><th>Invoice</th><th>Payment</th><th>Assigned</th></tr>';
			$accounts = $db->q('SELECT * FROM `Ethereum_accounts`');
			foreach ($accounts as $account) {
				echo '<tr><td><a href="https://etherscan.io/address/' . $account['address'] . '" target="_new">' . $account['address'] . '</a></td><td>' . $this->beautify($account['balance_last']) . ' ETH</td><td>' . ($account['invoice_id'] === NULL ? 'N/A' : '<a href="/Admin/Invoices/ID/' . $account['invoice_id'] . '/" target="_new">#' . $account['invoice_id'] . '</a>') . '</td><td>' . $this->currentPaidVal($account) . ' ETH</td><td>' . ($account['invoice_assigned'] === NULL ? 'N/A' : $account['invoice_assigned']) . '</td></tr>';
			}
			echo '</table></form>';
			echo '<form method="POST"><input type="hidden" name="billic_ajax_module" value="Ethereum"><input type="submit" name="check_connection" value="Check Connection &raquo;" class="btn btn-info"> <input type="submit" name="show_wallet_passwords" value="Show Wallet Passwords" class="btn btn-danger"></form>';
		} else {
			if (empty($billic->errors)) {
				set_config('ethereum_require_verification', $_POST['ethereum_require_verification']);
				set_config('ethereum_rpc_ip', $_POST['ethereum_rpc_ip']);
				set_config('ethereum_rpc_port', $_POST['ethereum_rpc_port']);
				set_config('ethereum_rate_source', $_POST['ethereum_rate_source']);
				set_config('ethereum_markup', $_POST['ethereum_markup']);
				set_config('ethereum_loss_percent', $_POST['ethereum_loss_percent']);
				set_config('ethereum_recycle_address_hours', $_POST['ethereum_recycle_address_hours']);
				$billic->status = 'updated';
			}
		}
	}
}
