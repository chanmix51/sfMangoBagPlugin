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
  }

  public function hydrate(Doctrine_Record $object, $data)
  {
    $this->object = $object;
    $this->data = $data;
  }

  protected function getCollection()
  {
    return $this->collection;
  }

  public function save()
  {
    if (!array_key_exists('_doctrine_info', $this->data))
    {
      $this->data['_doctrine_info'] = array(
        'id' => $this->object->getId(),
        'type' => get_class($this->object)
        );
    }

    $this->getCollection()->save($this->data);
  }

  public function getData()
  {
    return $this->data;
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

