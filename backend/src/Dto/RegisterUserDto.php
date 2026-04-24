<?php

namespace App\Dto;

use Symfony\Component\Validator\Constraints as Assert;

final class RegisterUserDto
{
    #[Assert\NotBlank(message: "L'email est obligatoire.")]
    #[Assert\Email(message: "L'email fourni n'est pas valide.")]
    #[Assert\Length(max: 180)]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Le mot de passe est obligatoire.')]
    #[Assert\Length(min: 8, max: 4096, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.')]
    public ?string $password = null;
}
