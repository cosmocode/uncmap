<?php
// must be run within DokuWiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once DOKU_PLUGIN.'syntax.php';

/**
 * Plugin maps drive letters to a unc path.
 */
class syntax_plugin_uncmap extends DokuWiki_Syntax_Plugin {

    /**
     * stores an array with the mappings.
     *
     * format is
     *   letter => path
     */
    var $pathes;

    /**
     * the path to the mounted fileserver.
     *
     * this is used for linkchecking. if the path is null the linkcheck is disabled.
     */
    var $fileserver = null;

    /**
     * load the mapping array.
     */
    function syntax_plugin_uncmap() {
        $this->pathes = confToHash(dirname(__FILE__).'/conf/mapping.php');

        $fs = $this->getConf('fileserver');
        if ($fs !== null && file_exists($fs)) {

            $fs = str_replace('\\','/',$fs);
            if (substr($fs,-1) == '/') {
                $fs = substr($fs,0,-1);
            }
            $this->fileserver = $fs;
        }
    }

    /**
     * syntax type is formating
     */
    function getType() { return 'formatting'; }

    /**
     * a very important plugin ;-)
     */
    function getSort() { return 282; }

    /**
     * connect to the renderer.
     */
    function connectTo($mode) {
        $this->Lexer->addSpecialPattern('\[\[[a-z]{1}\:[\\\/]{1}.+?\]\]',$mode,'plugin_uncmap');
    }

    /**
     * review the given url.
     */
    function handle($match, $state, $pos, &$handler) {

        // trim [[ and ]]
        $match = substr($match,2,-2);

        // remove surrounding whitespaces
        $match = trim($match);

        // get the drive letter in lower case
        $letter = strtolower($match[0]);

        // if there is a mapping for the drive letter we have work to do
        if ($this->pathes[$letter]) {

            // get the mapping path
            $path = $this->pathes[$letter];
            $match = substr($match, 1);

            $match = str_replace('/','\\',$match);

            // ensure it is a real path
            if (substr($match,0,2) == ':\\') {

                // build windows share link
                $new = $path;
                if (substr($path,-1) != '\\') $new .= '\\';
                $new .=  substr($match,2);
            }
        }

        return array($new);
    }

    /**
     * displays the link.
     */
    function render($mode, &$R, $data) {
        if($mode == 'xhtml'){

            // check if there is a link and give it to the renderer
            if (!empty($data[0])) {
                $this->windowssharelink($R, $data[0], $this->checkLink($data[0]));
            }
        }
        return false;
    }

    /**
     * checks if the link exists on the server.
     */
    function checkLink($link) {
        if (!$this->fileserver) {
            return null;
        }

        $link = str_replace('\\','/',$link);
        $path = preg_replace('/^\/\/[^\/]+/i',$this->fileserver,$link);

        return file_exists($path)?1:-1;
    }

    /**
     * from inc/renderer/xhtml.php
     */
    function windowssharelink(&$R, $url, $exists = null) {
        global $conf;
        global $lang;

        //simple setup
        $link['target'] = $conf['target']['windows'];
        $link['pre']    = '';
        $link['suf']   = '';
        $link['style']  = '';

        $link['name'] = $R->_getLinkTitle(null, $url, $isImage);
        if ( !$isImage ) {
            $link['class'] = 'windows';
        } else {
            $link['class'] = 'media';
        }
        if ($exists == 1) {
            $link['class'] .= ' wikilink1';
        } elseif ($exists == -1) {
            $link['class'] .= ' wikilink2';
        }


        $link['title'] = $R->_xmlEntities($url);
        $url = str_replace('\\','/',$url);
        $url = 'file:///'.$url;
        $link['url'] = $url;

        //output formatted
        $R->doc .= $R->_formatLink($link);
    }
}

