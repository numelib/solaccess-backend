<?php

namespace App\Scheduler;

use App\Scheduler\Message\SendAccessDeviceAlert;
use Symfony\Component\Scheduler\Attribute\AsSchedule;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;
use Symfony\Component\Scheduler\ScheduleProviderInterface;
use Symfony\Contracts\Cache\CacheInterface;

#[AsSchedule('access_device_alerts')]
class AccessDeviceAlertProcider implements ScheduleProviderInterface
{
    public function __construct(
        private CacheInterface $cache,
    ){}

    public function getSchedule(): Schedule
    {
        return (new Schedule())
            ->add(
                RecurringMessage::every('10 seconds', new SendAccessDeviceAlert()),
            )
            ->stateful($this->cache)
            ->processOnlyLastMissedRun(true);
        ;
    }
}
