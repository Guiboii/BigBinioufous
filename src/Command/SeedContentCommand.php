<?php

namespace App\Command;

use App\DataFixtures\StorySectionSeedData;
use App\Entity\Event;
use App\Entity\Instrument;
use App\Entity\StorySection;
use App\Repository\EventRepository;
use App\Repository\InstrumentRepository;
use App\Repository\StorySectionRepository;
use Cocur\Slugify\Slugify;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

// One-off seeding of public showcase content (Story sections + season schedule) and
// reference data (instruments) for environments where fixtures don't run, e.g. the
// prod_vitrine deploy. Idempotent: rows already present (matched by slug / by
// title+date / by title) are skipped, so it is safe to re-run and it never
// overwrites content edited later from /admin.
#[AsCommand(
    name: 'app:seed-content',
    description: 'Seed Story sections, schedule events and instruments if missing (prod-safe, idempotent).',
)]
class SeedContentCommand extends Command
{
    // Real instrument lineup of the band (not the fixtures' broader demo list).
    private const INSTRUMENTS = [
        'Flûte traversière',
        'Hautbois',
        'Cor anglais',
        'Batterie',
        'Chant',
        'Autre',
    ];
    // 2026-2027 season, taken from AppFixtures. Rehearsal times are placeholders
    // (the fixtures had obviously fake ones); adjust from /admin once the real
    // schedule is known.
    private const EVENTS = [
        ['2026-09-05', 'other', 'Ze Big Journée de Rentrée', 'À préciser', null, null],
        ['2026-09-20', 'concert', 'Pestacle', 'Maison du Canal', null, null],
        ['2026-09-26', 'rehearsal', 'Répétition en West Side', 'ENM de Villeurbanne', null, '09:30-12:30'],
        ['2026-10-02', 'concert', 'Pestacle', 'Fête de quartier des Buers', null, null],
        ['2026-10-17', 'rehearsal', 'Répétition en West Side', 'ENM de Villeurbanne', null, '09:30-12:30'],
        ['2026-11-21', 'rehearsal', 'Répétition en West Side', 'ENM de Villeurbanne', null, '09:30-12:30'],
        ['2026-12-12', 'rehearsal', 'Répétition en West Side', 'ENM de Villeurbanne', null, '09:30-12:30'],
        ['2027-01-23', 'other', 'Résidence d\'Hiver', 'ENM de Villeurbanne', 'Du 23 au 24 janvier.', null],
        ['2027-02-13', 'rehearsal', 'Répétition en West Side', 'ENM de Villeurbanne', null, '09:30-12:30'],
        ['2027-03-13', 'rehearsal', 'Répétition en West Side', 'ENM de Villeurbanne', null, '09:30-12:30'],
        ['2027-04-10', 'rehearsal', 'Répétition en West Side', 'ENM de Villeurbanne', null, '09:30-12:30'],
        ['2027-05-29', 'rehearsal', 'Répétition en West Side', 'ENM de Villeurbanne', null, '09:30-12:30'],
        ['2027-06-19', 'rehearsal', 'Répétition en West Side', 'ENM de Villeurbanne', null, '09:30-12:30'],
    ];

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly StorySectionRepository $storySections,
        private readonly EventRepository $events,
        private readonly InstrumentRepository $instruments,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $slugify = new Slugify();

        $storyAdded = 0;
        foreach (StorySectionSeedData::SECTIONS as $position => [$title, $content]) {
            $slug = $slugify->slugify($title);
            if (null !== $this->storySections->findOneBy(['slug' => $slug])) {
                continue;
            }
            $this->em->persist(
                (new StorySection())
                    ->setTitle($title)
                    ->setSlug($slug)
                    ->setContent($content)
                    ->setPosition($position)
            );
            ++$storyAdded;
        }

        $eventsAdded = 0;
        foreach (self::EVENTS as [$date, $type, $title, $location, $description, $hours]) {
            [$startTime, $endTime] = $hours ? explode('-', $hours) : [null, null];
            $start = new \DateTimeImmutable($date.' '.($startTime ?? '00:00'));

            if (null !== $this->events->findOneBy(['title' => $title, 'date' => $start])) {
                continue;
            }
            $this->em->persist(
                (new Event())
                    ->setDate($start)
                    ->setEndDate($endTime ? new \DateTimeImmutable($date.' '.$endTime) : null)
                    ->setType($type)
                    ->setTitle($title)
                    ->setLocation($location)
                    ->setDescription($description)
            );
            ++$eventsAdded;
        }

        $instrumentsAdded = 0;
        foreach (self::INSTRUMENTS as $title) {
            if (null !== $this->instruments->findOneBy(['title' => $title])) {
                continue;
            }
            $this->em->persist((new Instrument())->setTitle($title));
            ++$instrumentsAdded;
        }

        $this->em->flush();

        $io->success(sprintf(
            '%d story section(s), %d event(s) and %d instrument(s) added. Existing rows were left untouched.',
            $storyAdded,
            $eventsAdded,
            $instrumentsAdded,
        ));

        return Command::SUCCESS;
    }
}
