<?php
declare(strict_types=1);

namespace Basster\ElasticaLoggable\Listener;

use Basster\ElasticaLoggable\Entity\Activity;
use Doctrine\Common\EventArgs;
use Doctrine\Common\Persistence\ObjectManager;
use Elastica\Document;
use Elastica\Type;
use Gedmo\Loggable\LoggableListener;
use Gedmo\Loggable\Mapping\Event\LoggableAdapter;
use Gedmo\Tool\Wrapper\AbstractWrapper;

/**
 * Class ElasticaLoggableListener.
 */
class ElasticaLoggableListener extends LoggableListener
{
    /** @var \Elastica\Type */
    private $type;

    /** @var   */
    private $activities;

    /**
     * ElasticaLoggableListener constructor.
     *
     * @param \Elastica\Type $type
     */
    public function __construct(Type $type)
    {
        parent::__construct();
        $this->type = $type;
        $this->activities = [];
    }

    public function getSubscribedEvents(): array
    {
        $subscribedEvents = parent::getSubscribedEvents();
        $subscribedEvents[] = 'postFlush';

        return $subscribedEvents;
    }

    public function postFlush(): void
    {
        foreach ($this->activities as $activity) {
            $this->persist($activity);
        }
    }

    /** {@inheritdoc} */
    public function getConfiguration(ObjectManager $objectManager, $class): array
    {
        if ($config = parent::getConfiguration($objectManager, $class)) {
            $config = array_merge($config, [
                'logEntryClass' => Activity::class,
            ]);
        }

        return $config;
    }

    public function postPersist(EventArgs $args): void
    {
        $ea = $this->getEventAdapter($args);
        $object = $ea->getObject();
        $om = $ea->getObjectManager();
        $oid = spl_object_hash($object);
        $uow = $om->getUnitOfWork();
        if ($this->pendingLogEntryInserts && array_key_exists($oid, $this->pendingLogEntryInserts)) {
            $wrapped = AbstractWrapper::wrap($object, $om);

            /** @var Activity $logEntry */
            $logEntry = $this->pendingLogEntryInserts[$oid];

            $id = $wrapped->getIdentifier();
            $logEntry->setObjectId($id);
            $ea->setOriginalObjectProperty($uow, spl_object_hash($logEntry), 'objectId', $id);
            unset($this->pendingLogEntryInserts[$oid]);
        }
        if ($this->pendingRelatedObjects && array_key_exists($oid, $this->pendingRelatedObjects)) {
            $wrapped = AbstractWrapper::wrap($object, $om);
            $identifiers = $wrapped->getIdentifier(false);
            foreach ($this->pendingRelatedObjects[$oid] as $props) {
                /** @var Activity $logEntry */
                $logEntry = $props['log'];
                $data[$props['field']] = $identifiers;

                $logEntry->setData($data);

                $ea->setOriginalObjectProperty($uow, spl_object_hash($logEntry), 'data', $data);
            }
            unset($this->pendingRelatedObjects[$oid]);
        }
    }

    protected function createLogEntry($action, $object, LoggableAdapter $ea)
    {
        $om = $ea->getObjectManager();
        $wrapped = AbstractWrapper::wrap($object, $om);
        $meta = $wrapped->getMetadata();

        // Filter embedded documents
        if (isset($meta->isEmbeddedDocument) && $meta->isEmbeddedDocument) {
            return null;
        }

        if ($config = $this->getConfiguration($om, $meta->name)) {
            $logEntry = new Activity;

            $logEntry->setAction($action);
            $logEntry->setUsername($this->username);
            $logEntry->setObjectClass($meta->name);
            $logEntry->setLoggedAt();

            // check for the availability of the primary key
            if (self::ACTION_CREATE === $action && $ea->isPostInsertGenerator($meta)) {
                $this->pendingLogEntryInserts[spl_object_hash($object)] = $logEntry;
            } else {
                $logEntry->setObjectId($wrapped->getIdentifier());
            }
            $newValues = [];
            if (self::ACTION_REMOVE !== $action && isset($config['versioned'])) {
                $logEntry->setChangeSet($ea->getObjectChangeSet($om->getUnitOfWork(), $object));
                $newValues = $this->getObjectChangeSetData($ea, $object, $logEntry);
                $logEntry->setData($newValues);
            }

            if (self::ACTION_UPDATE === $action && 0 === count($newValues)) {
                return null;
            }

            $this->prePersistLogEntry($logEntry, $object);

            $this->store($logEntry);

            return $logEntry;
        }

        return null;
    }

    private function store(Activity $activity)
    {
        $this->activities[] = $activity;
    }

    private function persist(Activity $logEntry)
    {
        $document = new Document();
        $document->setData($logEntry->toArray());

        $this->type->addDocument($document);
        $this->type->getIndex()->refresh();
    }
}
