<?php

namespace ProcessWire;

function rockgatekeeper(): RockGatekeeper
{
  return wire()->modules->get('RockGatekeeper');
}

/**
 * @author Bernhard Baumrock, 28.07.2025
 * @license Licensed under MIT
 * @link https://www.baumrock.com
 */
class RockGatekeeper extends WireData implements Module
{
  public function ready()
  {
    // early exits for minimal footprint
    if (!wire()->config->gatekeeper) return;
    if (wire()->config->external) return; // cli usage

    $this->allowUser();
    $this->preventAccess();
  }

  private function preventAccess()
  {
    // if not a guest, do nothing
    if (!wire()->user->isGuest()) return;

    // no redirect if correct password is set
    $pass = wire()->config->gatekeeper;
    if (wire()->session->gatekeeperPassword === $pass) return;

    // if the IP is allowed we also exit early
    if (wire()->session->gatekeeperIP === wire()->session->getIP()) return;

    // no access
    die('no access');
  }

  private function allowUser()
  {
    $pass = wire()->input->get('gatekeeper');
    if (!$pass) return;

    // if $pass does not match, exit
    if ($pass !== wire()->config->gatekeeper) {
      wire()->session->gatekeeperPassword = false;
      wire()->session->gatekeeperIP = false;
      return;
    }

    // correct password, set session vars
    wire()->session->gatekeeperPassword = $pass;
    wire()->session->gatekeeperIP = wire()->session->getIP();

    // redirect to the same page but without the gatekeeper param
    $url = $this->removeQueryParam();
    wire()->session->redirect($url);
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
