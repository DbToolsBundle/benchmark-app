<?php

namespace App\Entity\MariaDB;

use App\Repository\MariaDB\CustomerRepository;
use Doctrine\ORM\Mapping as ORM;
use MakinaCorpus\DbToolsBundle\Attribute\Anonymize;

#[ORM\Entity(repositoryClass: CustomerRepository::class)]
#[Anonymize(type: 'address', options: [
    'street_address' => 'street',
    'postal_code' => 'zip_code',
    'locality' => 'city',
    'country' => 'country',
])]
class Customer
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Anonymize(type: 'email')]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Anonymize(type: 'password')]
    private ?string $password = null;

    #[ORM\Column(length: 255)]
    #[Anonymize(type: 'lastname')]
    private ?string $lastname = null;

    #[ORM\Column(length: 255)]
    #[Anonymize(type: 'firstname')]
    private ?string $firstname = null;

    #[ORM\Column(length: 255)]
    #[Anonymize(type: 'string', options: ['sample' => ['none', 'bad', 'good', 'expert']])]
    private ?string $level = null;

    #[ORM\Column]
    #[Anonymize(type: 'integer', options: ['min' => 10, 'max' => 99])]
    private ?int $age = null;

    #[ORM\Column(length: 255)]
    private ?string $street = null;

    #[ORM\Column(length: 255)]
    private ?string $zipCode = null;

    #[ORM\Column(length: 255)]
    private ?string $city = null;

    #[ORM\Column(length: 255)]
    private ?string $country = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLevel(): ?string
    {
        return $this->level;
    }

    public function setLevel(string $level): static
    {
        $this->level = $level;

        return $this;
    }

    public function getAge(): ?int
    {
        return $this->age;
    }

    public function setAge(int $age): static
    {
        $this->age = $age;

        return $this;
    }

    public function getStreet(): ?string
    {
        return $this->street;
    }

    public function setStreet(string $street): static
    {
        $this->street = $street;

        return $this;
    }

    public function getZipCode(): ?string
    {
        return $this->zipCode;
    }

    public function setZipCode(string $zipCode): static
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): static
    {
        $this->country = $country;

        return $this;
    }
}
