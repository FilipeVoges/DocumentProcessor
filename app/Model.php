<?php

namespace App;

use App\Modules\Connection\Database;
use Exception;

/**
 * \Model
 *
 * @since 2018-10-15
 * @author Filipe Voges <filipe@emsventura.com.br>
 */
abstract class Model extends \stdClass
{

    /**
     * @var int $aI ;
     * @access private
     */
    private static int $aI = 0;

    /**
     * @var string $table;
     * @access protected
     */
    protected string $table;

    /**
     * @var int $identifier
     * @access protected
     */
    protected int $idClass;

    /**
     * @var bool
     * @access protected
     */
    protected bool $hasConn = true;

    /**
     * @var Database
     * @access protected
     */
    protected Database $db;

    /**
     * @var string
     * @access protected
     */
    protected string $key = 'id';

    /**
     * @var array
     * @access protected
     */
    protected array $privateAttrs = [
        'db',
        'idClass',
        'table',
        'key',
        'equals',
        'hasConn'
    ];

    /**
     * @var array
     * @access protected
     */
    protected array $equals = [];

    /**
     * Return Table
     *
     * @return string
     */
    public static function table(): string
    {
        $className = get_called_class();

        $class = new $className();

        try {
            return $class->get('table');
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * Returns all data
     *
     * @param array $fields
     * @param array $filter
     * @return array
     * @throws Exception
     */
    public static function getAll(array $fields = ['*'], array $filter = []): array
    {
        $result = [];

        $db = Database::getInstance();

        $fields = implode(', ', $fields);
        $sql = buildQuery(self::table(), $fields, $filter);
        $stmt = $db->getQuery($sql);

        $dados = $db->getFetchAll($stmt);
        $className = get_called_class();
        foreach ($dados as $dado) {
            $result[] = new $className($dado);
        }

        return $result;
    }

    /**
     * @param $search
     * @param string $key
     * @param bool $required
     * @return bool|mixed
     * @throws Exception
     */
    public static function find($search, string $key = 'id', bool $required = false)
    {
        $db = Database::getInstance();

        $className = get_called_class();
        $class = new $className();

        $sql = buildQuery($class->get('table'), '*', [$key => $search], [], NULL, 1);

        $stmt = $db->getQuery($sql);

        $data = $db->getFetchAssoc($stmt);

        if (!empty($data)) {
            $class->populate($data);
        }

        if ($required && empty($data)) {
            return false;
        }

        return $class;
    }

    /**
     * Model constructor.
     * @param array $data
     * @throws Exception
     */
    public function __construct(array $data = [])
    {
        Model::$aI = Model::$aI + 1;
        $this->set('idClass', Model::$aI);

        if ($this->get('hasConn')) {
            $this->db = Database::getInstance();
        }

        if (!empty($data)) {
            $this->populate($data);
        }
    }

    /**
     * Getter Generic
     *
     * @param $property | String
     * @return mixed
     * @throws Exception
     */
    public function get($property)
    {
        if (property_exists($this, $property)) {
            return $this->$property;
        } else {
            throw new Exception("Atributo inexistente." . "-> {$property}", 500);
        }
    }

    /**
     * Setter Generic
     *
     * @param $property | String
     * @param $value | mixed
     * @return void
     */
    public function set($property, $value)
    {
        $this->$property = $value;
    }

    /**
     * returns a Attributes of Class
     *
     * @param bool $ignoreEmptys
     * @param bool $ignoreNulls
     * @return array
     * @throws Exception
     */
    public function getAttributes(bool $ignoreEmptys = false, bool $ignoreNulls = false): array
    {
        $attrs = get_object_vars($this);

        foreach ($attrs as $key => $value) {
            if (in_array($key, $this->get('privateAttrs'))) {
                unset($attrs[$key]);
            }
        }

        if (array_key_exists('privateAttrs', $attrs)) {
            unset($attrs['privateAttrs']);
        }

        foreach ($attrs as $key => $attr) {
            if ($ignoreEmptys) {
                if ($attr == '') {
                    unset($attrs[$key]);
                }
            }
            if ($ignoreNulls) {
                if (is_null($attr)) {
                    unset($attrs[$key]);
                }
            }
        }

        return $attrs;
    }

    /**
     * Returns the Object Equality filters
     *
     * @return array
     * @throws Exception
     */
    public function filter(): array
    {
        $where = $this->getAttributes(true);
        if (!empty($this->get('equals'))) {
            $newWhere = [];
            foreach ($this->get('equals') as $e) {
                if (array_key_exists($e, $where)) {
                    $newWhere[$e] = $where[$e];
                }
            }
            if (!empty($newWhere)) {
                $where = $newWhere;
            }
        }

        return $where;
    }

    /**
     * Checks whether data exists
     *
     * @return bool
     * @throws Exception
     */
    public function exists(): bool
    {

        $key = $this->get('key');

        if (boolval($this->get($key))) {
            return true;
        }

        $sql = buildQuery($this->get('table'), 'count(*) as `reg`', $this->filter());
        $stmt = $this->db->getQuery($sql);

        $result = $this->db->getFetchAssoc($stmt);

        return isset($result['reg']) && $result['reg'] > 0;
    }

    /**
     * Create or update a database record.
     *
     * @param bool $force
     * @return bool
     */
    public function save(bool $force = false): bool
    {
        try {
            $key = $this->get('key');
            if ($this->exists() && !$force) {
                _update($this->get('table'), $this->getAttributes(false, true), buildWhereQuery($this->filter()));
            } else {
                $id = _insert($this->get('table'), $this->getAttributes(true), true);
                $this->set($key, $id);
            }
            return true;
        } catch (Exception $e) {
            echo $e->getMessage();
            return false;
        }
    }

    /**
     * delete a database record.
     *
     * @return bool
     */
    public function delete(): bool
    {
        try {
            $attributes = $this->getAttributes(true, true);
            _delete($this->get('table'), buildWhereQuery($attributes));
            $this->populate([]);
            return $this;
        } catch (Exception $e) {
            return false;
        }
    }


    /**
     * Populates the class attributes with the results obtained from the database
     *
     * @param array $dados
     * @return void
     * @throws Exception
     */
    protected function populate(array $dados)
    {
        foreach ($dados as $key => $value) {
            if (property_exists($this, $key)) {
                $this->set($key, $value);
            }
        }
    }

    /**
     * Compare Classes
     *
     * @param Model $obj
     * @return bool
     * @throws Exception
     */
    public function equals(Model $obj): bool
    {
        if (get_class($this) != get_class($obj)) return false;
        if ($obj == NULL) return false;

        $id = $this->get('idClass');
        $idObj = $obj->get('idClass');
        $obj->set('idClass', NULL);
        $this->set('idClass', NULL);
        $rs = ($this == $obj);
        $this->set('idClass', $id);
        $obj->set('idClass', $idObj);

        return $rs;
    }

}
