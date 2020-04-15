<?php

/**
 * @version 1.3 2020
 * @license facceapps.com/license
 * @copyright 2020-04-15
 * @author nabil ali ahmed jaran
 * one line to do every thing in database
 * Class Mysql very fast sql manager
 */
class Mysql
{

    public static function where_block($info, $type = 'OR', $operator = "=")
    {
        $where = "(";
        foreach ($info as $row => $value) {
            $where .= sprintf("`%s`%s'%s' %s ", $row, $operator, addslashes($value), $type);
        }
        $len=(-1 * (strlen($type)+ 2) );
        return substr($where, 0, $len) . ")";
    }


    private $link = null;

    private $info = array(
        'last_query' => null,
        'num_rows' => null,
        'insert_id' => null
    );

    private $connection_info = array();

    private $extra;

    private $where;

    private $limit;

    private $offset;

    private $join;

    private $order;

    function __construct($host, $user, $pass, $db)
    {
        $this->connection_info = array(
            'host' => $host,
            'user' => $user,
            'pass' => $pass,
            'db' => $db
        );
    }

    function __destruct()
    {
        if ($this->link instanceof mysqli_result)
            mysqli_close($this->link);
    }

    /**
     * Setter method
     */
    private function set($field, $value)
    {
        $this->info[$field] = $value;
    }

    /**
     * Getter methods
     */
    public function last_query()
    {
        return $this->info['last_query'];
    }

    public function num_rows()
    {
        return $this->info['num_rows'];
    }

    public function insert_id()
    {
        return $this->info['insert_id'];
    }

    public function get_where()
    {
        return $this->where;
    }

    public function set_where($where)
    {
        $this->where = $where;
        return $this;
    }

    /**
     * Create or return a connection to the MySQL server.
     */
    private function connection()
    {
        if (!is_resource($this->link) || empty($this->link)) {
            try {
                if (($link = mysqli_connect($this->connection_info['host'], $this->connection_info['user'], $this->connection_info['pass'])) && mysqli_select_db($link, $this->connection_info['db'])) {
                    $this->link = $link;
                    mysqli_set_charset($link, 'utf8');
                } else {
                    throw new Exception('Could not connect to MySQL database.');
                }
            } catch (Exception $e) {
                throw new Exception('Could not connect to data - no internet.');;
            }
        }
        return $this->link;
    }

    /**
     * MySQL Where methods
     */
    private function __where($info, $type = 'AND', $operator = "=")
    {
        $link = self::connection();
        $where = $this->where;
        foreach ($info as $row => $value) {
            if (empty($where)) {
                $where = sprintf("WHERE `%s`%s'%s'", $row, $operator, mysqli_real_escape_string($link, $value));
            } else {
                $where .= sprintf(" %s `%s`%s'%s'", $type, $row, $operator, mysqli_real_escape_string($link, $value));
            }
        }
        $this->where = $where;
    }

    private function __add_where($where_str, $type = 'AND')
    {
        $where = $this->where;
        if (empty($where)) {
            $where = sprintf("WHERE %s", $where_str);
        } else {
            $where .= sprintf(" %s %s", $type, $where_str);
        }
        $this->where = $where;
    }
    private function __where_like($info, $type = 'AND')
    {
        $link = self::connection();
        $where = $this->where;
        foreach ($info as $row => $value) {
            if (empty($where)) {
                $where = sprintf("WHERE `%s` LIKE '%s'", $row, /* mysqli_real_escape_string($link, $value) */ $value);
            } else {
                $where .= sprintf(" %s `%s` LIKE '%s'", $type, $row, /* mysqli_real_escape_string($link, $value) */ $value);
            }
        }
        $this->where = $where;
    }

    /**
     * value like 1,2,3,4,5,...<br>
     * <code>array('column'=>'22,1,66,65,10')</code>
     *
     * @param array $info
     * @param string $type
     */
    private function __where_in($info, $type = 'AND')
    {
        /* $link = self::connection(); */
        $where = $this->where;
        foreach ($info as $row => $value) {
            if (empty($where)) {
                $where = sprintf("WHERE `%s` IN (%s)", $row, $value);
            } else {
                $where .= sprintf(" %s `%s` IN (%s)", $type, $row, $value);
            }
        }
        $this->where = $where;
    }

