<?php

class sfMangoBagPluginConfiguration extends sfPluginConfiguration
{
  public function initialize()
  {
    $this->dispatcher->connect('context.load_factories', array('sfMangoBagFinder', 'setDatabase'));
  }

}
