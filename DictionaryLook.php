<?php
/**
 * DictionaryLook Plugin
 *
 * @author Encuesta.Biz <http://www.encuesta.biz/>
 * @copyright 2023 Encuesta.Biz <http://www.encuesta.biz/>
 * @license General Common
 * @version 1.0.0
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 */
class DictionaryLook extends PluginBase {

    protected $storage = 'DbStorage';
    static protected $description = 'Add explanations from dictionary to specific terms and words.';
    static protected $name = 'DictionaryLook';
    protected $settings = array();
    

    public function init()
    {
        $this->subscribe('beforeSurveyPage');
        $this->subscribe('beforeCloseHtml');
        $this->subscribe('newDirectRequest');
    }

    public function beforeSurveyPage()
    {
        // Get Client
        $client = Yii::app()->getClientScript();
        $event = $this->getEvent();
        $surveyId = $event->get('surveyId');

        $setupUrl = $this->api->createUrl('plugins/direct', 
            array(
                'plugin' => $this->getName(),
                'function'=> 'lookup', 
                'surveyId'=> $surveyId,
            )
        );
        $dictUrl = $this->api->createUrl('plugins/direct', 
            array(
                'plugin' => $this->getName(),
                'function'=> 'searchWord', 
                'surveyId'=> $surveyId,
            )
        );
        $client->registerScript(
            self::$name . "_lookupUrl", 'var _lookupUrl="'.$setupUrl.'";', 
            CClientScript::POS_BEGIN
        );
        $client->registerScript(
            self::$name . "_dictUrl", 'var _dictUrl="'.$dictUrl.'";', 
            CClientScript::POS_BEGIN
        );

        // Register JS & CSS
        $client->registerScriptFile($this->getPluginFileUrl('js/dictSetup.js'));
        $client->registerScriptFile($this->getPluginFileUrl('js/language.js'));
        $client->registerScriptFile($this->getPluginFileUrl('js/dictLookup.js'));
        $client->registerCssFile($this->getPluginFileUrl('css/dictLookup.css'));
    }

    public function beforeCloseHtml()
    {
        $event = $this->getEvent();
        $surveyId = $event->get('surveyId');
        if (empty($surveyId)) return;

        $html = '';

        // Compose HTML here.

        if (!empty($html)) {
            $event->set('html', $html);
        }
    }

    /** 
     * Subscription to Direct Request event 
     */ 
    public function newDirectRequest() 
    { 
        header("Content-type: application/json");
        // Don't output full body. Just this json.
        $oEvent = $this->event; 
 
        if ($oEvent->get('target') != $this->getName()) return; 
 
        // Init 
 
        /** 
         * Initializa output 
         */ 
        $out = $oEvent->getContent($this);
        // $out->addContent("<p>Processing " . $oEvent->get('function') . "</p>"); 
 
        /** 
         * Process request 
         */ 
 
        $content = ""; 
        // If this is a showChart request 
        if ($oEvent->get('function') == 'lookup') 
        { 
            $content = $this->lookup();
        } 
        if ($oEvent->get('function') == 'searchWord') 
        { 
            $content = $this->searchWord();
        } 

        /** 
         * Finish output 
         */ 
        // $out->addContent($content); 
        echo json_encode($content);
        // $oEvent->addContent($this, $content); 
        return; 
    }

    public function lookup()
    {
        $lang = $_GET['l'];
        $defs = include 'php/dictionary-'.$lang.'.php';
        $defs = array_change_key_case($defs, CASE_LOWER);
        $keys = array_keys($defs);

        return json_encode($keys);
    }

    public function searchWord()
    {
        $defs = include 'php/dictionary-'.$lang.'.php';

        $defs = array_change_key_case($defs, CASE_LOWER);
        $term = trim(strtolower($term));
        $definition = "Sorry. The term wasn't found.";

        if (array_key_exists($term, $defs))
            $definition = $defs[$term];
        return $definition;
    }

    protected function getPluginFileUrl($relativePath)
    {
        return $this->getPluginBaseUrl() . '/' . $relativePath;
    }

    protected function getPluginBaseUrl()
    {
        $pluginDir = $this->getPluginDir();
        $pluginDir = str_replace(FCPATH, "", $pluginDir);
        $url = \Yii::app()->getConfig('publicurl') . $pluginDir;
        return $url;
    }

    protected function getPluginDir($class = NULL)
    {
        if (empty($class)) {
            $object = new \ReflectionObject($this);
        } else {
            $object = new \ReflectionClass($class);
        }

        $filename = $object->getFileName();
        $basePath = dirname($filename);

        return $basePath;
    }

    protected function getPluginFilePath($relativePath)
    {
        return $this->getPluginDir() . '/' . $relativePath;
    }

}