<?php

namespace ProcessWire;

/**
 * @author Bernhard Baumrock, 28.07.2025
 * @license Licensed under MIT
 * @link https://www.baumrock.com
 */
class RockGatekeeper extends WireData implements Module, ConfigurableModule
{
  const cachekey = 'gatekeeper-ips';
  public $allowIP = false;
  public $duration = false;

  public function ready()
  {
    // early exits for minimal footprint
    if (!wire()->config->gatekeeper) return;
    if (wire()->config->external) return; // cli usage
    if (wire()->config->ajax) return; // ajax usage

    $method = wire()->input->requestMethod();
    if ($this->allowNonGET && $method !== 'GET') return;

    if (!$this->duration) $this->duration = 30;

    $this->checkPassword();
    $this->preventAccess();
  }

  private function allowIP()
  {
    if (!$this->allowIP) return;
    $ip = wire()->session->getIP();
    $ips = wire()->cache->get(self::cachekey) ?: [];
    $ips[$ip] = time() + 60 * $this->duration;
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

  /**
   * Config inputfields
   * @param InputfieldWrapper $inputfields
   */
  public function getModuleConfigInputfields($inputfields)
  {
    $inputfields->add([
      'type' => 'checkbox',
      'name' => 'allowIP',
      'label' => 'Allow IP',
      'value' => $this->allowIP,
      'description' => 'When enabled, allows access from the same IP address after successful password authentication. Useful for testing workflows across multiple devices on the same network (e.g., testing email signup links on your phone while developing on desktop). Note: This will grant access to ALL devices on the same network, so use with caution.',
      'checked' => $this->allowIP,
      'icon' => 'globe',
    ]);

    $inputfields->add([
      'type' => 'integer',
      'name' => 'duration',
      'label' => 'Duration',
      'value' => $this->duration,
      'description' => 'How long a successful password authentication is valid for. Default is 30 minutes.',
      'icon' => 'hourglass-2',
      'notes' => 'If your ProcessWire session timeout is set to a lower value, this will be ignored.',
    ]);

    $inputfields->add([
      'type' => 'checkbox',
      'name' => 'allowNonGET',
      'label' => 'Allow all non-GET requests',
      'checked' => $this->allowNonGET,
      'notes' => 'This can be useful for allowing webhooks that send POST requests etc.',
    ]);

    return $inputfields;
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

  public function ___preventAccess()
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
