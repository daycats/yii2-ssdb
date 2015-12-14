<?php
/**
 * Connect - yii2-ssdb
 *
 * @author Jack.wu <xiaowu365@gmail.com>
 * @create_time 2015-06-02 14:29
 *
 *
 *
 */

namespace wsl\ssdb;

use yii\base\Component;

/**
 * Class Connection
 *
 * @package yii\SSDB
 * @method info()
 * @method dbsize()
 * @method ping()
 * @method qset(String $name, $index, $val)
 * @method getbit(String $key, Int $offset)
 * @method setbit(String $key, Int $offset, Int $val)
 * @method countbit(String $key, Int $start, Int $size)
 * @method strlen(String $key)
 * @method set(String $key, String $value)
 * @method setx(String $key, String $value, Int $ttl)
 * @method setnx(String $key, String $value)
 * @method zset(String $name, String $key, Int $score)
 * @method hset(String $name, String $key, String $value)
 * @method qpush(String $name, String $item)
 * @method qpush_front(String $name, String $item)
 * @method qpush_back(String $name, String $item)
 * @method qtrim_front(String $name, Int $size)
 * @method qtrim_back(String $name, Int $size)
 * @method del(String $key)
 * @method zdel(String $name, String $key)
 * @method hdel(String $name, String $key)
 * @method hsize(String $name)
 * @method zsize(String $name)
 * @method qsize(String $name)
 * @method hclear(String $name)
 * @method zclear(String $name)
 * @method qclear(String $name)
 * @method multi_del(Array $keys)
 * @method multi_hdel(String $name, Array $keys)
 * @method multi_zdel(String $name, Array $keys)
 * @method zget(String $name, String $key)
 * @method zrank(String $name, String $key)
 * @method zrrank(String $name, String $key)
 * @method zcount(String $name, Int $score_start, Int $score_end)
 * @method zsum(String $name, Int $score_start, Int $score_end)
 * @method zremrangebyrank(String $name, Int $start, Int $end)
 * @method zremrangebyscore(String $name, Int $start, Int $end)
 * @method ttl(String $key)
 * @method zavg(String $name, Int $score_start, Int $score_end)
 * @method get(String $key)
 * @method substr(String $key, Int $start, Int $size)
 * @method getset(String $key, String $value)
 * @method hget(String $name, String $key)
 * @method qget(String $name, Int $index)
 * @method qfront(String $name)
 * @method qback(String $name)
 * @method qpop(String $name, Int $size)
 * @method qpop_front(String $name, Int $size)
 * @method qpop_back(String $name, Int $size)
 * @method keys(String $key_start, String $key_end, Int $limit)
 * @method zkeys(String $name, String $key_start, Int $score_start, Int $score_end, Int $limit)
 * @method hkeys(String $name, String $key_start, String $key_end, Int $limit)
 * @method hlist(Int $name_start, Int $name_end, Int $limit)
 * @method zlist(Int $name_start, Int $name_end, Int $limit)
 * @method zrlist(Int $name_start, Int $name_end, Int $limit)
 * @method qslice(String $name, Int $begin, Int $end)
 * @method exists(String $key)
 * @method hexists(String $name, String $key)
 * @method zexists(String $name, String $key)
 * @method multi_exists
 * @method multi_hexists
 * @method multi_zexists
 * @method scan(String $key_start, String $key_end, Int $limit)
 * @method rscan(String $key_start, String $key_end, Int $limit)
 * @method zscan(String $name, String $key_start, Int $score_start, Int $score_end, Int $limit)
 * @method zrscan(String $name, String $key_start, Int $score_start, Int $score_end, Int $limit)
 * @method zrange(String $name, Int $offset, Int $limit)
 * @method zrrange(String $name, Int $offset, Int $limit)
 * @method hscan(String $name, String $key_start, String $key_end, Int $limit)
 * @method hrscan(String $name, String $key_start, String $key_end, Int $limit)
 * @method hgetall(String $name)
 * @method multi_hsize
 * @method multi_zsize
 * @method multi_get(Array $keys)
 * @method multi_hget(String $name, Array $keys)
 * @method multi_zget(String $name, Array $keys)
 * @method zpop_front(string $name, integer $limit)
 * @method zpop_back(string $name, integer $limit)
 */
class Connection extends Component
{
	private $debug = false;
	public $sock = null;
	private $_closed = false;
	private $recv_buf = '';
	public $last_resp = null;

	public $host = '127.0.0.1';
	public $port = 8888;
	public $timeout_ms=2000;
	public $easy = true;


