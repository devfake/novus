<?php

  namespace Devfake\Novus;

  interface Database {

    /**
     * Update database.
     */
    public function update($values);

    /**
     * Select specific data by values from database.
     *
     * Return all data if no value or the value '*' is given.
     */
    public function select($values = null);

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
     * Insert new data in file.
     */
    public function insert($values);

    /**
     * Conditions to specify which data to return.
     */
    public function where($conditions);

    /**
     * Create the table with optional fields.
     */
    public function create($fields = null);

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
    public function lastID();

    /**
     * Change the primary key of a table.
     */
    public function changePrimaryKey($key);
  }