    private function __where_between($column, $value1, $value2, $between = 'BETWEEN', $type = 'AND')
    {
        $link = self::connection();
        $where = $this->where;
        if (empty($where)) {
            $where = sprintf("WHERE `%s` %s %s AND %s", $column, $between, mysqli_real_escape_string($link, $value1), mysqli_real_escape_string($link, $value2));
        } else {
            $where .= sprintf(" %s `%s` %s %s AND %s", $type, $column, $between, mysqli_real_escape_string($link, $value1), mysqli_real_escape_string($link, $value2));
        }

        $this->where = $where;
    }

    private function __join($table, $condition, $type = 'INNER')
    {
        $join = $this->join;
        $join .= " {$type} JOIN {$table} ON ";
        if (is_array($condition)) {
            foreach ($condition as $key => $cond) {
                if ($key > 0) {
                    $join .= " AND ";
                }
                $join .= $cond;
            }
        } else {
            $join .= $condition;
        }
        $this->join = $join;
    }

    public function join($table, $condition)
    {
        self::__join($table, $condition);
        return $this;
    }

    public function leftJoin($table, $condition)
    {
        self::__join($table, $condition, 'LEFT');
        return $this;
    }

    public function rightJoin($table, $condition)
    {
        self::__join($table, $condition, 'RIGHT');
        return $this;
    }

    public function crossJoin($table, $condition)
    {
        self::__join($table, $condition, 'CROSS');
        return $this;
    }

    /**
     * $field value like 1,2,3,4,5,...<br>
     * <code>array('column'=>'22,1,66,65,10')</code>
     *
     * @param array|string $field
     * @param string $equal
     *            null if using $field as array
     * @return Mysql
     */
    public function where_in($field, $equal = null)
    {
        if (is_array($field)) {
            self::__where_in($field);
        } else {
            self::__where_in(array(
                $field => $equal
            ));
        }
        return $this;
    }

    /**
     * $field value like 1,2,3,4,5,...<br>
     * <code>array('column'=>'22,1,66,65,10')</code>
     *
     * @param array|string $field
     * @param string $equal
     *            null if using $field as array
     * @return Mysql
     */
    public function and_where_in($field, $equal = null)
    {
        return self::where_in($field, $equal);
    }

    /**
     * $field value like 1,2,3,4,5,...<br>
     * <code>array('column'=>'22,1,66,65,10')</code>
     *
     * @param array|string $field
     * @param string $equal
     *            null if using $field as array
     * @return Mysql
     */
    public function or_where_in($field, $equal = null)
    {
        if (is_array($field)) {
            self::__where_in($field, 'OR');
        } else {
            self::__where_in(array(
                $field => $equal
            ), 'OR');
        }
        return $this;
    }

    public function where_between($column, $value1, $value2)
    {
        self::__where_between($column, $value1, $value2);

        return $this;
    }

    public function where_not_between($column, $value1, $value2)
    {
        self::__where_between($column, $value1, $value2, 'NOT BETWEEN');

        return $this;
    }

    public function and_where_between($column, $value1, $value2)
    {
        return self::where_between($column, $value1, $value2);
    }

    public function and_where_not_between($column, $value1, $value2)
    {
        return self::where_not_between($column, $value1, $value2);
    }

    public function or_where_between($column, $value1, $value2)
    {
        self::__where_between($column, $value1, $value2, 'BETWEEN', 'OR');

        return $this;
    }

    public function or_where_not_between($column, $value1, $value2)
    {
        self::__where_between($column, $value1, $value2, 'NOT BETWEEN', 'OR');

        return $this;
    }

    public function where_like($field, $like = null)
    {
        if (is_array($field)) {
            self::__where_like($field);
        } else {
            self::__where_like(array(
                $field => $like
            ));
        }
        return $this;
    }

    public function and_where_like($field, $like = null)
    {
        return self::where_like($field, $like);
    }

    public function or_where_like($field, $like = null)
    {
        if (is_array($field)) {
            self::__where_like($field, "OR");
        } else {
            self::__where_like(array(
                $field => $like
            ), "OR");
        }
        return $this;
    }


    public function and_add_where($where_str)
    {
        self::__add_where($where_str);
        return $this;
    }

