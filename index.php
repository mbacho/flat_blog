<?php

require 'slim/Slim.php';
require_once 'data/BlogPost.php';
require_once 'data/BlogComment.php';
require_once 'slim/Http/Request.php';


$settings = array(
    "root_folder" => "store",
    "snippet_len" => 30
);


$app = new Slim($settings);


$app->get('/', function () {
          Slim::getInstance()->render('index.html', array('brod' => 'brod_all_*'));
        });

//get info about a blog
$app->get('/blog/:id', function($id)use($app) {
          
        });

//create blog
$app->map('/blog/new', function() use($app) { //add blogpost
          $is_post = ($app->request()->getMethod() == Slim_Http_Request::METHOD_POST);
          if ($is_post) {
            $name = $app->request()->post('name');
            if (is_null($name)) {
              echo 'name not set';
              return;
            }
            $author = $app->request()->post('author');
            if (is_null($author)) {
              echo 'author not set';
              return;
            }
            $content = $app->request()->post('content');
            if (is_null($content)) {
              echo 'content not set';
              return;
            }
            $p = new BlogPost($name, $author, $content);
            $msg = ($p->save()) ?
                    array('msg' => 'save successful', 'id' => $p->getFolderName()) :
                    array('error' => 'save failed');
            echo json_encode($msg);
          } else {
            echo 'data entry page...coming soon';
          }
        })->via('GET', 'POST');

//delete blog
$app->map('/blog/del/:id', function($id)use($app) {
          $bp = BlogPost::fromStore($id);
          if (is_null($bp)) {
            echo json_encode(array('error' => 'blog post not found'));
            return;
          }
          if ($bp->delete())
            echo json_encode(array('msg' => 'post deleted'));
          else
            echo json_encode(array('error' => 'post deletion failed'));
        })->via('GET', 'POST');

//edit blog
$app->map('/blog/edit/:id', function($id)use($app) {
          $is_post = ($app->request()->getMethod() == Slim_Http_Request::METHOD_POST);
          if ($is_post) {
            $bp = BlogPost::fromStore($id);
            if (is_null($bp)) {
              echo json_encode(array('error' => 'blog post not found'));
              return;
            }
            $has_changes = false;
            $name = $app->request()->post('name');
            if (!is_null($name)) {
              if ($bp->getName() != $name) {
                $has_changes = true;
                $bp->setName($name);
              }
            }
            $author = $app->request()->post('author');
            if (!is_null($author)) {
              if ($bp->getAuthor() != $author) {
                $has_changes = true;
                $bp->setAuthor($author);
              }
            }
            $content = $app->request()->post('content');
            if (!is_null($content)) {
              if ($bp->getContent() != $content) {
                $has_changes = true;
                $bp->setContent($content);
              }
            }
            if ($has_changes) {
              if ($bp->save())
                echo json_encode(array('msg' => 'post edited'));
              else
                echo json_encode(array('error' => 'post editing failed'));
            }
          } else {
            echo 'data entry page here';
          }
        })->via('GET', 'POST');

//list comments in blog post
$app->get('/blog/comments/:id', function($id)use($app) {
          $bp = BlogPost::fromStore($id);
          if (is_null($bp)) {
            echo json_encode(array('error' => 'blog post not found'));
            return;
          }
          $val = $bp->getCommentSnippets();
          if ($val === false) {
            echo json_encode(array('error' => 'error occured while fetching comments'));
          } else {
            echo json_encode($val);
          }
        });

//list blogs
$app->get('/blogs', function()use($app) {
          $bp = new BlogPost();
          $val = $bp->getPostSnippets();
          if ($val === false) {
            echo json_encode(array('error' => 'error occured while fetching posts'));
          } else {
            echo json_encode($val);
          }
        });

//add comment to blog
$app->map('/comment/new', function()use($app) {
          $is_post = ($app->request()->getMethod() == Slim_Http_Request::METHOD_POST);
          if ($is_post) {
            $bid = $app->request()->post('bid');
            if (is_null($bid)) {
              echo json_encode(array('error' => 'blog post not set'));
              return;
            }
            if (!BlogPost::inStore($bid)) {
              echo json_encode(array('error' => 'blog post not found'));
              return;
            }

            $author = $app->request()->post('author');
            if (is_null($author)) {
              echo 'author not set';
              return;
            }
            $content = $app->request()->post('content');
            if (is_null($content)) {
              echo 'content not set';
              return;
            }
            $mgr = new FileMgr();
            $bc = new BlogComment($mgr->join($app->config('root_folder'), $bid), $author, $content);
            $msg = ($bc->save()) ?
                    array('msg' => 'save successful', 'id' => $bc->getId()) :
                    array('error' => 'save failed');
            echo json_encode($msg);
          } else {
            echo 'data entry form here';
          }
        })->via('GET', 'POST');

//edit comment
$app->map('/comment/edit/:id', function($id)use($app) {
          $is_post = ($app->request()->getMethod() == Slim_Http_Request::METHOD_POST);
          if ($is_post) {
            $bid = $app->request()->post('bid');
            if (is_null($bid)) {
              echo json_encode(array('error' => 'blog post not set'));
              return;
            }
            if (!BlogPost::inStore($bid)) {
              echo json_encode(array('error' => 'blog post not found'));
              return;
            }

            $bc = BlogComment::fromStore($id, $bid);
            if (is_null($bc)) {
              echo json_encode(array('error' => 'comment not found'));
              return;
            }
            $has_changes = false;
            $author = $app->request()->post('author');
            if (!is_null($author)) {
              IF ($bc->getAuthor() != $author) {
                $has_changes = true;
                $bc->setAuthor($author);
              }
            }
            $content = $app->request()->post('content');
            if (!is_null($content)) { {
                IF ($bc->getContent() != $content) {
                  $has_changes = true;
                  $bc->setAuthor($author);
                }
              }
            }
            if ($has_changes) {
              if ($bc->save())
                echo json_encode(array('msg' => 'comment edited'));
              else
                echo json_encode(array('error' => 'comment editing failed'));
            }
          } else {
            echo'data entry page';
          }
        });

//delete comment
$app->map('/comment/del/:id', function()use($app) {
          $bid = $app->request()->post('bid');
          if (is_null($bid)) {
            echo json_encode(array('error' => 'blog post not set'));
            return;
          }
          if (!BlogPost::inStore($bid)) {
            echo json_encode(array('error' => 'blog post not found'));
            return;
          }

          $bc = BlogComment::fromStore($id, $bid);
          if (is_null($bc)) {
            echo json_encode(array('error' => 'comment not found'));
            return;
          }
          if ($bp->delete())
            echo json_encode(array('msg' => 'post deleted'));
          else
            echo json_encode(array('error' => 'post deletion failed'));
        })->via('GET', 'POST');



$app->run();
