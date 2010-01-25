<?php

class sfMangoFinder
{

  protected static $collection;

  protected static function initCollection()
  {
    if (is_null(self::$collection))
    {
    self::$collection = sfContext::getInstance()
      ->getDatabaseManager()
      ->getDatabase('mongo')
      ->connect()
      ->selectCollection('mangoBag')
      ;
    }
  }

  public static function findFromDoctrineObject(Doctrine_Record $object)
  {
    self::initCollection();
    $mango_bag = new sfMangoBag(self::$collection);
    $mango_bag->fetchFromDoctrineObject($object);

    return $mango_bag;
  }

  public static function findFromDoctrineObjects($objects)
  {
    self::initCollection();
    $in = array();
    $sorted_objects = array();
    $mango_bags = array();

    foreach($objects as $object)
    {
      $in[] = array('id' => $object->getId(), 'type' =>get_class($object));
      $sorted_objects[$object->getId()] = $object;
    }

    $mg_iterator = self::$collection->find(array('_doctrine_info' => array('$in' => $in)));

    foreach($mg_iterator as $result)
    {
      $mango_bag = new sfMangoBag(self::$collection);
      $mango_bag->hydrate($sorted_objects[$result['_doctrine_info']['id']], $result);

      $mango_bags[] = $mango_bag;
    }

    return $mango_bags;
  }

  public static function findOne($params)
  {
    self::initCollection();
    $record = self::$collection->findOne($params);
    $object = Doctrine::getTable($record['_doctrine_info']['type'])
                ->find($record['_doctrine_info']['id']);
    $mango_bag = new sfMangoBag(self::$collection);
    $mango_bag->hydrate($object, $record);

    return $mango_bag;
  }

  public static function find($params)
  {
    self::initCollection();
    $records = array();
    $doctrine_map = array();
    $mango_bags = array();

    foreach(self::$collection->find($params) as $record)
    {
      if (array_key_exists($record['_doctrine_info']['type'], $doctrine_map))
      {
        $doctrine_map[$record['_doctrine_info']['type']][$record['_doctrine_info']['id']] = $record;
      }
      else
      {
        $doctrine_map[$record['_doctrine_info']['type']] = array($record['_doctrine_info']['id'] => $record);
      }
    }

    foreach($doctrine_map as $type => $records)
    {
      foreach(Doctrine::getTable($type)->createQuery('object')->where('id IN ?', array_keys($records))->execute() as $result)
      {
        $mango_bag = new sfMangoBag(self::$collection);
        $mango_bag->hydrate($result, $records[$result->getId()]);

        $mango_bags[] = $mango_bag;
      }
    }

    return $mango_bags;
  }
}
