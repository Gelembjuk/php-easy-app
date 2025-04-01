<?php 

namespace Gelembjuk\EasyApp\Session;

class StandardSession implements SessionInterface {
    protected $config = [];
    public function __construct($config = []) 
    {
        $this->config = $config;
    }
    
    public function get(string $key) 
    {
        $this->start();
        return $_SESSION[$key] ?? null;
    }
    
    public function set(string $key, $value) 
    {
        $this->start();
        $_SESSION[$key] = $value;
    }
    
    public function delete(string $key) 
    {
        $this->start();
        unset($_SESSION[$key]);
    }
    
    public function clear() 
    {
        $this->start();
        $_SESSION = [];
    }
    
    public function start() 
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return;
        }

        if (isset($this->config['cache_limiter'])) {
            session_cache_limiter($this->config['cache_limiter']);
        }

        if (isset($this->config['cache_expire'])) {
            session_cache_expire($this->config['cache_expire']);
        }

        session_start();
    }
    
    public function destroy() 
    {
        $this->start();
        session_destroy();
    }
    
    public function getUserID(): string 
    {
        $userid = $this->get('userid');

        if (empty($userid)) {
            return '';
        }
        return $userid;
    }
    
    public function setUserID(string $userid) 
    {
        $this->set('userid', $userid);
    }
    
    public function isLoggedIn(): bool 
    {
        return !empty($this->getUserID());
    }
}