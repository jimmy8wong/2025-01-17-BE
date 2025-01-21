<?php

namespace App\Model;

use Symfony\Component\Validator\Constraints as Assert;

class EventDto
{
    public function __construct(
        #[Assert\NotBlank(
            message: 'name required'
        )]
        public string $name = "",

        public string $description = "",

        #[Assert\NotBlank(
            message: 'startDate required'
        )]
        #[Assert\DateTime(
            message: 'startDate format should be YYYY-MM-DD HH:ii:ss'
        )]
        public string $startDate = "",

        #[Assert\NotBlank(
            message: 'endDate required'
        )]
        #[Assert\DateTime(
            message: 'endDate format should be YYYY-MM-DD HH:ii:ss'
        )]
        public string $endDate = "",

        #[Assert\NotBlank(
            message: 'location required'
        )]
        public string $location = "",

        #[Assert\GreaterThan(
            value: 0,
            message: 'capacity required and should be greater than 0'
        )]
        public int $capacity = 0,
    ) {
    }
}