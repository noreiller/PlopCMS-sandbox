<?php

/**
 * sfAssetFolder
 *
 * @package sfAssetsLibraryPlugin
 * @author  Massimiliano Arione
 */
class sfAssetFolder extends PluginsfAssetFolder
{

  /**
   * Chmod recursive
   *
   * @see http://www.php.net/manual/fr/function.chmod.php#105570
   */
  private function chmod_R($path, $filemode, $dirmode)
  {
      if (is_dir($path) ) {
          if (!chmod($path, $dirmode)) {
              $dirmode_str=decoct($dirmode);
              print "Failed applying filemode '$dirmode_str' on directory '$path'\n";
              print "  `-> the directory '$path' will be skipped from recursive chmod\n";
              return;
          }
          $dh = opendir($path);
          while (($file = readdir($dh)) !== false) {
              if($file != '.' && $file != '..') {  // skip self and parent pointing directories
                  $fullpath = $path.'/'.$file;
                  $this->chmod_R($fullpath, $filemode,$dirmode);
              }
          }
          closedir($dh);
      } else {
          if (is_link($path)) {
              print "link '$path' is skipped\n";
              return;
          }
          if (!chmod($path, $filemode)) {
              $filemode_str=decoct($filemode);
              print "Failed applying filemode '$filemode_str' on file '$path'\n";
              return;
          }
      }
  }

  /**
   * Overrrides the parent create() to set a different chmod, because the user
   * who create the dir is not always the one who reads.
   *
   * @see parent::create()
   */
  public function create()
  {
    list ($base, $name) = sfAssetsLibraryTools::splitPath($this->getRelativePath());
    $status = sfAssetsLibraryTools::mkdir($name, $base);
    $this->chmod_R($this->getFullPath(), 0604, 0755);

    return $status;
  }
  /**
   * Overrrides the parent synchronizeWith() method to add a fix when calling
   * insertAsLastChildOf($this->reload()) because $this->reload() doesn't return
   * $this.
   *
   * @see parent::synchronizeWith()
   */
  public function synchronizeWith($baseFolder, $verbose = true, $removeOrphanAssets = false, $removeOrphanFolders = false)
  {
    if (!is_dir($baseFolder))
    {
      throw new sfAssetException(sprintf('%s is not a directory', $baseFolder));
    }

    $files = sfFinder::type('file')->maxdepth(0)->ignore_version_control()->in($baseFolder);
    $assets = $this->getAssetsWithFilenames();
    foreach ($files as $file)
    {
      if (!array_key_exists(basename($file), $assets))
      {
        // File exists, asset does not exist: create asset
        $sfAsset = new sfAsset();
        $sfAsset->setFolderId($this->getId());
        $sfAsset->create($file, false);
        $sfAsset->save();
        if ($verbose)
        {
          sfAssetsLibraryTools::log(sprintf("Importing file %s", $file), 'green');
        }
      }
      else
      {
        // File exists, asset exists: do nothing
        unset($assets[basename($file)]);
      }
    }

    foreach ($assets as $name => $asset)
    {
      if ($removeOrphanAssets)
      {
        // File does not exist, asset exists: delete asset
        $asset->delete();
        if ($verbose)
        {
          sfAssetsLibraryTools::log(sprintf("Deleting asset %s", $asset->getUrl()), 'yellow');
        }
      }
      else
      {
        if ($verbose)
        {
          sfAssetsLibraryTools::log(sprintf("Warning: No file for asset %s", $asset->getUrl()), 'red');
        }
      }
    }

    $dirs = sfFinder::type('dir')->maxdepth(0)->discard(sfConfig::get('app_sfAssetsLibrary_thumbnail_dir', 'thumbnail'))->ignore_version_control()->in($baseFolder);
    $folders = $this->getSubfoldersWithFolderNames();
    foreach ($dirs as $dir)
    {
      list(,$name) = sfAssetsLibraryTools::splitPath($dir);
      if (!array_key_exists($name, $folders))
      {
        $this->reload();
        // dir exists in filesystem, not in database: create folder in database
        $sfAssetFolder = new sfAssetFolder();
        $sfAssetFolder->insertAsLastChildOf($this);
        $sfAssetFolder->setName($name);
        $sfAssetFolder->save();
        if ($verbose)
        {
          sfAssetsLibraryTools::log(sprintf("Importing directory %s", $dir), 'green');
        }
      }
      else
      {
        // dir exists in filesystem and database: look inside
        $sfAssetFolder = $folders[$name];
        unset($folders[$name]);
      }
      $sfAssetFolder->synchronizeWith($dir, $verbose, $removeOrphanAssets, $removeOrphanFolders);
    }

    foreach ($folders as $name => $folder)
    {
      if ($removeOrphanFolders)
      {
        $folder->delete(null, true);
        if ($verbose)
        {
          sfAssetsLibraryTools::log(sprintf("Deleting folder %s", $folder->getRelativePath()), 'yellow');
        }
      }
      else
      {
        if ($verbose)
        {
          sfAssetsLibraryTools::log(sprintf("Warning: No directory for folder %s", $folder->getRelativePath()), 'red');
        }
      }
    }
  }
}
