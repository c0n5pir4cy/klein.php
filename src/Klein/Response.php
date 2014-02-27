<?php
/**
 * Klein (klein.php) - A lightning fast router for PHP
 *
 * @author      Chris O'Hara <cohara87@gmail.com>
 * @author      Trevor Suarez (Rican7) (contributor and v2 refactorer)
 * @copyright   (c) Chris O'Hara
 * @link        https://github.com/chriso/klein.php
 * @license     MIT
 */

namespace Klein;

use DOMDocument;

/**
 * Response 
 * 
 * @uses        AbstractResponse
 * @package     Klein
 */
class Response extends AbstractResponse
{

    /**
     * Methods
     */

    /**
     * Enable response chunking
     *
     * @link https://github.com/chriso/klein.php/wiki/Response-Chunking
     * @link http://bit.ly/hg3gHb
     * @param string $str   An optional string to send as a response "chunk"
     * @access public
     * @return Response
     */
    public function chunk($str = null)
    {
        parent::chunk();

        if (null !== $str) {
            printf("%x\r\n", strlen($str));
            echo "$str\r\n";
            flush();
        }

        return $this;
    }

    /**
     * Dump a variable
     *
     * @param mixed $obj    The variable to dump
     * @access public
     * @return Response
     */
    public function dump($obj)
    {
        if (is_array($obj) || is_object($obj)) {
            $obj = print_r($obj, true);
        }

        $this->append('<pre>' .  htmlentities($obj, ENT_QUOTES) . "</pre><br />\n");

        return $this;
    }

    /**
     * Sends a file
     *
     * It should be noted that this method disables caching
     * of the response by default, as dynamically created
     * files responses are usually downloads of some type
     * and rarely make sense to be HTTP cached
     *
     * Also, this method removes any data/content that is
     * currently in the response body and replaces it with
     * the file's data
     *
     * @param string $path      The path of the file to send
     * @param string $filename  The file's name
     * @param string $mimetype  The MIME type of the file
     * @access public
     * @return Response
     */
    public function file($path, $filename = null, $mimetype = null)
    {
        $this->body('');
        $this->noCache();

        if (null === $filename) {
            $filename = basename($path);
        }
        if (null === $mimetype) {
            $mimetype = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
        }

        $this->header('Content-type', $mimetype);
        $this->header('Content-length', filesize($path));
        $this->header('Content-Disposition', 'attachment; filename="'.$filename.'"');

        $this->send();

        readfile($path);

        return $this;
    }

    /**
     * Sends an object as json or jsonp by providing the padding prefix
     *
     * It should be noted that this method disables caching
     * of the response by default, as json responses are usually
     * dynamic and rarely make sense to be HTTP cached
     *
     * Also, this method removes any data/content that is
     * currently in the response body and replaces it with
     * the passed json encoded object
     *
     * @param mixed $object         The data to encode as JSON
     * @param string $jsonp_prefix  The name of the JSON-P function prefix
     * @access public
     * @return Response
     */
    public function json($object, $jsonp_prefix = null)
    {
        $this->body('');
        $this->noCache();

        $json = json_encode($object);

        if (null !== $jsonp_prefix) {
            // Should ideally be application/json-p once adopted
            $this->header('Content-Type', 'text/javascript');
            $this->body("$jsonp_prefix($json);");
        } else {
            $this->header('Content-Type', 'application/json');
            $this->body($json);
        }

        $this->send();

        return $this;
    }

    /**
     * Sends an object as pseudo XML
     *
     * It should be noted that this method disables caching
     * of the response by default, as XML responses are usually
     * dynamic and rarely make sense to be HTTP cached
     *
     * Also, this method removes any data/content that is
     * currently in the response body and replaces it with
     * the passed json encoded object
     *
     * @param mixed $object         The data to encode as XML
     * @access public
     * @return Response
     */
    public function xml($object, $jsonp_prefix = null)
    {
        $this->body('');
        $this->noCache();



        $xml = $this->xml_encode($object);

        $this->header('Content-Type', 'application/xml');
        $this->body($xml);

        $this->send();

        return $this;
    }

    /**
    * Encode an object as XML string
    *
    * @param Object $obj
    * @param string $root_node
    * @return string $xml
    */
    public function xml_encode($obj, $root_node = 'response') {
        $xml = '<?xml version="1.0" encoding="utf-8"?>' . PHP_EOL;
        if (sizeof($obj) == 1){
            $xml .= $this->encode($obj[array_keys($obj)[0]], array_keys($obj)[0], $depth = 0);
        } else {
            $xml .= $this->encode($obj, $root_node, $depth = 0);
        }
        return $xml;
    }
 
 
    /**
    * Encode an object as XML string
    *
    * @param Object|array $data
    * @param string $root_node
    * @param int $depth Used for indentation
    * @return string $xml
    */
    private function encode($data, $node, $depth,$parent) {
        $xml = str_repeat("\t", $depth);
        if (is_numeric($node)){
            $parent = (strrpos($parent, "s") == strlen($parent)-1) ? substr($parent, 0,strlen($parent) -1) : $parent;
            $nodeOp = "<$parent>";
            $nodeCl = "</$parent>";
        } else {
            $nodeOp = "<$node>";
            $nodeCl = "</$node>";
        }

        $xml .= $nodeOp . PHP_EOL;
        foreach($data as $key => $val) {
            if(is_array($val) || is_object($val)) {
                $xml .= self::encode($val, $key, ($depth + 1),$node);
            } else {
                if(is_int($key)){
                    $node = (strrpos($node, "s") == strlen($node)-1) ? substr($node, 0,strlen($node) -1) : $node;
                    $xml .= str_repeat("\t", ($depth + 1));
                    $xml .= "<{$node}>" . htmlspecialchars($val) . "</{$node}>" . PHP_EOL;
                } else {
                    $xml .= str_repeat("\t", ($depth + 1));
                    $xml .= "<{$key}>" . htmlspecialchars($val) . "</{$key}>" . PHP_EOL;
                }
            }
        }
        $xml .= str_repeat("\t", $depth);
        $xml .= $nodeCl . PHP_EOL;
        return $xml;
    }

}
