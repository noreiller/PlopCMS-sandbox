<?php

require_once sfConfig::get('sf_plugins_dir') . '/sfAssetsLibraryPlugin/modules/sfAsset/lib/BasesfAssetActions.class.php';

class sfAssetActions extends BasesfAssetActions
{
  public function preExecute()
  {
    $module = 'sfAssetLibrary';
    if (!in_array($module, array_keys(sfPlop::getSafePluginModules())))
      $this->forward404();

    if (!$this->getUser()->isAuthenticated())
      $this->forward(sfConfig::get('sf_login_module'), sfConfig::get('sf_login_action'));

    if (!$this->getUser()->hasCredential($module))
      $this->forward(sfConfig::get('sf_secure_module'), sfConfig::get('sf_secure_action'));

    parent::preExecute();

    $user = $this->getUser();
    $user->setCulture($user->getProfile()->getCulture());

    ProjectConfiguration::getActive()->LoadHelpers(array('I18N'));
    $this->getResponse()->setTitle(sfPlop::setMetaTitle(__('Media library', '', 'plopAdmin')));
  }
}