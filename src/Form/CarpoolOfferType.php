<?php

namespace App\Form;

use App\Entity\CarpoolOffer;
use App\Entity\Event;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Range;

// Form for creating/editing a CarpoolOffer. Only future events are offered: no point proposing a ride for a date that has already passed.
class CarpoolOfferType extends ApplicationType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('event', EntityType::class, [
                'class' => Event::class,
                'label' => $this->trans('carpool.field_event'),
                'placeholder' => $this->trans('carpool.field_event_placeholder'),
                'choice_label' => fn (Event $event) => sprintf('%s - %s (%s)', $event->getDate()->format('d/m/Y'), $event->getTitle(), $event->getLocation()),
                'query_builder' => fn (EntityRepository $er) => $er->createQueryBuilder('e')
                    ->andWhere('e.date >= :now')
                    ->setParameter('now', new \DateTimeImmutable())
                    ->orderBy('e.date', 'ASC'),
            ])
            ->add('departureLocation', TextType::class, $this->getConfiguration('carpool.field_departure_location', 'carpool.field_departure_location_placeholder'))
            ->add('departureTime', DateTimeType::class, array_merge(
                $this->getConfiguration('carpool.field_departure_time', ''),
                ['widget' => 'single_text', 'required' => false]
            ))
            ->add('seatsTotal', IntegerType::class, array_merge(
                $this->getConfiguration('carpool.field_seats_total', ''),
                ['constraints' => [new Range(['min' => 1, 'max' => 20])]]
            ))
            ->add('comment', TextareaType::class, array_merge(
                $this->getConfiguration('carpool.field_comment', 'carpool.field_comment_placeholder'),
                ['required' => false]
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => CarpoolOffer::class,
        ]);
    }
}
