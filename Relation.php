<?php

namespace ext\activedocument;

use \CComponent;

/**
 * BaseRelation class
 *
 * @version $Version: 1.0.dev.29 $
 * @author  $Author: intel352 $.0
 */
abstract class BaseRelation extends CComponent {

    /**
     * @var string name of the related object
     */
    public $name;

    /**
     * @var string name of the related document class
     */
    public $className;
    /**
     * @var array the inputs that will be used for the query (typical for map/reduce queries)
     * array(
     *  array('container' => $container, 'key' => $key, 'data' => $data)
     * )
     */
    public $inputs = array();
    /**
     * @var array custom phases that should be executed (typically used for chaining multiple map & reduce phases)
     * array(
     *  array('phase' => $phase, 'function' => $function, 'args' => $args)
     * )
     */
    public $phases = array();
    /**
     * @var array the parameters that are to be bound to the condition.
     * The keys are parameter placeholder names, and the values are parameter values.
     */
    public $params = array();
    /**
     * Search conditions
     * array(
     *  array('column' => $column, 'keyword' => $keyword, 'like' => $like, 'escape' => $escape)
     * )
     *
     * @var array
     */
    public $search = array();
    /**
     * Column conditions
     * array(
     *  array('column' => $name, 'value' => $value, 'operator' => $operator)
     * )
     *
     * @var array
     */
    public $columns = array();
    /**
     * Array conditions (column [not] in array)
     * array(
     *  array('column' => $column, 'values' => $values, 'like' => $like)
     * )
     *
     * @var array
     */
    public $array = array();
    /**
     * Between conditions
     * array(
     *  array('column' => $column, 'valueStart' => $valueStart, 'valueEnd' => $valueEnd)
     * )
     *
     * @var array
     */
    public $between = array();

    /**
     * @var string Order by clause. For {@link \ext\activedocument\Relation} descendant classes
     */
    public $order = '';
    protected $_scopes = array();

    /**
     * Constructor.
     *
     * @param string $name      name of the relation
     * @param string $className name of the related active record class
     * @param array  $options   additional options (name=>value). The keys must be the property names of this class.
     */
    public function __construct($name, $className, $options = array()) {
        $this->name      = $name;
        $this->className = $className;
        foreach ($options as $name => $value)
            $this->$name = $value;
    }

    /**
     * Array or string accepted (string format: scope1:scope2:scope3)
     *
     * @param mixed $scopes
     */
    public function setScopes($scopes) {
        if (!is_array($scopes))
            $scopes = explode(':', $scopes);
        $this->_scopes = array_merge($this->_scopes, $scopes);
    }

    public function getScopes() {
        return $this->_scopes;
    }

    /**
     * Merges this relation with a criteria specified dynamically.
     *
     * @param array|Criteria $criteria the dynamically specified criteria
     * @return self
     */
    public function mergeWith($criteria) {
        if ($criteria instanceof Criteria)
            $criteria = $criteria->toArray();

        foreach (array('inputs', 'phases', 'params', 'search', 'columns', 'array', 'between') as $arr) {
            if (isset($criteria[$arr]) && (array)$this->$arr !== (array)$criteria[$arr])
                $this->$arr = array_merge((array)$this->$arr, (array)$criteria[$arr]);
        }

        if (isset($criteria['order']) && $this->order !== $criteria['order']) {
            if ($this->order === '')
                $this->order = $criteria['order'];
            else if ($criteria['order'] !== '')
                $this->order = $criteria['order'] . ', ' . $this->order;
        }

        if (isset($criteria['scopes']))
            $this->setScopes($criteria['scopes']);

        return $this;
    }

}

/**
 * StatRelation represents a statistical relational query.
 *
 * @version $Version: 1.0.dev.29 $
 * @author  $Author: intel352 $
 */
class StatRelation extends BaseRelation {

    /**
     * @var mixed the default value to be assigned to those records that do not
     * receive a statistical query result. Defaults to 0.
     */
    public $defaultValue = 0;

    /**
     * Merges this relation with a criteria specified dynamically.
     *
     * @param array $criteria the dynamically specified criteria
     * @return self
     */
    public function mergeWith($criteria) {
        if ($criteria instanceof Criteria)
            $criteria = $criteria->toArray();
        parent::mergeWith($criteria);

        if (isset($criteria['defaultValue']))
            $this->defaultValue = $criteria['defaultValue'];

        return $this;
    }

}

