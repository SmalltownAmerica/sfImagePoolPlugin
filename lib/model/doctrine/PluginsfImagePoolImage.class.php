<?php
/**
 * PluginsfImagePoolImage
 * 
 * This class has been auto-generated by the Doctrine ORM Framework
 * 
 * @package    sfImagePoolPlugin
 * @subpackage model
 * @author     Ben Lancaster
 * @version    SVN: $Id: Builder.php 7380 2010-03-15 21:07:50Z jwage $
 */
abstract class PluginsfImagePoolImage extends BasesfImagePoolImage
{
  /**
   * Default filename, placeholder used when image missing
   * Requires placeholder.png to be created in image-pool folder
   * 
   * @var string
   */
  const DEFAULT_FILENAME = 'placeholder.jpg';
  
  /**
   * Overriding the values returned for 'tagging' if the parent project has it enabled
   * This is for testing only - 'tagging' needs to be turned off
   * 
   * @author Jo Carter
   * @return mixed
   */
  public function option($name, $value = null)
  {
    $return = parent::option($name, $value);
    
    if ('test' == sfConfig::get('sf_environment'))
    {
      if ('tagging' == $name && is_null($value)) // a get not a set
      {
        return false;
      }
    }
    
    if ($value === null && !is_array($name)) {
       return $return;
    }
  }
  
  /**
   * Set up image to have or not have tagging depending on the project settings
   * Option to be set in the schema.yml - see README
   * 
   * @see PluginsfImagePoolImage::option() - if in test environment
   * 
   * @author Ben Lancaster
   * @requires sfDoctrineActAsTaggablePlugin
   * @throws sfPluginDependencyException
   */
  public function setup()
  {
    parent::setup();
    
    // Ensure set up to cascade crops on delete - not generated by schema for some reason
    $this->hasMany('sfImagePoolCrop as Crops', array(
           'local' => 'id',
           'foreign' => 'sf_image_id',
           'cascade' => array(
           0 => 'delete',
        )));
        
    if (true === $this->option('tagging'))
    {
      if (!class_exists('Taggable'))
      {
        throw new sfPluginDependencyException("sfDoctrineActAsTaggable is required to use image pool tagging");
      }
  
      $taggable0 = new Taggable;
      
      if (!$taggable0 instanceof Doctrine_Template)
      {
        throw new sfPluginDependencyException("Taggable is not a Doctrine_Template");
      }
      
      $this->actAs($taggable0);
    }
  }
  
  /**
   * Image rendered as string returns original filename
   * 
   * @return string
   */
  public function __toString()
  {
    return (string) $this['original_filename'];
  }
  
  /**
   * Call sfImagePoolCache implementation to take care of deleting files
   */
  public function postDelete($event)
  {
    $cache = sfImagePoolCache::getInstance($event->getInvoker());
    $cache->delete();
  }
  
  /**
   * Get the path to the original file
   * @return string
   */
  public function getPathToOriginalFile()
  {
    $cache = sfImagePoolCache::getInstance($this);
    
    return $cache->getPathToOriginalFile();
  }
  
  /**
   * Fetch the image width
   * @return integer 
   */
  public function getWidth()
  {
      if (empty($this->imageInfo))
      {
        $this->imageInfo = getimagesize($this->getPathToOriginalFile());
      }
      
      return $this->imageInfo[0];
  }

  /**
   * Fetch the image height
   * @return integer
   */
  public function getHeight()
  {
    if (empty($this->imageInfo))
    {
      $this->imageInfo = getimagesize($this->getPathToOriginalFile());
    }
    
    return $this->imageInfo[1];
  }

  /**
   * @return mixed Boolean false or array containing count of which models use image.
   */
  public function isUsed()
  {
    $models     = array();
    $model_path = sfConfig::get('sf_lib_dir').DIRECTORY_SEPARATOR.'model';
    $all_models = Doctrine_Core::loadModels($model_path, Doctrine_Core::MODEL_LOADING_CONSERVATIVE);
    
    foreach ($all_models as $class)
    {
      // filter out Base and Table classes
      if (!strstr($class, 'Base') && !strstr($class, 'Table'))
      {
        // check for presence of sfImagePoolable behaviour
        if (Doctrine_Core::getTable($class)->hasTemplate('sfImagePoolable'))
        {
          // Only take into account if actually linked to existing image
          $count = Doctrine_Core::getTable('sfImagePoolLookup')->getModelCount($class, $this->getPrimaryKey());
          
          if (0 < $count)
          {
            $models[$class] = $count;
          }
        }
      }
    }        
    
    if (!count($models))
    {
      return false;
    }
    
    return $models;
  }
  
  /**
   * @return sfImagePoolImage
   */
  static public function getDefaultImage()
  {
    $i              = new sfImagePoolImage();
    $i['filename']  = self::DEFAULT_FILENAME;
    $i['mime_type'] = 'image/png';
    
    return $i;
  }
  
  public function getFilesize()
  {
    if(!$this->hasMappedValue('size_on_disk'))
    {
      $sizes  = array('Bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
      $path   = array( sfConfig::get('sf_web_dir'), sfConfig::get('app_sf_image_pool_folder'), $this['filename'] );
      $path   = implode(DIRECTORY_SEPARATOR, $path);
      $size   = filesize($path);
      $s      = (round($size/pow(1024, ($i = floor(log($size, 1024)))), 2) . ' ' . $sizes[$i]);

      $this->mapValue('size_on_disk',$s);
    }

    return $this['size_on_disk'];
  }
}