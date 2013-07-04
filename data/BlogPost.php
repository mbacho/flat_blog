<?php

/**
 * Description of BlogPost
 *
 * @author ero <choeringa@gmail.com>
 */
require_once 'basic/DataObject.php';
require_once 'FileMgr.php';

class BlogPost extends DataObject {

  /**
   * Stores the blog's meta-data e.g. author,created date,comment count e.t.c.
   * Format of a config file. The lines in the file correspond to the following 
   * <ul>
   * <li>created timestamp</li>
   * <li>author</li>
   * <li>name</li>
   * <li>content</li>
   * </ul>
   * @var string
   */
  private static $CONFIG_FILE = "__post__";
  private static $CONFIG_FILE_TEMP = "__post__temp";

  /**
   * Unix timestamp when it was created
   * @var long int
   */
  private $created;

  /**
   * Name of the blog post
   * @var string
   */
  private $name;

  /**
   * Author of the blog post
   * @var string
   */
  private $author;

  /**
   * Content of the blog post
   * @var string
   */
  private $content;

  /**
   * Folder of the blog post
   * @var string
   */
  private $folder_name;

  /** An instance of the filemanager for this class
   * @var FileMgr
   */
  private $fMgr;

  /**
   * Create from store
   * @param string $id
   * @return \BlogPost 
   */
  public static function fromStore($id, $parent = null) {
    if (!BlogPost::inStore($id)) {
      return null;
    }
    $mgr = new FileMgr();
    $root_folder = Slim::getInstance()->config("root_folder");
    $root = $mgr->join($root_folder, $id);
    $fp = $mgr->join($root, BlogPost::$CONFIG_FILE);
    $fyl = fopen($fp, 'r');

    $created = $mgr->readLine($fyl);
    $name = $mgr->readLine($fyl);
    $author = $mgr->readLine($fyl);
    $content = $mgr->readLine($fyl);
    $bp = new BlogPost($name, $author, $content);
    $bp->created = $created;
    $bp->folder_name = $id;
    return $bp;
  }

  public static function inStore($id, $parent = null) {
    $mgr = new FileMgr();
    $f = Slim::getInstance()->config('root_folder');
    $root = $mgr->join($f, $id);
    return $mgr->exists($root);
  }

  public function __construct($name = '', $author = '', $content = '') {
    $this->fMgr = new FileMgr();

    $this->name = $name;
    $this->author = $author;
    $this->content = $content;
    $this->created = null;

    $this->folder_name = '';
  }

  public function getName() {
    return $this->name;
  }

  public function getAuthor() {
    return $this->author;
  }

  public function getContent() {
    return $this->content;
  }

  public function getFolderName() {
    return $this->folder_name;
  }

  public function getCreated() {
    return $this->created;
  }

  public function setName($name) {
    $this->name = $name;
  }

  public function setAuthor($author) {
    $this->author = $author;
  }

  public function setContent($content) {
    $this->content = $content;
  }

  public function delete() {
    return $this->fMgr->rmdir($this->getFolderPath());
  }

  public function getFolderPath() {
    $root_folder = $this->getSlimSetting("root_folder");
    return $this->fMgr->join($root_folder, $this->folder_name);
  }

  protected function edit() {
    if (!$this->fMgr->exists($this->getFolderPath())) {
      return $this->__create();
    }
    $origi_path = $this->fMgr->join($this->getFolderPath(), BlogPost::$CONFIG_FILE);
    $temp_path = $this->fMgr->join($this->getFolderPath(), BlogPost::$CONFIG_FILE_TEMP);
    $fyl = fopen($origi_path, 'r');
    $temp = fopen($temp_path, 'w');

    fwrite($temp, fgets($fyl)); //skip created
    fwrite($temp, "$this->author\n"); //change author
    fwrite($temp, "$this->name\n"); //change name
    fwrite($temp, $this->content); //change content

    fclose($fyl);
    fclose($temp);
    $this->fMgr->delFile($origi_path);
    $this->fMgr->moveFile($temp_path, $origi_path);
    return true;
  }

  public function save() {
    if ($this->fMgr->exists($this->getFolderPath())) {
      return $this->edit();
    } else {
      return $this->__create();
    }
  }

  private function __is_valid() {
    //TODO create validation class with all the possible settings e.g. allowed title lengths
    return true;
  }

  protected function __create() {
    $this->folder_name = $this->__newFolderName();
    if ($this->fMgr->mkdir($this->getFolderPath())) {
      $fyl_path = $this->fMgr->join($this->getFolderPath(), BlogPost::$CONFIG_FILE);
      $fyl = fopen($fyl_path, 'w');
      if ($fyl === false) {
        return false;
      }

      $this->created = date('U');
      fwrite($fyl, "$this->created\n");
      fwrite($fyl, "$this->author\n");
      fwrite($fyl, "$this->name\n");
      fwrite($fyl, $this->content);
      fclose($fyl);
      return true;
    }
    return false;
  }

  private function __newFolderName() {
    $salt = date('U');
    $folder_name = sha1($this->name . $salt);
    return $folder_name;
  }

  /**
   *
   * @return boolean |array
   */
  public function getPostSnippets($max = 200) {
    $root = $this->getSlimSetting('root_folder');
    $posts = $this->fMgr->listDir($root);
    if ($posts === false || !is_array($posts))
      return false;

    $data = array();
    $count = 0;
    foreach ($posts as $i) {
      if ($count >= $max)
        break;
      if ($i == '.' || $i == '..')
        continue;
      $config_path = $this->fMgr->join($this->fMgr->join($root, $i), BlogPost::$CONFIG_FILE);
      $fyl = fopen($config_path, 'r');
      $item = array();
      $item['id'] = $i;

      $info = $this->fMgr->readLine($fyl);
      $item['created'] = $info;

      $info = $this->fMgr->readLine($fyl);
      $item['author'] = $info;

      $info = $this->fMgr->readLine($fyl);
      $item['name'] = $info;

      $info = fgets($fyl, $this->getSlimSetting('snippet_len'));
      $item['content'] = $info;
      $data[] = $item;
      $count++;
    }
    return array('data' => $data, 'count' => count($posts) - 2, 'max' => $max); //subtract '.' & '..' from filelist
  }

  /**
   *
   * @param int $max
   * @return boolean | array
   */
  public function getCommentSnippets($max = 200) {
    $comments = $this->fMgr->listDir($this->getFolderPath());
    if ($comments === false || !is_array($comments))
      return false;
    $data = array();
    $count = 0;

    foreach ($comments as $i) {//do until max comments
      if ($count >= $max)
        break;
      if ($i == BlogPost::$CONFIG_FILE || $i == BlogPost::$CONFIG_FILE_TEMP || $i == '.' || $i == '..')
        continue;
      $fpath = $this->fMgr->join($this->getFolderPath(), $i);
      $fyl = fopen($fpath, 'r');

      $item = array();
      $item['id'] = $i;
      $info = $this->fMgr->readLine($fyl);
      $item['created'] = $info;

      $info = $this->fMgr->readLine($fyl);
      $item['author'] = $info;

      $info = fgets($fyl, $this->getSlimSetting('snippet_len'));
      $item['content'] = $info;
      $data[] = $item;
      $count++;
    }
    return array('data' => $data, 'count' => count($comments) - 3, 'max' => $max); //exclude '__post__', '.' & '..' from filelist
  }

  private function getSlimSetting($key) {
    return Slim::getInstance()->config($key);
  }

}

?>
