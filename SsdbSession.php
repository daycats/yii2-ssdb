<?php
/**
 * SsdbSession- yii2-ssdb
 *
 * @author Jack.wu <xiaowu365@gmail.com>
 * @create_time 2015-06-02 14:29
 */

namespace wsl\ssdb;

use Yii;
use yii\base\InvalidConfigException;
use yii\web\Session;

/*
 *  * To use ssdb Session as the session application component, configure the application as follows,
 *
 * ~~~
 * [
 *     'components' => [
 *         'session' => [
 *             'class' => 'wsl\ssdb\SsdbSession',
 *             'ssdb' => [
 *                 'host' => 'localhost',
 *                 'port' => 8888,
 *                 'easy' => true
 *             ]
 *         ],
 *     ],
 * ]
 * ~~~
 *
 * Or if you have configured the ssdb [[Connect]] as an application component, the following is sufficient:
 *
 * ~~~
 * [
 *     'components' => [
 *         'session' => [
 *             'class' => 'wsl\ssdb\SsdbSession',
 *             // 'ssdb' => 'ssdb' // id of the connect application component
 *         ],
 *     ],
 * ]
 * ~~~
 */

class SsdbSession extends Session {

	/**
	 * @var string
	 */
	public $key_prefix = 'ssdb_session_';

	/**
	 * 
	 * @var Connection
	 */
	public $ssdb = 'ssdb';


	public function init()
	{
		parent::init();
		if (is_string($this->ssdb)) {
			$this->ssdb = \Yii::$app->get($this->ssdb);
		} elseif (is_array($this->ssdb)) {
			if (!isset($this->ssdb['class'])) {
				$this->ssdb['class'] = Connection::className();
			}
			$this->ssdb = \Yii::createObject($this->ssdb);
		}

		if (!$this->ssdb instanceof Connection) {
			throw new InvalidConfigException("Session::ssdb must be either a Ssdb Connection instance or the application component ID of a ssdb Connection.");
		}
		if ($this->key_prefix === null) {
			$this->key_prefix = substr(md5(Yii::$app->id), 0, 5);
		}
	}

	/**
	 * Returns a value indicating whether to use custom session storage.
	 * This method overrides the parent implementation and always returns true.
	 * @return boolean whether to use custom storage.
	 */
	public function getUseCustomStorage()
	{
		return true;
	}
	/**
	 * Session read handler.
	 * Do not call this method directly.
	 * @param string $id session ID
	 * @return string the session data
	 */
	public function readSession($id)
	{
		$data = $this->ssdb->get($this->calculateKey($id));
		return $data === false ? '' : $data;
	}
	/**
	 * Session write handler.
	 * Do not call this method directly.
	 * @param string $id session ID
	 * @param string $data session data
	 * @return boolean whether session write is successful
	 */
	public function writeSession($id, $data)
	{
		return (bool) $this->ssdb->setx($this->calculateKey($id), $data, $this->getTimeout());
	}
	/**
	 * Session destroy handler.
	 * Do not call this method directly.
	 * @param string $id session ID
	 * @return boolean whether session is destroyed successfully
	 */
	public function destroySession($id)
	{
		return (bool) $this->ssdb->del($this->calculateKey($id));
	}
	/**
	 * Generates a unique key used for storing session data in cache.
	 * @param string $id session variable name
	 * @return string a safe cache key associated with the session variable name
	 */
	protected function calculateKey($id)
	{
		return $this->key_prefix . $id;
	}
}
