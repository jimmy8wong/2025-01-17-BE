<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

class AttendeeDto
{
    public function __construct(
        #[Assert\NotBlank(
            message: 'name required'
        )]
        public string $name = "",

        #[Assert\NotBlank(
            message: 'Email required'
        )]
        #[Assert\Email(
            message: 'The email {{ value }} is not a valid email.'
        )]
        public string $email = "",
    ) {
    }
}