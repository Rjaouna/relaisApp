<?php

namespace App\Form;

use App\Entity\Client;
use App\Entity\Commercial;
use App\Entity\FieldFeedback;
use App\Entity\Visit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FieldFeedbackType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'name',
                'label' => 'Client',
                'required' => false,
            ])
            ->add('commercial', EntityType::class, [
                'class' => Commercial::class,
                'choice_label' => 'fullName',
                'label' => 'Commercial',
                'required' => false,
            ])
            ->add('visit', EntityType::class, [
                'class' => Visit::class,
                'choice_label' => fn (Visit $visit): string => sprintf('%s - %s', $visit->getClient()?->getName() ?? 'Visite', $visit->getScheduledAt()?->format('d/m/Y') ?? ''),
                'label' => 'Visite source',
                'required' => false,
            ])
            ->add('category', ChoiceType::class, [
                'label' => 'Categorie',
                'choices' => FieldFeedback::categoryChoices(),
            ])
            ->add('priority', ChoiceType::class, [
                'label' => 'Priorite',
                'choices' => FieldFeedback::priorityChoices(),
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Statut',
                'choices' => FieldFeedback::statusChoices(),
            ])
            ->add('summary', TextareaType::class, ['label' => 'Synthese terrain'])
            ->add('marketSignals', TextareaType::class, [
                'label' => 'Retours / veille',
                'required' => false,
            ])
            ->add('decisionAction', TextareaType::class, [
                'label' => 'Decision / action',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FieldFeedback::class,
        ]);
    }
}
