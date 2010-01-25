<?php

class sfMangoBagDatabase extends sfDatabase
{
  protected $database;
  protected $is_bound = FALSE;

  public function initialize($parameters = array())
  {
    if (!class_exists('Mongo'))
    {
      throw new sfDatabaseException(sprintf('No such PHP extension "%s".', 'mongoDB'));
    }

    if (array_key_exists('dsn', $parameters))
    {

      preg_match('#mongodb:(?://(?:(\w+)(:\w+)?@)?(\w+)(:\w+)?)?/(\w+)#', $parameters['dsn'], $matchs);

      $parameters['dsn'] = null;
      $parameters['username'] = $matchs[1] === "" ? null : $matchs[1];
      $parameters['password'] = $matchs[2] === "" ? null : substr($matchs[2], 1);
      $parameters['hostname'] = $matchs[3] === "" ? 'localhost' : $matchs[3] ;
      $parameters['port'] = $matchs[4] === "" ? "27017" : substr($matchs[4], 1);
      $parameters['database'] = $matchs[5];
    }
    if (!array_key_exists('database', $parameters))
    {
      throw new sfDatabaseException('No database name given in the dsn class "sfMangoBagDatabase"');
    }

    parent::initialize($parameters);
  }

  /**
   * connect 
   * 
   * @access public
   * @return mongoDatabase object
   */
  public function connect()
  {
    if ($this->isBound()) return $this->database;

    $str = $this->parameterHolder->get('hostname').":".$this->parameterHolder->get('port');
    $this->connection = new Mongo($str, false);

    try
    {
      if ($this->parameterHolder->has('persistent') and $this->parameterHolder->get('persistent'))
      {
        $this->connection->persistConnect();
      }
      else
      {
        $this->connection->connect();
      }
    }
    catch(MongoConnectionException $e)
    {
      throw new sfDatabaseException(sprintf('Connection error, the drivers said "%s".', $e->getMessage()));
    }
    try
    {
      $this->database = $this->connection->selectDB($this->parameterHolder->get('database'));
    }
    catch(InvalidArgumentException $e)
    {
      throw new sfDatabaseException(sprintf('Could not bind to database "%s", the driver said "%s"', $this->parameterHolder->get('database'), $e->getMessage()));
    }

    $this->setBound();
    return $this->database;
  }


  public function shutdown()
  {
    try
    {
      $this->connection->close();
      $this->database = NULL;
      $this->setBound(FALSE);
    }
    catch (MongoConnectionException $e)
    {
      throw new sfDatabaseException(sprintf('Error while disconnecting from mongodb, driver said "%s"'), $e->getMessage());
    }
  }

  public function isBound()
  {
    return $this->is_bound;
  }

  public function setBound($state = TRUE)
  {
    $this->is_bound = $state;
  }

  public function getDatabase()
  {
    return $this->database;
  }
}
