<?php
declare(strict_types=1);

namespace Basster\ElasticaLoggable\Entity;

use Elastica\ArrayableInterface;
use Gedmo\Loggable\Entity\MappedSuperclass\AbstractLogEntry;

/**
 * Class Activity.
 */
class Activity extends AbstractLogEntry implements ArrayableInterface
{
    private const OLD_INDEX = 0;

    /** @var array */
    private $changeSet;

    public function __construct(string $action, string $username, string $objectClass)
    {
        $this->setAction($action);
        $this->setUsername($username);
        $this->setObjectClass($objectClass);
        $this->setLoggedAt();
    }

    public function toArray(): array
    {
        return [
            'action' => $this->getAction(),
            'logged_at' => $this->getLoggedAt()->format(\DateTime::ATOM),
            'object_id' => $this->getObjectId(),
            'object_class' => $this->getObjectClass(),
            'data' => $this->getData(),
            'username' => $this->getUsername(),
        ];
    }

    public function getData()
    {
        $changed = parent::getData();
        $data = array();

        if ($changed) { // is null during deletions
            foreach ($changed as $property => $value) {
                $data[$property] = [
                    'from' => null,
                    'to' => $value,
                ];

                if (array_key_exists($property, $this->changeSet)) {
                    $data[$property]['from'] = $this->changeSet[$property][self::OLD_INDEX];
                }
            }
        }

        return $data;




    }

    public function setChangeSet($changeSet) {
        $this->changeSet = $changeSet;
    }
}
