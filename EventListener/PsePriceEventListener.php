<?php


namespace ElasticProduct\EventListener;


use ElasticProduct\Event\PsePriceEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductPriceEventListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            PsePriceEvent::GET_PSE_PRICES => ['computePsePrice', 128],
        ];
    }

    public function computePsePrice(PsePriceEvent $event)
    {

    }
}