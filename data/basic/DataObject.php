<?php

/**
 *
 * @author ero <choeringa@gmail.com>
 */
abstract class DataObject {

  public abstract function save();

  protected function edit() {
    
  }

  protected function __create() {
    
  }

  public abstract function delete();

  public abstract static function inStore($id, $parent = null);

  public abstract static function fromStore($id, $parent = null);
}

?>