    public function or_add_where($where_str)
    {
        self::__add_where($where_str, 'OR');
        return $this;
    }

    public function where($field, $equal = null)
    {
        if (is_array($field)) {
            self::__where($field);
        } else {
            self::__where(array(
                $field => $equal
            ));
        }
        return $this;
    }

    public function and_where($field, $equal = null)
    {
        return self::where($field, $equal);
    }

    public function or_where($field, $equal = null)
    {
        if (is_array($field)) {
            self::__where($field, 'OR');
        } else {
            self::__where(array(
                $field => $equal
            ), 'OR');
        }
        return $this;
    }

    public function where_operator($field, $operator = "=", $equal = null)
    {
        if (is_array($field)) {
            self::__where($field, "AND", $operator);
        } else {
            self::__where(array(
                $field => $equal
            ), "AND", $operator);
        }
        return $this;
    }

    public function or_where_operator($field, $operator = "=", $equal = null)
    {
        if (is_array($field)) {
            self::__where($field, "OR", $operator);
        } else {
            self::__where(array(
                $field => $equal
            ), "OR", $operator);
        }
        return $this;
    }

    public function and_where_operator($field, $operator = "=", $equal = null)
    {
        return self::where_operator($field, $operator, $equal);
    }

    /**
     * MySQL limit method
     */
    public function limit($limit)
    {
        $this->limit = 'LIMIT ' . $limit;
        return $this;
    }

    /**
     * MySQL OFFSET method
     */
    public function offset($offset)
    {
        $this->offset = "OFFSET " . $offset;
        return $this;
    }

    /**
     * MySQL Order By method
     */
    public function order_by($by, $order_type = 'DESC')
    {
        $order = $this->order;
        if (is_array($by)) {
            foreach ($by as $field => $type) {
                if (is_int($field) && !preg_match('/(DESC|desc|ASC|asc)/', $type)) {
                    $field = $type;
                    $type = $order_type;
                }
                if (empty($order)) {
                    $order = sprintf("ORDER BY `%s` %s", $field, $type);
                } else {
                    $order .= sprintf(", `%s` %s", $field, $type);
                }
            }
        } else {
            if (empty($order)) {
                $order = sprintf("ORDER BY `%s` %s", $by, $order_type);
            } else {
                $order .= sprintf(", `%s` %s", $by, $order_type);
            }
        }
        $this->order = $order;
        return $this;
    }

    /**
     * MySQL query helper
     */
    private function extra()
    {
        $extra = '';
        if (empty($this->extra)) {
            if (!empty($this->where)) $extra .= ' ' . $this->where;
            if (!empty($this->join)) $extra .= ' ' . $this->join;
            if (!empty($this->order)) $extra .= ' ' . $this->order;
            if (!empty($this->limit)) $extra .= ' ' . $this->limit;
            if (empty($this->offset) && !empty($this->limit)) $extra .= ' ';

            if (!empty($this->offset) && (/* !empty($this->where)|| */ !empty($this->limit))) $extra .= ' ' . $this->offset;
            elseif (!empty($this->offset)) $extra .= ' LIMIT 18446744073709551615 ' . $this->offset;

            $this->extra = $extra;

            // cleanup
            self::clean_extra();
        } else {
            $extra = $this->extra;
        }
        return $extra;
    }

    /**
     * reset extra
     */
    public function clean_extra()
    {
        $this->where = null;
        $this->join = null;
        $this->order = null;
        $this->limit = null;
        $this->offset = null;
        $this->extra = null;
        return $this;
    }

    public function clear_extra()
    {
        return self::clean_extra();
    }

    public function set_extra($extra)
    {
        $extra = trim($extra);
        if (stripos($extra, "limit") !== false) {
            if (stripos($extra, "offset") === false) $extra .= ' ';
        }
        $this->extra = ' ' . $extra;
        return $this;
    }

    public function get_extra()
    {
        return self::extra();
    }

    public function check_connection()
    {
        try {
            self::connection();
            $this->__destruct();
            return TRUE;
        } catch (Exception $e) {
            return FALSE;
        }
    }

