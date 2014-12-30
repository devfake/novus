<?php

  require '../src/Database.php';
  date_default_timezone_set("Europe/Berlin");

  class NovusTest extends PHPUnit_Framework_TestCase {

    private $dir;
    private $novus;

    /**
     * Init the tests.
     */
    public function setUp()
    {
      $this->dir = __DIR__ . '/database';
      $this->novus = new Database(array(
        'primaryKey' => 'id',
        'path' => 'database',
        'table' => 'test_db'
      ));
    }

    /**
     * Remove all soft deleted files.
     */
    public function tearDown()
    {
      $savesFiles = scandir($this->dir . '/saves');
      foreach($savesFiles as $file) {
        if(substr($file, 0, 7) == 'test_db') {
          unlink($this->dir . '/saves/' . $file);
        }
      }
    }

    /**
     * Test if file was created.
     */
    public function testIfFileWasCreated()
    {
      $this->novus->table('test_db')->create();

      $this->assertFileExists($this->dir . '/test_db.json');
    }

    /**
     * Test if file value is correct.
     */
    public function testIfFileHasCorrectContentAfterCreating()
    {
      $this->assertEquals('{"table":"test_db","id":1,"fields":[["id"]],"data":[]}', file_get_contents($this->dir . '/test_db.json'));
    }

    /**
     * Test if file was complete deleted.
     */
    public function testIfFileWasCompleteRemoved()
    {
      $this->novus->remove(true);
      $this->assertFileNotExists($this->dir . '/test_db.json');
      $this->assertFileNotExists($this->dir . '/saves/test_db-' . date('d.m.Y--H-i', time()) . '.json');
    }

    /**
     * Test if file was created with fields parameter.
     * todo: mit der array schreibweise ebenfalls testen
     */
    public function testIfFileWasCreatedWithFields()
    {
      $this->novus->table('test_db')->create('house, words');

      $this->assertEquals('{"table":"test_db","id":1,"fields":[["id"],["house"],["words"]],"data":[]}', file_get_contents($this->dir . '/test_db.json'));
    }

    /**
     * Insert new data.
     */
    public function testInsertNewData()
    {
      $this->novus->table('test_db')->insert('house = Stark, words = Winter Is Coming');

      $this->assertEquals('{"table":"test_db","id":2,"fields":[["id"],["house"],["words"]],"data":[[[1],["Stark"],["Winter Is Coming"]]]}', file_get_contents($this->dir . '/test_db.json'));
    }

    /**
     * Test the method for adding new fields.
     */
    public function testAddFields()
    {
      $this->novus->table('test_db')->addFields('honorable');

      $this->assertEquals('{"table":"test_db","id":2,"fields":[["id"],["house"],["words"],["honorable"]],"data":[[[1],["Stark"],["Winter Is Coming"],[]]]}', file_get_contents($this->dir . '/test_db.json'));
    }

    /**
     * Test update for all data.
     */
    public function testUpdateForAllData()
    {
      // Create dummy data.
      $this->novus->table('test_db')->insert('house = Bolton, words = Our Blades are Sharp, honorable = false');
      $this->novus->table('test_db')->update('house = MARTIN, words = kill kill kill');

      $this->assertEquals('{"table":"test_db","id":3,"fields":[["id"],["house"],["words"],["honorable"]],"data":[[[1],["MARTIN"],["kill kill kill"],[]],[[2],["MARTIN"],["kill kill kill"],["false"]]]}', file_get_contents($this->dir . '/test_db.json'));
    }

    /**
     * Test it lastID method works correct.
     */
    public function testIfLastIDIsCorrect()
    {
      $lastID = $this->novus->table('test_db')->lastID();

      $this->assertEquals(2, $lastID);
    }

    /**
     * Test if file was deleted with soft delete.
     */
    public function testIfFileWasRemovedWithSoftDelete()
    {
      $this->novus->remove();
      $this->assertFileNotExists($this->dir . '/test_db.json');
      $this->assertFileExists($this->dir . '/saves/test_db-' . date('d.m.Y--H-i', time()) . '.json');
    }
  }