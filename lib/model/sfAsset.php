<?php

/**
 * sfAsset
 *
 * @package sfAssetsLibraryPlugin
 * @author  Massimiliano Arione
 */
class sfAsset extends PluginsfAsset
{
  /**
   * Overrrides the parent create() method to add a fix when the uploaded file
   * cannot be set with a readable chmod, for example on a shared host with
   * restricted /tmp privileges.
   */
  public function create($assetPath, $move = true, $checkDuplicate = true)
  {
    parent::create($assetPath, $move, $checkDuplicate);
    chmod($this->getFolderPath() . "/" . $this->getFilename(), 0604);
  }
}
