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
            $fs = rtrim($fs,'/');
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
        $letters = implode('',array_keys($this->pathes));
        if($letters !== '') {
            $this->Lexer->addSpecialPattern('\[\[[' . $letters . ']{1}\:[\\\/]{1}.+?\]\]', $mode, 'plugin_uncmap');
        }
    }

    /**
     * review the given url.
     */
    function handle($match, $state, $pos, Doku_Handler $handler) {

        // trim [[ and ]]
        $match = substr($match,2,-2);

        // remove surrounding whitespaces
        $match = trim($match);

        // get the drive letter in lower case
        $letter = strtolower($match[0]);
        $return = array();
        // if there is a mapping for the drive letter we have work to do
        if ($this->pathes[$letter]) {
            $titlepos = strpos($match,'|');
            if ($titlepos !== false){
                $title = substr($match,$titlepos+1);
                $match = substr($match,0,$titlepos);
                $return['title'] = $title;
            } else {
                $return['title'] = null;
            }
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
            $return['url'] = $new;
        }

        return $return;
    }

    /**
     * displays the link.
     */
    function render($mode, Doku_Renderer $renderer, $data) {
        if($mode == 'xhtml'){

            // check if there is a link and give it to the renderer
            if (!empty($data['url'])) {
                $renderer->windowssharelink($data['url'], $data['title']);

                // check if the linked file exists on the fileserver and set the class accordingly
                $data['exists'] = $this->checkLink($data['url']);
                if ($data['exists'] == 1) {
                    $this->replaceLinkClass($renderer,'wikilink1');
                } elseif ($data['exists'] == -1) {
                    $this->replaceLinkClass($renderer,'wikilink2');
                }
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
     * @param Doku_Renderer $renderer:
     * @param $newClass: replace the class of the windowssharelink with this value
     */
    function replaceLinkClass(Doku_Renderer $renderer, $newClass){
        $ourdoc = & $renderer->doc;

        //detach the link from doc
        $linkoffset = strrpos($ourdoc,'<a ');
        $link = substr($ourdoc, $linkoffset);

        $ourdoc = preg_replace('/'. preg_quote($link, '/') . '$/', '', $ourdoc);
        $link = preg_replace('/class=\"(windows|media)\"/','class="' . $newClass . '"',$link);

        $ourdoc .= $link;
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

