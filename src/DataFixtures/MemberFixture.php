<?php

namespace App\DataFixtures;

use App\Entity\Member;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class MemberFixture extends Fixture
{
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager)
    {
        $member = new Member();
        $member->setEmail('julie.pascal@gmail.com');
        $member->setFirstname('Julie');
        $member->setLastname('Julie');
        $member->setIsActive(false);
        $member->setReceiveEmailNewComment(false);
        $member->setPassword($this->passwordEncoder->encodePassword(
            $member,
            'julie'
        ));

        $manager->persist($member);
        $manager->flush();
    }
}
