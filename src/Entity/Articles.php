<?php

namespace App\Entity;

use App\Repository\ArticlesRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ArticlesRepository::class)]
class Articles
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $title;

    #[ORM\Column(type: 'text')]
    private $text;

    #[ORM\Column(type: 'datetime')]
    private $date_create;

    #[ORM\Column(type: 'datetime')]
    private $date_change;

    #[ORM\ManyToOne(targetEntity: Users::class, inversedBy: 'articles')]
    #[ORM\JoinColumn(nullable: false)]
    private $fk_users;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getDateCreate(): ?\DateTimeInterface
    {
        return $this->date_create;
    }

    public function setDateCreate(\DateTimeInterface $date_create): self
    {
        $this->date_create = $date_create;

        return $this;
    }

    public function getDateChange(): ?\DateTimeInterface
    {
        return $this->date_change;
    }

    public function setDateChange(\DateTimeInterface $date_change): self
    {
        $this->date_change = $date_change;

        return $this;
    }

    public function getFkUsers(): ?Users
    {
        return $this->fk_users;
    }

    public function setFkUsers(?Users $fk_users): self
    {
        $this->fk_users = $fk_users;

        return $this;
    }
}
