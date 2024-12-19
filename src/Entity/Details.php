<?php

namespace App\Entity;

use App\Repository\DetailsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DetailsRepository::class)]
class Details
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?float $prixVente = null;

    #[ORM\Column]
    private ?int $qteVendu = null;

    #[ORM\ManyToOne(inversedBy: 'details')]
    private ?Article $artcile = null;

    #[ORM\ManyToOne(inversedBy: 'details')]
    private ?Dette $dette = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPrixVente(): ?float
    {
        return $this->prixVente;
    }

    public function setPrixVente(float $prixVente): static
    {
        $this->prixVente = $prixVente;

        return $this;
    }

    public function getQteVendu(): ?int
    {
        return $this->qteVendu;
    }

    public function setQteVendu(int $qteVendu): static
    {
        $this->qteVendu = $qteVendu;

        return $this;
    }

    public function getArtcile(): ?Article
    {
        return $this->artcile;
    }

    public function setArtcile(?Article $artcile): static
    {
        $this->artcile = $artcile;

        return $this;
    }

    public function getDette(): ?Dette
    {
        return $this->dette;
    }

    public function setDette(?Dette $dette): static
    {
        $this->dette = $dette;

        return $this;
    }
}
