<?php

namespace App\Form;

use App\Entity\Commercial;
use App\Entity\Tour;
use App\Entity\Zone;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TourType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Nom de tournee'])
            ->add('commercial', EntityType::class, [
                'label' => 'Commercial',
                'class' => Commercial::class,
                'choice_label' => 'fullName',
            ])
            ->add('zone', EntityType::class, [
                'label' => 'Zone',
                'class' => Zone::class,
                'choice_label' => static function (Zone $zone): string {
                    $cityName = $zone->getCity()?->getName();

                    return $cityName ? sprintf('%s (%s)', $zone->getName(), $cityName) : (string) $zone;
                },
                'placeholder' => 'Selectionner une zone',
                'query_builder' => static fn (EntityRepository $repository) => $repository->createQueryBuilder('zone')
                    ->leftJoin('zone.city', 'city')
                    ->addSelect('city')
                    ->andWhere('zone.isActive = :active')
                    ->setParameter('active', true)
                    ->orderBy('city.name', 'ASC')
                    ->addOrderBy('zone.name', 'ASC'),
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tour::class,
        ]);
    }
}
