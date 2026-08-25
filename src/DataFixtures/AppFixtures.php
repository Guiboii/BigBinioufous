<?php

namespace App\DataFixtures;

use App\Entity\Artist;
use App\Entity\CarpoolOffer;
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

// Demo data set: roles, instruments, users (admin, pending, validated), setlist, schedule, story sections, notes, carpool offer.
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

        // Roles

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
        // No ROLE_MEMBER/ROLE_SIMPLE/ROLE_USER here: none of them are real assignable roles anymore, no reason to recreate data with no functional purpose.

        // Instruments

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

        // "Other" is deliberately not added to $instruments above: it wouldn't make sense to pick it randomly below.
        $autre = new Instrument();
        $autre->setTitle('Autre');
        $manager->persist($autre);

        // Super admin account

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

        // Disposable admin/admin account for quick local testing; change this trivial password before any real deployment.
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

        // Accounts pending admin validation, to populate the /admin/valid list.
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
        // Plain validated users with no business role, to populate the "simple" list and test the "make member" toggle.
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

        // Artists
        $artists = [];

        for ($i = 1; $i <= 5; ++$i) {
            $artist = new Artist();

            $artist->setName($faker->firstname);

            $manager->persist($artist);
            $artists[] = $artist;
        }

        // Setlist songs, with no demo audio file: a title alone (plus maybe a YouTube link) is a normal state for a SetlistItem, not an incomplete one.
        for ($i = 1; $i <= 10; ++$i) {
            $item = new SetlistItem();

            $artist = $artists[mt_rand(0, count($artists) - 1)];

            $item->setTitle($faker->realText($maxNbChars = 30, $indexSize = 2))
                    ->setArtist($artist)
                    ->setPosition($i - 1);

            $manager->persist($item);
        }

        // 2026-2027 season schedule. Events with no set time use 00:00, which the schedule display already treats as "no time" rather than midnight, so those get no endDate either.
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

        $residenceEvent = null;
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

            // Kept aside for the carpool demo below: a multi-day out-of-town event is the typical "dates éloignées" case from ROADMAP.md.
            if ('Résidence d\'Hiver' === $title) {
                $residenceEvent = $event;
            }
        }

        // Initial content of the Story page, editable afterwards by ROLE_ADMIN on /admin/story.
        $slugify = new Slugify();
        foreach (StorySectionSeedData::SECTIONS as $position => [$title, $content]) {
            $section = new StorySection();
            $section->setTitle($title)
                ->setSlug($slugify->slugify($title))
                ->setContent($content)
                ->setPosition($position);
            $manager->persist($section);
        }

        // Demo notes attached to the quick admin/admin account, one private and one shared, to test both cases locally.
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

        // Demo carpool offer for the out-of-town winter residency: QuickAdmin drives, Guiboï already has a seat.
        if ($residenceEvent) {
            $carpoolOffer = new CarpoolOffer();
            $carpoolOffer->setEvent($residenceEvent)
                ->setDriver($quickAdmin)
                ->setDepartureLocation('Parking de la mairie, Villeurbanne')
                ->setDepartureTime(new \DateTimeImmutable('2027-01-23 07:30'))
                ->setSeatsTotal(3)
                ->setComment('Coffre dispo pour un instrument, départ pile à l\'heure !')
                ->addPassenger($admin);
            $manager->persist($carpoolOffer);
        }

        $manager->flush();
    }
}
