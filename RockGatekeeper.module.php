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
class RockGatekeeper extends WireData implements Module, ConfigurableModule
{
  public function init() {}

  /**
   * Config inputfields
   * @param InputfieldWrapper $inputfields
   */
  public function getModuleConfigInputfields($inputfields)
  {
    return $inputfields;
  }
}
