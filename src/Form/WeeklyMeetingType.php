<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\WeeklyMeeting;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class WeeklyMeetingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('meetingDate', DateTimeType::class, [
                'label' => 'Date de reunion',
                'widget' => 'single_text',
            ])
            ->add('title', TextType::class, ['label' => 'Intitule'])
            ->add('teamScope', TextType::class, [
                'label' => 'Equipe / perimetre',
                'required' => false,
            ])
            ->add('attendees', EntityType::class, [
                'class' => User::class,
                'choices' => $options['meeting_attendees'],
                'choice_label' => static fn (User $user): string => sprintf('%s - %s', $user->getFullName(), $user->getRoleLabels() ?: 'Utilisateur'),
                'label' => 'Utilisateurs concernes',
                'multiple' => true,
                'required' => true,
                'help' => 'Les personnes selectionnees recevront automatiquement un email avec le sujet, la date et l organisateur.',
                'attr' => [
                    'size' => 8,
                ],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => WeeklyMeeting::statusChoices(),
            ])
            ->add('agenda', TextareaType::class, [
                'label' => 'Ordre du jour',
                'required' => true,
            ])
            ->add('decisions', TextareaType::class, [
                'label' => 'Decisions',
                'required' => false,
            ])
            ->add('actionItems', TextareaType::class, [
                'label' => 'Actions a suivre',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => WeeklyMeeting::class,
            'meeting_attendees' => [],
        ]);

        $resolver->setAllowedTypes('meeting_attendees', 'array');
    }
}
