<?php

namespace App\Entity;

use App\Config\CommandStatus;
use App\Repository\CommandRepository;
use Doctrine\ORM\Mapping as ORM;
use LogicException;

#[ORM\Entity(repositoryClass: CommandRepository::class)]
class Command
{
    public const int TIMEOUT_INTERVAL_IN_SECONDS = 15;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $issuedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\ManyToOne(inversedBy: "commands")]
    #[ORM\JoinColumn(nullable: false)]
    private ?AccessDevice $accessDevice = null;

    public function getStatus(): CommandStatus
    {
        if ($this->getTimeoutAt() !== null) {
            return CommandStatus::TIMEOUT;
        }

        if ($this->issuedAt !== null && $this->processedAt === null) {
            return CommandStatus::IN_PROGRESS;
        }

        if ($this->issuedAt !== null && $this->processedAt !== null) {
            return CommandStatus::PROCESSED;
        }

        // Should not be possible because a command must be issued when it's created
        throw new LogicException(
            "Command should not have the \"issuedAt\" property set to null.",
        );
    }

    public function getTimeoutAt(): ?\DateTimeImmutable
    {
        $issuedAt = $this->issuedAt;
        $processedAt = $this->processedAt;
        $now = new \DateTimeImmutable();

        if ($issuedAt === null) {
            return null;
        }

        if ($processedAt === null) {
            $hasTimeout =
                $now->getTimestamp() - $issuedAt->getTimestamp() >
                self::TIMEOUT_INTERVAL_IN_SECONDS;

            return $hasTimeout
                ? \DateTimeImmutable::createFromTimestamp(
                    $issuedAt->getTimestamp() +
                        self::TIMEOUT_INTERVAL_IN_SECONDS,
                )
                : null;
        }

        $hasTimeout =
            $processedAt->getTimestamp() - $issuedAt->getTimestamp() >
            self::TIMEOUT_INTERVAL_IN_SECONDS;

        return $hasTimeout
            ? \DateTimeImmutable::createFromTimestamp(
                $issuedAt->getTimestamp() + self::TIMEOUT_INTERVAL_IN_SECONDS,
            )
            : null;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIssuedAt(): ?\DateTimeImmutable
    {
        return $this->issuedAt;
    }

    public function setIssuedAt(\DateTimeImmutable $issuedAt): static
    {
        $this->issuedAt = $issuedAt;

        return $this;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }

    public function setProcessedAt(\DateTimeImmutable $processedAt): static
    {
        if ($this->getStatus() === CommandStatus::TIMEOUT) {
            $this->processedAt = null;
            return $this;
        }

        $this->processedAt = $processedAt;

        return $this;
    }

    public function getAccessDevice(): ?AccessDevice
    {
        return $this->accessDevice;
    }

    public function setAccessDevice(?AccessDevice $accessDevice): static
    {
        $this->accessDevice = $accessDevice;

        return $this;
    }
}
