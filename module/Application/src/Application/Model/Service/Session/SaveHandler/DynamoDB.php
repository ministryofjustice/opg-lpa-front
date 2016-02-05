<?php
namespace Application\Model\Service\Session\SaveHandler;

use Aws\DynamoDb\SessionHandler as DynamoDbSessionHandler;

use Zend\Session\SaveHandler\SaveHandlerInterface;

/**
 * Extends Amazon's DynamoDb Session Handler (mostly) so we can apply Zend's Save Handler Interface.
 *
 * Class DynamoDB
 * @package Application\Model\Service\Session\SaveHandler
 */
class DynamoDB extends DynamoDbSessionHandler implements SaveHandlerInterface {


} // class
