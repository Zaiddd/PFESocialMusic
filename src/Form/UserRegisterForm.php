<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\DateType;

class UserRegisterForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('mail')
            ->add('mdp', PasswordType::class,['label' => 'Votre mot de passe', "mapped"=>false])
            ->add('confirmMdp', PasswordType::class,['label' => 'Confirmez votre mot de passe', "mapped"=>false])
            ->add('nom')
            ->add('prenom')
            ->add('pseudo')
            ->add('couleurFond', TextType::class,[
                'required' => false])
            ->add('couleurMenu', TextType::class, [
                'required' => false])
            ->add('dateNaissance', DateType::class, [
                'widget' => 'single_text',
                'html5' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}