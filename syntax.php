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
    var $pathes = array();

    /**
     * the path to the mounted fileserver.
     *
     * this is used for linkchecking. if the path is null the linkcheck is disabled.
     */
    var $fileserver = array(0 => null);

    /**
     * load the mapping array.
     */
    function syntax_plugin_uncmap() {

        $lines = @file( dirname(__FILE__) . '/conf/mapping.php' );
        if ( $lines ) {
            $lines_fs = array();
            $lines_pathes = array();
            foreach ($lines as $line) {
                if (preg_match('/[a-z]/',strtolower($line[0])) == 1) {
                    $letter = $line[0];
                    $line = substr($line,1);
                    $line = trim($line);
                    if (empty($line)) continue;
                    $delim = strpos($line,' ');
                    if ($delim === false){
                        $lines_pathes[] = implode(' ',array($letter,$line));
                        $lines_fs[] = implode(' ',array($letter,'default'));
                    } else {
                        $path = substr($line,0,$delim);
                        $fs = substr($line,$delim);
                        $fs = trim($fs);
                        $lines_pathes[] = implode(' ',array($letter,$path));
                        $lines_fs[] = implode(' ',array($letter,$fs));
                    }
                }
            }

            $this->pathes = linesToHash($lines_pathes);
            $this->fileserver[0] = $this->getConf('fileserver');
            $this->fileserver = $this->fileserver + linesToHash($lines_fs);
            foreach ($this->fileserver as $letter => $fs) {
                if($fs !== null && file_exists($fs)) {

                    $fs = str_replace('\\', '/', $fs);
                    $fs = rtrim($fs, '/');
                    if(substr($fs, -1) == '/') {
                        $fs = substr($fs, 0, -1);
                    }
                    $this->fileserver[$letter] = $fs;
                } elseif ($this->fileserver[0] === null || !file_exists($this->fileserver[0])){
                    $this->fileserver[$letter] = null;
                } else {
                    $this->fileserver[$letter] = $this->fileserver[0];
                }
            }
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
        $return['letter'] = $letter;
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
                $data['exists'] = $this->checkLink($data);
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
    function checkLink($data) {
        if (!$this->fileserver[$data['letter']]) {
            return null;
        }

        $data['url'] = str_replace('\\','/',$data['url']);
        $path = preg_replace('/^\/\/[^\/]+/i',$this->fileserver[$data['letter']],$data['url']);

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
        $ourdoc = substr($ourdoc, 0, $linkoffset);
        $link = preg_replace('/class=\"(windows|media)\"/','class="' . $newClass . '"',$link);

        $ourdoc .= $link;
    }

}
