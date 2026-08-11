<?php

namespace App\DataFixtures;

use App\Entity\Artist;
use App\Entity\Instrument;
use App\Entity\Role;
use App\Entity\Track;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $encoder;

    public function __construct(UserPasswordHasherInterface $encoder)
    {
        $this->encoder = $encoder;
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('FR-fr');

        // création des rôles

        $adminRole = new Role();
        $adminRole->setTitle('ROLE_ADMIN')
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
        $simpleRole = new Role();
        $simpleRole->setTitle('ROLE_SIMPLE')
                    ->setDescription('Simple');
        $manager->persist($simpleRole);
        $userRole = new Role();
        $userRole->setTitle('ROLE_USER')
                    ->setDescription('User');
        $manager->persist($userRole);

        // création des instruments

        $instruments = [];

        $hautbois = new Instrument();
        $hautbois->setTitle('Hautbois');
        $manager->persist($hautbois);
        $instruments[] = $hautbois;

        $coranglais = new Instrument();
        $coranglais->setTitle('Cor Anglais');
        $manager->persist($coranglais);
        $instruments[] = $coranglais;

        $flute = new Instrument();
        $flute->setTitle('Flûte');
        $manager->persist($flute);
        $instruments[] = $flute;

        $clarinette = new Instrument();
        $clarinette->setTitle('Clarinette');
        $manager->persist($clarinette);
        $instruments[] = $clarinette;

        $tuba = new Instrument();
        $tuba->setTitle('Tuba');
        $manager->persist($tuba);
        $instruments[] = $tuba;

        $euphonium = new Instrument();
        $euphonium->setTitle('Euphonium');
        $manager->persist($euphonium);
        $instruments[] = $euphonium;

        $batterie = new Instrument();
        $batterie->setTitle('Batterie');
        $manager->persist($batterie);
        $instruments[] = $batterie;

        $cor = new Instrument();
        $cor->setTitle('Cor');
        $manager->persist($cor);
        $instruments[] = $cor;

        // ajout du Super Admin

        $admin = new User();

        $hash = $this->encoder->hashPassword($admin, 'password');

        $admin->setGender('male')
                ->setFirstName('Guillaume')
                ->setLastName('Hamet')
                ->setEmail('guibrouille@gmail.com')
                ->setHash($hash)
                ->setNickname('Guiboï')
                ->setCity('Vaulx-en-Velin')
                ->setCountry('France')
                ->setBirth($faker->dateTime($max = 'now'))
                ->setValidation(true)
                ->setWish('Administrator')
                ->addRole($adminRole)
                ->setInstrument($coranglais)
                ->setCreatedAt($faker->dateTimeBetween($startDate = '-3 months', $endDate = 'now'));

        $manager->persist($admin);

        // ajout d'utilisateurs Binioufous et membres

        for ($i = 1; $i <= 20; ++$i) {
            $user = new User();

            $hash = $this->encoder->hashPassword($user, 'password');

            $genders = ['male', 'female'];
            $gender = $faker->randomElement($genders);

            $wishes = ['Binioufous', 'Member'];
            $wish = $faker->randomElement($wishes);

            $user->setGender($gender)
                    ->setFirstName($faker->firstName($gender))
                    ->setLastName($faker->lastName($gender))
                    ->setEmail($faker->email)
                    ->setHash($hash)
                    ->setNickname($faker->firstname)
                    ->setCity($faker->city)
                    ->setCountry($faker->country)
                    ->setBirth($faker->dateTime($max = 'now'))
                    ->setValidation(false)
                    ->setWish($wish)
                    ->setInstrument($faker->randomElement($instruments))
                    ->setCreatedAt($faker->dateTimeBetween($startDate = '-3 months', $endDate = 'now'));

            $manager->persist($user);
        }
        // ajout de simples utilisateurs

        for ($i = 1; $i <= 10; ++$i) {
            $user = new User();

            $hash = $this->encoder->hashPassword($user, 'password');

            $genders = ['male', 'female'];
            $gender = $faker->randomElement($genders);

            $user->setGender($gender)
                    ->setFirstName($faker->firstName($gender))
                    ->setLastName($faker->lastName($gender))
                    ->setEmail($faker->email)
                    ->setHash($hash)
                    ->setNickname($faker->firstname)
                    ->setCity($faker->city)
                    ->setCountry($faker->country)
                    ->setBirth($faker->dateTime($max = 'now'))
                    ->setValidation(true)
                    ->setWish('Simple')
                    ->addRole($simpleRole)
                    ->setInstrument($faker->randomElement($instruments))
                    ->setCreatedAt($faker->dateTimeBetween($startDate = '-3 months', $endDate = 'now'));

            $manager->persist($user);
        }

        // ajout d'artistes
        $artists = [];

        for ($i = 1; $i <= 5; ++$i) {
            $artist = new Artist();

            $artist->setName($faker->firstname);

            $manager->persist($artist);
            $artists[] = $artist;
        }

        // ajout de titres
        // Fichiers réels déjà présents dans public/uploads/music/ (pas de nouveau
        // binaire ajouté), sinon trackFilename restait vide et aucun morceau
        // de démo n'était jouable (cf. ROADMAP.md).
        $demoFiles = ['Oi-Tate-A2.mp3', 'test.mpga'];

        for ($i = 1; $i <= 10; ++$i) {
            $track = new Track();

            $artist = $artists[mt_rand(0, count($artists) - 1)];
            $minutes = mt_rand(1, 4);
            $seconds = mt_rand(1, 59);

            $track->setTitle($faker->realText($maxNbChars = 30, $indexSize = 2))
                    ->setArtist($artist)
                    ->setMinutes($minutes)
                    ->setSeconds($seconds)
                    ->setTrackFilename($demoFiles[$i % count($demoFiles)]);

            $manager->persist($track);
        }

        $manager->flush();
    }
}
