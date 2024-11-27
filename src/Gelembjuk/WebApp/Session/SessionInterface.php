<?php 

namespace Gelembjuk\WebApp\Session;

interface SessionInterface {
    public function get(string $key);
    public function set(string $key, $value);
    public function delete(string $key);
    public function clear();
    public function start();
    public function destroy();
    public function getUserID(): string;
    public function setUserID(string $userid);
    public function isLoggedIn(): bool;
}