/**
 * Relation is the base class for representing active relations that bring back related objects.
 *
 * @version $Version: 1.0.dev.29 $
 * @author  $Author: intel352 $
 */
abstract class Relation extends BaseRelation {

    /**
     * @var bool If true, related document[s] will be stored within current document
     */
    public $nested = false;

    /**
     * @var string|array specifies which related objects should be eagerly loaded when this related object is lazily loaded.
     * For more details about this property, see {@link Document::with()}.
     */
    public $with = array();

    /**
     * @var boolean whether this table should be joined with the primary table.
     * When setting this property to be false, the table associated with this relation will
     * appear in a separate JOIN statement.
     * If this property is set true, then the corresponding table will ALWAYS be joined together
     * with the primary table, no matter the primary table is limited or not.
     * If this property is not set, the corresponding table will be joined with the primary table
     * only when the primary table is not limited.
     */
    public $together;

    /**
     * @var mixed scopes to apply
     * Can be set to the one of the following:
     * <ul>
     * <li>Single scope: 'scopes'=>'scopeName'.</li>
     * <li>Multiple scopes: 'scopes'=>array('scopeName1','scopeName2').</li>
     * </ul>
     */
    public $scopes;

    /**
     * Merges this relation with a criteria specified dynamically.
     *
     * @param array $criteria the dynamically specified criteria
     * @return self
     */
    public function mergeWith($criteria) {
        if ($criteria instanceof Criteria)
            $criteria = $criteria->toArray();

        parent::mergeWith($criteria);

        if (isset($criteria['with']))
            $this->with = $criteria['with'];

        if (isset($criteria['together']))
            $this->together = $criteria['together'];

        return $this;
    }

}

/**
 * BelongsToRelation represents the parameters specifying a BELONGS_TO relation.
 *
 * @version $Version: 1.0.dev.29 $
 * @author  $Author: intel352 $
 */
class BelongsToRelation extends Relation {

}

/**
 * HasOneRelation represents the parameters specifying a HAS_ONE relation.
 *
 * @version $Version: 1.0.dev.29 $
 * @author  $Author: intel352 $
 */
class HasOneRelation extends Relation {

    /**
     * @var string the name of the relation that should be used as the bridge to this relation.
     * Defaults to null, meaning don't use any bridge.
     */
    public $through;

}

/**
 * HasManyRelation represents the parameters specifying a HAS_MANY relation.
 *
 * @version $Version: 1.0.dev.29 $
 * @author  $Author: intel352 $
 */
class HasManyRelation extends Relation {

    /**
     * @var integer limit of the rows to be selected. It is effective only for lazy loading this related object. Defaults to -1, meaning no limit.
     */
    public $limit = -1;

    /**
     * @var integer offset of the rows to be selected. It is effective only for lazy loading this related object. Defaults to -1, meaning no offset.
     */
    public $offset = -1;

    /**
     * @var string the name of the column that should be used as the key for storing related objects.
     * Defaults to null, meaning using zero-based integer IDs.
     */
    public $index;

    /**
     * @var string the name of the relation that should be used as the bridge to this relation.
     * Defaults to null, meaning don't use any bridge.
     */
    public $through;

    /**
     * @var array Array of columns that this relation should be indexed by
     */
    public $autoIndices = array();

    /**
     * Merges this relation with a criteria specified dynamically.
     *
     * @param array $criteria the dynamically specified criteria
     * @return self
     */
    public function mergeWith($criteria) {
        if ($criteria instanceof Criteria)
            $criteria = $criteria->toArray();
        parent::mergeWith($criteria);
        if (isset($criteria['limit']) && $criteria['limit'] > 0)
            $this->limit = $criteria['limit'];

        if (isset($criteria['offset']) && $criteria['offset'] >= 0)
            $this->offset = $criteria['offset'];

        if (isset($criteria['index']))
            $this->index = $criteria['index'];

        return $this;
    }

}

/**
 * ManyManyRelation represents the parameters specifying a MANY_MANY relation.
 *
 * @version $Version: 1.0.dev.29 $
 * @author  $Author: intel352 $
 */
class ManyManyRelation extends HasManyRelation {

}