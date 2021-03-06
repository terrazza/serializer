<?php
namespace Terrazza\Component\Serializer\Tests\_Examples\Model;

use DateTime;

class SerializerRealLifeProduct {
    private SerializerRealLifeProductUUID $id;
    private SerializerRealLifeProductPrice $price;
    private SerializerRealLifeUserUUID $user;
    private ?string $description=null;
    /**
     * @var SerializerRealLifeProductLabel[]
     */
    private array $vLabels=[];
    /**
     * @var SerializerRealLifeProductLabel[]
     */
    private array $aLabels=[];
    /**
     * @var SerializerRealLifePerson|null
     */
    private ?SerializerRealLifePerson $person=null;

    private ?DateTime $createdAt=null;

    public function __construct(SerializerRealLifeProductUUID $id)
    {
        $this->id       = $id;
        $this->price    = new SerializerRealLifeProductPrice;
        $this->user     = new SerializerRealLifeUserUUID;
    }

    /**
     * @param SerializerRealLifeProductPrice|null $price
     */
    public function setPrice(SerializerRealLifeProductPrice $price=null): void
    {
        $this->price = $price ?? new SerializerRealLifeProductPrice;
    }

    /**
     * @return SerializerRealLifeProductUUID
     */
    public function getId(): SerializerRealLifeProductUUID
    {
        return $this->id;
    }

    /**
     * @return SerializerRealLifeProductPrice
     */
    public function getPrice(): SerializerRealLifeProductPrice
    {
        return $this->price;
    }

    /**
     * @param SerializerRealLifeUserUUID|null $user
     */
    public function setUser(SerializerRealLifeUserUUID $user=null) : void {
        $this->user = $user ?? new SerializerRealLifeUserUUID();
    }

    /**
     * @return SerializerRealLifeUserUUID
     */
    public function getUser(): SerializerRealLifeUserUUID
    {
        return $this->user;
    }

    /**
     * @param string|null $description
     */
    public function setDescription(string $description=null): void
    {
        $this->description = $description;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param SerializerRealLifeProductLabel[]|null $vLabels
     */
    public function setVLabels(?SerializerRealLifeProductLabel ...$vLabels): void {
        //
        // variadic hack to clean null
        //
        if (is_null($vLabels) || (count($vLabels) && is_null($vLabels[0]))) { $vLabels = [];}
        $this->vLabels = $vLabels;
    }

    /**
     * @return SerializerRealLifeProductLabel[]
     */
    public function getVLabels() : array {
        return $this->vLabels;
    }

    /**
     * @param SerializerRealLifeProductLabel[]|null $aLabels
     */
    public function setALabels(array $aLabels=null): void
    {
        $this->aLabels = $aLabels ?? [];
    }

    /**
     * @return SerializerRealLifeProductLabel[]
     */
    public function getALabels(): array
    {
        return $this->aLabels;
    }

    /**
     * @return SerializerRealLifePerson|null
     */
    public function getPerson(): ?SerializerRealLifePerson {
        return $this->person;
    }

    /**
     * @param SerializerRealLifePerson|null $person
     */
    public function setPerson(SerializerRealLifePerson $person=null): void {
        $this->person = $person;
    }

    /**
     * @param DateTime|null $createdAt
     */
    public function setCreatedAt(DateTime $createdAt=null): void {
        $this->createdAt = $createdAt;
    }

    /**
     * @return DateTime|null
     */
    public function getCreatedAt(): ?DateTime
    {
        return $this->createdAt;
    }
}