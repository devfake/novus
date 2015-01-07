<?php

  namespace Devfake\Novus;

  interface Contract {

    /**
     * Create the table with optional fields.
     */
    public function create($fields = null);

    /**
     * Select specific data by values from database.
     *
     * Return all data if no value or the value '*' is given.
     */
    public function select($values = null);

    /**
     * Insert new data in file.
     */
    public function insert($values);

    /**
     * Update database.
     */
    public function update($values);

    /**
     * Remove the complete database file. Save a backup in 'database/saves'.
     * Pass 'true' in parameter to avoid the softdelete.
     */
    public function remove($delete = false);

    /**
     * Delete the data in a database file. Save a backup in 'database/saves'.
     * Pass 'true' in parameter to avoid the softdelete.
     */
    public function delete($delete = false);

    /**
     * Conditions to specify which data to return.
     */
    public function where($conditions);

    /**
     * Order the output.
     */
    public function orderBy($fields);

    /**
     * Limit the output.
     */
    public function limit($limit, $offset = null, $reverse = false);

    /**
     * Choose a table by name.
     */
    public function table($name);

    /**
     * Add fields for a table.
     */
    public function addFields($fields);

    /**
     * Remove fields from a table.
     */
    public function removeFields($fields);

    /**
     * Get the last primary key of a table.
     */
    public function lastPrimaryKey();

    /**
     * Return the primary key of next insert data.
     */
    public function nextPrimaryKey();

    /**
     * Get the first data of a table.
     */
    public function first();

    /**
     * Get the last data of a table.
     */
    public function last();

    /**
     * Find data by primary key.
     */
    public function find($id);

    /**
     * Find data by primary key. It no data was found, return exception.
     */
    public function findOrFail($id);

    /**
     * Change the primary key of a table.
     */
    public function changePrimaryKey($key);

    /**
     * Change the fields name.
     */
    public function changeFields($fields);
  }