	public function init()
	{
		parent::init();

		$timeout_f = (float)$this->timeout_ms/1000;
		$this->sock = @stream_socket_client("$this->host:$this->port", $errno, $errstr, $timeout_f);
		if(!$this->sock){
			throw new Exception("$errno: $errstr");
		}
		$timeout_sec = intval($this->timeout_ms/1000);
		$timeout_usec = ($this->timeout_ms - $timeout_sec * 1000) * 1000;
		@stream_set_timeout($this->sock, $timeout_sec, $timeout_usec);
		if(function_exists('stream_set_chunk_size')){
			@stream_set_chunk_size($this->sock, 1024 * 1024);
		}

		if (true === $this->easy) {
			$this->easy();
		}
	}

	/**
	 * After this method invoked with yesno=true, all requesting methods
	 * will not return a Response object.
	 * And some certain methods like get/zget will return false
	 * when response is not ok(not_found, etc)
	 */
	public function easy(){
		$this->easy = true;
	}

	public function close(){
		if(!$this->_closed){
			@fclose($this->sock);
			$this->_closed = true;
			$this->sock = null;
		}
	}

	public function closed(){
		return $this->_closed;
	}

	private $batch_mode = false;
	private $batch_cmds = array();

	public function batch(){
		$this->batch_mode = true;
		$this->batch_cmds = array();
		return $this;
	}

	public function multi(){
		return $this->batch();
	}

	public function exec(){
		$ret = array();
		foreach($this->batch_cmds as $op){
			list($cmd, $params) = $op;
			$this->send_req($cmd, $params);
		}
		foreach($this->batch_cmds as $op){
			list($cmd, $params) = $op;
			$resp = $this->recv_resp($cmd, $params);
			$resp = $this->check_easy_resp($cmd, $resp);
			$ret[] = $resp;
		}
		$this->batch_mode = false;
		$this->batch_cmds = array();
		return $ret;
	}
	
	public function request(){
		$args = func_get_args();
		$cmd = array_shift($args);
		return $this->__call($cmd, $args);
	}
	
	private $async_auth_password = null;
	
	public function auth($password){
		$this->async_auth_password = $password;
		return null;
	}

	public function __call($cmd, $params=array()){
		$cmd = strtolower($cmd);
		if($this->async_auth_password !== null){
			$pass = $this->async_auth_password;
			$this->async_auth_password = null;
			$auth = $this->__call('auth', array($pass));
			if($auth->data !== true){
				throw new \Exception("Authentication failed");
			}
		}

		if($this->batch_mode){
			$this->batch_cmds[] = array($cmd, $params);
			return $this;
		}

		try{
			if($this->send_req($cmd, $params) === false){
				$resp = new Response('error', 'send error');
			}else{
				$resp = $this->recv_resp($cmd, $params);
			}
		}catch(Exception $e){
			if($this->easy){
				throw $e;
			}else{
				$resp = new Response('error', $e->getMessage());
			}
		}

		if($resp->code == 'noauth'){
			$msg = $resp->message;
			throw new \Exception($msg);
		}
		
		$resp = $this->check_easy_resp($cmd, $resp);
		return $resp;
	}

	private function check_easy_resp($cmd, $resp){
		$this->last_resp = $resp;
		if($this->easy){
			if($resp->not_found()){
				return NULL;
			}else if(!$resp->ok() && !is_array($resp->data)){
				return false;
			}else{
				return $resp->data;
			}
		}else{
			$resp->cmd = $cmd;
			return $resp;
		}
	}

	public function multi_set($kvs=array()){
		$args = array();
		foreach($kvs as $k=>$v){
			$args[] = $k;
			$args[] = $v;
		}
		return $this->__call(__FUNCTION__, $args);
	}

	public function multi_hset($name, $kvs=array()){
		$args = array($name);
		foreach($kvs as $k=>$v){
			$args[] = $k;
			$args[] = $v;
		}
		return $this->__call(__FUNCTION__, $args);
	}

	public function multi_zset($name, $kvs=array()){
		$args = array($name);
		foreach($kvs as $k=>$v){
			$args[] = $k;
			$args[] = $v;
		}
		return $this->__call(__FUNCTION__, $args);
	}

	public function incr($key, $val=1){
		$args = func_get_args();
		return $this->__call(__FUNCTION__, $args);
	}

	public function decr($key, $val=1){
		$args = func_get_args();
		return $this->__call(__FUNCTION__, $args);
	}

	public function zincr($name, $key, $score=1){
		$args = func_get_args();
		return $this->__call(__FUNCTION__, $args);
	}

	public function zdecr($name, $key, $score=1){
		$args = func_get_args();
		return $this->__call(__FUNCTION__, $args);
	}

	public function zadd($key, $score, $value){
		$args = array($key, $value, $score);
		return $this->__call('zset', $args);
	}

