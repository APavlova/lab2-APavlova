<?php

namespace App\Entity;

use App\Repository\OrderItemRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=OrderItemRepository::class)
 * @ORM\Table(name="order_items")
 */
class OrderItem
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isCanceled;

    /**
     * @ORM\Column(type="uuid")
     */
    private $orderItemUUID;

    /**
     * @ORM\Column(type="uuid")
     */
    private $orderUUID;

    /**
     * @ORM\ManyToOne(targetEntity=Item::class, inversedBy="orderItems")
     * @ORM\JoinColumn(nullable=false)
     */
    private $item;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIsCanceled(): ?bool
    {
        return $this->isCanceled;
    }

    public function setIsCanceled(bool $isCanceled): self
    {
        $this->isCanceled = $isCanceled;

        return $this;
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

    public function getOrderUUID()
    {
        return $this->orderUUID;
    }

    public function setOrderUUID($orderUUID): self
    {
        $this->orderUUID = $orderUUID;

        return $this;
    }

    public function getItem(): ?Item
    {
        return $this->item;
    }

    public function setItem(?Item $item): self
    {
        $this->item = $item;

        return $this;
    }
}
