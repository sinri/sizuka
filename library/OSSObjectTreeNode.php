<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/1/17
 * Time: 10:40
 */

namespace sinri\sizuka\library;


class OSSObjectTreeNode
{
    /**
     * @var string
     */
    public $title;
    /**
     * @var bool
     */
    public $expand;
    /**
     * @var OSSObjectTreeNode[]
     */
    public $children;
    /**
     * @var string
     */
    public $path;

    public function __construct($path, $expand)
    {
        $components = explode("/", $path);
        $last_component = $components[count($components) - 1];
        if ($last_component === '') {
            $this->title = $components[count($components) - 2];
            $this->children = [];
        } else {
            $this->title = $last_component;
            $this->children = null;
        }
        $this->expand = $expand;
        $this->path = $path;
    }

    public function rootLoadItem($object)
    {
        $components = explode("/", $object);
        $last_component = $components[count($components) - 1];
        $dir_link = $components;
        unset($dir_link[count($dir_link) - 1]);
        if ($last_component === '') {
            unset($dir_link[count($dir_link) - 1]);
        }

        $node =& $this;
        $dir_path = "";
        foreach ($dir_link as $dir) {
            $dir_key = null;
            $dir_path .= $dir . '/';
            foreach ($node->children as $key => $child) {
                if ($child->title === $dir) {
                    $dir_key = $key;
                    break;
                }
            }
            if ($dir_key === null) {
                $node->children[] = new OSSObjectTreeNode($dir_path, false);
                $dir_key = count($node->children) - 1;
            }
            $node =& $node->children[$dir_key];
        }
        $node->children[] = new OSSObjectTreeNode($object, false);
    }

    public function toJsonObject()
    {
        $json = [
            "title" => $this->title,
            "expand" => $this->expand,
            "path" => $this->path,
        ];
        if ($this->children !== null) {
            $json['children'] = [];
            foreach ($this->children as $child) {
                $json['children'][] = $child->toJsonObject();
            }
        }
        return $json;
    }
}