<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\Role;
use App\Entity\User;
use App\Entity\Track;
use App\Entity\Artist;
use Cocur\Slugify\Slugify;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class AppFixtures extends Fixture
{
    private $encoder;

    public function __construct(UserPasswordEncoderInterface $encoder){
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager)
    {

        $faker = Factory::create('FR-fr');

        //création des rôles

        $adminRole = new Role();
        $adminRole  ->setTitle('ROLE_ADMIN')
                    ->setDescription('Administrator');
            $manager->persist($adminRole);
        $accountantRole = new Role();
        $accountantRole->setTitle('ROLE_COMPTA')
                    ->setDescription('Accountant');
            $manager->persist($accountantRole);
        $binioufousRole = new Role();
        $binioufousRole->setTitle('ROLE_BINIOUFOUS')
                    ->setDescription('Binioufous');
        $manager->persist($binioufousRole);
        $memberRole = new Role();
        $memberRole->setTitle('ROLE_MEMBER')
                    ->setDescription('Member');
        $manager->persist($memberRole);
        $userRole = new Role();
        $userRole->setTitle('ROLE_USER')
                    ->setDescription('User');
        $manager->persist($userRole);

        // ajout d'utilisateurs

        for($i = 1; $i <= 30; $i++) {
            $user = new User();

            $hash = $this->encoder->encodePassword($user, 'password');

            $genders = ['male', 'female'];
            $gender = $faker->randomElement($genders);

            $wishes = ['binioufous', 'simple', 'admin', 'member'];
            $wish = $faker->randomElement($wishes);

            $user   ->setGender($gender)
                    ->setFirstName($faker->firstName($gender))
                    ->setLastName($faker->lastName($gender))
                    ->setEmail($faker->email)
                    ->setHash($hash)
                    ->setUsername($faker->firstname)
                    ->setCity($faker->city)
                    ->setCountry($faker->country)
                    ->setBirth($faker->dateTime($max='now'))
                    ->setValidation(false)
                    ->setWish($wish);


            $manager-> persist($user);
        }  
        
        //ajout d'artistes
        $artists = [];
        
         for($i = 1; $i <= 5; $i++) {
            $artist = new Artist();
    
            $artist  ->setName($faker->firstname);

            $manager-> persist($artist);            
            $artists[] = $artist;
        }  
        
			//ajout de titres        
        
        for($i = 1; $i <= 10; $i++) {
            $track = new Track();
            
            $artist = $artists[mt_rand(0, count($artists) -1)];
            $minutes = mt_rand(1, 4);
            $seconds = mt_rand(1, 59);
    
            $track  ->setTitle($faker->realText($maxNbChars = 30, $indexSize = 2))
                    ->setArtist($artist)
                    ->setMinutes($minutes)
                    ->setSeconds($seconds);

            $manager-> persist($track);
        }  

        $manager->flush();
    }
}
