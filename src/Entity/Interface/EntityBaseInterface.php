<?php

namespace App\Entity\Interface;

use DateTimeImmutable;

interface EntityBaseInterface
{
    public function updatedTimestamps(): void;

    public function getCreatedAt(): ?DateTimeImmutable;

    public function setCreatedAt(?DateTimeImmutable $createdAt);

    public function getUpdatedAt(): ?DateTimeImmutable;

    public function setUpdatedAt(?DateTimeImmutable $updatedAt);

    public function getDeletedAt(): ?DateTimeImmutable;

    public function setDeletedAt(?DateTimeImmutable $deletedAt);

    public  function getCreatedBy(): ?int;

    public  function setCreatedBy(?int $createdBy);

    public  function getUpdatedBy(): ?int;

    public  function setUpdatedBy(?int $updatedBy);

    public  function getDeletedBy(): ?int;

    public  function setDeletedBy(?int $deletedBy);

    public  function setEtatdelete(?bool $detatdelete);

    public  function getEtatdelete(): ?bool;

}
