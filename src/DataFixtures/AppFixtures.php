<?php

namespace App\DataFixtures;

use App\Entity\Artist;
use App\Entity\Event;
use App\Entity\Instrument;
use App\Entity\Role;
use App\Entity\StorySection;
use App\Entity\Track;
use App\Entity\User;
use Cocur\Slugify\Slugify;
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
        // Pas de ROLE_MEMBER ici : rôle retiré des attributions possibles
        // (cf. AdminController::toggleMembership, "Rôles simplifiés"), ne
        // reste en base que pour d'éventuels comptes de prod qui l'avaient
        // déjà. Aucune raison d'en fabriquer un jamais utilisé dans des
        // fixtures fraîches.
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

        // "Autre" (champ facultatif User::$otherInstrumentDetail sur le
        // profil pour préciser lequel, cf. AccountType) : volontairement
        // pas ajouté à $instruments ci-dessus, pas de sens de le tirer au
        // sort dans les fixtures aléatoires plus bas.
        $autre = new Instrument();
        $autre->setTitle('Autre');
        $manager->persist($autre);

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
                ->addRole($adminRole)
                ->setInstrument($coranglais)
                ->setCreatedAt($faker->dateTimeBetween($startDate = '-3 months', $endDate = 'now'));

        $manager->persist($admin);

        // compte admin/admin jetable demandé par l'utilisatrice le
        // 2026-08-11 pour tester rapidement en local, à changer avant toute
        // mise en ligne (mot de passe volontairement trivial).
        $quickAdmin = new User();

        $hash = $this->encoder->hashPassword($quickAdmin, 'admin');

        $quickAdmin->setGender('unknown')
                ->setFirstName('Admin')
                ->setLastName('Admin')
                ->setEmail('admin@admin.com')
                ->setHash($hash)
                ->setNickname('admin')
                ->setCity('Vaulx-en-Velin')
                ->setCountry('France')
                ->setBirth($faker->dateTime($max = 'now'))
                ->setValidation(true)
                ->addRole($adminRole)
                ->setInstrument($coranglais)
                ->setCreatedAt($faker->dateTimeBetween($startDate = '-3 months', $endDate = 'now'));

        $manager->persist($quickAdmin);

        // Comptes en attente de validation admin (inscription simplifiée
        // depuis le 2026-08-12 : plus de wish choisi à l'inscription, le
        // rôle est décidé après coup via le toggle "Membre"/"Pas membre",
        // décorrélé de cette validation). Sert à peupler la liste des
        // inscriptions en attente (/admin/valid).
        for ($i = 1; $i <= 20; ++$i) {
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
                    ->setValidation(false)
                    ->setInstrument($faker->randomElement($instruments))
                    ->setCreatedAt($faker->dateTimeBetween($startDate = '-3 months', $endDate = 'now'));

            $manager->persist($user);
        }
        // ajout de simples utilisateurs (déjà validés, ROLE_SIMPLE : sert à
        // peupler la liste desk/lists/simples.html.twig pour tester le
        // toggle "Passer Membre").

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

        // ajout du planning saison 2026-2027 (communiqué par l'utilisatrice
        // le 2026-08-11, remplace les événements créés à la main via
        // /admin/event et perdus lors d'un rechargement de fixtures, cf.
        // ROADMAP.md Phase 6). Répétitions : 09:17-12:34 (horaire donné par
        // l'utilisatrice, clin d'œil à l'exemple déjà utilisé dans
        // ROADMAP.md pour illustrer l'heure de fin facultative). Reste des
        // événements : heure non précisée -> 00:00, que l'affichage
        // (ScheduleController/schedule/index.html.twig) traite déjà comme
        // "pas d'heure" plutôt que comme minuit, donc pas d'endDate non plus.
        $events = [
            ['2026-09-05', 'other', 'Ze Big Journée de Rentrée', 'À préciser', null, null],
            ['2026-09-20', 'concert', 'Pestacle', 'Maison du Canal', null, null],
            ['2026-09-26', 'rehearsal', 'Répétition en West Side', 'ENM de Villeurbanne', null, '09:17-12:34'],
            ['2026-10-02', 'concert', 'Pestacle', 'Fête de quartier des Buers', null, null],
            ['2026-10-17', 'rehearsal', 'Répétition en West Side', 'ENM de Villeurbanne', null, '09:17-12:34'],
            ['2026-11-21', 'rehearsal', 'Répétition en West Side', 'ENM de Villeurbanne', null, '09:17-12:34'],
            ['2026-12-12', 'rehearsal', 'Répétition en West Side', 'ENM de Villeurbanne', null, '09:17-12:34'],
            ['2027-01-23', 'other', 'Résidence d\'Hiver', 'ENM de Villeurbanne', 'Du 23 au 24 janvier.', null],
            ['2027-02-13', 'rehearsal', 'Répétition en West Side', 'ENM de Villeurbanne', null, '09:17-12:34'],
            ['2027-03-13', 'rehearsal', 'Répétition en West Side', 'ENM de Villeurbanne', null, '09:17-12:34'],
            ['2027-04-10', 'rehearsal', 'Répétition en West Side', 'ENM de Villeurbanne', null, '09:17-12:34'],
            ['2027-05-29', 'rehearsal', 'Répétition en West Side', 'ENM de Villeurbanne', null, '09:17-12:34'],
            ['2027-06-19', 'rehearsal', 'Répétition en West Side', 'ENM de Villeurbanne', null, '09:17-12:34'],
        ];

        foreach ($events as [$date, $type, $title, $location, $description, $hours]) {
            [$startTime, $endTime] = $hours ? explode('-', $hours) : [null, null];

            $event = new Event();
            $event->setDate(new \DateTimeImmutable($date.' '.($startTime ?? '00:00')))
                    ->setEndDate($endTime ? new \DateTimeImmutable($date.' '.$endTime) : null)
                    ->setType($type)
                    ->setTitle($title)
                    ->setLocation($location)
                    ->setDescription($description);

            $manager->persist($event);
        }

        // Contenu initial de la page Histoire (StorySection, éditable
        // ensuite par ROLE_ADMIN sur /admin/story), même contenu que la
        // commande ponctuelle app:seed-story-sections utilisée pour la
        // migration d'une base déjà en prod : cf. StorySectionSeedData.
        $slugify = new Slugify();
        foreach (StorySectionSeedData::SECTIONS as $position => [$title, $content]) {
            $section = new StorySection();
            $section->setTitle($title)
                ->setSlug($slugify->slugify($title))
                ->setContent($content)
                ->setPosition($position);
            $manager->persist($section);
        }

        $manager->flush();
    }
}
