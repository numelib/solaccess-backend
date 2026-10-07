<?php

namespace App\Scheduler\Handler;

use App\Entity\AccessDevice;
use App\Entity\AccessLog;
use App\Entity\User;
use App\Repository\AccessDeviceRepository;
use App\Repository\AccessLogRepository;
use App\Scheduler\Message\SendAccessDeviceAlert;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Mime\RawMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsMessageHandler]
class SendAccessDeviceAlertHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        private readonly MailerInterface $mailer,
        private readonly string $adminEmail,
    ){}

    public function __invoke(SendAccessDeviceAlert $message)
    {
        $since = \DateTime::createFromTimestamp(time() - 24 * 60 * 60);
        /** @var AccessDevice[] */
        $accessDevices = $this->entityManager->getRepository(AccessDevice::class)->findByMissingSince($since);

        /** @var \WeakMap<User, array{'name': string, 'building': string, 'lastSeenAt': \DateTime} */
        $devicesByOwner = new \WeakMap();
        foreach($accessDevices as $accessDevice) {
            /** @var Accesslog[] */
            $logs = $this->entityManager->getRepository(AccessLog::class)->findLatestByDevice($accessDevice, 1);
            $owner = $accessDevice->getUser();
            if(count($logs) !== 1 || $owner === null) {
                continue;
            }

            if(!isset($devicesByOwner[$owner]['devices'])) {
                $devicesByOwner[$owner] = [];
            }

            $devicesByOwner[$owner][] = [
                'name' => $accessDevice->__toString(),
                'building' => $accessDevice->getBuilding()?->__toString() ?? '',
                'lastSeenAt' => $logs[0]->getOccurredAt(),
            ];
        }

        foreach($devicesByOwner as $owner => $devices) {
            $email = (new TemplatedEmail())
                ->from($this->adminEmail)
                ->to($owner->getEmail())
                ->subject($this->translator->trans('access_device_alert_email_subject'))
                ->htmlTemplate('emails/access_device_alert.html.twig')
                ->context(['devices' => $devices, 'since' => $since]);

            $this->mailer->send($email);
        }
    }
}
