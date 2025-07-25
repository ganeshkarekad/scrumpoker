<?php

namespace App\DataFixtures;

use App\Entity\Vote;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class VoteFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Standard Scrum Poker voting values
        $voteValues = [
            '0',    // No effort
            '1',    // Minimal effort
            '2',    // Small effort
            '3',    // Small-medium effort
            '5',    // Medium effort
            '8',    // Large effort
            '13',   // Very large effort
            '21',   // Extremely large effort
            '?',    // Unknown/Need more info
            '∞',    // Infinite/Too complex
            'BRB'   // Break/Away from keyboard
        ];

        foreach ($voteValues as $value) {
            $vote = new Vote();
            $vote->setLabel($value);
            $manager->persist($vote);
        }

        $manager->flush();
    }
}
