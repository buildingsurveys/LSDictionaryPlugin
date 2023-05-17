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
        $assetsUrl = $this->getPluginFileUrl('assets/info-icon.jpeg');
        $assetsUrl = str_replace("\\","/", $assetsUrl);

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
            self::$name . "assetsUrl", 'var assetsUrl="'.$assetsUrl.'";', 
            CClientScript::POS_BEGIN
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
        $client->registerScriptFile($this->getPluginFileUrl('js/config.js'));
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
            'active' => array(
                'type' => 'boolean',
                'default' => 0,
                'label' => 'Activate plugin for this survey:',
                'current' => $this->get('active', 'Survey', $event->get('survey'))
            ),
        );
        // echo $this->beforeSurveySettings_Current('dictionarySurvey');
        // Set all settings
        $event->set("surveysettings.{$this->id}", array(
            'name' => get_class($this),
            'settings' => $newSettings,
        ));
    }

    /**
     * Checks if a survey setting is active
     */
    protected function isSurveySettingActive($setting, $surveyId = NULL, $surveyAtt = 'surveyId')
    {
        $event = $this->getEvent();
        if (empty($surveyId)) $surveyId = $event->get($surveyAtt);

        $val = $this->get($setting, 'Survey', $surveyId);
        return ($val == TRUE || $val > 0);
    }

    protected function checkActiveSetting()
    {
        $request = Yii::app()->request;
        $surveyId = $request->getParam('surveyId', null);
        $isActive = $this->isSurveySettingActive('active', $surveyId);
        return $isActive;
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
        echo $content;
        // $oEvent->addContent($this, $content); 
        return; 
    }

    public function getPluginSettings($getValues = true)
    {
        $surveys = $this->getSurveySelectList();

        $this->settings = [
            'globalSurveyId' => array(
                'type'=>'select',
                'options' => $surveys,
                'htmlOptions'=>array(
                    'empty'=>$this->t("None"),
                ),
                'label' => $this->t('Suggested Dictionary Survey:'),
                'help' => 'Survey holding all the suggested terms and definitions. This is the source for the dashboard.',
            ),
    ];

        return parent::getPluginSettings($getValues);
    }

    protected function getSurveySelectList()
    {
        // Get the survey list according to user permissions
        $oSurvey = new \Survey;
        $oSurvey->permission(\Yii::app()->user->getId());
        $aoSurveys = $oSurvey->with(
            array(
                'languagesettings' => 
                    array(
                        'condition' =>
                        'surveyls_language=language'), 
                'owner'
                )
            )->findAll();

        // Compose list
        $aSurveys = [];
        foreach($aoSurveys as $survey)
        {
            $aSurveys[ $survey->sid ]= "[". $survey->sid . "] " . $survey->LocalizedTitle;
        }

        return $aSurveys;
    }

    public function lookup()
    {
        $request = Yii::app()->request;
        $lang = $request->getParam('l', null);
        if (!$this->checkActiveSetting()) return;
        if (empty($lang)) die('No language defined');

        // Pickup Dictionary Survey
        $surveyId = $request->getParam('surveyId', null);
        $dictionarySurveyId = $this->get('dictionarySurvey', 'Survey', $surveyId);
        // Get General an specific list terms  
        $termList = $this->getTerms($surveyId, $dictionarySurveyId);
        $globalTermList = $this->getTerms(
            $surveyId,
            $this->get('globalSurveyId')
        );
        // remove duplicate values
        $totalTermList = array_unique(array_merge($termList, $globalTermList));
        $totalTermList = array_values($totalTermList);;
        return CJSON::encode($totalTermList);
    }

    public function searchWord()
    {
        // Check if plugin is active for this survey
        if (!$this->checkActiveSetting()) return;

        $request = Yii::app()->request;
        $term = $request->getParam('t', null);
        $lang = $request->getParam('l', null);
        // Pickup Dictionary Survey
        $surveyId = $request->getParam('surveyId', null);
        $dictionarySurveyId = $this->get('dictionarySurvey', 'Survey', $surveyId);
        // Get definitions
        $definitionTerm = $this->getDefinitions($surveyId, $dictionarySurveyId, $term);
        $finalDefinitionTerm = $definitionTerm ? 
            $definitionTerm : 
            $this->getDefinitions($surveyId, $this->get('globalSurveyId'), $term);
        if(!$finalDefinitionTerm) echo "Sorry. The term wasn't found.";

        return json_encode($finalDefinitionTerm);
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

    public function getTerms($surveyId, $dictionarySurveyId)
    {
        // Pickup Dictionary Survey terms
        $response = \SurveyDynamic::model($dictionarySurveyId)->findAll();
    
        // Pickup Term Question Code
        $termQuestionTitleCode = $this->get('termQuestionCode', 'Survey', $surveyId);
        $sourceQuestion = Question::model()->findByAttributes(array(
            'sid' => $dictionarySurveyId,
            'title' => $termQuestionTitleCode
        ));
        $questionColumnCode = $this->getSGQ($sourceQuestion);

        $termResponse = [];
        foreach($response as $value){
            array_push($termResponse, $value[$questionColumnCode]);
        }

        return $termResponse;
    }

    public function getDefinitions($surveyId, $dictionarySurveyId, $term)
    {
        // Get plugin settings
        $termQuestionTitleSetting = $this->get('termQuestionCode', 'Survey', $surveyId);
        $definitionQuestionTitleSetting = $this->get('definitionQuestionCode', 'Survey', $surveyId);
        $sourceQuestions = Question::model()->findAllByAttributes(
            array(
                'sid' => $dictionarySurveyId,
            )
        );

        // Get records
        $recordWithTermCode = $this->getRecordByTitle($sourceQuestions, $termQuestionTitleSetting);
        $recordWithDefCode = $this->getRecordByTitle($sourceQuestions, $definitionQuestionTitleSetting);

        // Get record columns 
        $questionTermColumnCode = $this->getSGQ($recordWithTermCode);
        $questionDefColumnCode = $this->getSGQ($recordWithDefCode);

        $definitionRaw = SurveyDynamic::model($dictionarySurveyId)->findByAttributes(
            array(
                $questionTermColumnCode => $term,
            )
        );

        return $definitionRaw[$questionDefColumnCode];
    }

    protected function getRecordByTitle($questions, $title)
    {
        foreach($questions as $question){
            if($question->title === $title)
            return $question;
        }
        return null;
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
        $messageSource = get_class($this) . 'Lang';
        return \Yii::t('', $string,array(), $messageSource);
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