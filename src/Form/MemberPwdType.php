<?php

namespace App\Form;

use App\Entity\Member;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class MemberPwdType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
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
            ->add('save', SubmitType::class, ['label' => 'Changer mon mot de passe']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Member::class
        ]);
    }
}
