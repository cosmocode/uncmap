<?php

abstract class uncmapDokuWikiTest extends DokuWikiTest {

    function flatten_array($array){
        $array = array_values($array);
        $return = array();
        while ($array){
            $value = array_shift($array);
            if (is_array($value)){
                array_splice($array,0,0,$value);
            }else{
                $return[] = $value;
            }
        }
        return $return;
    }

}
