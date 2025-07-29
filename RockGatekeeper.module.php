<?php

namespace ProcessWire;

/**
 * @author Bernhard Baumrock, 28.07.2025
 * @license Licensed under MIT
 * @link https://www.baumrock.com
 */
class RockGatekeeper extends WireData implements Module
{
  const cachekey = 'gatekeeper-ips';

  public function ready()
  {
    $this->allowIP();

    // early exits for minimal footprint
    if (!wire()->config->gatekeeper) return;
    if (wire()->config->external) return; // cli usage
    if (wire()->config->ajax) return; // ajax usage

    $this->checkPassword();
    $this->preventAccess();
  }

  private function allowIP()
  {
    $ip = wire()->session->getIP();
    $ips = wire()->cache->get(self::cachekey) ?: [];
    $ips[$ip] = time() + 60 * 30; // +30min
    wire()->cache->save(self::cachekey, $ips);
  }

  private function checkPassword()
  {
    $pass = wire()->input->get('gatekeeper');
    if (!$pass) return;

    // if $pass does not match, exit
    if ($pass !== wire()->config->gatekeeper) {
      $this->removePassword();
      $this->removeIP();
      return;
    }

    // correct password, set session vars
    wire()->session->gatekeeperPassword = $pass;
    $this->allowIP();

    // redirect to the same page but without the gatekeeper param
    $url = $this->removeQueryParam();
    wire()->session->redirect($url);
  }

  private function hasCorrectPassword()
  {
    return wire()->session->gatekeeperPassword === wire()->config->gatekeeper;
  }

  private function isAllowedIP()
  {
    $ips = wire()->cache->get(self::cachekey) ?: [];
    $ip = wire()->session->getIP();
    return isset($ips[$ip]) && $ips[$ip] > time();
  }

  private function preventAccess()
  {
    // if not a guest, do nothing
    if (!wire()->user->isGuest()) return;

    // no redirect if correct password is set
    if ($this->hasCorrectPassword()) return;

    // no password, but IP is allowed?
    // this makes it possible to access the page from multiple devices
    // eg when testing registration process and clicking on a link in an email
    // on your phone instead of your desktop pc
    if ($this->isAllowedIP()) return;

    // no access
    die('no access');
  }

  private function removeIP()
  {
    $ip = wire()->session->getIP();
    $ips = wire()->cache->get(self::cachekey) ?: [];
    unset($ips[$ip]);
    wire()->cache->save(self::cachekey, $ips);
  }

  private function removePassword()
  {
    wire()->session->remove('gatekeeperPassword');
  }

  private function removeQueryParam()
  {
    $url = wire()->input->url();
    $query = wire()->input->queryString();
    parse_str($query, $query);
    unset($query['gatekeeper']);
    if ($query) return $url . '?' . http_build_query($query);
    return $url;
  }
}
