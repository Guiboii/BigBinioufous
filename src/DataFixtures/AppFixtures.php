<?php

namespace App\DataFixtures;

use Faker\Factory;
use App\Entity\User;
use App\Entity\Artist;
use App\Entity\Track;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
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
    
        // ajout d'utilisateurs

        for($i = 1; $i <= 10; $i++) {
            $user = new User();

            $hash = $this->encoder->encodePassword($user, 'password');
    
            $user   ->setFirstName($faker->firstname)
                    ->setLastName($faker->lastname)
                    ->setEmail($faker->email)
                    ->setHash($hash);

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
    
            $track  ->setTitle($faker->realText($maxNbChars = 30, $indexSize = 2))
                    ->setDuration($faker->randomFloat)
                    ->setArtist($artist);

            $manager-> persist($track);
        }  

        $manager->flush();
    }
}
