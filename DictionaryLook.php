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
        $this->subscribe('beforeSurveySettings');
        $this->subscribe('beforeSurveyPage');
        $this->subscribe('beforeCloseHtml');
        $this->subscribe('newDirectRequest');
        $this->subscribe('newSurveySettings');
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

    /**
     * Survey Settings
     */
    public function beforeSurveySettings()
    {
        $event = $this->event;
        
        $newSettings = array(
            'dictionarySurvey' => array(
                'type'=>'select',
                'options' => $this->beforeSurveySettings_SurveySelectList(),
                'htmlOptions'=>array(
                    'empty'=>$this->t("None"),
                ),
                'label' => $this->t('Dictionary Survey:'),
                'current' => $this->beforeSurveySettings_Current('dictionarySurvey'),
                'help' => 'Source survey where terms and definitions can be found',
            ),
            'termQuestionCode' => array(
                'type' => 'string',
                'label' => $this->t('Term question:'),
                'current' => $this->beforeSurveySettings_Current('termQuestionCode'),
                'help' => 'Question code of the question holding the terms',
            ),
            'definitionQuestionCode' => array(
                'type' => 'string',
                'label' => $this->t('Definition question:'),
                'current' => $this->beforeSurveySettings_Current('definitionQuestionCode'),
                'help' => 'Question code of the question holding the definitions',
            ),            
        );
        // echo $this->beforeSurveySettings_Current('dictionarySurvey');
        // Set all settings
        $event->set("surveysettings.{$this->id}", array(
            'name' => get_class($this),
            'settings' => $newSettings,
        ));
    }

    public function newSurveySettings()
    {
        $event = $this->event;

        foreach ($event->get('settings') as $name => $value)
        {
            $this->set($name, $value, 'Survey', $event->get('survey'));
        }
    }

    protected function beforeSurveySettings_SurveySelectList($curSid = NULL)
    {
        if (empty($curSid)) $curSid = $this->event->get('survey');

        // Get the survey list according to user permissions
        $oSurvey = new \Survey;
        $oSurvey->permission(\Yii::app()->user->getId());
        $aoSurveys = $oSurvey->with(
            array(
                'languagesettings' => array('condition'=>'surveyls_language=language'),
                'owner'
            )
        )->findAll("sid <> :sid",array(":sid"=>$curSid));

        // Compose list
        $aSurveys = [];
        foreach($aoSurveys as $S)
        {
            $aSurveys[ $S->sid ]= "[". $S->sid . "] " . $S->LocalizedTitle;
        }

        return $aSurveys;
    }

    public function beforeSurveySettings_Current($setting)
    {
        $event = $this->event;
        return $this->get($setting, 'Survey', $event->get('survey'), $this->get('setting'));
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
        if (empty($_GET['l'])) die('No language defined');
        $termList = $this->getTerms($_GET['surveyId']);
        return $termList;

        $lang = $_GET['l'];
        $defs = include 'php/dictionary-'.$lang.'.php';
        $defs = array_change_key_case($defs, CASE_LOWER);
        $keys = array_keys($defs);

        return json_encode($keys);
    }

    public function searchWord()
    {
        $term = $_GET['t'];
        $lang = $_GET['l'];
        $definitionTerm = $this->getDefnitions($_GET['surveyId'], $term);
        return $definitionTerm;

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

    public function getTerms($surveyId)
    {
        $termResponse = [];
        $dictionarySurveyId = $this->get('dictionarySurvey', 'Survey', $surveyId);
        // $questionId = $this->get('termQuestionCode', 'Survey', $surveyId);        
        $response = \SurveyDynamic::model($dictionarySurveyId)->findAll();
        $sourceQuestion = Question::model()->findByAttributes(array('sid' => $dictionarySurveyId));
        $questionColumnCode = $this->getSGQ($sourceQuestion);

        foreach($response as $value){
            array_push($termResponse, $value[$questionColumnCode]);
        }

        return CJSON::encode($termResponse);
    }

    public function getDefnitions($surveyId, $term)
    {
        $dictionarySurveyId = $this->get('dictionarySurvey', 'Survey', $surveyId);
        $sourceQuestion = Question::model()->findByAttributes(
            array(
                'sid' => $dictionarySurveyId,
            )
        );
        $sourceTerm = Question::model()->findByAttributes(
            array(
                'sid' => $dictionarySurveyId,
                'title' => 'def',
            )
        );
        $questionColumnCode = $this->getSGQ($sourceQuestion);
        $termColumnCode = $this->getSGQ($sourceTerm);

        $definitionRaw = SurveyDynamic::model($dictionarySurveyId)->findByAttributes(
            array(
                $questionColumnCode => $term,
            )
        );

        return $definitionRaw[$termColumnCode];
    }

    // Get a SGQ from a Question
    public static function getSGQ($Q)
    {
        if (!empty($Q->parent_qid))
            $sgq = $Q->sid.'X'.$Q->gid.'X'.$Q->parent_qid."".$Q->title;
        else
            $sgq = $Q->sid.'X'.$Q->gid.'X'.$Q->qid;
        return $sgq;
    }

    protected function t($string)
    {
        $messageSource = get_class($this).'Lang';
        return \Yii::t('',$string,array(),$messageSource);
    }
    
    /**
     * Loads tranlsations on own namespace
     */
    public function loadTranslations($basePath = NULL)
    {
        if (empty($basePath)) $basePath = __DIR__
                                            . DIRECTORY_SEPARATOR .'..'
                                            . DIRECTORY_SEPARATOR . '..'
                                            . DIRECTORY_SEPARATOR . get_class($this) . '/locale';
        // var_dump($basePath); die();

        // messageSource for this plugin:
    }
}