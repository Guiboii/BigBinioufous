<?php

namespace App\DataFixtures;

use App\Entity\Artist;
use App\Entity\Event;
use App\Entity\Instrument;
use App\Entity\Note;
use App\Entity\Role;
use App\Entity\SetlistItem;
use App\Entity\StorySection;
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
        // Pas de ROLE_MEMBER, ROLE_SIMPLE, ni ROLE_USER ici : ROLE_MEMBER
        // retiré des attributions possibles puis supprimé entièrement le
        // 2026-08-12 (cf. AdminController::toggleMembership, "Rôles
        // simplifiés"/"Rôles legacy et implicite nettoyés"), ROLE_SIMPLE
        // fusionné avec ROLE_USER le même jour (cf. ROADMAP.md "Rôles
        // fusionnés"). ROLE_USER est implicite (User::getRoles() l'ajoute
        // en dur, jamais stocké en base) : une ligne Role "ROLE_USER"
        // existait ici avant, jamais assignée à personne (aucun
        // ->addRole() dessus), uniquement pour faire apparaître une
        // pastille "User" cosmétique sur les fiches admin. Retirée (cf.
        // migration Version20260812180000 qui nettoie aussi cette ligne
        // sur les bases existantes) : aucune raison de recréer une donnée
        // qui ne sert à rien fonctionnellement.

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
        // ajout de simples utilisateurs (validés, sans rôle métier : "simple"
        // n'est plus un rôle à assigner, cf. ROADMAP.md "Rôles fusionnés")
        // pour peupler la liste desk/lists/simples.html.twig et tester le
        // toggle "Passer Membre".

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

        // ajout de morceaux à la setlist (sans fichier audio de démo : pas
        // de vrai binaire à fournir ici, cf. SetlistItem::$folder nullable,
        // "juste un titre + éventuellement un lien YouTube" est un cas
        // normal, pas un état incomplet).
        for ($i = 1; $i <= 10; ++$i) {
            $item = new SetlistItem();

            $artist = $artists[mt_rand(0, count($artists) - 1)];

            $item->setTitle($faker->realText($maxNbChars = 30, $indexSize = 2))
                    ->setArtist($artist)
                    ->setPosition($i - 1);

            $manager->persist($item);
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

        // Notes de démo pour l'outil de prise de note du bureau/conseil
        // (/desk/notes), rattachées au compte admin/admin jetable pour
        // tester facilement les deux cas (privée / partagée) en local.
        $privateNote = new Note();
        $privateNote->setTitle('Idées pour la prochaine AG')
            ->setContent("- Point sur les adhésions\n- Budget instruments\n- Date à caler avec la salle")
            ->setAuthor($quickAdmin)
            ->setShared(false);
        $manager->persist($privateNote);

        $sharedNote = new Note();
        $sharedNote->setTitle('Compte-rendu réunion du bureau')
            ->setContent("## Présents\nQuickAdmin, Guiboï\n\n## Décisions\n- Validation du budget résidence d'hiver\n- Relance des devis en attente")
            ->setAuthor($quickAdmin)
            ->setShared(true);
        $manager->persist($sharedNote);

        $manager->flush();
    }
}
