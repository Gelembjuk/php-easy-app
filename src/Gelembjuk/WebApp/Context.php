<?php

namespace Gelembjuk\WebApp;

use Gelembjuk\WebApp\Present\HTMLPresenter as HTMLPresenter;

class Context {
	public Config $config;
	/**
	 * @var \Gelembjuk\WebApp\Session\SessionInterface
	 * 
	 * This is a session object. It has info about current user
	 */
	public Session\SessionInterface $session;
	/**
	 * @var \Psr\Log\LoggerInterface
	 * 
	 * This is a logger for the application. It is used to log application events
	 */
	protected \Psr\Log\LoggerInterface $logger;
	/**
	 * @var \Psr\Log\LoggerInterface
	 * 
	 * This is a error logger for the application. It is used to log application errors
	 */
	public \Psr\Log\LoggerInterface $errorLogger;
	
	/**
	 * Locale, 2 chars language code. Optional.
	 * Only for applications with multilanguage support
	 * 
	 * @var string
	 */
	protected string $locale = '';

	public $applicationRootDirectory;
	public $presentFormat = '';
	public $actionInitiator = '';

	/**
	 * This is the place to keep settings. It is one of places where to keep settings.
	 * Alternative place will be properties declared in child classes
	 * 
	 * Adding settings in child classes. Just add public property with the name of setting. Or use this array to keep settings
	 * NOTE. Public property is better solution. It is more clear and IDE will help you with autocompletion.
	 * However, in some places you will need to redefine the context property class , so IDE points to your class, not to the parent class
	 */
	public $settings = [];

	public function __construct(string $applicationRootDirectory = '', ?Config $config = null, ?Session\SessionInterface $session = null) 
	{
		$this->applicationRootDirectory = $applicationRootDirectory;
		
		if ($config === null) {
			$config = new Config();
		}

		$this->config = $config;
		$this->logger = new \Psr\Log\NullLogger();
		$this->errorLogger = new \Psr\Log\NullLogger();
		
		if ($session === null) {
			$session = new Session\NemoSession();
		}
		
		$this->session = $session;

		$this->completeSettingsSetup();
	}
	protected function completeSettingsSetup() 
	{
		// this is a place to complete settings setup
	}
	public function withSession(Session\SessionInterface $session): Context
	{
		$this->session = $session;
		return $this;
	}
	public function withLogger(\Psr\Log\LoggerInterface $logger): Context
	{
		$this->logger = $logger;
		return $this;
	}
	public function withPresentFormat(string $format): Context
	{
		$this->presentFormat = $format;
		return $this;
	}
	public function withActionInitiator(string $initiator): Context
	{
		$this->actionInitiator = $initiator;
		return $this;
	}
	public function getPresentFormat(): string
	{
		return $this->presentFormat;
	}
	protected function configureLogger() 
	{
		// by defaule there is no logger
	}
	public function getLogger($channel): \Psr\Log\LoggerInterface
	{
		// return logger for a channel
		return $this->logger;
	}
	public function getRelativeBaseURL(): string
	{
		// return relative base url for the application
		return '/';
	}
	/**
	 * This should return the string like http://domain.com/.
	 * But by default it is not implemented, it must be redefined in the child class
	 * It could read the host andscheme from the request object or from config , etc.
	 */
	public function getAbsoluteBaseURL(): string
	{
		throw new \Exception('Method getAbsoluteBaseURL is not implemented in the context class');
	}
	public function getSetting($name, $default = null)
	{
		// check if has public property with same name, then return it
		if (property_exists($this, $name)) {
			// ensure it is public property. Settings are public
			$ref = new \ReflectionProperty($this, $name);
			if ($ref->isPublic()) {
				return $this->$name;
			}
			unset($ref);
		}
		return $this->settings[$name] ?? $default;
	}
	/**
	 * Return current locale
	 * 
	 * @return string
	 */
	public function getLocale() 
	{
		return $this->locale;
	}
	/**
	 * Set locale
	 * 
	 * @param string $locale
	 */
	public function setLocale($locale) 
	{
		$this->locale = $locale;
	}
	/**
	 * This is the "abstract" function for localization.
	 * It should be redefined in the child class to do some real useful work
	 */
	public function _($key, ...$params):string 
	{
		return $key;
	}
	public function inDebugMode():bool 
	{
		return false;
	}	
	public function getPresenterSettings($format): array
	{
		if ($format == HTMLPresenter::OUTPUT_FORMAT) {
            $settings = [
                'templatepath' => $this->applicationRootDirectory.'/templates/',
				'compiledpath' => $this->applicationRootDirectory.'/tmp/templates_c/',
                'extension' => 'htm',
            ];

			if (!file_exists($settings['templatepath'])) {
				throw new \Exception('Templates path does not exist: '.$settings['templatepath']);
			}
			if (!file_exists($settings['compiledpath'])) {
				throw new \Exception('Compiled templates path does not exist: '.$settings['compiledpath']);
			}

			return $settings;
        }
		return [];
	}
} 
