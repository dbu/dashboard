<?php

namespace Dbu\GhCollectorBundle\Github\Plugin;

use Phoebe\Event\Event;
use Phoebe\Plugin\PluginInterface;

class HackingdayPlugin implements PluginInterface
{
    const TRIGGER_PATTERN = '/\b(issue)\b/';

    public function __construct()
    {

    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return array(
            'irc.received.PRIVMSG' => array('onMessage', 0)
        );
    }

    /**
     * @param Event $event
     */
    public function onMessage(Event $event)
    {
        $message = $event->getMessage();
        $matches = array();


        $responseMessage = '10 top github issues with links:';
        $responseMessage .= ;

        if ($message->isInChannel() && $message->matchText(self::TRIGGER_PATTERN, $matches)) {
            $event
                ->getWriteStream()
                ->ircPrivmsg($message->getSource(), sprintf('%s: %s', $message['nick'], $responseMessage))
            ;
        }
    }
}
