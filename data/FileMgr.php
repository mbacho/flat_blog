<?php

/**
 * Manages all I/O file operations and management of the files
 *
 * @author ero <choeringa@gmail.com>
 */
class FileMgr {

  /**
   * File separator in filepaths
   * @var string
   */
  public static $FILE_SEP = "/";

  public function exists($fpath) {
    return file_exists($fpath);
  }

  public function copyFile($srcpath, $destpath) {
    return copy($srcpath, $destpath);
  }

  public function moveFile($srcpath, $destpath) {
    if (copy($srcpath, $destpath)) {
      return $this->delFile($srcpath);
    } else {
      return false;
    }
  }

  public function delFile($fpath) {
    return unlink($fpath);
  }

  /**
   * Gets information about a file. <br/>Returns null on error, array(key-value) otherwise</br>
   * The following keys are used in the array:
   * <ul>
   * <li>uid   (userid of owner)</li>
   * <li>gid   (groupid of owner)</li>
   * <li>size  (size in bytes)</li>
   * <li>atime (time of last access (Unix timestamp))</li>
   * <li>mtime (time of last modification (Unix timestamp))</li>
   * <li>ctime (time of last inode change (Unix timestamp))</li>
   * </ul>
   * @param string $fpath File path rooted at <code style="display:inline">FileMgr::ROOT_FOLDER</code>
   * @return null|array 
   */
  public function stat($fpath) {
    $info = stat($fpath);
    if ($info === false || !is_array($info)) {
      return null;
    }
    return array(
        'uid' => $info['uid'],
        'gid' => $info['gid'],
        'size' => $info['size'],
        'atime' => $info['atime'],
        'mtime' => $info['mtime'],
        'ctime' => $info['ctime']
    );
  }

  public function mkdir($fpath) {
    //TODO permission management for apache
    $perm = 0777; //644;
    $result = mkdir($fpath, $perm, true);
    return $result;
  }

  public function rmdir($fpath) {
    $files = $this->listDir($fpath);
    foreach ($files as $i) {
      if ($i == '.' || $i == '..')
        continue;
      unlink($this->join($fpath, $i));
    }
    $result = rmdir($fpath);
    return $result;
  }

//  public function listDir($fpath) {
//    $arr = array();
//    $dir = new DirectoryIterator($fpath);
//    while (strlen($dir->getFilename()) > 0) {
//      if ($dir->getFilename() != '.' && $dir->getFilename() != '..')
//      $arr[] = $dir->getFilename();
//      $dir->next();
//    }
//    return $arr;
//  }
  public function listDir($fpath) {
    if (is_dir($fpath) && $this->exists($fpath)) {
      return scandir($fpath);
    }
    return false;
  }

  public function readLine($handle) {
    $str = fgets($handle);
    $str = substr($str, 0, strlen($str) - 1);
    return $str;
  }

  public function join($head, $tail) {
    if (strlen($tail) < 1) {
      return null;
    }
    $t_pos = ($tail[0] == FileMgr::$FILE_SEP);
    $h_pos = strripos($head, FileMgr::$FILE_SEP);
    $h_pos = ($h_pos == strlen($head) - 1);

    $t = ($t_pos) ? substr($tail, 1) : $tail;
    $h = ($h_pos ) ? substr($head, 0, strlen($head) - 1) : $head;

    return $h . FileMgr::$FILE_SEP . $t;
  }

  public function split($fpath) {
    $a = explode(FileMgr::$FILE_SEP, $fpath);
    $len = count($a);
    if ($len > 0)
      return $a[$len - 1];
    else
      return $fpath;
  }

}

?>
