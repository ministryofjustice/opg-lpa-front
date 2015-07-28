<?php
namespace Application\Adapter;

use Zend\Cache\Storage\StorageInterface;
use Aws\DynamoDb\DynamoDbClient;
use Symfony\Component\Intl\Exception\NotImplementedException;

/**
 * An adapter to use DynamoDB as a simple key/value store
 */
class DynamoDbKeyValueStore implements StorageInterface
{
    
    /**
     * The AWS client
     * 
     * @var Aws\DynamoDb\DynamoDbClient
     */
    private $client;

    /**
     * The name of the table holding the key/value store
     * 
     * @var string
     */
    private $tableName;
    
    /**
     * Constructor
     * 
     * @param array $config
     * 
     * [
     *      'settings' => [
     *          'table_name' => 'my-table',
     *      ],
     *      'client' => [
     *          [
     *              'version' => '2012-08-10',
     *              'region' => 'eu-west-1',
     *              'credentials' => [
     *                  'key'    => '',
     *                  'secret' => '',
     *              ],
     *          ],
     *      ],
     *  ]
     */
    public function __construct(array $config)
    {
        $this->client = new DynamoDbClient($config['client']);
        
        $this->tableName = $config['settings']['table_name'];
        
    }
    
    /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::setItem()
     */
    public function setItem($key, $value)
    {
        $result = $this->client->putItem(array(
            'TableName' => $this->tableName,
            'Item' => array(
                'id'      => array('S' => $key),
                'value'   => array('B' => $value),
            )
        ));
    
    }
    
    /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::getItem()
     */
    public function getItem($key, & $success = null, & $casToken = null)
    {
        $result = $this->client->getItem(array(
            'TableName' => $this->tableName,
            'Key' => array(
                'id'      => array('S' => $key),
            )
        ));
        
        return $result['Item']['value']['B'];
    
    }
    
     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::addItem()
     */
    public function addItem($key, $value)
    {
        throw new NotImplementedException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::addItems()
     */
    public function addItems(array $keyValuePairs)
    {
        throw new NotImplementedException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::checkAndSetItem()
     */
    public function checkAndSetItem($token, $key, $value)
    {
        throw new NotImplementedException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::decrementItem()
     */
    public function decrementItem($key, $value)
    {
        throw new NotImplementedException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::decrementItems()
     */
    public function decrementItems(array $keyValuePairs)
    {
        throw new NotImplementedException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::getCapabilities()
     */
    public function getCapabilities()
    {
        throw new NotImplementedException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::getItems()
     */
    public function getItems(array $keys)
    {
        throw new NotImplementedException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::getMetadata()
     */
    public function getMetadata($key)
    {
        throw new NotImplementedException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::getMetadatas()
     */
    public function getMetadatas(array $keys)
    {
        throw new NotImplementedException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::getOptions()
     */
    public function getOptions()
    {
        throw new NotImplementedException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::hasItem()
     */
    public function hasItem($key)
    {
        throw new NotImplementedException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::hasItems()
     */
    public function hasItems(array $keys)
    {
        throw new NotImplementedException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::incrementItem()
     */
    public function incrementItem($key, $value)
    {
        throw new NotImplementedException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::incrementItems()
     */
    public function incrementItems(array $keyValuePairs)
    {
        throw new NotImplementedException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::removeItem()
     */
    public function removeItem($key)
    {
        throw new NotImplementedException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::removeItems()
     */
    public function removeItems(array $keys)
    {
        throw new NotImplementedException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::replaceItem()
     */
    public function replaceItem($key, $value)
    {
        throw new NotImplementedException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::replaceItems()
     */
    public function replaceItems(array $keyValuePairs)
    {
        throw new NotImplementedException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::setItems()
     */
    public function setItems(array $keyValuePairs)
    {
        throw new NotImplementedException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::setOptions()
     */
    public function setOptions($options)
    {
        throw new NotImplementedException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::touchItem()
     */
    public function touchItem($key)
    {
        throw new NotImplementedException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

     /* (non-PHPdoc)
     * @see \Zend\Cache\Storage\StorageInterface::touchItems()
     */
    public function touchItems(array $keys)
    {
        throw new NotImplementedException('The ' . __FUNCTION__ . ' method has not been implemented.');
    }

}
