<?php 

namespace Gelembjuk\EasyApp\Resources\Localization;

class Localization
{
    /**
	 * Current locale
	 * 
	 * @var string
	 */
	protected string $locale;

    /**
     * The main method to get a text coresponding to a key.
     * It does a "translation" and it should be reimplemented in child classes
     *
     * @param string $key Key to get a text for
     * @param string $group Group of texts. Optional
     * @param mixed ...$params Parameters to replace in the text. Optional
     * @return string Text for the key
     */
    public function getText($key, $group = '', ...$params) : string
    {
        return "$group:$key";
    }
    /**
     * Set locale
     * 
     * @param string $locale
     * @return self
     */
    public function withLocale(string $locale)
	{
		$this->locale = $locale;
		return $this;
	}
    /**
     * Get current locale
     * 
     * @return string
     */
    public function getLocale() : string
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
}