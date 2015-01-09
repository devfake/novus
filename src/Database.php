<?php

  namespace Devfake\Novus;

  /**
   * Novus.
   * JSON-File Database For PHP.
   *
   * @author Viktor Geringer <devfakeplus@googlemail.com>
   * @version 0.3.9
   * @license The MIT License (MIT)
   * @link https://github.com/devfake/novus
   */
  class Database implements Contract {

    /**
     * Store the current table name for working.
     */
    private $tableName = null;

    /**
     * Your path for your database folder.
     * Relative to your root folder (where your composers vendor folder is stored).
     */
    private $databasePath = 'database';

    /**
     * Your primary key for the tables. Default is 'id'.
     */
    private $primaryKey = 'id';

    /**
     * Check if conditions was set.
     */
    private $where = false;

    /**
     * Set true or false to handle if table conditions must be checked.
     */
    private $checkTable = true;

    /**
     * Saves all condition expressions.
     */
    private $conditions = ['<=' => [], '>=' => [], '!=' => [], '=' => [], '>' => [], '<' => []];

    /**
     * Saves all orderBy conditions.
     */
    private $orderBy = [];

    /**
     * Saves all limit conditions.
     */
    private $limit = ['limit' => null, 'offset' => null, 'reverse' => false];

    /**
     * Unleash the option parser.
     */
    public function __construct($options = null)
    {
      set_time_limit(0);
      $this->parseOptions($options);
      Helper::createDatabaseAndSavesFolder($this->databasePath);
    }

    /**
     * Create the table with optional fields.
     */
    public function create($fields = null)
    {
      $this->handleTableConditions();

      // Create an empty database file and give them write access.
      file_put_contents($this->tablePath(), Helper::boilerplate($this->tableName, $this->primaryKey));
      chmod($this->tablePath(), 0777);

      if($fields) {
        $this->checkTable = false;
        $this->addFields($fields);
      }
    }

    /**
     * Select specific data by values from database.
     * Return all data if no value or the value '*' is passed.
     */
    public function select($values = null)
    {
      $this->handleTableConditions(true);

      $tableFile = $this->tableFile();
      $newTableFile = $this->flattenData($tableFile);
      $newTableFile = $this->checkWhereConditions($newTableFile);

      $values = $this->convertStringParameterToArrayLight($values);

      if($values && $values[0] != '*') {
        $tmpData = [];

        foreach($newTableFile as $data) {
          $tmpData[] = array_intersect_key($data, array_flip($values));
        }

        $newTableFile = $tmpData;
      }

      $newTableFile = $this->limitConditions($newTableFile);

      return $this->orderByConditions($newTableFile);
    }

    /**
     * Insert new data in file.
     */
    public function insert($values)
    {
      $this->handleTableConditions(true);
      $values = $this->convertStringParameterToArray($values);

      $tableFile = $this->tableFile();
      $newTableFile = [];

      $this->checkForErrors($values, $tableFile);

      // First insert the primary key.
      $newTableFile[] = (array) $this->nextPrimaryKey();

      foreach($tableFile->fields as $key => $value) {
        for($i = 0; $i < count($values); $i++) {
          // Skip the primary key.
          if($key == 0) continue;

          if($value[0] == $values[$i][0]) {
            $newTableFile[] = (array) $values[$i][1];
            break;
          }

          if($i == count($values) - 1) {
            $newTableFile[] = [];
          }
        }
      }

      $tableFile->data[] = $newTableFile;

      // Increase the primary key.
      $primaryKey = $this->tablePrimaryKey($tableFile);
      $tableFile->$primaryKey++;

      $this->writeFile($tableFile);
    }

    /**
     * Update database.
     */
    public function update($values)
    {
      $this->handleTableConditions(true);
      $values = $this->convertStringParameterToArray($values);

      $tableFile = $this->tableFile();
      $tableFields = array_map(['Devfake\Novus\Helper', 'flatten'], $tableFile->fields);

      $this->checkForErrors($values, $tableFile);

      // Save current data.
      $saveTableFile = [];
      foreach($tableFile->data as $data) {
        $saveTableFile[] = array_map(['Devfake\Novus\Helper', 'flatten'], $data);
      }

      // Get the field names for keys.
      $fieldNames = [];
      foreach($tableFields as $key => $value) {
        foreach($values as $k => $v) {
          if($value == $v[0]) {
            $fieldNames[$v[1]] = $key;
          }
        }
      }

      // Update all data. No 'where' conditions available currently.
      foreach($saveTableFile as & $saved) {
        foreach($fieldNames as $key => $value) {
          $saved[$value] = $key;
        }
      }

      // See the warning: http://uk.php.net/manual/en/control-structures.foreach.php
      unset($saved);

      // Connects the new values.
      $tmpTableFile = [];
      $i = 0;
      foreach($saveTableFile as $saved) {
        foreach($saved as $save) {
          if($save == '') {
            $tmpTableFile[$i][] = [];
          } else {
            $tmpTableFile[$i][] = (array) $save;
          }
        }
        $i++;
      }

      $tableFile->data = $tmpTableFile;

      $this->writeFile($tableFile);
    }

    /**
     * Remove the complete database file. Save a backup in 'database/saves'.
     * Pass 'true' in parameter to avoid the soft delete.
     */
    public function remove($delete = false)
    {
      $this->handleTableConditions(true);

      if($delete === true) {
        return unlink($this->tablePath());
      }

      return rename($this->tablePath(), $this->savesPath());
    }

    /**
     * Delete the data in a database file. Save a backup in 'database/saves'.
     * Pass 'true' in parameter to avoid the soft delete.
     */
    public function delete($delete = false)
    {
      $this->handleTableConditions(true);

      $tableFile = $this->tableFile();

      if(count($tableFile->data)) {
        if($delete !== true) {
          copy($this->tablePath(), $this->savesPath());
        }

        // It delete all data. No 'where' conditions available currently.
        $tableFile->data = [];

        $this->writeFile($tableFile);
      }
    }

    /**
     * Conditions to specify which data to return.
     * Currently it working only with one condition for '='.
     */
    public function where($conditions)
    {
      foreach($this->conditions as $key => $value) {
        $this->conditions[$key] = array_map('trim', explode($key, $conditions));

        // Remove all other condition arrays.
        if(count($this->conditions[$key]) <= 1) {
          unset($this->conditions[$key]);
        }
      }

      // Remove all other condition arrays. Need to rewrite.
      foreach($this->conditions as $key => $value) {
        foreach($value as $val) {
          $lastChar = substr($val, -1);
          $firstChar = $val[0];

          if($lastChar == '=' || $lastChar == '<' || $lastChar == '>' || $lastChar == '!'
            || $firstChar == '=' || $firstChar == '<' || $firstChar == '>' || $firstChar == '!') {
            unset($this->conditions[$key]);
          }
        }
      }

      $this->where = true;

      return $this;
    }

    /**
     * Order the output.
     */
    public function orderBy($fields)
    {
      if(is_array($fields)) {
        $i = 0;

        foreach($fields as $key => $value) {
          $this->orderBy[$i][0] = is_int($key) ? $value : $key;
          $this->orderBy[$i][1] = is_int($key) ? '' : $value;

          $i++;
        }
      } else {
        $fields = explode(',', $fields);

        foreach($fields as $field) {
          $this->orderBy[] = explode(' ', trim($field));
        }
      }

      return $this;
    }

    /**
     * Limit the output.
     */
    public function limit($limit, $offset = null, $reverse = false)
    {
      $this->limit['limit'] = $limit;
      $this->limit['offset'] = is_bool($offset) ? null : $offset;
      $this->limit['reverse'] = is_bool($offset) ? $offset : $reverse;

      return $this;
    }

    /**
     * Choose a table by name.
     */
    public function table($name)
    {
      $this->tableName = $name;

      return $this;
    }

    /**
     * Add fields for a table.
     */
    public function addFields($fields)
    {
      $this->handleTableConditions(true);

      $fields = $this->convertStringParameterToArrayLight($fields);

      $tableFile = $this->tableFile();

      foreach($fields as $field) {
        // Add the new fields.
        foreach($tableFile->fields as $key => $tableField) {
          if($tableField[0] == $field) {
            break;
          }

          if($key == count($tableFile->fields) - 1) {
            $tableFile->fields[] = (array) $field;
          }
        }

        // Add empty data for every new field.
        foreach($tableFile->data as $key => $tableData) {
          if(count($tableData) != count($tableFile->fields)) {
            $tableFile->data[$key][] = [];
          }
        }
      }

      $this->writeFile($tableFile);
    }

    /**
     * Remove fields from a table.
     */
    public function removeFields($fields)
    {
      $this->handleTableConditions(true);
      $keys = [];
      $tmpData = [];

      $fields = $this->convertStringParameterToArrayLight($fields);

      $tableFile = $this->tableFile();

      // Delete the fields.
      foreach($fields as $field) {
        foreach($tableFile->fields as $key => $tableField) {
          if($tableField[0] == $field) {
            $keys[] = $key;
            unset($tableFile->fields[$key]);
            break;
          }
        }
      }

      // Delete the data.
      foreach($keys as $index) {
        foreach($tableFile->data as $key => $tableData) {
          unset($tableFile->data[$key][$index]);
          $tmpData[$key] = array_values($tableFile->data[$key]);
        }
      }

      // Reset keys.
      $tableFile->fields = array_values($tableFile->fields);
      $tableFile->data = $tmpData ?: $tableFile->data;

      $this->writeFile($tableFile);
    }

    /**
     * Get the last primary key of a table.
     * If no data exists, return null.
     */
    public function lastPrimaryKey()
    {
      $this->handleTableConditions(true);

      $tableFile = $this->tableFile();

      return end($tableFile->data)[0][0] ?: null;
    }

    /**
     * Return the primary key of next insert data.
     */
    public function nextPrimaryKey()
    {
      $this->handleTableConditions(true);

      $tableFile = (array) $this->tableFile();
      $primaryKey = $this->tablePrimaryKey($tableFile);

      return $tableFile[$primaryKey];
    }

    /**
     * Get the first data of a table.
     */
    public function first()
    {
      $this->handleTableConditions(true);

      $tableFile = $this->tableFile();
      $tableFile = $this->flattenData($tableFile);

      return count($tableFile) ? reset($tableFile) : [];
    }

    /**
     * Get the last data of a table.
     */
    public function last()
    {
      $this->handleTableConditions(true);

      $tableFile = $this->tableFile();
      $tableFile = $this->flattenData($tableFile);

      return count($tableFile) ? end($tableFile) : [];
    }

    /**
     * Find data by primary key.
     */
    public function find($id)
    {
      $this->handleTableConditions(true);
      $primaryKey = $this->tablePrimaryKey($this->tableFile());

      $data = $this->where($primaryKey . ' = ' . $id)->select();

      return isset($data[0]) ? $data[0] : [];
    }

    /**
     * Find data by primary key. It no data was found, return exception.
     */
    public function findOrFail($id)
    {
      $data = $this->find($id);

      if( ! $data) {
        throw new DataNotFoundException('No data found for primary key ' . $id);
      }

      return $data;
    }

    /**
     * Change the primary key of a table.
     */
    public function renamePrimaryKey($key)
    {
      $this->handleTableConditions(true);
      $tmpTableFile = [];

      $tableFile = (array) $this->tableFile();

      // Detect the old primary key, set the new and delete the old.
      $primaryKey = $this->tablePrimaryKey($tableFile);
      $tmpTableFile[$key] = $tableFile[$primaryKey];
      unset($tableFile[$primaryKey]);

      // Order the new primary key.
      $tableFile = array_slice($tableFile, 0, 1, true) + $tmpTableFile + array_slice($tableFile, 1, null, true);

      // Change the fields value for primary key.
      foreach($tableFile['fields'] as $index => & $field) {
        if($index == 0) {
          $field[0] = $key;
        }
      }

      $this->writeFile($tableFile);
    }

    /**
     * Change the fields name.
     */
    public function renameFields($fields)
    {
      $this->handleTableConditions(true);
      $fields = $this->convertStringParameterToArray($fields);

      $tableFile = $this->tableFile();

      foreach($tableFile->fields as $key => & $value) {
        for($i = 0; $i < count($fields); $i++) {
          if($value[0] == $fields[$i][0]) {
            $value[0] = $fields[$i][1];
          }
        }
      }

      $this->writeFile($tableFile);
    }

    /**
     * Parse optional options.
     */
    private function parseOptions($options)
    {
      if(is_string($options)) {
        return $this->tableName = $options;
      }

      if(is_array($options)) {
        foreach($options as $key => $value) {
          switch($key) {
            case 'table': $this->tableName = $value; break;
            case 'path': $this->databasePath = $value; break;
            case 'primaryKey': $this->primaryKey = $value; break;
          }
        }
      }
    }

    /**
     * Wrapper for table conditions.
     */
    private function handleTableConditions($exists = false)
    {
      if( ! $this->checkTable) return;

      // Check if a table name is selected.
      if( ! $this->tableName) {
        throw new NoTableNameSelectedException('No table selected.');
      }

      // Check if table MUST exists.
      if($exists) {
        if( ! file_exists($this->tablePath())) {
          throw new TableDoesNotExistsException('Table ' . $this->tableName . ' does not exists.');
        }

        return;
      }

      // Check if table must NOT exists.
      if(file_exists($this->tablePath())) {
        throw new TableAlreadyExistsException('Table ' . $this->tableName . ' already exists.');
      }
    }

    /**
     * Get the relative root path for the table.
     */
    private function tablePath()
    {
      return Helper::rootPath() . '/' . $this->databasePath . '/' . $this->tableName . '.json';
    }

    /**
     * Get the relative root path for saves with correct timestamps for filename.
     */
    private function savesPath()
    {
      return Helper::rootPath() . '/' . $this->databasePath . '/saves/' . $this->tableName . '-' . date('d.m.Y--H-i', time()) . '.json';
    }

    /**
     * Get the table file.
     */
    private function tableFile()
    {
      return json_decode(file_get_contents($this->tablePath()));
    }

    /**
     * Get the primary key of the table.
     */
    private function tablePrimaryKey($tableFile)
    {
      return array_keys((array) $tableFile)[1];
    }

    /**
     * Convert the 'string-parameter-method' into an array and split them between '='.
     * Need for the insert() and update() methods.
     */
    private function convertStringParameterToArray($values)
    {
      $tmpValues = [];
      $i = 0;

      if(is_string($values)) {
        $values = array_map('trim', explode(',', $values));

        foreach($values as $key => $value) {
          $tmpValues[] = array_map('trim' , explode('=', $value));
        }
      } else {
        // Restructure given array.
        foreach($values as $key => $value) {
          $tmpValues[$i][0] = trim($key);
          $tmpValues[$i][1] = trim($value);
          $i++;
        }
      }

      return $tmpValues;
    }

    /**
     * If the 'string-parameter-method' was passed, convert into an array for continue working.
     */
    private function convertStringParameterToArrayLight($values)
    {
      if(is_string($values)) {
        return array_map('trim', explode(',', $values));
      }

      return $values;
    }


    /**
     * Check limit conditions and update the data.
     */
    private function limitConditions($table)
    {
      $limit = $this->limit['limit'];
      $offset = $this->limit['offset'];
      $reverse = $this->limit['reverse'];

      if($limit) {

        if($reverse) {
          $table = array_reverse($table);
        }

        if($offset && is_int($offset)) {
          if($reverse) {
            return array_reverse(array_slice($table, $limit, $offset));
          }

          return array_slice($table, $limit, $offset);
        }

        if($reverse) {
          return array_reverse(array_slice($table, 0, $limit));
        }

        return array_slice($table, 0, $limit);
      }

      return $table;
    }

    /**
     * Check all conditions for order data and return them.
     */
    private function orderByConditions($table)
    {
      if($this->orderBy) {
        $callbackData[] = $table;

        foreach($this->orderBy as $orderBy) {
          $callbackData[] = $orderBy[0];
          // Convert ASC and DESC to own constant keys.
          $callbackData[] = isset($orderBy[1]) ? (strtolower($orderBy[1]) == 'asc' ? 4 : 3) : 4;
        }

        return call_user_func_array([$this, "array_orderby"], $callbackData);
      }

      return $table;
    }

    /**
     * Write the new file.
     */
    private function writeFile($data)
    {
      file_put_contents($this->tablePath(), json_encode($data, JSON_UNESCAPED_UNICODE));
    }

    /**
     * Modified version of http://php.net/array_multisort#100534.
     */
    private function array_orderby()
    {
      $args = func_get_args();
      $data = array_shift($args);

      foreach($args as $n => $field) {
        if(is_string($field)) {
          $tmp = [];
          foreach($data as $key => $row)
            if(isset($row[$field])) {
              $tmp[$key] = $row[$field];
            } else {
              throw new FieldsNotExistsException('The field ' . $args[$n] . ' dont exist.');
            }
          $args[$n] = $tmp;
        }
      }

      $args[] = & $data;
      call_user_func_array('array_multisort', $args);

      return array_pop($args);
    }

    /**
     * Check and filter the conditions.
     */
    private function checkWhereConditions($data)
    {
      if($this->where) {
        $temp = $data;
        $data = [];

        foreach($this->conditions as $key => $value) {
          foreach($temp as $_data) {
            $field = $_data[$value[0]];

            switch($key) {
              case '=':
                if($field == $value[1]) $data[] = $_data; break;
              case '>':
                if($field > $value[1]) $data[] = $_data; break;
              case '<':
                if($field < $value[1]) $data[] = $_data; break;
              case '!=':
                if($field != $value[1]) $data[] = $_data; break;
              case '<=':
                if($field <= $value[1]) $data[] = $_data; break;
              case '>=':
                if($field >= $value[1]) $data[] = $_data; break;
            }
          }
        }

        $this->where = false;
      }

      return $data;
    }

    /**
     * Flat the selected data from table and set the right key <=> value.
     */
    private function flattenData($tableFile)
    {
      $newTableFile = [];

      // Flat the data array to work with.
      foreach($tableFile->data as $data) {
        $newTableFile[] = array_map([__NAMESPACE__ . '\Helper', 'flatten'], $data);
      }

      // Now we need to flat the real fields.
      $tableFile->fields = array_map([__NAMESPACE__ . '\Helper', 'flatten'], $tableFile->fields);

      // Set the right field names as keys.
      foreach($newTableFile as & $new) {
        $i = 0;
        foreach($new as $n) {
          if($i > count($tableFile->fields) - 1) continue;

          $new[$tableFile->fields[$i]] = $new[$i];
          unset($new[$i]);

          $i++;
        }
      }

      return $newTableFile;
    }

    /**
     * Iterate and check if fields available in table.
     */
    private function checkForErrors($values, $tableFile)
    {
      $errors = [];

      foreach($values as $key => $value) {
        $i = 0;
        foreach($tableFile->fields as $k => $v) {
          if($v[0] == trim($value[0])) continue;

          // If the code at the end of the loop comes here, there are errors.
          if($i == count($tableFile->fields) - 1) {
            $errors[] = trim($value[0]);
          }
          $i++;
        }
      }

      if($errors) {
        throw new FieldsNotExistsException('The fields ' . implode(', ', $errors) . ' dont exists.');
      }
    }
  }