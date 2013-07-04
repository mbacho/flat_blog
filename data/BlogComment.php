<?php

/**
 * Description of BlogComment
 *
 * @author ero <choeringa@gmail.com>
 */
require_once 'basic/DataObject.php';

class BlogComment extends DataObject {

  private $id, $author, $content, $created, $fMgr, $parent;

  public static function fromStore($id, $parent = null) {
    if (!BlogPost::inStore($parent)) {
      return null;
    }
    if (!BlogPost::inStore($id, $parent)) {
      return null;
    }
    $mgr = new FileMgr();
    $root = Slim::getInstance()->config('root_folder');
    $fp = $mgr->join($mgr->join($root, $parent), $id);
    $fyl = fopen($fp, 'r');

    $created = $mgr->readLine($fyl);
    $author = $mgr->readLine($fyl);
    $content = $mgr->readLine($fyl);
    $bc = new BlogComment($author, $content);
    $bc->created = $created;
    $bc->id = $id;
    $bc->parent = $parent;
    return $bc;
  }

  public static function inStore($id, $parent = null) {
    $mgr = new FileMgr();
    $root = Slim::getInstance()->config('root_folder');
    $fpath = $mgr->join($mgr->join($root, $parent), $id);
    return $mgr->exists($fpath);
  }

  public function __construct($parent, $author = '', $content = '') {
    $this->author = $author;
    $this->content = $content;
    $this->fMgr = new FileMgr();
    $this->created = null;
    $this->id = null;
    $this->parent = $parent;
  }

  public function delete() {
    return $this->fMgr->delFile($this->getFilePath());
  }

  public function getFilePath() {
    return $this->getPathForFile($this->id);
  }

  private function getPathForFile($f) {
    $root = Slim::getInstance()->config('root_folder');
    return $this->fMgr->join($this->fMgr->join($root, $this->parent), $f);
  }

  protected function edit() {
    $fpath = $this->getFilePath();
    if (!$this->fMgr->exists($fpath)) {
      return $this->__create();
    }
    $temp_path = $this->getPathForFile('com_temp');
    $fyl = fopen($fpath, 'r');
    $temp = fopen($temp_path, 'w');

    fwrite($temp, fgets($fyl)); //skip created
    fwrite($temp, "$this->author\n");
    fwrite($temp, $this->content);

    fclose($fyl);
    fclose($temp);
    $this->fMgr->delFile($fpath);
    $this->fMgr->moveFile($temp_path, $fpath);
    return true;
  }

  public function save() {
    $fpath = $this->fMgr->join($this->parent, $this->id);
    if ($this->fMgr->exists($fpath)) {
      return $this->edit();
    } else {
      return $this->__create();
    }
  }

  protected function __create() {
    $this->id = $this->__newId();
    $fpath = $this->getFilePath();
    if ($this->fMgr->exists($fpath)) {//hash confict
      sleep(1);
      return $this->__create();
    } else {
      $fyl = fopen($fpath, 'w');
      fwrite($fyl, "$this->created\n");
      fwrite($fyl, "$this->author\n");
      fwrite($fyl, $this->content);
      fclose($fyl);
      return true;
    }
  }

  public function getAuthor() {
    return $this->author;
  }

  public function getContent() {
    return $this->content;
  }

  public function getId() {
    return $this->id;
  }

  public function getCreated() {
    return $this->created;
  }

  public function setAuthor($author) {
    $this->author = $author;
  }

  public function setContent($content) {
    $this->content = $content;
  }

  private function __newId() {
    $salt = date('U');
    $folder_name = sha1($this->author . $salt);
    return $folder_name;
  }

}

?>
