<?php
/**
 * General tests for the uncmap plugin
 *
 * @group plugin_uncmap
 * @group plugins
 */


require_once 'uncmap.inc.php';

class parser_plugin_uncmap_test extends uncmapDokuWikiTest {

    protected $pluginsEnabled = array('uncmap');

    function test_parser_colon() {
        $parser_response = p_get_instructions('Testlink: [[:facts:figures|Foo]]');
        $parser_response = $this->flatten_array($parser_response);
        $uncmap_pos = array_search("uncmap",$parser_response,true);
        $this->assertTrue($uncmap_pos === false,'A link beginning with a colon should not be handled at all by this plugin.');
    }

    function test_parser_slash() {
        $parser_response = p_get_instructions('Testlink: [[/facts:figures|Foo]]');
        $parser_response = $this->flatten_array($parser_response);
        $uncmap_pos = array_search("uncmap",$parser_response,true);
        $this->assertTrue($uncmap_pos === false,'A link beginning with a slash should not be handled at all by this plugin.');
    }

    function test_parser_backslash() {
        $parser_response = p_get_instructions('Testlink: [[\facts:figures|Foo]]');
        $parser_response = $this->flatten_array($parser_response);
        $uncmap_pos = array_search("uncmap",$parser_response,true);
        $this->assertTrue($uncmap_pos === false,'A link beginning with a backslash should not be handled at all by this plugin.');
    }

}
