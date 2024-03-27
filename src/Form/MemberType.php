<?php

namespace App\Form;

use App\Entity\Member;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MemberType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstname', TextType::class, ['label' => 'Prénom *', 'required' => true])
            ->add('lastname', TextType::class, ['label' => 'Nom', 'required' => false])
            ->add('email', EmailType::class, ['label' => 'Adresse mail *', 'required' => true])
            ->add('birthdate', BirthdayType::class, [
                'label' => 'Date de naissance',
                'required' => false,
                'widget' => 'single_text',
                'placeholder' => [
                    'year' => 'Année',
                    'month' => 'Mois',
                    'day' => 'Jour',
                ]])
            ->add('password', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options'  => [
                    'label' => 'Mot de passe *',
                    'required' => true,
                    // todo: ajouter le pattern une fois en prod
//                    'attr' => ['pattern' => '(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}'],
                    'help' => 'Le mot de passe doit contenir au moins 8 caractères dont 1 chiffre, une lettre minuscule et une lettre majuscule'
                ],
                'second_options' => [
                    'label' => 'Confirmation du mot de passe *',
                    'required' => true,
                    // todo: ajouter le pattern une fois en prod
//                    'attr' => ['pattern' => '(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}'],
                ]
            ])
            ->add('save', SubmitType::class, ['label' => 'Créer mon compte']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Member::class,
        ]);
    }
}
