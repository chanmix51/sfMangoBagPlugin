<?php

class sfMangoBagFinder
{

  protected $collection;
  protected $class_name;

  protected function __construct()
  {
  }

  public static function getFinder($finder = null, $class_name = 'sfMangoBag')
  {
    if (is_null($finder))
    {
      $finder = new self();
    }
    elseif (!$finder instanceOf sfMangoBagFinder)
    {
      throw new InvalidArgumentException(sprintf('Finder must be a child of "sfMangoBagFinder", "%s" given.', get_class($finder)));
    }

    $finder->collection = sfContext::getInstance()
      ->getDatabaseManager()
      ->getDatabase('mongo')
      ->connect()
      ->selectCollection('mangoBag')
      ;
    $finder->class_name = $class_name;

    return $finder;
  }

  public function createMangoBag(Doctrine_Record $object)
  {
    return $this->getMangoBag()->hydrate($object, array('tags' => array()));
  }

  public function findFromDoctrineObject(Doctrine_Record $object)
  {
    return $this->getMangoBag()->fetchFromDoctrineObject($object);
  }

  public function findFromDoctrineObjects($objects)
  {
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
      $mango_bag = $this->getMangoBag()->hydrate($sorted_objects[$result['_doctrine_info']['id']], $result);

      $mango_bags[] = $mango_bag;
    }

    return $mango_bags;
  }

  public function findOne($params)
  {
    $record = $this->collection->findOne($params);
    if (count($record) == 0) return;

    $object = Doctrine::getTable($record['_doctrine_info']['type'])
                ->find($record['_doctrine_info']['id']);
    $mango_bag = $this->getMangoBag()->hydrate($object, $record);

    return $mango_bag;
  }

  public function getMangoBagFrom($id, $type)
  {
    $mango_bag = $this->findOne(array('_doctrine_info.type' => $type, '_doctrine_info.id' => $id));
    if (is_null($mango_bag))
    {
      $object = Doctrine::getTable($type)->find($id);
      if (!$object)
      {
        throw new InvalidArgumentException(sprintf('No such Doctrine object type="%s", id="%s".', $type, $id));
      }
      $mango_bag = $this->getMangoBag()->hydrate($object, array('tags' => array()));
    }

    return $mango_bag;
  }

  public function find($params)
  {
    $records = array();
    $doctrine_map = array();
    $mango_bags = array();

    $results = $this->collection->find($params);

    foreach ($results as $result)
    {
      if (!in_array($result['_doctrine_info']['type'], $doctrine_map))
      {
        $doctrine_map[$result['_doctrine_info']['type']] = array();
      }
      $doctrine_map[$result['_doctrine_info']['type']][$result['_doctrine_info']['id']] = $result;
    }

    foreach($doctrine_map as $type => $records)
    {
      foreach(Doctrine::getTable($type)->createQuery('object')->whereIn('object.id', array_keys($records))->execute() as $result)
      {
        $mango_bag = $this->getMangoBag()->hydrate($result, $records[$result->getId()]);

        $mango_bags[] = $mango_bag;
      }
    }

    return $mango_bags;
  }

  public function getTypes()
  {
    $types = array();

    foreach($this->collection->find(array(), array('_doctrine_info.type')) as $result)
    {
      if (!in_array($result['_doctrine_info']['type'], $types))
      {
        $types[] = $result['_doctrine_info']['type'];
      }
    }

    return $types;
  }

  public function findAll()
  {
    return $this->collection->find();
  }

  public function findByType($type)
  {
    return $this->collection->find(array('_doctrine_info.type' => $type));
  }

  protected function getMangoBag()
  {
    $class_name = $this->class_name;

    return new $class_name($this->collection);
  }
}
