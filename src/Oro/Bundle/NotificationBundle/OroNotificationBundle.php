<?php

namespace Oro\Bundle\NotificationBundle;

use Oro\Bundle\MessageQueueBundle\DependencyInjection\Compiler\AddTopicMetaPass;
use Oro\Bundle\NotificationBundle\Async\Topics;
use Oro\Bundle\NotificationBundle\DependencyInjection\Compiler\SetRegisteredNotificationEventsCompilerPass;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The NotificationBundle bundle class.
 */
class OroNotificationBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(
            AddTopicMetaPass::create()
                ->add(Topics::SEND_NOTIFICATION_EMAIL, 'Sending email notifications')
        );

        $container->addCompilerPass(
            new SetRegisteredNotificationEventsCompilerPass(),
            PassConfig::TYPE_AFTER_REMOVING,
            -250
        );
    }
}
