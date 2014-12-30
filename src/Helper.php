<?php

  namespace Devfake\Novus;

  class Helper {

    /**
     * The root folder path.
     */
    public static function rootPath()
    {
      return __DIR__ . '/../../../..';
    }

    /**
     * Create database and saves folder.
     */
    public static function createDatabaseAndSavesFolder($databasePath)
    {
      if( ! is_dir(self::rootPath() . '/' . $databasePath)) {
        if( ! mkdir(self::rootPath() . '/' . $databasePath, 0777, true)) {
          throw new FolderCanNotCreatedException('Database Folder ' . $databasePath . ' could not be created. Check your permissions.');
        }
      }

      if( ! is_dir(self::rootPath() . '/' . $databasePath . '/saves/')) {
        if( ! mkdir(self::rootPath() . '/' . $databasePath . '/saves/', 0777, true)) {
          throw new FolderCanNotCreatedException('Folder for saves in ' . $databasePath . ' could not be created. Check your permissions.');
        }
      }
    }

    /**
     * Boilerplate for empty json file.
     */
    public static function boilerplate($tablename, $primaryKey)
    {
      return '{"table":"' . $tablename . '","' . $primaryKey . '":1,"fields":[["' . $primaryKey . '"]],"data":[]}';
    }

    /**
     * Flatten array.
     */
    public static function flatten($data)
    {
      if(isset($data[0])) {
        return $data[0];
      }

      return '';
    }
  }

  class TableAlreadyExistsException extends \Exception {}
  class TableDoesNotExistsException extends \Exception {}
  class NoTablenameSelectedException extends \Exception {}
  class FolderCanNotCreatedException extends \Exception {}
  class FieldsNotExistsException extends \Exception {}
  class DataNotFoundException extends \Exception {}