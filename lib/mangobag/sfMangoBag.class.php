<?php

class sfMangoBag implements ArrayAccess, Iterator
{
  protected $object;
  protected $collection;
  protected $data = array();

  public function __construct($collection)
  {
    $this->collection = $collection;
  }

  public function fetchFromDoctrineObject(Doctrine_Record $object)
  {
    $this->object = $object;
    $this->data = $this->collection->findOne(array('_doctrine_info' => array('id' => $object->getId(), 'type' => get_class($object))));

    if (!count($this->data))
    {
      $this->hydrate($this->object);
    }
    return $this;
  }

  public function hydrate(Doctrine_Record $object, $data)
  {
    $this->object = $object;
    $this->data = array_merge(array('_doctrine_info' => array('id' => (string) $object->getId(), 'type' => get_class($object))), $data);

    return $this;
  }

  protected function getCollection()
  {
    return $this->collection;
  }

  public function save()
  {
    if (!array_key_exists('_doctrine_info', $this->data))
    {
      throw new Exception('Tried to save a mango bag without doctrine info.');
    }

    $this->getCollection()->save($this->data);
  }

  public function delete()
  {
    $this->getCollection()->drop($this->data);
    $this->__destruct();
  }

  public function getData()
  {
    return $this->data;
  }

  public function getObjectType()
  {
    return $this->data['_doctrine_info']['type'];
  }

  public function offsetGet($key)
  {
    return $this->data[$key];
  }

  public function offsetSet($key, $value)
  {
    $this->data[$key] = $value;
  }

  public function OffsetExists($key)
  {
    return array_key_exists($key, $this->data);
  }

  public function OffsetUnset($key)
  {
    unset($this->data[$key]);
  }

  public function reset()
  {
    reset($this->data);
  }

  public function next()
  {
    return next($this->data);
  }

  public function rewind()
  {
    rewind($this->data);
  }

  public function current()
  {
    return current($this->data);
  }

  public function key()
  {
    return key($this->data);
  }

  public function valid()
  {
    return current($this->data);
  }

  public function getObject()
  {
    return $this->object;
  }
}

