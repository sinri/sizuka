<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/1/17
 * Time: 10:40
 */

namespace sinri\sizuka\library;


use sinri\enoch\helper\CommonHelper;

class OSSObjectTreeNode
{
    /**
     * @var string
     */
    public $objectName;
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

    /**
     * @var bool
     */
    public $isDir;

    public $metaKey;
    public $metaLastModified;
    public $metaSize;
    public $metaType;

    public function __construct($path, $expand, $meta = null)
    {
        $this->metaKey = CommonHelper::safeReadArray($meta, 'key', $path);
        $this->metaLastModified = CommonHelper::safeReadArray($meta, 'last_modified', 'Unknown');
        $this->metaSize = CommonHelper::safeReadArray($meta, 'size', 'Unknown');
        $this->metaType = CommonHelper::safeReadArray($meta, 'type', 'Unknown');

        $components = explode("/", $path);
        $last_component = $components[count($components) - 1];
        if ($last_component === '') {
            $this->objectName = $components[count($components) - 2];
            $this->children = [];
            $this->isDir = true;
        } else {
            $this->objectName = $last_component;
            $this->isDir = false;
            $this->children = null;
        }
        $this->expand = $expand;
        $this->path = $path;
    }

    public function rootLoadItem($meta)
    {
        $object = CommonHelper::safeReadArray($meta, 'key');
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
                if ($child->objectName === $dir) {
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
        $node->children[] = new OSSObjectTreeNode($object, false, $meta);
    }

    public function toJsonObject()
    {
        $title = $this->objectName;
        $title .= " - - - ";
        $title .= number_format(1.0 * $this->metaSize) . ' bytes ~ ';
        if ($this->metaLastModified !== 'Unknown') {
            $title .= " since " . (new \DateTime($this->metaLastModified))->format('Y-m-d H:i:s');
        }
        $json = [
            "title" => $title,
            "expand" => $this->expand,
            "path" => $this->path,
            "last_modified" => $this->metaLastModified,
            "size" => $this->metaSize,
            "type" => $this->metaType,
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