	public function zRevRank($name, $key){
		$args = func_get_args();
		return $this->__call("zrrank", $args);
	}

	public function zRevRange($name, $offset, $limit){
		$args = func_get_args();
		return $this->__call("zrrange", $args);
	}

	public function hincr($name, $key, $val=1){
		$args = func_get_args();
		return $this->__call(__FUNCTION__, $args);
	}

	public function hdecr($name, $key, $val=1){
		$args = func_get_args();
		return $this->__call(__FUNCTION__, $args);
	}

	private function send_req($cmd, $params){
		$req = array($cmd);
		foreach($params as $p){
			if(is_array($p)){
				$req = array_merge($req, $p);
			}else{
				$req[] = $p;
			}
		}
		return $this->send($req);
	}

	private function recv_resp($cmd, $params){
		$resp = $this->recv();
		if($resp === false){
			return new Response('error', 'Unknown error');
		}else if(!$resp){
			return new Response('disconnected', 'Connection closed');
		}
		if($resp[0] == 'noauth'){
			$errmsg = isset($resp[1])? $resp[1] : '';
			return new Response($resp[0], $errmsg);
		}
		switch($cmd){
			case 'ping':
			case 'qset':
			case 'getbit':
			case 'setbit':
			case 'countbit':
			case 'strlen':
			case 'set':
			case 'setx':
			case 'setnx':
			case 'zset':
			case 'hset':
			case 'qpush':
			case 'qpush_front':
			case 'qpush_back':
			case 'qtrim_front':
			case 'qtrim_back':
			case 'del':
			case 'zdel':
			case 'hdel':
			case 'hsize':
			case 'zsize':
			case 'qsize':
			case 'hclear':
			case 'zclear':
			case 'qclear':
			case 'multi_set':
			case 'multi_del':
			case 'multi_hset':
			case 'multi_hdel':
			case 'multi_zset':
			case 'multi_zdel':
			case 'incr':
			case 'decr':
			case 'zincr':
			case 'zdecr':
			case 'hincr':
			case 'hdecr':
			case 'zget':
			case 'zrank':
			case 'zrrank':
			case 'zcount':
			case 'zsum':
			case 'zremrangebyrank':
			case 'zremrangebyscore':
				if($resp[0] == 'ok'){
					$val = isset($resp[1])? intval($resp[1]) : 0;
					return new Response($resp[0], $val);
				}else{
					$errmsg = isset($resp[1])? $resp[1] : '';
					return new Response($resp[0], $errmsg);
				}
			case 'zavg':
				if($resp[0] == 'ok'){
					$val = isset($resp[1])? floatval($resp[1]) : (float)0;
					return new Response($resp[0], $val);
				}else{
					$errmsg = isset($resp[1])? $resp[1] : '';
					return new Response($resp[0], $errmsg);
				}
			case 'get':
			case 'substr':
			case 'getset':
			case 'hget':
			case 'qget':
			case 'qfront':
			case 'qback':
				if($resp[0] == 'ok'){
					if(count($resp) == 2){
						return new Response('ok', $resp[1]);
					}else{
						return new Response('server_error', 'Invalid response');
					}
				}else{
					$errmsg = isset($resp[1])? $resp[1] : '';
					return new Response($resp[0], $errmsg);
				}
				break;
			case 'qpop':
			case 'qpop_front':
			case 'qpop_back':
				if($resp[0] == 'ok'){
					$size = 1;
					if(isset($params[1])){
						$size = intval($params[1]);
					}
					if($size <= 1){
						if(count($resp) == 2){
							return new Response('ok', $resp[1]);
						}else{
							return new Response('server_error', 'Invalid response');
						}
					}else{
						$data = array_slice($resp, 1);
						return new Response('ok', $data);
					}
				}else{
					$errmsg = isset($resp[1])? $resp[1] : '';
					return new Response($resp[0], $errmsg);
				}
				break;
			case 'keys':
			case 'zkeys':
			case 'hkeys':
			case 'hlist':
			case 'zlist':
			case 'qslice':
				if($resp[0] == 'ok'){
					$data = array();
					if($resp[0] == 'ok'){
						$data = array_slice($resp, 1);
					}
					return new Response($resp[0], $data);
				}else{
					$errmsg = isset($resp[1])? $resp[1] : '';
					return new Response($resp[0], $errmsg);
				}
			case 'auth':
			case 'exists':
			case 'hexists':
			case 'zexists':
				if($resp[0] == 'ok'){
					if(count($resp) == 2){
						return new Response('ok', (bool)$resp[1]);
					}else{
						return new Response('server_error', 'Invalid response');
					}
				}else{
					$errmsg = isset($resp[1])? $resp[1] : '';
					return new Response($resp[0], $errmsg);
				}
				break;
			case 'multi_exists':
			case 'multi_hexists':
			case 'multi_zexists':
				if($resp[0] == 'ok'){
					if(count($resp) % 2 == 1){
						$data = array();
						for($i=1; $i<count($resp); $i+=2){
							$data[$resp[$i]] = (bool)$resp[$i + 1];
						}
						return new Response('ok', $data);
					}else{
						return new Response('server_error', 'Invalid response');
					}
				}else{
					$errmsg = isset($resp[1])? $resp[1] : '';
					return new Response($resp[0], $errmsg);
				}
				break;
			case 'scan':
			case 'rscan':
			case 'zscan':
			case 'zrscan':
			case 'zrange':
			case 'zrrange':
			case 'hscan':
			case 'hrscan':
			case 'hgetall':
			case 'multi_hsize':
			case 'multi_zsize':
			case 'multi_get':
			case 'multi_hget':
			case 'multi_zget':
				if($resp[0] == 'ok'){
					if(count($resp) % 2 == 1){
						$data = array();
						for($i=1; $i<count($resp); $i+=2){
							if($cmd[0] == 'z'){
								$data[$resp[$i]] = intval($resp[$i + 1]);
							}else{
								$data[$resp[$i]] = $resp[$i + 1];
							}
						}
						return new Response('ok', $data);
					}else{
						return new Response('server_error', 'Invalid response');
					}
				}else{
					$errmsg = isset($resp[1])? $resp[1] : '';
					return new Response($resp[0], $errmsg);
				}
				break;
			default:
				return new Response($resp[0], array_slice($resp, 1));
		}
		return new Response('error', 'Unknown command: $cmd');
	}

