<?php

class action_plugin_uncmap extends DokuWiki_Action_Plugin {

    public function register(Doku_Event_Handler $controller) {
        $controller->register_hook('CONFMANAGER_CONFIGFILES_REGISTER', 'BEFORE',  $this, 'addConfigFile', array());
    }

    public function addConfigFile(Doku_Event $event, $params) {
        if (class_exists('ConfigManagerTwoLine')) {
            $config = new ConfigManagerTwoLine('UncMap',  file_get_contents($this->localFN('confmanager_description')),  DOKU_PLUGIN . 'uncmap/conf/mapping.php');
            $event->data[] = $config;
        }
    }
}