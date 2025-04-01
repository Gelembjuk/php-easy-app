<?php

/**
* This trait helps to include integrate application with a class.
* It allows to use logging and translation system of an application. And also to use any other builders inluded in app/
* 
* LICENSE: MIT
*
* @category   MVC
* @package    Gelembjuk/EasyApp
* @copyright  Copyright (c) 2024 Roman Gelembjuk. (http://gelembjuk.com)
* @version    1.0
* @link       https://github.com/Gelembjuk/webapp
*/

namespace Gelembjuk\EasyApp;

trait ContextTrait {
    /**
     * We will use this for making singleton if needed. Each class will support singletons
     */
    private static $singletonObject = [];
    /**
     * We will use this for mocking. It will be used for unit testing. On production this property will be always null
     */
    private static $readyObject = [];
    /**
    * This is context object , instance of Gelembjuk\EasyApp\Applicaion
    */
    protected Context $context;

    protected \Psr\Log\LoggerInterface $logger;

    protected $loggingChannel = "";

    /**
     * If true then user must be signed in to access the class.
     * This is to disable some classes usage for not signed in users.
     * We want to decrease the amount of code and checks in the class itself. So, just set this to true in the class
     */
    protected $signinreqired = false;

    public function __construct(Context $context) 
    {
        $this->withContext($context);
        $this->afterConstruct();
    }

    /**
     * This creates new object or uses existent liek a singleton. But if there is a ready object then it will return it
     */
    public static function getInstance(Context $context) : static
    {
        if (isset(static::$readyObject[static::class])) {
            return static::$readyObject[static::class];
        }
        if (!isset(static::$singletonObject[static::class])) {
            static::$singletonObject[static::class] = new static($context);
        }
        return static::$singletonObject[static::class];
    }
    /**
     * This creates new object. But if there is a ready object then it will return it
     */
    public static function getNewInstance(Context $context) : static
    {
        if (isset(static::$readyObject[static::class])) {
            return static::$readyObject[static::class];
        }
        return new static($context);
    }
    /**
     * This is used for unit testing. It allows to set a ready object to use in the test.
     * But sometimes this could be used for production too
     */
    public static function setReadyObject($object) 
    {
        self::$readyObject = $object;
    }

    protected function afterConstruct() 
    {
        // do nothing here. It is used to have a constructor in a class that uses this trait
        // to do some actions after application is known and set
    }

    /**
     * Set context object. 
     *
     * @param object $context Gelembjuk\EasyApp\Context
     */
    
    public function withContext(Context $context) 
    {
        $this->context = $context;
        // fail if user is not signed in
        $this->checkIfSignedInRequired();
        
        $channel = $this->loggingChannel;

        if (empty($channel)) {
            $channel = get_class($this);
        }

        $this->logger = $this->context->getLogger($channel);
        return $this;
    }
    
    // The set of methods for user session checks
    /**
     * 
     */
    protected function checkIfSignedInRequired()
    {
        if ($this->signinreqired) {
            $this->signinRequired();
        }
    }
    /**
     * This is basic function to call from any place to ensure a user is logged in to access a page
     * 
     * @param string $errormessage Error message to show if user is not logged in
     * @param string $url URL to redirect user to login page. In case if error action is redirect and not just view 
     */
    protected function signinRequired($errormessage = '') 
	{
        if (!empty($this->getUserID())) {
            return true;
        }
		
        if (empty($errormessage)) {
            // this is needed for correct localisation
            $errormessage = $this->_('user_auth_required_please_login','exceptions');

            if ($errormessage == 'user_auth_required_please_login') {
                $errormessage = 'User Auth required. Please login';
            }
        }

        throw new Exceptions\UnauthorizedException($errormessage);
	}
    /**
	 * It can be reloaded in a child class to define a single url where to send a user in case if login is required
	 */
	protected function getDefaultAuthExceptionRedirectUrl()
	{
		return null;
	}
    protected function actionRequiresSignin()
    {
        $this->signinreqired = true;
    }
    protected function actionDoesNotRequiresSignin()
    {
        $this->signinreqired = false;
    }
	protected function getUserID():string 
	{
		return $this->context->session->getUserID() ?? '';
	}
    protected function _($key, $group = '', ...$params):string 
	{
		return $this->context->translation->getText($key, $group, ...$params);
	}
}
