<?php

namespace ext\activedocument;

use \Yii,
\CApplicationComponent,
\CLogger;

class Connection extends CApplicationComponent {

    public $driver;
    public $schemaCachingDuration = 0;
    public $schemaCachingExclude = array();
    public $schemaCacheID = 'activecache';
    /* public $queryCachingDuration=0;
      public $queryCachingDependency;
      public $queryCachingCount=0;
      public $queryCacheID='cache'; */
    public $autoConnect = true;
    /* public $emulatePrepare=false;
      public $enableParamLogging=false;
      public $enableProfiling=false; */
    public $containerPrefix;
    /* public $initSQLs; */
    public $driverMap = array(
        'memory' => '\ext\activedocument\drivers\memory\Adapter',
        'riak' => '\ext\activedocument\drivers\riak\Adapter',
        'mongo' => '\ext\activedocument\drivers\mongo\Adapter',
        #'redis' => '\ext\activedocument\drivers\redis\Adapter',
    );
    protected $_attributes = array();
    private $_active = false;
    /**
     * @var \ext\activedocument\Adapter
     */
    protected $_adapter;

    /**
     * When enabled, profiles requests using Yii::beginProfile && Yii::endProfile
     *
     * @var bool
     */
    public $enableProfiling = false;

    public function __construct($driver = '', array $attributes = array()) {
        $this->driver = $driver;
        $this->_attributes = $attributes;
    }

    public function __sleep() {
        $this->close();
        return array_keys(get_object_vars($this));
    }

    public function init() {
        parent::init();
        if ($this->autoConnect)
            $this->setActive(true);
    }

    public function getActive() {
        return $this->_active;
    }

    public function setActive($value) {
        if ($value != $this->_active) {
            if ($value)
                $this->open();
            else
                $this->close();
        }
    }

    /* public function cache($duration, $dependency=null, $queryCount=1)
      {
      $this->queryCachingDuration=$duration;
      $this->queryCachingDependency=$dependency;
      $this->queryCachingCount=$queryCount;
      return $this;
      } */

    protected function open() {
        if ($this->_adapter === null) {
            if (empty($this->driver))
                throw new Exception(Yii::t('yii', 'Connection.driver cannot be empty.'));
            try {
                Yii::trace('Opening data storage connection', 'ext.activedocument.Connection');
                $this->_adapter = $this->createConnectionInstance();
                $this->_active = true;
            } catch (Exception $e) {
                if (YII_DEBUG) {
                    throw new Exception(Yii::t('yii', 'Connection failed to open the data storage connection: {error}', array('{error}' => $e->getMessage())), (int)$e->getCode(), $e->errorInfo);
                } else {
                    Yii::log($e->getMessage(), CLogger::LEVEL_ERROR, 'exception.ActiveDocument.Exception');
                    throw new Exception(Yii::t('yii', 'Connection failed to open the data storage connection.'), (int)$e->getCode(), $e->errorInfo);
                }
            }
        }
    }

    protected function close() {
        Yii::trace('Closing data storage connection', 'ext.activedocument.Connection');
        $this->_adapter = null;
        $this->_active = false;
    }

    protected function createConnectionInstance() {
        if (isset($this->driverMap[$this->driver]))
            return new $this->driverMap[$this->driver]($this, $this->_attributes);
        else
            throw new Exception(Yii::t('yii', 'Connection does not support {driver} storage adapter.', array('{driver}' => $this->driver)));
    }

    /**
     * @return \ext\activedocument\Adapter
     */
    public function getAdapter() {
        $this->setActive(true);
        return $this->_adapter;
    }

    /* public function getColumnCase() {
      return $this->getAttribute(ACO::ATTR_CASE);
      }

      public function setColumnCase($value) {
      $this->setAttribute(ACO::ATTR_CASE, $value);
      }

      public function getNullConversion() {
      return $this->getAttribute(ACO::ATTR_ORACLE_NULLS);
      }

      public function setNullConversion($value) {
      $this->setAttribute(ACO::ATTR_ORACLE_NULLS, $value);
      }

      public function getAutoCommit() {
      return $this->getAttribute(ACO::ATTR_AUTOCOMMIT);
      }

      public function setAutoCommit($value) {
      $this->setAttribute(ACO::ATTR_AUTOCOMMIT, $value);
      }

      public function getPersistent() {
      return $this->getAttribute(ACO::ATTR_PERSISTENT);
      }

      public function setPersistent($value) {
      return $this->setAttribute(ACO::ATTR_PERSISTENT, $value);
      }

      public function getClientVersion() {
      return $this->getAttribute(ACO::ATTR_CLIENT_VERSION);
      }

      public function getConnectionStatus() {
      return $this->getAttribute(ACO::ATTR_CONNECTION_STATUS);
      }

      public function getPrefetch() {
      return $this->getAttribute(ACO::ATTR_PREFETCH);
      }

      public function getServerInfo() {
      return $this->getAttribute(ACO::ATTR_SERVER_INFO);
      }

      public function getServerVersion() {
      return $this->getAttribute(ACO::ATTR_SERVER_VERSION);
      }

      public function getTimeout() {
      return $this->getAttribute(ACO::ATTR_TIMEOUT);
      } */

    /**
     * @todo Needs refactoring
     *
     * @param $name
     * @return mixed
     */
    public function getAttribute($name) {
        $this->setActive(true);
        return $this->_adapter->getAttribute($name);
    }

    /**
     * @todo Needs refactoring
     *
     * @param $name
     * @param $value
     */
    public function setAttribute($name, $value) {
        if ($this->_adapter instanceof Adapter)
            $this->_adapter->setAttribute($name, $value);
        else
            $this->_attributes[$name] = $value;
    }

    /**
     * @todo Needs refactoring
     *
     * @return array
     */
    public function getAttributes() {
        return $this->_attributes;
    }

    /**
     * @todo Needs refactoring
     *
     * @param $values
     */
    public function setAttributes($values) {
        foreach ($values as $name => $value)
            $this->_attributes[$name] = $value;
    }

    public function getStats() {
        $logger = Yii::getLogger();
        $timings = $logger->getProfilingResults(null, 'ext.activedocument.query.*');
        $count = count($timings);
        $time = array_sum($timings);
        $timings = $logger->getProfilingResults(null, 'ext.activedocument.execute.*');
        $count += count($timings);
        $time += array_sum($timings);
        return array($count, $time);
    }

}