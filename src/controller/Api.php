<?php
/**
 * Created by PhpStorm.
 * User: Sinri
 * Date: 2018/1/16
 * Time: 23:48
 */

namespace sinri\sizuka\controller;


use Exception;
use Parsedown;
use sinri\ark\core\ArkHelper;
use sinri\ark\web\implement\ArkWebController;
use sinri\sizuka\library\AliyunOSSLibrary;

class Api extends ArkWebController
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getSiteMeta()
    {
        $configured_token = Ark()->readConfig(['token'], '');
        $site_title = Ark()->readConfig(['site_title'], 'Sizuka');
        $site_footer_remark = Ark()->readConfig(['site_footer_remark'], '');
        $this->_sayOK([
            'is_public' => ($configured_token === ''),
            'site_title' => $site_title,
            'site_footer_remark' => $site_footer_remark,
        ]);
    }

    /**
     * If there is a token set, return NULL;
     * Else, if there is an array of tokens, seek the configured.
     * @return string[]|null
     */
    private function getPermittedPatterns()
    {
        $token = Ark()->webInput()->readCookie('sizuka_token');
        $configured_token = Ark()->readConfig(['token'], '');
        if (!is_array($configured_token)) return null;
        return ArkHelper::readTarget($configured_token, $token, []);
    }

    public function setToken($token = 'sizuka')
    {
        setcookie("sizuka_token", $token);
    }

    public function explorer()
    {
        $patterns = $this->getPermittedPatterns();
        $patternsHash = md5(json_encode($patterns));

        $cache = Ark()->cache('explorer_object_tree');

        $force_update = $this->_readRequest("force_update", 'NO');
        $path = '';//LibRequest::getRequest("path", '');
        // find sub objects
        try {
            $result = $cache->getObject("object_tree_" . $patternsHash);
            if (empty($result) || $force_update === 'YES') {
                $list = (new AliyunOSSLibrary())->listObjects($path, $patterns);
                Ark()->logger('api')->info('list objects count', ['count' => count($list)]);
                $tree = (new AliyunOSSLibrary())->makeObjectTree($list);
                //Sizuka::log(LibLog::LOG_INFO,'object tree',$tree);
                $result = [
                    "tree" => $tree->toJsonObject(),
                    "cache_time" => date('Y-m-d H:i:s'),
                ];
                $cache->saveObject("object_tree_" . $patternsHash, $result, 60 * 60);
            }
            $this->_sayOK($result);
        } catch (Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function listObjectsForPath()
    {
        $cache = Ark()->cache('explorer_object_tree');
        $path = $this->_readRequest("path", '');// such as `lab/`
        try {
            $full_result = $cache->getObject("object_list");
            if (empty($full_result)) {
                $full_list = (new AliyunOSSLibrary())->listObjects();
                $full_result = [
                    "list" => $full_list,
                    "cache_time" => date('Y-m-d H:i:s'),
                ];
                $cache->saveObject("object_list", $full_result, 60 * 60);
            }
            $result = [
                'folders' => [],
                'objects' => [],
                'cache_time' => $full_result['cache_time'],
            ];
            foreach ($full_result['list'] as $item) {
                $key = $item['key'];
                if (strlen($path) > 0 && strpos($key, $path) !== 0) {
                    continue;
                }
                $tail = substr($key, strlen($path));
                if (strlen($tail) === 0) continue;
                $p = strpos($tail, '/');
//                echo "p=$p tail.len=".strlen($tail).PHP_EOL;
                if ($p === false) {
                    $result['objects'][] = $item;
                } elseif ($p === strlen($tail) - 1) {
                    $result['folders'][] = $item;
                }
            }
            $this->_sayOK($result);
        } catch (Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function previewUrlForObject()
    {
        try {
            $object = $this->_readRequest("object");

            $ext = pathinfo($object, PATHINFO_EXTENSION);
            $ext = strtolower($ext);

            $url = (new AliyunOSSLibrary())->objectDownloadURL($object);
            $previewUrl = null;

            switch ($ext) {
                case "xlsx":
                case "xls":
                case "docx":
                case "doc":
                case "pptx":
                case "ppt":
                    $previewUrl = "https://view.officeapps.live.com/op/view.aspx?src=" . urlencode($url);
                    break;
                case "md":
                    $previewUrl = "./showRenderedMarkdown?src=" . urlencode($url);
                    break;
                default:
                    $previewUrl = Ark()->readConfig(["gateway"]) . "/proxy/" . $object;
                    break;
            }

            header("Location: " . $previewUrl);
            //$this->_sayOK($previewUrl);
        } catch (Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function directlyDownloadUrlForObject()
    {
        try {
            $object = $this->_readRequest("object");
            $url = (new AliyunOSSLibrary())->objectDownloadURL($object);
            header("Location: " . $url);
            //$this->_sayOK($previewUrl);
        } catch (Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function showRenderedMarkdown()
    {
        try {
            $src = $this->_readRequest("src");
            ArkHelper::quickNotEmptyAssert("not valid src: " . $src, $src);

            $content = file_get_contents($src);
            ArkHelper::quickNotEmptyAssert("cannot fetch content from " . $src, $content !== false);

            $parseDown = new Parsedown;
            $parseDown->setSafeMode(true)->setBreaksEnabled(true);
            $content = $parseDown->text($content);

            echo $content;
        } catch (Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }

    public function getObjectMeta()
    {
        try {
            $object = $this->_readRequest('object', '');
            $lifetime = $this->_readRequest('life_time', 60 * 30);

            $lib = (new AliyunOSSLibrary());
            $meta = $lib->getObjectMeta($object);
            $signed_url = $lib->objectDownloadURL($object, $lifetime);

            $this->_sayOK([
                'meta' => $meta,
                'object' => $object,
                'object_original_name' => basename($object),
                'signed_url' => $signed_url,
            ]);
        } catch (Exception $exception) {
            $this->_sayFail($exception->getMessage());
        }
    }
}