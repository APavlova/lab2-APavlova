<?php

namespace App\Entity;

use App\Repository\OrderRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OrderRepository::class)
 * @ORM\Table(name="orders")
 */
class Order
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="uuid")
     */
    private $orderItemUUID;

    /**
     * @ORM\Column(type="datetime")
     */
    private $orderDate;

    /**
     * @ORM\Column(type="uuid")
     */
    private $orderUUID;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $status;

    const STATUS_PAID = 'PAID';

    /**
     * @ORM\Column(type="uuid")
     */
    private $userUUID;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrderItemUUID()
    {
        return $this->orderItemUUID;
    }

    public function setOrderItemUUID($orderItemUUID): self
    {
        $this->orderItemUUID = $orderItemUUID;

        return $this;
    }

    public function getOrderDate(): ?\DateTimeInterface
    {
        return $this->orderDate;
    }

    public function setOrderDate(\DateTimeInterface $orderDate): self
    {
        $this->orderDate = $orderDate;

        return $this;
    }

    public function getOrderUUID()
    {
        return $this->orderUUID;
    }

    public function setOrderUUID($orderUUID): self
    {
        $this->orderUUID = $orderUUID;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getUserUUID()
    {
        return $this->userUUID;
    }

    public function setUserUUID($userUUID): self
    {
        $this->userUUID = $userUUID;

        return $this;
    }
}