    /**
     * MySQL Query methods
     */
    public function query($qry, $return = false)
    {
        $link = self::connection();
        self::set('last_query', $qry);
        $result = mysqli_query($link, $qry);
        if ($result instanceof mysqli_result) {
            self::set('num_rows', mysqli_num_rows($result));
        }
        if ($return) {
            if (preg_match('/LIMIT 1/', $qry)) {
                $data = mysqli_fetch_assoc($result);
                mysqli_free_result($result);
                return $data;
            } else {
                $data = array();
                while ($row = mysqli_fetch_assoc($result)) {
                    $data[] = $row;
                }
                mysqli_free_result($result);
                return $data;
            }
        }
        return true;
    }

    /**
     * to force array if return data from database only one row
     * @param $data array the returned data from get function
     * @return array 100% array data
     */
    public function forceArray(&$data){
        $tmp_0=[];
        if(self::num_rows()===1)$tmp_0[]=$data;
        elseif(self::num_rows() > 1)$tmp_0=$data;
        $data=$tmp_0;
        return $tmp_0;
    }

    public function get($table, $select = '*')
    {
        $link = self::connection();
        if (is_array($select)) {
            $cols = '';
            foreach ($select as $col) {
                $cols .= "{$col},";
            }
            $select = substr($cols, 0, -1);
        }
        $sql = sprintf("SELECT %s FROM %s%s", $select, $table, self::extra());
        self::set('last_query', $sql);
        if (!($result = mysqli_query($link, $sql))) {
            $data = false;
            throw new Exception('Error executing MySQL query: ' . $sql . '. MySQL error ' . mysqli_errno($link) . ': ' . mysqli_error($link));
        } elseif ($result instanceof mysqli_result) {
            $num_rows = mysqli_num_rows($result);
            self::set('num_rows', $num_rows);
            if ($num_rows === 0) {
                $data = false;
                /* todo modify space */
            } elseif (preg_match('/LIMIT 1 /', $sql) || $num_rows === 1) {
                $data = mysqli_fetch_assoc($result);
            } else {
                $data = array();
                while ($row = mysqli_fetch_assoc($result)) {
                    $data[] = $row;
                }
            }
        } else {
            $data = false;
        }
        mysqli_free_result($result);
        return $data;
    }

    public function insert($table, $data)
    {
        $link = self::connection();
        $fields = '';
        $values = '';
        foreach ($data as $col => $value) {
            $fields .= sprintf("`%s`,", $col);
            $values .= sprintf("'%s',", mysqli_real_escape_string($link, $value));
        }
        $fields = substr($fields, 0, -1);
        $values = substr($values, 0, -1);
        $sql = sprintf("INSERT INTO %s (%s) VALUES (%s)", $table, $fields, $values);
        self::set('last_query', $sql);
        if (!mysqli_query($link, $sql)) {
            throw new Exception('Error executing MySQL query: ' . $sql . '. MySQL error ' . mysqli_errno($link) . ': ' . mysqli_error($link));
        } else {
            self::set('insert_id', mysqli_insert_id($link));
            return true;
        }
    }

    public function update($table, $info)
    {
        if (empty($this->where)) {
            throw new Exception("Where is not set. Can't update whole table.");
        } else {
            $link = self::connection();
            $update = '';
            foreach ($info as $col => $value) {
                $update .= sprintf("`%s`='%s', ", $col, mysqli_real_escape_string($link, $value));
            }
            $update = substr($update, 0, -2);
            $sql = sprintf("UPDATE %s SET %s%s", $table, $update, self::extra());
            self::set('last_query', $sql);
            if (!mysqli_query($link, $sql)) {
                throw new Exception('Error executing MySQL query: ' . $sql . '. MySQL error ' . mysqli_errno($link) . ': ' . mysqli_error($link));
            } else {
                return true;
            }
        }
    }

    public function delete($table)
    {
        if (empty($this->where)) {
            throw new Exception("Where is not set. Can't delete whole table.");
        } else {
            $link = self::connection();
            $sql = sprintf("DELETE FROM %s%s", $table, self::extra());
            self::set('last_query', $sql);
            if (!mysqli_query($link, $sql)) {
                throw new Exception('Error executing MySQL query: ' . $sql . '. MySQL error ' . mysqli_errno($link) . ': ' . mysqli_error($link));
            } else {
                return true;
            }
        }
    }
}

?>