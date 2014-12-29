<?php

  namespace Devfake\Novus;

  /**
   * Novus.
   * JSON-File Database For PHP.
   *
   * @author Viktor Geringer <devfakeplus@googlemail.com>
   * @version 0.1.3
   * @license The MIT License (MIT)
   * @link https://github.com/devfake/novus
   */
  class Novus implements Database {

    /**
     * Store the current tablename for working.
     */
    private $tablename = null;

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
    private $conditions = ['=' => [], '>' => [], '<' => [], '!=' => [], '<=' => [], '>=' => []];

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
      file_put_contents($this->tablePath(), Helper::boilerplate($this->tablename, $this->primaryKey));
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

      $newTableFile = $this->checkConditions($newTableFile);

      // If the 'string-parameter-method' was passed, convert into an array for continue working.
      if(gettype($values) === 'string') {
        $values = array_map('trim', explode(',', $values));
      }

      if($values && $values[0] != '*') {
        $tmpData = [];

        foreach($newTableFile as $data) {
          $tmpData[] = array_intersect_key($data, array_flip($values));
        }

        $newTableFile = $tmpData;
      }

      return $newTableFile;
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
      $newTableFile[] = (array) (int) $tableFile->{$this->primaryKey}++;

      foreach($tableFile->fields as $key => $value) {
        for($i = 0; $i < count($values); $i++) {
          // Skip the primary key.
          if($key == 0) continue;

          if($value[0] == trim($values[$i][0])) {
            $newTableFile[] = (array) trim($values[$i][1]);
            break;
          }

          if($i == count($values) - 1) {
            $newTableFile[] = [];
          }
        }
      }

      $tableFile->data[] = $newTableFile;

      $newTableFile = json_encode($tableFile, JSON_UNESCAPED_UNICODE);
      file_put_contents($this->tablePath(), $newTableFile);
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

      $tableFile = json_encode($tableFile, JSON_UNESCAPED_UNICODE);
      file_put_contents($this->tablePath(), $tableFile);
    }

    /**
     * Remove the complete database file. Save a backup in 'database/saves'.
     * Pass 'true' in parameter to avoid the softdelete.
     */
    public function remove($delete = false)
    {
      $this->handleTableConditions(true);

      if( ! $delete) {
        rename($this->tablePath(), $this->savesPath());
      } else {
        unlink($this->tablePath());
      }
    }

    /**
     * Delete the data in a database file. Save a backup in 'database/saves'.
     * Pass 'true' in parameter to avoid the softdelete.
     */
    public function delete($delete = false)
    {
      $this->handleTableConditions(true);

      $tableFile = $this->tableFile();

      if(count($tableFile->data)) {
        if( ! $delete) {
          copy($this->tablePath(), $this->savesPath());
        }

        // It delete all data. No 'where' conditions available currently.
        $tableFile->data = [];

        $tableFile = json_encode($tableFile, JSON_UNESCAPED_UNICODE);
        file_put_contents($this->tablePath(), $tableFile);
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

      $this->where = true;

      return $this;
    }

    /**
     * Choose a table by name.
     */
    public function table($name)
    {
      $this->tablename = $name;

      return $this;
    }

    /**
     * Add fields for a table.
     */
    public function addFields($fields)
    {
      $this->handleTableConditions(true);

      // If the 'string-parameter-method' was passed, convert into an array for continue working.
      if(gettype($fields) === 'string') {
        $fields = array_map('trim', explode(',', $fields));
      }

      $tableFile = $this->tableFile();

      foreach($fields as $field) {
        // Add the new fields.
        foreach($tableFile->fields as $key => $value) {
          if($value[0] == $field) {
            echo 'Field <b>' . $field . '</b> already exists in <b>' . $this->tablename . '</b>.<br>';
            break;
          }

          if($key == count($tableFile->fields) - 1) {
            $tableFile->fields[] = (array) $field;
          }
        }

        // Add empty data for every new field.
        foreach($tableFile->data as $key => $value) {
          if(count($value) != count($tableFile->fields)) {
            $tableFile->data[$key][] = [];
          }
        }
      }

      $tableFile = json_encode($tableFile, JSON_UNESCAPED_UNICODE);
      file_put_contents($this->tablePath(), $tableFile);
    }

    /**
     * Remove fields from a table.
     */
    public function removeFields($fields)
    {
      // TODO: Implement removeFields() method.
    }

    /**
     * Get the last primary key of a table.
     * If no data exists, return 0.
     */
    public function lastID()
    {
      $this->handleTableConditions(true);

      $tableFile = $this->tableFile();

      return end($tableFile->data)[0][0] ?: 0;
    }

    /**
     * Return the primary key of next insert data.
     */
    public function currentID()
    {
      $this->handleTableConditions(true);

      $tableFile = (array) $this->tableFile();
      $keys = array_keys($tableFile);

      return $tableFile[$keys[1]];
    }

    /**
     * Get the first data of a table.
     */
    public function first()
    {
      $this->handleTableConditions(true);

      $tableFile = $this->tableFile();
      $tableFile = $this->flattenData($tableFile);

      return count($tableFile) ? $tableFile[0] : [];
    }

    /**
     * Get the last data of a table.
     */
    public function last()
    {
      $this->handleTableConditions(true);

      $tableFile = $this->tableFile();
      $tableFile = $this->flattenData($tableFile);

      return count($tableFile) ? $tableFile[count($tableFile) - 1] : [];
    }

    /**
     * Find data by primary key.
     */
    public function find($id)
    {
      $this->handleTableConditions(true);

      $data = $this->where($this->primaryKey . ' = ' . $id)->select();

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
    public function changePrimaryKey($key)
    {
      // TODO: Implement changePrimaryKey() method.
    }

    /**
     * Parse optional options.
     */
    private function parseOptions($options)
    {
      if(gettype($options) === 'string') {
        return $this->tablename = $options;
      }

      if(gettype($options) === 'array') {
        foreach($options as $key => $value) {
          switch($key) {
            case 'table': $this->tablename = $value; break;
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

      // Check if a tablename is selected.
      if( ! $this->tablename) {
        throw new NoTablenameSelectedException('No table selected.');
      }

      // Check if table MUST exists.
      if($exists) {
        if( ! file_exists($this->tablePath())) {
          throw new TableDoesNotExistsException('Table ' . $this->tablename . ' does not exists.');
        }

        return;
      }

      // Check if table must NOT exists.
      if(file_exists($this->tablePath())) {
        throw new TableAlreadyExistsException('Table ' . $this->tablename . ' already exists.');
      }
    }

    /**
     * Get the relative root path for the table.
     */
    private function tablePath()
    {
      return Helper::rootPath() . '/' . $this->databasePath . '/' . $this->tablename . '.json';
    }

    /**
     * Get the relative root path for saves with correct timestamps for filename.
     */
    private function savesPath()
    {
      return Helper::rootPath() . '/' . $this->databasePath . '/saves/' . $this->tablename . '-' . date('d.m.Y--H-i', time()) . '.json';
    }

    /**
     * Get the table file.
     */
    private function tableFile()
    {
      return json_decode(file_get_contents($this->tablePath()));
    }

    /**
     * Convert the 'string-parameter-method' into an array and split them between '='.
     * Need for the insert() and update() methods.
     */
    private function convertStringParameterToArray($values)
    {
      $tmpValues = [];
      $i = 0;

      if(gettype($values) === 'string') {
        $values = array_map('trim', explode(',', $values));

        foreach($values as $key => $value) {
          $tmpValues[] = array_map('trim' , explode('=', $value));
        }
      } else {
        // Restructure given array.
        foreach($values as $key => $value) {
          $tmpValues[$i][0] = $key;
          $tmpValues[$i][1] = $value;
          $i++;
        }
      }

      return $tmpValues;
    }

    /**
     * Check and filter the conditions.
     */
    private function checkConditions($data)
    {
      if($this->where) {
        $temp = $data;
        $data = [];

        foreach($this->conditions as $key => $value) {
          foreach($temp as $_data) {
            if($_data[$value[0]] == $value[1]) {
              $data[] = $_data;
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

      // Set the right fieldnames as keys.
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