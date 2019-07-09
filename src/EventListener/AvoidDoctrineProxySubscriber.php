<?php

namespace App\EventListener;

use Doctrine\ORM\PersistentCollection;
use Doctrine\Common\Persistence\Proxy;
use Doctrine\ORM\Proxy\Proxy as ORMProxy;
use JMS\Serializer\EventDispatcher\PreSerializeEvent;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;

class AvoidDoctrineProxySubscriber implements EventSubscriberInterface
{

    public function onPreSerialize(PreSerializeEvent $event)
    {
        $object = $event->getObject();
        $type = $event->getType();

        $virtualType = ! class_exists($type['name'], false);

        if ($object instanceof PersistentCollection) {
            if ( ! $virtualType) {
                $event->setType('ArrayCollection');
            }

            return;
        }

        if ( ! $object instanceof Proxy && ! $object instanceof ORMProxy) {
            return;
        }

        if ( ! $virtualType) {
            $event->setType(get_parent_class($object));
        }
    }

    public static function getSubscribedEvents()
    {
        return array(
            array('event' => 'serializer.pre_serialize', 'method' => 'onPreSerialize'),
        );
    }
}