	private function send($data){
		$ps = array();
		foreach($data as $p){
			$ps[] = strlen($p);
			$ps[] = $p;
		}
		$s = join("\n", $ps) . "\n\n";
		if($this->debug){
			echo '> ' . str_replace(array("\r", "\n"), array('\r', '\n'), $s) . "\n";
		}
		try{
			while(true){
				$ret = @fwrite($this->sock, $s);
				if($ret == false){
					$this->close();
					throw new Exception('Connection lost');
				}
				$s = substr($s, $ret);
				if(strlen($s) == 0){
					break;
				}
				@fflush($this->sock);
			}
		}catch(\Exception $e){
			$this->close();
			throw new Exception($e->getMessage());
		}
		return $ret;
	}

	private function recv(){
		$this->step = self::STEP_SIZE;
		while(true){
			$ret = $this->parse();
			if($ret === null){
				try{
					$data = @fread($this->sock, 1024 * 1024);
					if($this->debug){
						echo '< ' . str_replace(array("\r", "\n"), array('\r', '\n'), $data) . "\n";
					}
				}catch(\Exception $e){
					$data = '';
				}
				if($data === false || $data === ''){
					$this->close();
					throw new Exception('Connection lost');
				}
				$this->recv_buf .= $data;
#				echo "read " . strlen($data) . " total: " . strlen($this->recv_buf) . "\n";
			}else{
				return $ret;
			}
		}
	}

	const STEP_SIZE = 0;
	const STEP_DATA = 1;
	public $resp = array();
	public $step;
	public $block_size;

	private function parse(){
		$spos = 0;
		$epos = 0;
		$buf_size = strlen($this->recv_buf);
		// performance issue for large reponse
		//$this->recv_buf = ltrim($this->recv_buf);
		while(true){
			$spos = $epos;
			if($this->step === self::STEP_SIZE){
				$epos = strpos($this->recv_buf, "\n", $spos);
				if($epos === false){
					break;
				}
				$epos += 1;
				$line = substr($this->recv_buf, $spos, $epos - $spos);
				$spos = $epos;

				$line = trim($line);
				if(strlen($line) == 0){ // head end
					$this->recv_buf = substr($this->recv_buf, $spos);
					$ret = $this->resp;
					$this->resp = array();
					return $ret;
				}
				$this->block_size = intval($line);
				$this->step = self::STEP_DATA;
			}
			if($this->step === self::STEP_DATA){
				$epos = $spos + $this->block_size;
				if($epos <= $buf_size){
					$n = strpos($this->recv_buf, "\n", $epos);
					if($n !== false){
						$data = substr($this->recv_buf, $spos, $epos - $spos);
						$this->resp[] = $data;
						$epos = $n + 1;
						$this->step = self::STEP_SIZE;
						continue;
					}
				}
				break;
			}
		}

		// packet not ready
		if($spos > 0){
			$this->recv_buf = substr($this->recv_buf, $spos);
		}
		return null;
	}
}
