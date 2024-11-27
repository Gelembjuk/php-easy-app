<?php 

namespace Gelembjuk\WebApp\Session;

class NemoSession implements SessionInterface {
    protected $session = null;
    protected $userid = "";
    
    public function __construct() 
    {
        $this->session = [];
    }
    
    public function get(string $key) 
    {
        return $this->session[$key] ?? null;
    }
    
    public function set(string $key, $value) 
    {
        $this->session[$key] = $value;
    }
    
    public function delete(string $key) 
    {
        unset($this->session[$key]);
    }
    
    public function clear() 
    {
        $this->session = [];
    }
    
    public function start() 
    {
        // nothing to do
    }
    
    public function destroy() 
    {
        $this->session = [];
        $this->userid = "";
    }
    
    public function getUserID(): string 
    {
        return $this->userid;
    }
    
    public function setUserID(string $userid) 
    {
        $this->userid = $userid;
    }
    
    public function isLoggedIn(): bool 
    {
        return !empty($this->userid);
    }
}